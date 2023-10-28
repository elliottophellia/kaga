Clear-Host

# Print banner
Write-Host "
d8b                                     
?88                                     
 88b  FREE UNLIMITED REVERSE IP LOOKUP
 888  d88' d888b8b   d888b8b   d888b8b  
 888bd8P' d8P' ?88  d8P' ?88  d8P' ?88  
d88888b   88b  ,88b 88b  ,88b 88b  ,88b 
d88' `?88b,`?88P'`88b`?88P'`88b`?88P'`88b
by @elliottophellia        )88          
                          ,88P          
                      `?8888P           
" -ForegroundColor DarkBlue

# Get user input
$userInput = Read-Host -Prompt 'Input your Domain/IP Address' 

# Remove http:// or https://
$userInput = $userInput -replace 'http(s)?://', ''

# Validate if the input is a valid domain
if ($userInput -match '^(?!-)(?:[A-Za-z0-9-]{1,63}\.)+[A-Za-z]{2,}$') {

    $uri = 'https://kaga.rei.my.id/api/reverseip?domain=' + $userInput
    $response = Invoke-RestMethod -Uri $uri

    # Check if the response contains the ReqResult property
    if ($response.PSObject.Properties.Name -contains 'ReqResult') {
        $response.ReqResult.ResultList
        $randomMathNumber = Get-Random
        $outputFolder = "output"
        if (!(Test-Path -Path $outputFolder)) {
            New-Item -ItemType Directory -Path $outputFolder
        }
        $outputFile = "$outputFolder/kaga_$userInput"+"_$randomMathNumber.txt"
        $response.ReqResult.ResultList | Out-File $outputFile
        Write-Host "Results saved to $outputFile" -ForegroundColor Green
    } else {
        Write-Host "Invalid domain or IP address"
    }
} else {
    Write-Host "Invalid domain or IP address"
}