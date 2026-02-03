$patterns = @('__pycache__','*.pyc','.pytest_cache','.mypy_cache','build','dist','*.egg-info','.cache')
$found = @()
foreach($p in $patterns){
    if($p -eq '*.pyc'){
        $items = Get-ChildItem -Path . -Recurse -Force -ErrorAction SilentlyContinue | Where-Object { -not $_.PSIsContainer -and $_.Extension -eq '.pyc' }
    } else {
        $items = Get-ChildItem -Path . -Recurse -Force -ErrorAction SilentlyContinue | Where-Object { $_.PSIsContainer -and $_.Name -like $p -or -not $_.PSIsContainer -and $_.Name -like $p }
    }
    if($items){
        $size = ($items | Where-Object {!$_.PSIsContainer} | Measure-Object Length -Sum).Sum
        if(-not $size){ $size = 0 }
        $sizeMB = '{0:N1}' -f ($size/1MB)
        $found += [PSCustomObject]@{Pattern=$p; Count=($items|Measure-Object).Count; SizeMB=$sizeMB}
    }
}
if($found.Count -eq 0){ Write-Host 'No cache/build artefacts found.'; exit 0 }
Write-Host 'Will remove these patterns and approximate sizes:'
$found | Format-Table -AutoSize
foreach($p in $patterns){
    Write-Host "Removing items matching: $p"
    if($p -eq '*.pyc'){
        Get-ChildItem -Path . -Recurse -Force -ErrorAction SilentlyContinue | Where-Object { -not $_.PSIsContainer -and $_.Extension -eq '.pyc' } | ForEach-Object { Remove-Item -LiteralPath $_.FullName -Force -ErrorAction SilentlyContinue }
    } else {
        Get-ChildItem -Path . -Recurse -Force -ErrorAction SilentlyContinue | Where-Object { $_.PSIsContainer -and $_.Name -like $p -or -not $_.PSIsContainer -and $_.Name -like $p } | ForEach-Object {
            if($_.PSIsContainer){ Remove-Item -LiteralPath $_.FullName -Recurse -Force -ErrorAction SilentlyContinue } else { Remove-Item -LiteralPath $_.FullName -Force -ErrorAction SilentlyContinue }
        }
    }
}
Write-Host 'Cleanup done.'
