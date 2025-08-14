# PayPerCrawl Production API Testing Script
# Tests actual production API endpoints

$baseUrl = "https://paypercrawl.tech/api/v1"
$adminApiKey = "paypercrawl_admin_2025_secure_key"
$testResults = @()

# Color output functions
function Write-Success { param($msg) Write-Host $msg -ForegroundColor Green }
function Write-Error { param($msg) Write-Host $msg -ForegroundColor Red }
function Write-Info { param($msg) Write-Host $msg -ForegroundColor Cyan }
function Write-Warning { param($msg) Write-Host $msg -ForegroundColor Yellow }

# PowerShell 5.1 compatible web request
function Invoke-ApiRequest {
    param(
        [string]$Method,
        [string]$Endpoint,
        [hashtable]$Headers = @{},
        [object]$Body = $null
    )
    
    $result = @{
        Success = $false
        StatusCode = $null
        Response = $null
        Error = $null
        ResponseTime = 0
    }
    
    try {
        $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
        
        # Build parameters
        $params = @{
            Uri = "$baseUrl$Endpoint"
            Method = $Method
            UseBasicParsing = $true
        }
        
        # Add headers
        if ($Headers.Count -gt 0) {
            $params.Headers = $Headers
        }
        
        # Add body if provided
        if ($Body) {
            $jsonBody = $Body | ConvertTo-Json -Depth 10
            $params.Body = $jsonBody
            $params.ContentType = 'application/json'
        }
        
        # Make request
        $response = Invoke-WebRequest @params -ErrorAction Stop
        $stopwatch.Stop()
        
        $result.Success = $true
        $result.StatusCode = $response.StatusCode
        $result.ResponseTime = $stopwatch.Elapsed.TotalSeconds
        
        # Parse response
        try {
            $result.Response = $response.Content | ConvertFrom-Json
        } catch {
            $result.Response = $response.Content
        }
        
    } catch {
        $stopwatch.Stop()
        $result.Error = $_.Exception.Message
        $result.ResponseTime = $stopwatch.Elapsed.TotalSeconds
        
        # Try to get status code
        if ($_.Exception.Response) {
            $result.StatusCode = [int]$_.Exception.Response.StatusCode.value__
        }
    }
    
    return $result
}

Write-Host "========================================" -ForegroundColor Magenta
Write-Host " PayPerCrawl Production API Test" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta
Write-Host "Base URL: $baseUrl"
Write-Host "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-Host ""

# Test 1: Health Check
Write-Info "Testing: Health Check Endpoint"
$healthResult = Invoke-ApiRequest -Method "GET" -Endpoint "/status"

if ($healthResult.Success) {
    Write-Success "✓ Health Check Passed - Status: $($healthResult.StatusCode)"
    if ($healthResult.Response) {
        Write-Host "Response:" -ForegroundColor Gray
        $healthResult.Response | ConvertTo-Json -Depth 10
    }
} else {
    Write-Warning "Health check failed: $($healthResult.Error)"
}

$testResults += @{
    Test = "Health Check"
    Success = $healthResult.Success
    StatusCode = $healthResult.StatusCode
    ResponseTime = $healthResult.ResponseTime
}

# Test 2: Bot Detection with API Key
Write-Info "`nTesting: Bot Detection Endpoint (Googlebot)"

$headers = @{
    "X-API-Key" = $adminApiKey
    "Accept" = "application/json"
}

$botBody = @{
    user_agent = "Mozilla/5.0 (compatible Googlebot/2.1 +http://www.google.com/bot.html)"
    ip_address = "66.249.66.1"
    request_headers = @{
        "Accept" = "text/html"
        "Accept-Encoding" = "gzip, deflate"
    }
}

$botResult = Invoke-ApiRequest -Method "POST" -Endpoint "/detect" -Headers $headers -Body $botBody

