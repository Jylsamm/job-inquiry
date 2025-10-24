$php = 'C:\xampp\php\php.exe'
if (-not (Test-Path $php)) {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { $php = $cmd.Source } else { Write-Host 'PHP not found in PATH or C:\xampp\php\php.exe'; exit 2 }
}
Write-Host "Using PHP: $php"
$errors = @()
Get-ChildItem -Path . -Recurse -Include *.php | ForEach-Object {
    $file = $_.FullName
    $out = & $php -l $file 2>&1
    if ($LASTEXITCODE -ne 0) {
        $errors += @{file=$file; out=$out}
    }
}
if ($errors.Count -eq 0) {
    Write-Host 'PHP lint: all files OK'
    exit 0
} else {
    Write-Host 'PHP lint: errors found:'
    foreach($e in $errors) {
        Write-Host "---- $($e.file) ----"
        Write-Host $e.out
    }
    exit 1
}