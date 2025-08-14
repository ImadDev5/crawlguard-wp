# PayPerCrawl API Testing Script
# Tests all API endpoints and validates responses

$baseUrl = "https://api.paypercrawl.tech/v1"
$testResults = @()

# Color output functions
function Write-Success { param($msg) Write-Host $msg -ForegroundColor Green }
function Write-Error { param($msg) Write-Host $msg -ForegroundColor Red }
function Write-Info { param($msg) Write-Host $msg -ForegroundColor Cyan }
function Write-Warning { param($msg) Write-Host $msg -ForegroundColor Yellow }

# Test function with error handling
function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Endpoint,
        [hashtable]$Headers = @{},
        [object]$Body = $null,
        [string]$Description
    )
    
    $testResult = @{
        Endpoint = $Endpoint
        Method = $Method
        Description = $Description
        Success = $false
        StatusCode = $null
        ResponseTime = $null
        Response = $null
        Error = $null
    }
    
    Write-Info "`nTesting: $Description"
    Write-Host "Endpoint: $Method $Endpoint"
    
    try {
        $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
        
        $params = @{
            Uri = "$baseUrl$Endpoint"
            Method = $Method
            Headers = $Headers
            ErrorAction = 'Stop'
        }
        
        if ($Body) {
            $params.Body = ($Body | ConvertTo-Json -Depth 10)
            $params.ContentType = 'application/json'
        }
        
        $response = Invoke-WebRequest @params
        $stopwatch.Stop()
        
        $testResult.Success = $true
        $testResult.StatusCode = $response.StatusCode
        $testResult.ResponseTime = $stopwatch.Elapsed.TotalSeconds
        
        try {
            $testResult.Response = $response.Content | ConvertFrom-Json
        } catch {
            $testResult.Response = $response.Content
        }
        
        Write-Success "✓ Status: $($response.StatusCode) - Response Time: $([math]::Round($stopwatch.Elapsed.TotalSeconds, 3))s"
        
        if ($testResult.Response) {
            Write-Host "Response:" -ForegroundColor Gray
            $testResult.Response | ConvertTo-Json -Depth 10 | Write-Host
        }
        
    } catch {
        $stopwatch.Stop() 
        $testResult.Error = $_.Exception.Message
        $testResult.ResponseTime = $stopwatch.Elapsed.TotalSeconds
        
        # Try to get status code from error
        if ($_.Exception.Response) {
            $testResult.StatusCode = [int]$_.Exception.Response.StatusCode
        }
        
        Write-Error "✗ Error: $($_.Exception.Message)"
        
        # If it's a 4xx or 5xx error, try to get the response body
        if ($_.Exception.Response) {
            try {
                $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $errorContent = $reader.ReadToEnd()
                $reader.Close()
                
                if ($errorContent) {
                    Write-Host "Error Response:" -ForegroundColor Gray
                    try {
                        $errorContent | ConvertFrom-Json | ConvertTo-Json -Depth 10 | Write-Host
                    } catch {
                        Write-Host $errorContent
                    }
                }
            } catch {}
        }
    }
    
    return $testResult
}

# Generate JWT token (mock for testing)
function Get-MockJWT {
    # In production, this would generate a real JWT
    return "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ0ZXN0X3VzZXIiLCJpYXQiOjE3MDI0MDY0MDAsImV4cCI6MTcwMjQxMDAwMH0.test_signature"
}

Write-Host "========================================" -ForegroundColor Magenta
Write-Host " PayPerCrawl API Testing Suite" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta
Write-Host "Base URL: $baseUrl"
Write-Host "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-Host ""

# Test 1: Health Check (No Auth)
$testResults += Test-Endpoint `
    -Method "GET" `
    -Endpoint "/status" `
    -Headers @{"Accept" = "application/json"} `
    -Description "API Health Check (No Authentication)"

# Test 2: Health Check (With Auth)
$jwtToken = Get-MockJWT
$authHeaders = @{
    "Accept" = "application/json"
    "Authorization" = "Bearer $jwtToken"
}

$testResults += Test-Endpoint `
    -Method "GET" `
    -Endpoint "/status" `
    -Headers $authHeaders `
    -Description "API Health Check (With JWT Authentication)"