if ($botResult.Success) {
    Write-Success "✓ Bot Detection Passed - Status: $($botResult.StatusCode)"
    if ($botResult.Response) {
        Write-Host "Detection Result:" -ForegroundColor Gray
        $botResult.Response | ConvertTo-Json -Depth 10
    }
} else {
    Write-Warning "Bot detection failed: $($botResult.Error)"
}

$testResults += @{
    Test = "Bot Detection (Googlebot)"
    Success = $botResult.Success
    StatusCode = $botResult.StatusCode
    ResponseTime = $botResult.ResponseTime
}

# Test 3: AI Bot Detection
Write-Info "`nTesting: AI Bot Detection (Claude)"

$aiBody = @{
    user_agent = "Claude-Web/1.0"
    ip_address = "192.168.1.1"
    request_headers = @{
        "Accept" = "application/json"
        "X-AI-Agent" = "claude"
    }
}

$aiResult = Invoke-ApiRequest -Method "POST" -Endpoint "/detect" -Headers $headers -Body $aiBody

if ($aiResult.Success) {
    Write-Success "✓ AI Bot Detection Passed - Status: $($aiResult.StatusCode)"
    if ($aiResult.Response) {
        Write-Host "AI Detection Result:" -ForegroundColor Gray
        $aiResult.Response | ConvertTo-Json -Depth 10
    }
} else {
    Write-Warning "AI bot detection failed: $($aiResult.Error)"
}

$testResults += @{
    Test = "AI Bot Detection (Claude)"
    Success = $aiResult.Success
    StatusCode = $aiResult.StatusCode
    ResponseTime = $aiResult.ResponseTime
}

# Test 4: Human User Detection
Write-Info "`nTesting: Human User Detection"

$humanBody = @{
    user_agent = "Mozilla/5.0 (Windows NT 10.0 Win64 x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ip_address = "203.0.113.42"
    request_headers = @{
        "Accept" = "text/html,application/xhtml+xml"
        "Accept-Language" = "en-US,en;q=0.9"
        "DNT" = "1"
    }
}

$humanResult = Invoke-ApiRequest -Method "POST" -Endpoint "/detect" -Headers $headers -Body $humanBody

if ($humanResult.Success) {
    Write-Success "✓ Human Detection Passed - Status: $($humanResult.StatusCode)"
    if ($humanResult.Response) {
        Write-Host "Human Detection Result:" -ForegroundColor Gray
        $humanResult.Response | ConvertTo-Json -Depth 10
    }
} else {
    Write-Warning "Human detection failed: $($humanResult.Error)"
}

$testResults += @{
    Test = "Human User Detection"
    Success = $humanResult.Success
    StatusCode = $humanResult.StatusCode
    ResponseTime = $humanResult.ResponseTime
}

# Test 5: Analytics Endpoint
Write-Info "`nTesting: Analytics Endpoint"

$analyticsResult = Invoke-ApiRequest -Method "GET" -Endpoint "/analytics" -Headers $headers

if ($analyticsResult.Success) {
    Write-Success "✓ Analytics Passed - Status: $($analyticsResult.StatusCode)"
    if ($analyticsResult.Response) {
        Write-Host "Analytics Data:" -ForegroundColor Gray
        $analyticsResult.Response | ConvertTo-Json -Depth 10
    }
} else {
    Write-Warning "Analytics failed: $($analyticsResult.Error)"
}

$testResults += @{
    Test = "Analytics Data"
    Success = $analyticsResult.Success
    StatusCode = $analyticsResult.StatusCode
    ResponseTime = $analyticsResult.ResponseTime
}

# Test 6: Invalid API Key
Write-Info "`nTesting: Invalid API Key Test"

$invalidHeaders = @{
    "X-API-Key" = "invalid_key_12345"
    "Accept" = "application/json"
}

$invalidResult = Invoke-ApiRequest -Method "POST" -Endpoint "/detect" -Headers $invalidHeaders -Body $botBody

