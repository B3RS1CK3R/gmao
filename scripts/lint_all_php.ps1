Set-Location -LiteralPath 'c:\xampp\htdocs\gmao_GEMINI'
$files = Get-ChildItem -Recurse -Filter '*.php'
foreach ($f in $files) {
    Write-Host '----' $f.FullName
    & 'C:\xampp\php\php.exe' -l $f.FullName
}
