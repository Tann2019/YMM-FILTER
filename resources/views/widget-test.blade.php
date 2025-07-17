<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMM Widget Test Page</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .error {
            color: red;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            color: green;
            background: #e6ffe6;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>YMM Widget Test Page</h1>
    <p>This page tests the YMM filter widget integration and helps debug common issues.</p>

    <div class="test-section">
        <h2>Test 1: Direct Widget Embed</h2>
        <p>This embeds the widget directly using an iframe-like approach:</p>
        <div id="direct-widget-container">
            @include('components.ymm-filter-widget-custom-fields')
        </div>
    </div>

    <div class="test-section">
        <h2>Test 2: AJAX Load (Simulating BigCommerce Integration)</h2>
        <p>This tests loading the widget via AJAX like it would be loaded on BigCommerce:</p>
        <div id="ajax-widget-container">
            <p>Loading widget...</p>
        </div>
        <button onclick="loadWidget()">Reload Widget</button>
        <div id="load-status"></div>
    </div>

    <div class="test-section">
        <h2>Test 3: Error Scenario</h2>
        <p>This tests what happens when the container doesn't exist:</p>
        <button onclick="testMissingContainer()">Test Missing Container</button>
        <div id="error-test-result"></div>
    </div>

    <div class="test-section">
        <h2>Debug Information</h2>
        <p><strong>Current URL:</strong> <span id="current-url"></span></p>
        <p><strong>Store Hash Detection:</strong> <span id="store-hash"></span></p>
        <p><strong>Widget Endpoint:</strong> <span id="widget-endpoint"></span></p>
    </div>

    <script>
        // Update debug info
        document.getElementById('current-url').textContent = window.location.href;
        
        const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
            ? window.location.hostname.split('.')[0] 
            : 'test-store'; // Default for testing
            
        document.getElementById('store-hash').textContent = storeHash;
        document.getElementById('widget-endpoint').textContent = `{{ url('/ymm-widget') }}?store_hash=${storeHash}`;

        function loadWidget() {
            const container = document.getElementById('ajax-widget-container');
            const statusDiv = document.getElementById('load-status');
            
            if (!container) {
                statusDiv.innerHTML = '<div class="error">Error: Container not found!</div>';
                return;
            }
            
            container.innerHTML = '<p>Loading widget...</p>';
            statusDiv.innerHTML = '';
            
            fetch(`{{ url('/ymm-widget') }}?store_hash=${storeHash}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    container.innerHTML = html;
                    statusDiv.innerHTML = '<div class="success">Widget loaded successfully!</div>';
                })
                .catch(error => {
                    console.error('Error loading YMM filter:', error);
                    container.innerHTML = '<div class="error">Failed to load vehicle filter. Error: ' + error.message + '</div>';
                    statusDiv.innerHTML = '<div class="error">Load failed: ' + error.message + '</div>';
                });
        }

        function testMissingContainer() {
            const resultDiv = document.getElementById('error-test-result');
            
            // Try to set innerHTML on a non-existent element
            const nonExistentElement = document.getElementById('this-does-not-exist');
            
            if (!nonExistentElement) {
                resultDiv.innerHTML = '<div class="error">✓ Correctly detected: Cannot set innerHTML on null element</div>';
            } else {
                resultDiv.innerHTML = '<div class="success">Element found (unexpected)</div>';
            }
        }

        // Load the AJAX widget on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadWidget();
        });
    </script>
</body>
</html>
