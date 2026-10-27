$ErrorActionPreference = 'Stop'
$rbacWorkspace = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$rbacDatabase = 'admin_app_rbac_test_' + (Get-Date -Format 'yyyyMMddHHmmss') + '_' + (Get-Random -Minimum 1000 -Maximum 9999)
$rbacKeys = @('APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'RBAC_TEST_DATABASE')
$rbacPrevious = @{}
foreach ($rbacKey in $rbacKeys) {
    $rbacPrevious[$rbacKey] = [Environment]::GetEnvironmentVariable($rbacKey, 'Process')
}
$rbacCreated = $false
$rbacExit = 1
Push-Location $rbacWorkspace
try {
    $env:RBAC_TEST_DATABASE = $rbacDatabase
@'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$name = getenv('RBAC_TEST_DATABASE');
if (!preg_match('/^admin_app_rbac_test_[0-9]{14}_[0-9]{4}$/', $name)) {
    throw new RuntimeException('Invalid isolated database name.');
}
Illuminate\Support\Facades\DB::connection('mysql')->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo 'Created isolated database: '.$name.PHP_EOL;
'@ | php
    if ($LASTEXITCODE -ne 0) { throw 'Database creation failed; tests will not run.' }
    $rbacCreated = $true
    $env:APP_ENV = 'testing'
    $env:DB_CONNECTION = 'mysql'
    $env:DB_DATABASE = $rbacDatabase
    $env:DB_URL = ''
@'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('testing') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== getenv('RBAC_TEST_DATABASE')
    || Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS name')->name !== getenv('RBAC_TEST_DATABASE')) {
    throw new RuntimeException('Test isolation could not be verified. Clear cached configuration before retrying.');
}
'@ | php
    if ($LASTEXITCODE -ne 0) { throw 'Isolation check failed; tests will not run.' }
    php vendor/bin/pest --compact
    $rbacExit = $LASTEXITCODE
} finally {
    if ($rbacCreated) {
@'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$name = getenv('RBAC_TEST_DATABASE');
if (!preg_match('/^admin_app_rbac_test_[0-9]{14}_[0-9]{4}$/', $name)) {
    throw new RuntimeException('Refusing cleanup of a non-test database.');
}
Illuminate\Support\Facades\DB::connection('mysql')->statement('DROP DATABASE `'.$name.'`');
echo 'Removed isolated database: '.$name.PHP_EOL;
'@ | php
        if ($LASTEXITCODE -ne 0) {
            Write-Warning "Cleanup failed. Remove only the isolated test database $rbacDatabase manually."
            $rbacExit = 1
        }
    }
    foreach ($rbacKey in $rbacKeys) {
        [Environment]::SetEnvironmentVariable($rbacKey, $rbacPrevious[$rbacKey], 'Process')
    }
    Pop-Location
}
exit $rbacExit
