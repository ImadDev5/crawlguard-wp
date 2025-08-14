# Simple PayPerCrawl API Test
$baseUrl = "https://paypercrawl.tech/api/v1"
$apiKey = "paypercrawl_admin_2025_secure_key"

Write-Host "Testing PayPerCrawl API..." -ForegroundColor Cyan
Write-Host "===========================" -ForegroundColor Cyan

# Test 1: Health Check
Write-Host "`n1. Testing Health Check..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/status" -Method GET -UseBasicParsing
    Write-Host "   Status: $($response.StatusCode) - Health check passed!" -ForegroundColor Green
} catch {
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Bot Detection
Write-Host "`n2. Testing Bot Detection..."
$headers = @{ "X-API-Key" = $apiKey }
$body = @{
    user_agent = "Googlebot/2.1"
    ip_address = "66.249.66.1"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/detect" -Method POST -Headers $headers -Body $body -ContentType "application/json" -UseBasicParsing
    Write-Host "   Status: $($response.StatusCode) - Bot detection works!" -ForegroundColor Green
    $result = $response.Content | ConvertFrom-Json
    Write-Host "   Detection: Bot=$($result.detection.is_bot), Confidence=$($result.detection.confidence)%" -ForegroundColor Yellow
} catch {
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nAPI test complete!" -ForegroundColor Cyan