# Test 3: Bot Detection Endpoint
$detectionBody = @{
    user_agent = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
    ip_address = "66.249.66.1"
    request_headers = @{
        "Accept" = "text/html,application/xhtml+xml"
        "Accept-Encoding" = "gzip, deflate"
        "From" = "googlebot(at)googlebot.com"
    }
}

$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/detect" `
    -Headers $authHeaders `
    -Body $detectionBody `
    -Description "Bot Detection (Googlebot Test)"

# Test 4: Bot Detection - AI Bot Test
$aiDetectionBody = @{
    user_agent = "Claude-Web/1.0"
    ip_address = "192.168.1.1"
    request_headers = @{
        "Accept" = "application/json"
        "X-AI-Agent" = "claude"
    }
}

$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/detect" `
    -Headers $authHeaders `
    -Body $aiDetectionBody `
    -Description "Bot Detection (AI Bot Test)"

# Test 5: Bot Detection - Human User Test
$humanDetectionBody = @{
    user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ip_address = "203.0.113.42"
    request_headers = @{
        "Accept" = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
        "Accept-Language" = "en-US,en;q=0.5"
        "Accept-Encoding" = "gzip, deflate, br"
        "DNT" = "1"
        "Upgrade-Insecure-Requests" = "1"
    }
}

$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/detect" `
    -Headers $authHeaders `
    -Body $humanDetectionBody `
    -Description "Bot Detection (Human User Test)"

# Test 6: Analytics Endpoint
$testResults += Test-Endpoint `
    -Method "GET" `
    -Endpoint "/analytics" `
    -Headers $authHeaders `
    -Description "Get Analytics Data"

# Test 7: Analytics with Query Parameters
$analyticsParams = "?start_date=2024-01-01&end_date=2024-01-31&bot_type=googlebot"
$testResults += Test-Endpoint `
    -Method "GET" `
    -Endpoint "/analytics$analyticsParams" `
    -Headers $authHeaders `
    -Description "Get Filtered Analytics Data"

# Test 8: Webhook Handler Registration
$webhookBody = @{
    url = "https://example.com/webhook/bot-detected"
    events = @("bot_detected", "ai_bot_detected", "suspicious_activity")
    secret = "webhook_secret_key"
}

$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/webhook" `
    -Headers $authHeaders `
    -Body $webhookBody `
    -Description "Register Webhook Handler"

# Test 9: Invalid Endpoint (404 Test)
$testResults += Test-Endpoint `
    -Method "GET" `
    -Endpoint "/nonexistent" `
    -Headers $authHeaders `
    -Description "Invalid Endpoint (404 Test)"

# Test 10: Missing Authentication (401 Test)
$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/detect" `
    -Headers @{"Accept" = "application/json"} `
    -Body $detectionBody `
    -Description "Missing Authentication (401 Test)"

# Test 11: Invalid JWT Token (403 Test)
$invalidAuthHeaders = @{
    "Accept" = "application/json"
    "Authorization" = "Bearer invalid_token_12345"
}

$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/detect" `
    -Headers $invalidAuthHeaders `
    -Body $detectionBody `
    -Description "Invalid JWT Token (403 Test)"

# Test 12: Malformed Request Body (400 Test)
$malformedBody = @{
    invalid_field = "test"
}

$testResults += Test-Endpoint `
    -Method "POST" `
    -Endpoint "/detect" `
    -Headers $authHeaders `
    -Body $malformedBody `
    -Description "Malformed Request Body (400 Test)"

# Rate Limiting Test
Write-Info "`nTesting Rate Limiting (1000 requests limit)..."
Write-Host "Sending rapid requests to test rate limiting..."

$rateLimitResults = @()
$rateLimitHit = $false

for ($i = 1; $i -le 10; $i++) {
    try {
        $response = Invoke-WebRequest `
            -Uri "$baseUrl/status" `
            -Method GET `
            -Headers $authHeaders `
            -ErrorAction Stop
        
        if ($i % 100 -eq 0) {
            Write-Host "." -NoNewline
        }
    } catch {
        if ($_.Exception.Response.StatusCode -eq 429) {
            $rateLimitHit = $true
            Write-Warning "`nRate limit hit at request #$i (Status: 429)"
            break
        }
    }
}

