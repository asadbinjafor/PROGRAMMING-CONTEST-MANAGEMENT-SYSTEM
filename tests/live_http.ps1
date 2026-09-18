param(
    [string]$BaseUrl = 'http://127.0.0.1:8765',
    [string]$DemoPassword = 'Password123!'
)

$ErrorActionPreference = 'Stop'
$checks = @()

foreach ($path in @('/', '/contests', '/leaderboard', '/announcements')) {
    $response = Invoke-WebRequest -UseBasicParsing ($BaseUrl + $path)
    $checks += [pscustomobject]@{
        Check = "public $path"
        Status = $response.StatusCode
        Pass = $response.StatusCode -eq 200 -and $response.Content -notmatch 'Application unavailable'
    }
}

$roles = @(
    @{ Email='participant@pcms.test'; Name='participant'; Route='/submissions'; Marker='Recent Submissions'; Denied='/organizer/contests' },
    @{ Email='organizer@pcms.test'; Name='organizer'; Route='/organizer/contests'; Marker='Organizer workspace'; Denied='/admin/users' },
    @{ Email='admin@pcms.test'; Name='admin'; Route='/admin/users'; Marker='System overview'; Denied='/organizer/contests' }
)

foreach ($role in $roles) {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login = Invoke-WebRequest -UseBasicParsing ($BaseUrl + '/login') -WebSession $session
    $token = [regex]::Match($login.Content, 'name="_csrf" value="([^"]+)"').Groups[1].Value
    $dashboard = Invoke-WebRequest -UseBasicParsing ($BaseUrl + '/login') -Method Post -Body @{
        _csrf = $token
        email = $role.Email
        password = $DemoPassword
    } -WebSession $session

    $checks += [pscustomobject]@{
        Check = "$($role.Name) dashboard"
        Status = $dashboard.StatusCode
        Pass = $dashboard.StatusCode -eq 200 -and $dashboard.Content -match [regex]::Escape($role.Marker)
    }

    $rolePage = Invoke-WebRequest -UseBasicParsing ($BaseUrl + $role.Route) -WebSession $session
    $checks += [pscustomobject]@{
        Check = "$($role.Name) role page"
        Status = $rolePage.StatusCode
        Pass = $rolePage.StatusCode -eq 200
    }

    $deniedStatus = 0
    try {
        $denied = Invoke-WebRequest -UseBasicParsing ($BaseUrl + $role.Denied) -WebSession $session
        $deniedStatus = [int]$denied.StatusCode
    } catch {
        $deniedStatus = [int]$_.Exception.Response.StatusCode
    }
    $checks += [pscustomobject]@{
        Check = "$($role.Name) authorization"
        Status = $deniedStatus
        Pass = $deniedStatus -eq 403
    }
}

$checks | Format-Table -AutoSize
if ($checks.Pass -contains $false) {
    exit(1)
}

Write-Host 'All live HTTP checks passed.'
