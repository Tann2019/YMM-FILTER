<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\BigCommerceService;

// Test widget creation with fixed schema
$service = new BigCommerceService();

$storeHash = 'rgp5uxku7h';
$widgetTemplate = $service->generateWidgetTemplate($storeHash);
$widgetSchema = $service->generateWidgetSchema();

echo "=== Testing Widget Schema ===\n";
echo "Widget Template Length: " . strlen($widgetTemplate) . " characters\n";
echo "Widget Schema Structure:\n";
echo json_encode($widgetSchema, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Checking typeMeta Objects ===\n";
foreach ($widgetSchema as $tab) {
    if (isset($tab['sections'])) {
        foreach ($tab['sections'] as $section) {
            if (isset($section['settings'])) {
                foreach ($section['settings'] as $setting) {
                    if (isset($setting['typeMeta'])) {
                        echo "Setting '{$setting['id']}' typeMeta type: " . gettype($setting['typeMeta']) . "\n";
                        if ($setting['type'] === 'range') {
                            echo "Range setting details: " . json_encode($setting['typeMeta']) . "\n";
                        }
                    }
                }
            }
        }
    }
}

// Test widget creation
try {
    echo "\n=== Testing Widget Creation ===\n";
    $result = $service->installWidget($storeHash, 'YMM Filter Widget Test', $widgetTemplate, $widgetSchema);
    echo "Widget created successfully!\n";
    echo "Widget UUID: " . $result['uuid'] . "\n";
    echo "Widget Name: " . $result['name'] . "\n";
} catch (Exception $e) {
    echo "Widget creation failed: " . $e->getMessage() . "\n";
    echo "This might indicate the schema is still incorrect.\n";
}

?>
