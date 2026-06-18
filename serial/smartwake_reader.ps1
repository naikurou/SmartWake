# SmartWake - Lecteur serie PowerShell BIDIRECTIONNEL
# Lit les lux de la Tiva C ET renvoie les messages du site vers l'ecran OLED

# ============================================================
# CONFIGURATION
# ============================================================
$portName  = "COM14"
$baudRate  = 9600
$dbHost    = "178.33.122.21"
$dbUser    = "axst62997"
$dbPass    = "vN98OBrkug96JSeUmiFxuZGp"
$dbName    = "hangardb_axst62997"
$mysqlExe  = "C:\xampp\mysql\bin\mysql.exe"
$logFile   = "C:\xampp\htdocs\smartwake\logs\sensor.log"

# Seuils (doivent correspondre a functions.php)
function Get-TimePeriodLabel($hour) {
    if ($hour -ge 6 -and $hour -lt 9)   { return "Aube" }
    if ($hour -ge 9 -and $hour -lt 12)  { return "Matin" }
    if ($hour -ge 12 -and $hour -lt 18) { return "Apres-midi" }
    if ($hour -ge 18 -and $hour -lt 22) { return "Debut soiree" }
    return "Nuit"
}

function Get-DayStatus($lux) {
    if ($lux -ge 200) { return "JOUR" } else { return "NUIT" }
}

function Write-Log($level, $msg) {
    $line = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [$($level.ToUpper())] $msg"
    Write-Host $line
    Add-Content -Path $logFile -Value $line -Encoding UTF8
}

function Process-Measure($lux, $status) {
    $dbStatus = if ($status -eq "JOUR") { "DAY" } else { "NIGHT" }
    
    # 1. Recuperer les parametres
    $sqlSettings = "SELECT is_active, night_lux_threshold, day_lux_threshold FROM alarm_settings WHERE id=1;"
    $settingsRaw = & $mysqlExe -h $dbHost -u $dbUser "-p$dbPass" -N -B $dbName -e $sqlSettings
    
    $triggerAlarm = $false
    if ($settingsRaw -ne $null) {
        $parts = $settingsRaw -split "`t"
        if ($parts.Length -ge 3) {
            $isActive = $parts[0]
            $nightLux = [int]$parts[1]
            $dayLux   = [int]$parts[2]
            
            $hour = (Get-Date).Hour
            $isNight = ($hour -ge 22 -or $hour -lt 6)
            
            if ($isActive -eq "1") {
                if ($isNight -and $lux -ge $nightLux) { $triggerAlarm = $true }
                if (-not $isNight -and $lux -ge $dayLux) { $triggerAlarm = $true }
            }
        }
    }
    
    # 2. Inserer dans light_sensor_data
    $sqlInsert = "INSERT INTO light_sensor_data (light_value, day_status) VALUES ($lux, '$dbStatus');"
    
    # 3. Mettre a jour le buzzer
    $buzzerState = if ($triggerAlarm) { 1 } else { 0 }
    $sqlBuzzer = "INSERT INTO etats_actionneurs (composant, etat, declenche_par) VALUES ('buzzer', $buzzerState, 'groupe_ldr') ON DUPLICATE KEY UPDATE etat=$buzzerState, declenche_par='groupe_ldr';"
    
    # Executer les deux requetes
    $sqlCombined = $sqlInsert + $sqlBuzzer
    & $mysqlExe -h $dbHost -u $dbUser "-p$dbPass" $dbName -e $sqlCombined 2>&1 | Out-Null
    
    return $triggerAlarm
}

# ============================================================
# Format du message renvoy a la Tiva C (ASCII uniquement)
# ============================================================
function Build-OledMessage($lux) {
    $heure   = Get-Date -Format 'HH:mm'
    $periode = Get-TimePeriodLabel (Get-Date).Hour
    return "MSG:$heure|$lux lux|$periode"
}

# ============================================================
# LANCEMENT
# ============================================================
Write-Log "INFO" "=== SmartWake Serial Reader BIDIRECTIONNEL ==="
Write-Log "INFO" "Port     : $portName @ $baudRate baud"
Write-Log "INFO" "BDD      : $dbName @ $dbHost"
Write-Log "INFO" "Mode     : Lecture lux + Renvoi messages OLED"
Write-Host ("-" * 50)

try {
    $port = New-Object System.IO.Ports.SerialPort $portName, $baudRate, "None", 8, "One"
    $port.DtrEnable  = $true
    $port.RtsEnable  = $true
    $port.ReadTimeout = 3000
    $port.NewLine    = "`n"
    $port.Open()

    Write-Log "INFO" "Port ouvert. En attente de donnees de la carte... (Ctrl+C pour quitter)"
    Write-Host ("-" * 50)

    # Petite pause pour laisser la carte s'initialiser
    Start-Sleep -Milliseconds 500

    while ($true) {
        try {
            $raw = $port.ReadLine().Trim()

            if ([string]::IsNullOrWhiteSpace($raw)) { continue }

            # Ignorer les messages qu'on a nous-memes envoyes (echo)
            if ($raw.StartsWith("MSG:")) { continue }

            # Parser le float (separateur decimal = point)
            $luxFloat = 0.0
            if (-not [double]::TryParse($raw, [System.Globalization.NumberStyles]::Float, [System.Globalization.CultureInfo]::InvariantCulture, [ref]$luxFloat)) {
                Write-Log "DEBUG" "Ligne ignoree : '$raw'"
                continue
            }

            $lux     = [int][Math]::Round($luxFloat)
            $status  = Get-DayStatus $lux

            Write-Log "INFO" "Recu : $raw lux brut -> $lux lux | $status"

            # 1. Enregistrer en base de donnees et maj actionneur
            $trigger = Process-Measure $lux $status
            if ($trigger) {
                Write-Log "WARN" "Alarme declenchee pour $lux lux !"
            } else {
                Write-Log "OK" "Enregistre en BDD : $lux lux"
            }

            # 2. Renvoyer le message vers la Tiva C (pour l'ecran OLED)
            $msg = Build-OledMessage $lux
            $port.WriteLine($msg)
            Write-Log "INFO" "Envoye a la carte -> $msg"

        } catch [System.TimeoutException] {
            Write-Log "WARN" "Timeout : aucune donnee depuis 3s. Carte toujours connectee ?"
        } catch {
            Write-Log "ERROR" "Erreur lecture : $_"
        }
    }

} catch {
    Write-Log "ERROR" "Impossible d'ouvrir le port $portName : $_"
} finally {
    if ($null -ne $port -and $port.IsOpen) {
        $port.Close()
        Write-Log "INFO" "Port ferme proprement."
    }
}
