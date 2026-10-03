<?php

/**
 * ONE-TIME installer for hosts without SSH.
 *
 * 1. Upload this file into your Laravel /public folder.
 * 2. Open in browser:
 *    https://nachfolge-experten.ch/install-once.php?key=CHANGE_ME_NOW_2026
 * 3. DELETE this file immediately after success.
 */

use Illuminate\Contracts\Console\Kernel;

$secret = 'CHANGE_ME_NOW_2026';

header('Content-Type: text/html; charset=utf-8');

if (! isset($_GET['key']) || ! hash_equals($secret, (string) $_GET['key'])) {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>Invalid install key.</p>';
    exit;
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo '<h1>nachfolge-experten.ch — Install</h1>';
echo '<pre style="background:#111;color:#d8f5d0;padding:16px;white-space:pre-wrap;">';

try {
    echo "Running migrate --force...\n";
    $migrate = Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    echo "migrate exit: {$migrate}\n\n";

    echo "Running db:seed --force...\n";
    $seed = Artisan::call('db:seed', ['--force' => true]);
    echo Artisan::output();
    echo "seed exit: {$seed}\n\n";

    echo "Running storage:link...\n";
    try {
        Artisan::call('storage:link');
        echo Artisan::output();
    } catch (Throwable $e) {
        echo 'storage:link note: '.$e->getMessage()."\n";
    }

    echo "Clearing caches...\n";
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    echo Artisan::output();

    echo "\nDONE.\n";
    echo "DELETE public/install-once.php NOW for security.\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo $e->getTraceAsString();
}

echo '</pre>';
echo '<p><strong style="color:#b42318;">Delete this file from the server immediately.</strong></p>';
