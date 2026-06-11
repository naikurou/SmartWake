# SmartWake - Lecteur serie PowerShell BIDIRECTIONNEL
# Lit les lux de la Tiva C ET renvoie les messages du site vers l'ecran OLED

# ============================================================
# CONFIGURATION
# ============================================================
$portName  = "COM7"
$baudRate  = 9600
$dbHost    = "178.33.122.21"
$dbUser    = "axst62997"
$dbPass    = "vN98OBrkug96JSeUmiFxuZGp"
$dbName    = "hangardb_axst62997"
$mysqlExe  = "C:\xampp\mysql\bin\mysql.exe"
$logFile   = "C:\xampp\htdocs\smartwake\logs\sensor.log"

# Seuils (doivent correspondre a functions.php)
function Get-LuxLevel($lux) {
    if ($lux -lt 1)   { return @{ level="NIGHT_FULL"; label="Nuit complete";          action="Veille"        } }
    if ($lux -lt 10)  { return @{ level="NIGHT_DIM";  label="Nuit - faible eclairage"; action="Simul. aube"   } }
    if ($lux -lt 50)  { return @{ level="DAWN";       label="Aube naissante";          action="Alarme douce"  } }
    if ($lux -lt 200) { return @{ level="MORNING";    label="Matin clair";             action="Alarme princ." } }
    if ($lux -lt 500) { return @{ level="DAY";        label="Plein jour";              action="Mode jour"     } }
    return              @{ level="ALERT";      label="Alerte lumiere!";        action="ALERTE!"       }
}

function Get-DayStatus($lux) {
    if ($lux -ge 200) { return "JOUR" } else { return "NUIT" }
}

function Write-Log($level, $msg) {
    $line = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [$($level.ToUpper())] $msg"
    Write-Host $line
    Add-Content -Path $logFile -Value $line -Encoding UTF8
}

function Insert-Measure($lux, $status) {
    $dbStatus = if ($status -eq "JOUR") { "DAY" } else { "NIGHT" }
    $sql = "INSERT INTO light_sensor_data (light_value, day_status) VALUES ($lux, '$dbStatus');"
    & $mysqlExe -h $dbHost -u $dbUser "-p$dbPass" $dbName -e $sql 2>&1 | Out-Null
    return ($LASTEXITCODE -eq 0)
}

# ============================================================
# Format du message renvoy a la Tiva C (ASCII uniquement)
# Format : "MSG:<label>|<lux> lux|<statut>|<action>\n"
# La Tiva C peut parser avec Serial.readStringUntil('\n')
# et split sur '|' pour afficher sur l'ecran OLED
# ============================================================
function Build-OledMessage($lux, $luxInfo) {
    $status = Get-DayStatus $lux
    # Format compact pour OLED (max ~16 chars par ligne)
    # Ligne 1 : label du niveau
    # Ligne 2 : valeur en lux
    # Ligne 3 : statut JOUR/NUIT
    # Ligne 4 : action recommandee
    return "MSG:$($luxInfo.label)|$lux lux|$status|$($luxInfo.action)"
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
            $luxInfo = Get-LuxLevel $lux
            $status  = Get-DayStatus $lux

            Write-Log "INFO" "Recu : $raw lux brut -> $lux lux | $($luxInfo.label) | $status"

            # 1. Enregistrer en base de donnees
            if (Insert-Measure $lux $status) {
                Write-Log "OK" "Enregistre en BDD : $lux lux ($($luxInfo.label))"
            } else {
                Write-Log "WARN" "Echec insertion BDD"
            }

            # 2. Renvoyer le message vers la Tiva C (pour l'ecran OLED)
            $msg = Build-OledMessage $lux $luxInfo
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
