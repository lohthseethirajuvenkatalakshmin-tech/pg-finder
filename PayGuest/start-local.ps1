$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$documentRoot = Split-Path -Parent $projectRoot
$mysqlBin = 'C:\Program Files\MariaDB 13.0\bin\mariadbd.exe'
$mysqlDataDir = 'C:\Program Files\MariaDB 13.0\data'
$phpHost = '127.0.0.1'
$phpPort = 8017

if (-not (Test-Path $mysqlBin)) {
    throw "MariaDB was not found at $mysqlBin. Install MariaDB or update the path in start-local.ps1."
}

$mysqlCheck = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue
if (-not $mysqlCheck) {
    $dataDirectoryArgument = '--datadir="' + $mysqlDataDir + '"'
    Start-Process -FilePath $mysqlBin -ArgumentList @($dataDirectoryArgument, '--port=3306') -WindowStyle Hidden
    $startedAt = Get-Date
    while (-not (Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue)) {
        if ((Get-Date) - $startedAt -gt [TimeSpan]::FromSeconds(20)) {
            throw 'MariaDB did not start within 20 seconds.'
        }
        Start-Sleep -Seconds 1
    }
}

$phpCheck = Get-NetTCPConnection -LocalPort $phpPort -ErrorAction SilentlyContinue
if (-not $phpCheck) {
    Push-Location $documentRoot
    try {
        $documentRootArgument = '-t "' + $documentRoot + '"'
        Start-Process -FilePath 'php.exe' -ArgumentList @('-S', "${phpHost}:$phpPort", $documentRootArgument) -NoNewWindow
    }
    finally {
        Pop-Location
    }
}

Write-Host "Roomie is ready at http://${phpHost}:$phpPort/PayGuest/index.php"
