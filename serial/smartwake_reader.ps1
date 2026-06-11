# SmartWake - Lecteur serie PowerShell (fiable sur Windows)
# Lit la carte Tiva C sur COM7 et insere les valeurs en base MySQL

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

# Seuils lux
$SEUIL_ALERT   = 500
$SEUIL_DAY     = 200

function Write-Log($level, $msg) {
    $line = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [$($level.ToUpper())] $msg"
    Write-Host $line
    Add-Content -Path $logFile -Value $line -Encoding UTF8
}

function Get-DayStatus($lux) {
    if ($lux -ge $SEUIL_DAY) { return "DAY" } else { return "NIGHT" }
}

function Insert-Measure($lux) {
    $status = Get-DayStatus $lux
    $sql    = "INSERT INTO light_sensor_data (light_value, day_status) VALUES ($lux, '$status');"
    $result = & $mysqlExe -h $dbHost -u $dbUser "-p$dbPass" $dbName -e $sql 2>&1
    return ($LASTEXITCODE -eq 0)
}

# ============================================================
# LANCEMENT
# ============================================================
Write-Log "INFO" "=== SmartWake Serial Reader (PowerShell) ==="
Write-Log "INFO" "Port     : $portName"
Write-Log "INFO" "Baudrate : $baudRate baud"
Write-Log "INFO" "BDD      : $dbName @ $dbHost"
Write-Host ("-" * 50)

try {
    $port = New-Object System.IO.Ports.SerialPort $portName, $baudRate, "None", 8, "One"
    $port.DtrEnable  = $true
    $port.RtsEnable  = $true
    $port.ReadTimeout = 3000
    $port.Open()
    Write-Log "INFO" "Port serie ouvert. En attente de donnees... (Ctrl+C pour quitter)"
    Write-Host ("-" * 50)

    while ($true) {
        try {
            $raw = $port.ReadLine().Trim()

            # Ignorer les lignes vides ou non numeriques
            if ([string]::IsNullOrWhiteSpace($raw)) { continue }

            # Verifier si c'est un nombre valide
            $luxFloat = 0.0
            if (-not [double]::TryParse($raw, [System.Globalization.NumberStyles]::Float, [System.Globalization.CultureInfo]::InvariantCulture, [ref]$luxFloat)) {
                Write-Log "DEBUG" "Ligne ignoree (non numerique) : '$raw'"
                continue
            }

            # Arrondir a l'entier
            $lux = [int][Math]::Round($luxFloat)

            # Determiner le niveau
            $level = if ($lux -lt 1)    { "Nuit complete" }
                elseif ($lux -lt 10)    { "Nuit - faible eclairage" }
                elseif ($lux -lt 50)    { "Aube naissante" }
                elseif ($lux -lt 200)   { "Matin clair" }
                elseif ($lux -lt 500)   { "Plein jour" }
                else                    { "!!! ALERTE lumiere soudaine !!!" }

            Write-Log "INFO" "Lecture -> $raw lux brut | $lux lux | $level"

            if (Insert-Measure $lux) {
                Write-Log "OK" "Enregistre : $lux lux ($level)"
            } else {
                Write-Log "WARN" "Echec insertion DB pour $lux lux"
            }

        } catch [System.TimeoutException] {
            Write-Log "WARN" "Aucune donnee depuis 3s. Carte toujours connectee ?"
        }
    }

} catch {
    Write-Log "ERROR" "Erreur : $_"
} finally {
    if ($port -ne $null -and $port.IsOpen) {
        $port.Close()
        Write-Log "INFO" "Port ferme."
    }
}
