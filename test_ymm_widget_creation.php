<?php

/**
 * Test script for YMM Widget Creation
 * 
 * This script tests the new createYmmWidget functionality
 * Run this from the command line to test the widget creation process
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap Laravel
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BigCommerceService;
use Illuminate\Http\Request;

// Configuration
$storeHash = config('bigcommerce.local.store_hash');
$appUrl = config('app.url');

echo "🧪 Testing YMM Widget Creation\n";
echo "=================================\n";
echo "Store Hash: {$storeHash}\n";
echo "App URL: {$appUrl}\n";
echo "=================================\n\n";

if (!$storeHash) {
    echo "❌ Error: Store hash not found in configuration\n";
    echo "Please check your .env file for BIGCOMMERCE_STORE_HASH\n";
    exit(1);
}

try {
    $bigCommerceService = new BigCommerceService();
    
    echo "📋 Testing BigCommerce Service connectivity...\n";
    
    // Test basic API connectivity
    $stores = $bigCommerceService->makeApiCall($storeHash, '/store');
    echo "✅ Successfully connected to BigCommerce API\n";
    echo "   Store Name: " . ($stores['name'] ?? 'Unknown') . "\n\n";
    
    echo "🎯 Creating test YMM widget template...\n";
    
    // Test widget creation with custom settings
    $testSettings = [
        'widget_title' => 'Test YMM Filter',
        'button_text' => 'Find My Parts',
        'background_color' => '#e3f2fd',
        'text_color' => '#1565c0',
        'button_color' => '#1976d2',
        'widget_width' => 450
    ];
    
    $result = $bigCommerceService->installWidget($storeHash, $testSettings);
    
    if (isset($result['data'])) {
        echo "✅ Widget template created successfully!\n";
        echo "   Template ID: " . $result['data']['uuid'] . "\n";
        echo "   Template Name: " . $result['data']['name'] . "\n";
        echo "   Kind: " . $result['data']['kind'] . "\n\n";
        
        echo "📝 Next steps:\n";
        echo "1. Go to your BigCommerce admin panel\n";
        echo "2. Navigate to Storefront → Page Builder\n";
        echo "3. Edit a page and look for '" . $result['data']['name'] . "' in Custom Widgets\n";
        echo "4. Drag it to your page and configure the settings\n";
        echo "5. Save and preview your page\n\n";
        
        $templateId = $result['data']['uuid'];
        
        // Test widget deletion (cleanup)
        echo "🧹 Cleaning up test widget...\n";
        $deleteResult = $bigCommerceService->deleteWidgetTemplate($storeHash, $templateId);
        
        if ($deleteResult !== false) {
            echo "✅ Test widget deleted successfully\n";
        } else {
            echo "⚠️  Warning: Could not delete test widget (ID: {$templateId})\n";
            echo "   You may need to remove it manually from BigCommerce admin\n";
        }
    } else {
        echo "❌ Failed to create widget template\n";
        echo "   Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "   Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}

echo "\n🏁 Test completed\n";
