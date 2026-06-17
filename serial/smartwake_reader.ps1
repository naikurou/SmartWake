# SmartWake - Lecteur serie PowerShell BIDIRECTIONNEL
# Lit les lux de la Tiva C ET renvoie les messages du site vers l'ecran OLED

# ============================================================
# CONFIGURATION
# ============================================================
$portName  = "COM14"
$baudRate  = 9600
$dbHost    = "localhost"
$dbUser    = "root"
$dbPass    = ""
$dbName    = "smart"
$mysqlExe  = "C:\xampp\mysql\bin\mysql.exe"
$logFile   = "C:\xampp\htdocs\smartwake\logs\sensor.log"

# Seuils (doivent correspondre a functions.php)
function Get-TimePeriodLabel($hour) {
    if ($hour -ge 6 -and $hour -lt 9)   { return "Aube" }
    if ($hour -ge 9 -and $hour -lt 12)  { return "Matin" }
    if ($hour -ge 12 -and $hour -lt 18) { return "Après-midi" }
    if ($hour -ge 18 -and $hour -lt 22) { return "Début soirée" }
    return "Nuit"
}

function Get-LuxLevel($lux) {
    $hour = (Get-Date).Hour
    $label = Get-TimePeriodLabel $hour
    
    # Si on est en pleine nuit (22h à 6h)
    if ($hour -ge 22 -or $hour -lt 6) {
        if ($lux -ge 500) {
            return @{ level="ALERT"; label="Alerte lumiere!"; action="ALERTE!" }
        }
        return @{ level="NIGHT"; label=$label; action="Veille" }
    }
    
    # En journée
    if ($lux -lt 1)   { return @{ level="NIGHT_FULL"; label=$label;          action="Veille"        } }
    if ($lux -lt 10)  { return @{ level="NIGHT_DIM";  label=$label; action="Simul. aube"   } }
    if ($lux -lt 50)  { 
        $isMorning = ($hour -ge 6 -and $hour -lt 9)
        $act = "Mode jour"
        if ($isMorning) { $act = "Alarme douce" }
        return @{ level="DAWN"; label=$label; action=$act } 
    }
    if ($lux -lt 200) { 
        $isMorning = ($hour -ge 6 -and $hour -lt 10)
        $act = "Mode jour"
        if ($isMorning) { $act = "Alarme princ." }
        return @{ level="MORNING"; label=$label; action=$act } 
    }
    if ($lux -lt 500) { return @{ level="DAY";        label=$label;    action="Mode jour"     } }
    return              @{ level="ALERT";      label="Alerte lumiere!";        action="ALERTE!"       }
}

function Get-DayStatus($lux) {
    $hour = (Get-Date).Hour
    if ($hour -ge 7 -and $hour -lt 21) {
        if ($lux -ge 15) { return "JOUR" } else { return "NUIT" }
    }
    if ($lux -ge 150) { return "JOUR" } else { return "NUIT" }
}

# Decide si le reveil doit etre declenche (ON) selon la luminosite et l'heure
# Regle : nuit (22h-6h) + lumiere anormale (lux >= 50) -> ON (intrusion lumineuse)
#         journee + lux >= 500 -> ON (trop lumineux, heure de se lever)
#         tout le reste -> OFF
function Get-ReveilAction($lux) {
    $hour = (Get-Date).Hour
    $isNight = ($hour -ge 22 -or $hour -lt 6)
    if ($isNight -and $lux -lt 50)   { return "OFF" }  # nuit normale, pas d'alarme
    if ($isNight -and $lux -ge 50)   { return "ON"  }  # lumiere anormale la nuit -> reveil
    if ($lux -ge 500)                { return "ON"  }  # trop lumineux -> reveil
    if ($lux -lt 10)                 { return "OFF" }  # encore sombre -> pas encore
    return "OFF"                                       # journee normale -> pas d'alarme
}

function Write-Log($level, $msg) {
    $line = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [$($level.ToUpper())] $msg"
    Write-Host $line
    try {
        [System.IO.File]::AppendAllText($logFile, $line + [System.Environment]::NewLine, [System.Text.Encoding]::UTF8)
    } catch {
        # Ignorer silencieusement en cas d'erreur d'écriture (par ex. verrouillage)
    }
}

function Insert-Measure($lux, $status) {
    $dbStatus = if ($status -eq "JOUR") { "DAY" } else { "NIGHT" }
    $reveil   = Get-ReveilAction $lux
    $sql = "INSERT INTO light_sensor_data (light_value, day_status, reveil_action) VALUES ($lux, '$dbStatus', '$reveil');"
    if ($dbPass -eq "") {
        & $mysqlExe -h $dbHost -u $dbUser $dbName -e $sql 2>&1 | Out-Null
    } else {
        & $mysqlExe -h $dbHost -u $dbUser "-p$dbPass" $dbName -e $sql 2>&1 | Out-Null
    }
    return ($LASTEXITCODE -eq 0)
}

# ============================================================
# Format du message renvoy a la Tiva C (ASCII uniquement)
# Format : "MSG:<heure>|<lux> lux|<moment>\n"
# La Tiva C peut parser avec Serial.readStringUntil('\n')
# et split sur '|' pour afficher 3 lignes sur l'ecran OLED :
#   Ligne 1 : heure actuelle  (ex: 13:50)
#   Ligne 2 : luminosite      (ex: 320 lux)
#   Ligne 3 : moment journee  (ex: Apres-midi)
# ============================================================
function Build-OledMessage($lux, $luxInfo) {
    $heure   = Get-Date -Format 'HH:mm'
    $periode = Get-TimePeriodLabel (Get-Date).Hour
    # Retrait des accents pour l'ASCII de l'OLED
    $periode = $periode -replace 'è','e' -replace 'é','e' -replace 'î','i' -replace 'â','a'
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
            $luxInfo = Get-LuxLevel $lux
            $status  = Get-DayStatus $lux

            Write-Log "INFO" "Recu : $raw lux brut -> $lux lux | $($luxInfo.label) | $status"

            # 1. Enregistrer en base de donnees
            $reveil = Get-ReveilAction $lux
            if (Insert-Measure $lux $status) {
                Write-Log "OK" "Enregistre en BDD : $lux lux | reveil_action=$reveil"
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
