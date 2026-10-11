$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$Root = $PSScriptRoot
$Dist = Join-Path $Root 'dist'
$Version = '1.0.3'

New-Item -ItemType Directory -Force -Path $Dist | Out-Null

# Repo-only / tooling files - not part of the Joomla install package.
$excludeNames = @(
    'build.ps1',
    '.gitignore',
    'README.md',
    'updates.xml',
    'LICENSE',   # full GPL text stays in repo; LICENSE.txt ships in the zip for JED
    '_push_clean.bat'
)
$excludeRegex = '[\\/](\.git|dist)[\\/]|[\\/]_[^\\/]+\.(bat|cmd)$'

function New-UnixZipFromDirectory {
    param(
        [Parameter(Mandatory = $true)][string]$SourceDir,
        [Parameter(Mandatory = $true)][string]$ZipPath
    )
    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
    $sourceFull = (Resolve-Path $SourceDir).Path.TrimEnd('\', '/')
    $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        Get-ChildItem -Path $sourceFull -Recurse -File |
            Where-Object {
                $_.FullName -notmatch $excludeRegex -and
                ($excludeNames -notcontains $_.Name)
            } |
            ForEach-Object {
                $rel = $_.FullName.Substring($sourceFull.Length).TrimStart('\', '/').Replace('\', '/')
                [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                    $zip,
                    $_.FullName,
                    $rel,
                    [System.IO.Compression.CompressionLevel]::Optimal
                )
            }
    }
    finally {
        $zip.Dispose()
    }
}

$finalZip = Join-Path $Dist ("plg_system_cookienotice-$Version.zip")
New-UnixZipFromDirectory $Root $finalZip
Write-Host "Built $finalZip"
