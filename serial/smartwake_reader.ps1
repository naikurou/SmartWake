# SmartWake - Lecteur serie PowerShell BIDIRECTIONNEL
# Lit les lux de la Tiva C ET renvoie les messages du site vers l'ecran OLED

# ============================================================
# CONFIGURATION
# ============================================================
$portName  = "COM14"
$baudRate  = 9600
$apiUrl    = "https://smartwake.hangar.garageisep.com/api/hardware_sync.php"
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
    
    # Initialisation de l'état du buzzer
    if ($global:triggerAlarm -eq $null) { $global:triggerAlarm = $false }
    $buzzerState = if ($global:triggerAlarm) { 1 } else { 0 }
    
    # 1. Envoyer les données à l'API et récupérer les paramètres
    $body = @{
        lux = $lux
        status = $dbStatus
        buzzer = $buzzerState
    }
    
    try {
        $response = Invoke-RestMethod -Uri $apiUrl -Method Post -Body $body -ErrorAction Stop
    } catch {
        Write-Log "ERROR" "Impossible de joindre le site web: $_"
        return $global:triggerAlarm
    }
    
    # 2. Mettre a jour les parametres locaux avec la reponse
    if ($response.success -eq $true) {
        $isActive = $response.settings.is_active
        $nightLux = $response.settings.night_lux
        $dayLux   = $response.settings.day_lux
        $duration = $response.settings.duration
        
        $hour = (Get-Date).Hour
        $isNight = ($hour -ge 22 -or $hour -lt 6)
        
        $triggerAlarm = $false
        if ($isActive -eq 1) {
            if ($isNight -and $lux -ge $nightLux) { $triggerAlarm = $true }
            if (-not $isNight -and $lux -ge $dayLux) { $triggerAlarm = $true }
        }
        
        # Gestion de la durée de l'alarme
        if ($triggerAlarm) {
            if ($global:alarmStartTime -eq $null) {
                $global:alarmStartTime = Get-Date
                Write-Log "INFO" "Alarme declenchee. Duree prevue: $duration min."
            } else {
                $elapsed = (Get-Date) - $global:alarmStartTime
                if ($elapsed.TotalMinutes -ge $duration) {
                    $triggerAlarm = $false
                }
            }
        } else {
            $global:alarmStartTime = $null
        }
        
        $global:triggerAlarm = $triggerAlarm
        Write-Log "OK" "Enregistre sur le site : $lux lux (Buzzer: $buzzerState)"
        return $triggerAlarm
    } else {
        Write-Log "ERROR" "Erreur API : $($response.error)"
        return $global:triggerAlarm
    }
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
Write-Log "INFO" "URL API  : $apiUrl"
Write-Log "INFO" "Mode     : API HTTP + Renvoi messages OLED (Toutes les 15s)"
Write-Host ("-" * 50)

$global:lastOledUpdate = (Get-Date).AddSeconds(-20)
$global:lastApiSync = (Get-Date).AddSeconds(-20)

while ($true) {
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

                # 1. Enregistrer en base de donnees via API et maj actionneur (toutes les 5 secondes)
                if ((Get-Date) - $global:lastApiSync -ge [timespan]::FromSeconds(5)) {
                    $trigger = Process-Measure $lux $status
                    if ($trigger) {
                        Write-Log "WARN" "Alarme declenchee pour $lux lux !"
                    }
                    $global:lastApiSync = Get-Date
                }

                # 2. Renvoyer le message vers la Tiva C (pour l'ecran OLED) toutes les 15 secondes
                if ((Get-Date) - $global:lastOledUpdate -ge [timespan]::FromSeconds(15)) {
                    $msg = Build-OledMessage $lux
                    $port.WriteLine($msg)
                    Write-Log "INFO" "Envoye a la carte -> $msg"
                    $global:lastOledUpdate = Get-Date
                }

            } catch [System.TimeoutException] {
                # Timeout normal si aucune donnee n'est envoyee
                continue
            } catch {
                Write-Log "ERROR" "Erreur de lecture sur le port : $_"
                break # Sortir de la boucle interne pour tenter de rouvrir le port
            }
        }
    } catch {
        Write-Log "ERROR" "Impossible d'ouvrir le port $portName. En attente du materiel physique..."
        Start-Sleep -Seconds 5
    } finally {
        if ($port -ne $null -and $port.IsOpen) {
            $port.Close()
            $port.Dispose()
        }
    }
}
