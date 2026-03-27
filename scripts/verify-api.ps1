Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Set-Location (Join-Path $PSScriptRoot '..')

Write-Host '== Symfony checks =='
php bin/console cache:clear
php bin/console lint:container
php bin/console debug:router | findstr /I "api_login_check api_me api_passkey"

Write-Host ''
Write-Host '== JWT smoke test =='
$body = @{ username = 'user'; password = 'user1234' } | ConvertTo-Json
$login = Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8011/api/login_check' -ContentType 'application/json' -Body $body
$token = $login.token
$result = Invoke-RestMethod -Method Get -Uri 'http://127.0.0.1:8011/api/me' -Headers @{ Authorization = "Bearer $token" }
$result | ConvertTo-Json -Depth 5
