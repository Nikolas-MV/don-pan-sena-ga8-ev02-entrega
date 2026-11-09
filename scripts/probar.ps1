[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$Php,
    [string]$Node,
    [switch]$Integracion
)
$ErrorActionPreference = 'Stop'
$rootGa8 = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$phpGa8 = (Get-Item -LiteralPath $Php).FullName
$extensionGa8 = Join-Path (Split-Path $phpGa8 -Parent) 'ext'
if ($Integracion) {
    if (!$Node) { throw 'Indicar -Node para la integracion.' }
    $anteriorGa8 = [Environment]::GetEnvironmentVariable('GA8_PHP', 'Process')
    try {
        [Environment]::SetEnvironmentVariable('GA8_PHP', $phpGa8, 'Process')
        & $Node (Join-Path $rootGa8 'tests\Integration\ejecutar.cjs')
        if ($LASTEXITCODE -ne 0) { throw 'Integracion fallida; revisar resultado real.' }
    } finally {
        [Environment]::SetEnvironmentVariable('GA8_PHP', $anteriorGa8, 'Process')
    }
} else {
    $pharGa8 = Join-Path $rootGa8 'tools\phpunit-11.5.57.phar'
    if (!(Test-Path -LiteralPath $pharGa8)) { throw 'Falta PHPUnit local verificado; no se descarga automaticamente.' }
    if ((Get-FileHash -LiteralPath $pharGa8 -Algorithm SHA256).Hash.ToLowerInvariant() -ne 'c704fdd37c6c6ab7adb386f31b4e186cd51156801fa9028b5ea8099a2cd7f106') {
        throw 'SHA-256 de PHPUnit incorrecto.'
    }
    & $phpGa8 -n -d "extension_dir=$extensionGa8" -d extension=mbstring $pharGa8 --configuration (Join-Path $rootGa8 'phpunit.xml')
    if ($LASTEXITCODE -ne 0) { throw 'Pruebas unitarias fallidas.' }
}
