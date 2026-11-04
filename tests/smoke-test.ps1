# Smoke test for the Week 13 student information system.
# Requires Apache + MySQL running and the app at the URL below.
# Run from PowerShell:  powershell -ExecutionPolicy Bypass -File .\tests\smoke-test.ps1

$ErrorActionPreference = 'Stop'

$base   = 'http://localhost/WST_Laboratory_Activity_10'
$mysql  = 'F:\xampp\mysql\bin\mysql.exe'
$tmp    = Join-Path $env:TEMP ('wst_week13_test_' + [guid]::NewGuid().ToString('N'))
$jar    = Join-Path $tmp 'cookies.txt'
$stamp  = Get-Date -Format 'yyyyMMddHHmmss'

$studentEmail = "student$stamp@example.com"
$adminEmail   = "admin$stamp@example.com"
$studentPass  = 'StudentPass123!'
$adminPass    = 'AdminPass12345!'
$xss          = "<script>alert('XSS test')</script>"

New-Item -ItemType Directory -Force -Path $tmp | Out-Null

$script:passed = 0
$script:failed = 0

function Check {
    param([string]$Name, [bool]$Condition, [string]$Detail = '')
    if ($Condition) {
        $script:passed++
        Write-Host ("PASS  {0}" -f $Name) -ForegroundColor Green
    } else {
        $script:failed++
        Write-Host ("FAIL  {0}  {1}" -f $Name, $Detail) -ForegroundColor Red
    }
}

function Invoke-App {
    param(
        [string]$Method = 'GET',
        [string]$Path,
        [string]$Data,
        [switch]$UseJar
    )

    $bodyFile = Join-Path $tmp ('body-' + [guid]::NewGuid().ToString('N') + '.html')
    $curlArgs = @('-s', '-o', $bodyFile, '-w', '%{http_code}|%{redirect_url}', '-X', $Method)

    if ($UseJar) {
        $curlArgs += @('-b', $jar, '-c', $jar)
    }
    if ($PSBoundParameters.ContainsKey('Data')) {
        $curlArgs += @('--data', $Data)
    }

    $curlArgs += "$base/$Path"
    $out     = (& curl.exe @curlArgs) -join ''
    $parts   = $out -split '\|', 2
    $body    = ''
    if (Test-Path $bodyFile) {
        $body = Get-Content $bodyFile -Raw -Encoding UTF8
        Remove-Item $bodyFile -Force
    }

    [pscustomobject]@{
        Status   = [int]$parts[0]
        Location = $parts[1]
        Body     = $body
    }
}

Write-Host "`n== Registration ==" -ForegroundColor Cyan

$r = Invoke-App -Path 'register.php'
Check 'Register page loads' ($r.Status -eq 200)

$r = Invoke-App -Method POST -Path 'register.php' -Data "email=$studentEmail&password=$studentPass"
Check 'Valid account is created' ($r.Body -match 'Account created successfully')

$hash = (& $mysql -u root -N -e "SELECT password_hash FROM wst_week13.users WHERE email='$studentEmail'") -join ''
Check 'Password is stored as a bcrypt hash' ($hash -like '$2y$*' -and $hash -ne $studentPass)
Write-Host "      stored hash: $hash"

$r = Invoke-App -Method POST -Path 'register.php' -Data "email=$studentEmail&password=$studentPass"
Check 'Duplicate email is rejected' ($r.Body -match 'already registered')

$r = Invoke-App -Method POST -Path 'register.php' -Data "email=short$stamp@example.com&password=short"
Check 'Short password is rejected' ($r.Body -match 'at least 12 characters')

$r = Invoke-App -Method POST -Path 'register.php' -Data "email=not-an-email&password=$studentPass"
Check 'Invalid email is rejected' ($r.Body -match 'valid email address')

Write-Host "`n== Authentication ==" -ForegroundColor Cyan

$r = Invoke-App -Path 'dashboard.php'
Check 'Dashboard redirects a visitor without a session' ($r.Status -eq 302 -and $r.Location -match 'login\.php')

$r = Invoke-App -Method POST -Path 'login.php' -Data "email=$studentEmail&password=WrongPassword123" -UseJar
Check 'Wrong password shows the generic message' ($r.Body -match 'Invalid email or password\.')

$r = Invoke-App -Path 'dashboard.php' -UseJar
Check 'Failed login creates no authenticated session' ($r.Status -eq 302 -and $r.Location -match 'login\.php')

$r = Invoke-App -Method POST -Path 'login.php' -Data "email=$studentEmail&password=$studentPass" -UseJar
Check 'Correct credentials redirect to the dashboard' ($r.Status -eq 302 -and $r.Location -match 'dashboard\.php')

$r = Invoke-App -Path 'dashboard.php' -UseJar
Check 'Dashboard shows the signed-in email' ($r.Status -eq 200 -and $r.Body.Contains($studentEmail))

Write-Host "`n== Authorization ==" -ForegroundColor Cyan

$r = Invoke-App -Path 'admin.php' -UseJar
Check 'Student is denied the admin page (403)' ($r.Status -eq 403 -and $r.Body -match 'Access denied\. Administrator permission is required\.')

$r = Invoke-App -Method POST -Path 'register.php' -Data "email=$adminEmail&password=$adminPass"
Check 'Second account is created' ($r.Body -match 'Account created successfully')

& $mysql -u root -e "UPDATE wst_week13.users SET role='admin' WHERE email='$adminEmail'"

$r = Invoke-App -Path 'logout.php' -UseJar
Check 'Student session ends before switching accounts' ($r.Status -eq 302 -and $r.Location -match 'login\.php')

$r = Invoke-App -Method POST -Path 'login.php' -Data "email=$adminEmail&password=$adminPass" -UseJar
Check 'Admin can log in' ($r.Status -eq 302 -and $r.Location -match 'dashboard\.php')

$r = Invoke-App -Path 'admin.php' -UseJar
Check 'Admin can open the admin page' ($r.Status -eq 200 -and $r.Body -match 'Administrator Dashboard')

Write-Host "`n== XSS output encoding ==" -ForegroundColor Cyan

$comment = [uri]::EscapeDataString($xss)
$r = Invoke-App -Method POST -Path 'index.php' -Data "comment=$comment" -UseJar
Check 'XSS test comment is accepted as text' ($r.Status -eq 302)

$r = Invoke-App -Path 'index.php' -UseJar
$encoded = '&lt;script&gt;alert(&#039;XSS test&#039;)&lt;/script&gt;'
Check 'XSS payload is HTML-encoded' $r.Body.Contains($encoded)
Check 'Raw script tag is not rendered' (-not $r.Body.Contains('<script>alert'))

Write-Host "`n== Logout ==" -ForegroundColor Cyan

$r = Invoke-App -Path 'logout.php' -UseJar
Check 'Logout redirects to login' ($r.Status -eq 302 -and $r.Location -match 'login\.php')

$r = Invoke-App -Path 'dashboard.php' -UseJar
Check 'Old session can no longer reach the dashboard' ($r.Status -eq 302 -and $r.Location -match 'login\.php')

Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "`n$($script:passed) passed, $($script:failed) failed" -ForegroundColor Cyan
if ($script:failed -gt 0) { exit 1 }
