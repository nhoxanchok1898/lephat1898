$ts = Get-Date -Format yyyyMMdd_HHmmss
if (-Not (Test-Path .git)) { Write-Host '.git not found'; exit 0 }
$backup = "backup_git_$ts.zip"
Write-Host "Creating backup: $backup"
Compress-Archive -LiteralPath .git -DestinationPath $backup -Force
Start-Sleep -Seconds 1
if (Test-Path $backup) {
    $b = Get-Item $backup
    Write-Host ("Backup created: {0} ({1} bytes)" -f $backup, $b.Length)
} else {
    Write-Host 'Backup failed'
    exit 1
}
Write-Host 'Removing .git directory...'
Remove-Item -LiteralPath .git -Recurse -Force -ErrorAction SilentlyContinue
if (-Not (Test-Path .git)) { Write-Host '.git removed' } else { Write-Host 'Failed to remove .git'; exit 1 }