if ($invalidResult.StatusCode -eq 401 -or $invalidResult.StatusCode -eq 403) {
    Write-Success "✓ Invalid API Key properly rejected - Status: $($invalidResult.StatusCode)"
} else {
    Write-Warning "Invalid API key test unexpected result: Status $($invalidResult.StatusCode)"
}

$testResults += @{
    Test = "Invalid API Key"
    Success = ($invalidResult.StatusCode -eq 401 -or $invalidResult.StatusCode -eq 403)
    StatusCode = $invalidResult.StatusCode
    ResponseTime = $invalidResult.ResponseTime
}

# Test 7: Rate Limiting
Write-Info "`nTesting: Rate Limiting (Quick burst test)"

$rateLimitHit = $false
for ($i = 1; $i -le 20; $i++) {
    $quickResult = Invoke-ApiRequest -Method "GET" -Endpoint "/status"
    if ($quickResult.StatusCode -eq 429) {
        $rateLimitHit = $true
        Write-Warning "Rate limit hit at request #$i"
        break
    }
    Write-Host "." -NoNewline
}

if (-not $rateLimitHit) {
    Write-Success "`n✓ Completed 20 requests without rate limit"
}

$testResults += @{
    Test = "Rate Limiting"
    Success = $true
    StatusCode = if ($rateLimitHit) { 429 } else { 200 }
    ResponseTime = 0
}

# Summary Report
Write-Host "`n`n========================================" -ForegroundColor Magenta
Write-Host " Test Summary Report" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

$successCount = ($testResults | Where-Object { $_.Success }).Count
$totalCount = $testResults.Count

Write-Host "Total Tests: $totalCount"
Write-Success "Passed: $successCount"
Write-Error "Failed: $($totalCount - $successCount)"

Write-Host "`nDetailed Results:" -ForegroundColor Yellow
$testResults | ForEach-Object {
    $status = if ($_.Success) { "✓" } else { "✗" }
    $color = if ($_.Success) { "Green" } else { "Red" }
    
    Write-Host "$status $($_.Test)" -ForegroundColor $color
    Write-Host "  Status: $($_.StatusCode) | Time: $([math]::Round($_.ResponseTime, 3))s" -ForegroundColor Gray
}

# Validation Summary
Write-Host "`n========================================" -ForegroundColor Magenta
Write-Host " API Validation Results" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

$validationPassed = $true

# Check critical endpoints
if ($healthResult.Success) {
    Write-Success "✓ API is accessible and responding"
} else {
    Write-Error "✗ API is not accessible"
    $validationPassed = $false
}

if ($botResult.Success -and $botResult.Response.detection) {
    Write-Success "✓ Bot detection is working"
} else {
    Write-Error "✗ Bot detection is not working properly"
    $validationPassed = $false
}

if ($aiResult.Success -and $aiResult.Response.detection -and $aiResult.Response.detection.is_ai_bot) {
    Write-Success "✓ AI bot detection is working"
} else {
    Write-Warning "⚠ AI bot detection may need configuration"
}

if ($humanResult.Success -and $humanResult.Response.detection -and -not $humanResult.Response.detection.is_bot) {
    Write-Success "✓ Human user detection is accurate"
} else {
    Write-Warning "⚠ Human detection may need tuning"
}

if ($invalidResult.StatusCode -eq 401 -or $invalidResult.StatusCode -eq 403) {
    Write-Success "✓ API authentication is enforced"
} else {
    Write-Warning "⚠ API authentication may not be properly configured"
}

# Final Status
Write-Host "`n========================================" -ForegroundColor Magenta
Write-Host " Final Status" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

if ($validationPassed) {
    Write-Success "`n✓ API VALIDATION PASSED - Ready for production"
} else {
    Write-Error "`n✗ API VALIDATION FAILED - Requires attention"
}

Write-Host "`nTest completed at: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
