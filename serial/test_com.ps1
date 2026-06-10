$portName = "COM7"
$baudRate = 9600

Write-Host "Ouverture du port $portName à $baudRate baud..."
try {
    $port = new-Object System.IO.Ports.SerialPort $portName, $baudRate, None, 8, one
    $port.DtrEnable = $true
    $port.RtsEnable = $true
    $port.Open()
    
    Write-Host "Port ouvert ! En attente de données (appuyez sur Ctrl+C pour quitter)..."
    
    while ($true) {
        if ($port.BytesToRead -gt 0) {
            $line = $port.ReadLine()
            Write-Host "Reçu : $line"
        }
        Start-Sleep -Milliseconds 100
    }
} catch {
    Write-Host "Erreur : $_"
} finally {
    if ($port -ne $null -and $port.IsOpen) {
        $port.Close()
    }
}
