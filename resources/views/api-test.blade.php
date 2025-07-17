<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #0056b3;
        }
        .result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <h1>YMM Filter API Test Page</h1>
    
    <div class="test-section">
        <h2>BigCommerce API Tests</h2>
        <button class="test-button" onclick="testBigCommerceMakes()">Test BC Makes</button>
        <button class="test-button" onclick="testBigCommerceModels()">Test BC Models (Ford)</button>
        <button class="test-button" onclick="testBigCommerceYears()">Test BC Years (Ford, F-150)</button>
        <button class="test-button" onclick="testBigCommerceProducts()">Test BC Compatible Products</button>
        <div id="bc-results" class="result"></div>
    </div>

    <div class="test-section">
        <h2>Local Database API Tests</h2>
        <button class="test-button" onclick="testLocalMakes()">Test Local Makes</button>
        <button class="test-button" onclick="testLocalModels()">Test Local Models (Ford)</button>
        <button class="test-button" onclick="testLocalYears()">Test Local Years (Ford, F-150)</button>
        <button class="test-button" onclick="testLocalProducts()">Test Local Compatible Products</button>
        <div id="local-results" class="result"></div>
    </div>

    <div class="test-section">
        <h2>Vehicle Management API Tests</h2>
        <button class="test-button" onclick="testVehiclesList()">Test List Vehicles</button>
        <button class="test-button" onclick="testAddVehicle()">Test Add Vehicle</button>
        <div id="vehicle-results" class="result"></div>
    </div>

    <div class="test-section">
        <h2>Widget Integration Test</h2>
        <button class="test-button" onclick="testWidget()">Load Widget</button>
        <div id="widget-container" style="min-height: 400px; border: 2px dashed #ccc; border-radius: 8px; margin: 10px 0;">
            <p style="text-align: center; padding: 20px; color: #666;">Widget will load here</p>
        </div>
    </div>

    <script>
        async function makeApiCall(url, method = 'GET', data = null) {
            try {
                const options = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                };
                
                if (data && method !== 'GET') {
                    options.body = JSON.stringify(data);
                }
                
                const response = await fetch(url, options);
                const result = await response.json();
                
                return {
                    success: response.ok,
                    status: response.status,
                    data: result
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.message
                };
            }
        }

        function displayResult(containerId, result, testName) {
            const container = document.getElementById(containerId);
            const timestamp = new Date().toLocaleTimeString();
            
            let html = `<h4>${testName} - ${timestamp}</h4>`;
            
            if (result.success) {
                html += `<div class="success">✅ Success (${result.status})</div>`;
                html += `<pre>${JSON.stringify(result.data, null, 2)}</pre>`;
            } else {
                html += `<div class="error">❌ Failed</div>`;
                if (result.status) {
                    html += `<p>Status: ${result.status}</p>`;
                }
                if (result.error) {
                    html += `<p>Error: ${result.error}</p>`;
                }
                if (result.data) {
                    html += `<pre>${JSON.stringify(result.data, null, 2)}</pre>`;
                }
            }
            
            container.innerHTML = html;
        }

        // BigCommerce API Tests
        async function testBigCommerceMakes() {
            const result = await makeApiCall('/api/bigcommerce/makes');
            displayResult('bc-results', result, 'BigCommerce Makes');
        }

        async function testBigCommerceModels() {
            const result = await makeApiCall('/api/bigcommerce/models?make=Ford');
            displayResult('bc-results', result, 'BigCommerce Models (Ford)');
        }

        async function testBigCommerceYears() {
            const result = await makeApiCall('/api/bigcommerce/years?make=Ford&model=F-150');
            displayResult('bc-results', result, 'BigCommerce Years (Ford F-150)');
        }

        async function testBigCommerceProducts() {
            const result = await makeApiCall('/api/bigcommerce/compatible-products?year=2018&make=Ford&model=F-150');
            displayResult('bc-results', result, 'BigCommerce Compatible Products');
        }

        // Local Database API Tests
        async function testLocalMakes() {
            const result = await makeApiCall('/api/ymm/makes');
            displayResult('local-results', result, 'Local Database Makes');
        }

        async function testLocalModels() {
            const result = await makeApiCall('/api/ymm/models?make=Ford');
            displayResult('local-results', result, 'Local Database Models (Ford)');
        }

        async function testLocalYears() {
            const result = await makeApiCall('/api/ymm/year-ranges?make=Ford&model=F-150');
            displayResult('local-results', result, 'Local Database Years (Ford F-150)');
        }

        async function testLocalProducts() {
            const result = await makeApiCall('/api/ymm/compatible-products?year=2018&make=Ford&model=F-150');
            displayResult('local-results', result, 'Local Database Compatible Products');
        }

        // Vehicle Management API Tests
        async function testVehiclesList() {
            const result = await makeApiCall('/api/vehicles');
            displayResult('vehicle-results', result, 'List Vehicles');
        }

        async function testAddVehicle() {
            const testVehicle = {
                make: 'Toyota',
                model: 'Camry',
                year_start: 2015,
                year_end: 2020,
                trim: 'LE'
            };
            const result = await makeApiCall('/api/vehicles', 'POST', testVehicle);
            displayResult('vehicle-results', result, 'Add Test Vehicle');
        }

        // Widget Test
        function testWidget() {
            const container = document.getElementById('widget-container');
            container.innerHTML = `
                <iframe src="/ymm-widget" 
                        style="width: 100%; height: 400px; border: none; border-radius: 8px;">
                </iframe>
            `;
        }

        // Auto-run basic tests on page load
        window.onload = function() {
            console.log('API Test Page Loaded');
            // Uncomment to auto-run tests
            // testLocalMakes();
        };
    </script>
</body>
</html>
