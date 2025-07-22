<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BigCommerceService;

$service = app(BigCommerceService::class);
$storeHash = 'rgp5uxku7h';

try {
    $result = $service->makeApiCall($storeHash, '/content/widget-templates', 'GET');
    echo "Current widget templates:\n";
    print_r($result);
} catch (Exception $e) {
    echo "Error fetching templates: " . $e->getMessage() . "\n";
}