if (-not $rateLimitHit) {
    Write-Success "`n✓ Completed 10 test requests without hitting rate limit"
    Write-Info "Note: Full rate limit test (1000 requests) skipped for efficiency"
}

# Summary Report
Write-Host "`n========================================" -ForegroundColor Magenta
Write-Host " Test Summary Report" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

$successCount = ($testResults | Where-Object { $_.Success }).Count
$failureCount = ($testResults | Where-Object { -not $_.Success }).Count

Write-Host "Total Tests: $($testResults.Count)"
Write-Success "Successful: $successCount"
if ($failureCount -gt 0) {
    Write-Error "Failed: $failureCount"
} else {
    Write-Host "Failed: 0" -ForegroundColor Gray
}

Write-Host "`nDetailed Results:" -ForegroundColor Yellow
$testResults | ForEach-Object {
    $status = if ($_.Success) { "✓" } else { "✗" }
    $color = if ($_.Success) { "Green" } else { "Red" }
    
    Write-Host "$status " -NoNewline -ForegroundColor $color
    Write-Host "$($_.Method) $($_.Endpoint) - $($_.Description)"
    
    if ($_.StatusCode) {
        Write-Host "  Status Code: $($_.StatusCode)" -ForegroundColor Gray
    }
    
    if ($_.ResponseTime) {
        Write-Host "  Response Time: $([math]::Round($_.ResponseTime, 3))s" -ForegroundColor Gray
    }
    
    if (-not $_.Success -and $_.Error) {
        Write-Host "  Error: $($_.Error)" -ForegroundColor DarkRed
    }
}

# Response Format Validation
Write-Host "`n========================================" -ForegroundColor Magenta
Write-Host " Response Format Validation" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

$validationResults = @()

# Check successful responses for expected format
$successfulTests = $testResults | Where-Object { $_.Success -and $_.Response }

foreach ($test in $successfulTests) {
    Write-Host "`nValidating: $($test.Description)" -ForegroundColor Cyan
    
    $validation = @{
        Endpoint = $test.Endpoint
        Valid = $true
        Issues = @()
    }
    
    # Validate based on endpoint type
    switch -Regex ($test.Endpoint) {
        "/status" {
            if (-not $test.Response.status) {
                $validation.Issues += "Missing 'status' field"
                $validation.Valid = $false
            }
        }
        "/detect" {
            if (-not $test.Response.detection) {
                $validation.Issues += "Missing 'detection' field"
                $validation.Valid = $false
            }
            if ($test.Response.detection) {
                $requiredFields = @("is_bot", "confidence", "action")
                foreach ($field in $requiredFields) {
                    if ($null -eq $test.Response.detection.$field) {
                        $validation.Issues += "Missing 'detection.$field' field"
                        $validation.Valid = $false
                    }
                }
            }
        }
        "/analytics" {
            if (-not $test.Response.data -and -not $test.Response.analytics) {
                $validation.Issues += "Missing 'data' or 'analytics' field"
                $validation.Valid = $false
            }
        }
        "/webhook" {
            if (-not $test.Response.success -and -not $test.Response.webhook_id) {
                $validation.Issues += "Missing 'success' or 'webhook_id' field"
                $validation.Valid = $false
            }
        }
    }
    
    if ($validation.Valid) {
        Write-Success "  ✓ Response format is valid"
    } else {
        Write-Error "  ✗ Response format issues found:"
        $validation.Issues | ForEach-Object {
            Write-Host "    - $_" -ForegroundColor Red
        }
    }
    
    $validationResults += $validation
}

# Final Summary
Write-Host "`n========================================" -ForegroundColor Magenta
Write-Host " Final Test Results" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

$allTestsPassed = ($failureCount -eq 0) -and ($validationResults | Where-Object { -not $_.Valid }).Count -eq 0

if ($allTestsPassed) {
    Write-Success "`n✓ ALL TESTS PASSED SUCCESSFULLY!"
} else {
    Write-Warning "`n⚠ Some tests failed or had validation issues"
    Write-Host "Please review the detailed results above for more information."
}

Write-Host "`nTest completion time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
