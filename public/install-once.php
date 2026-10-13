<?php

/**
 * ONE-TIME installer for hosts without SSH (Plesk / Hosttech).
 *
 * Upload this file into the live Laravel /public folder, then open:
 *
 *   https://nachfolge-experten.ch/install-once.php?key=NeInstall9xK2
 *
 * Optional:
 *   &seed=1     also run database seeders (usually NOT needed on live)
 *
 * DELETE this file immediately after a successful run.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$secret = 'NeInstall9xK2';

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

$withSeed = isset($_GET['seed']) && (string) $_GET['seed'] === '1';

echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Install</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem;background:#0b1f2e;color:#e8f2f8}';
echo 'pre{background:#07131d;color:#c8f0c0;padding:1rem;overflow:auto;border:1px solid #1e3a4f}';
echo 'h1{font-size:1.4rem}.ok{color:#7dffa0}.warn{color:#ffd27d}.err{color:#ff8f8f}</style></head><body>';
echo '<h1>nachfolge-experten.ch — Database install</h1>';
echo '<pre>';

try {
    echo "DB connection check...\n";
    DB::connection()->getPdo();
    echo "OK — connected.\n\n";

    echo "Tables before migrate:\n";
    echo '  events: '.(Schema::hasTable('events') ? 'EXISTS' : 'MISSING')."\n";
    echo '  experts.professional_group: '.(Schema::hasColumn('experts', 'professional_group') ? 'EXISTS' : 'MISSING')."\n";
    echo '  experts.crawl_import_articles: '.(Schema::hasColumn('experts', 'crawl_import_articles') ? 'EXISTS' : 'MISSING')."\n\n";

    echo "Running: php artisan migrate --force\n";
    $migrate = Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    echo "migrate exit code: {$migrate}\n\n";

    if ($withSeed) {
        echo "Running: php artisan db:seed --force\n";
        $seed = Artisan::call('db:seed', ['--force' => true]);
        echo Artisan::output();
        echo "seed exit code: {$seed}\n\n";
    } else {
        echo "Skipping seeders (safe default). Add &seed=1 only if you need them.\n\n";
    }

    echo "Clearing caches...\n";
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    echo Artisan::output() ?: "caches cleared.\n";

    echo "\nTables after migrate:\n";
    echo '  events: '.(Schema::hasTable('events') ? 'EXISTS' : 'MISSING')."\n";
    echo '  experts.professional_group: '.(Schema::hasColumn('experts', 'professional_group') ? 'EXISTS' : 'MISSING')."\n";
    echo '  experts.crawl_import_articles: '.(Schema::hasColumn('experts', 'crawl_import_articles') ? 'EXISTS' : 'MISSING')."\n";

    if (Schema::hasTable('events')) {
        echo "\n<span class=\"ok\">DONE — events table is ready.</span>\n";
    } else {
        echo "\n<span class=\"err\">ERROR — events table still missing. Upload latest /database/migrations files, then run again.</span>\n";
    }

    echo "\nDELETE public/install-once.php NOW for security.\n";
} catch (Throwable $e) {
    echo '<span class="err">ERROR: '.htmlspecialchars($e->getMessage())."</span>\n";
    echo htmlspecialchars($e->getTraceAsString());
}

echo '</pre>';
echo '<p class="warn"><strong>Delete this file from the server immediately after success.</strong></p>';
echo '</body></html>';
