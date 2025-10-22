# PowerShell smoke test for WorkConnect PH APIs
# Run from project root in PowerShell: .\tests\run_smoke.ps1

$base = 'http://localhost/job-inquiry/api'

function Test-Endpoint($name, $url, $method = 'GET', $body = $null) {
    Write-Host "Running: $name..."
    try {
        if ($method -eq 'GET') {
            $resp = Invoke-RestMethod -Uri $url -Method Get -ErrorAction Stop
        } else {
            $resp = Invoke-RestMethod -Uri $url -Method Post -Body (ConvertTo-Json $body) -ContentType 'application/json' -ErrorAction Stop
        }
        Write-Host "  OK: success=$($resp.success) message=$($resp.message)" -ForegroundColor Green
    } catch {
        Write-Host "  ERROR: $_" -ForegroundColor Red
    }
}

Test-Endpoint 'Jobs Featured' "$base/jobs.php?action=featured"
Test-Endpoint 'Jobs Search' "$base/jobs.php?action=search&q=developer"
Test-Endpoint 'Auth Check' "$base/auth.php?action=check"
Test-Endpoint 'Auth Login (invalid)' "$base/auth.php?action=login" 'POST' @{ email='noone@example.com'; password='wrong'}

Write-Host 'Done.'
