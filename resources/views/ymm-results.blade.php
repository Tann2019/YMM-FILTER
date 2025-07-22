<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
        }
        
        .vehicle-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .vehicle-badge {
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .products-section {
            margin-top: 30px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            background: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-image {
            width: 100%;
            max-width: 200px;
            height: auto;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2d3748;
        }
        
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 15px;
        }
        
        .product-link {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .product-link:hover {
            background: #2563eb;
            text-decoration: none;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .back-link {
            display: inline-block;
            color: #3b82f6;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .customize-note {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">← Back to Store</a>
        
        <!-- Note for web designers -->
        <div class="customize-note">
            <strong>For Web Designers:</strong> This page template can be fully customized. 
            The URL parameters are: <code>ymm-year={{ $year }}</code>, <code>ymm-make={{ $make }}</code>, <code>ymm-model={{ $model }}</code>
        </div>
        
        <div class="header">
            <h1>{{ $pageTitle }}</h1>
            
            @if($year && $make && $model)
                <div class="vehicle-info">
                    <span class="vehicle-badge">{{ $year }}</span>
                    <span class="vehicle-badge">{{ $make }}</span>
                    <span class="vehicle-badge">{{ $model }}</span>
                </div>
            @endif
        </div>
        
        <div class="products-section">
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>Loading compatible products...</p>
            </div>
            
            <div id="products-container" style="display: none;">
                <h2>Compatible Products</h2>
                <div id="products-grid" class="products-grid">
                    <!-- Products will be loaded here via JavaScript -->
                </div>
            </div>
            
            <div id="no-results" class="no-results" style="display: none;">
                <h3>No Compatible Products Found</h3>
                <p>Sorry, we couldn't find any products compatible with your {{ $year }} {{ $make }} {{ $model }}.</p>
                <p>Try selecting different vehicle specifications or contact us for assistance.</p>
            </div>
        </div>
    </div>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const year = urlParams.get('ymm-year');
        const make = urlParams.get('ymm-make');
        const model = urlParams.get('ymm-model');
        
        console.log('YMM Results page loaded with:', { year, make, model });
        
        // Only fetch products if we have all three parameters
        if (year && make && model) {
            fetchCompatibleProducts();
        } else {
            showNoResults('Please select year, make, and model to see compatible products.');
        }
        
        function fetchCompatibleProducts() {
            // Get the API URL from the current domain
            const apiUrl = window.location.origin;
            
            // For now, we'll use a generic store hash - in production, this should be dynamic
            const storeHash = 'rgp5uxku7h'; // This should be made dynamic based on the store
            
            const url = `${apiUrl}/api/ymm/${storeHash}/search?year=${encodeURIComponent(year)}&make=${encodeURIComponent(make)}&model=${encodeURIComponent(model)}`;
            
            console.log('Fetching products from:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'ngrok-skip-browser-warning': 'true'
                },
                mode: 'cors'
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Products data:', data);
                hideLoading();
                
                if (data.products && data.products.length > 0) {
                    displayProducts(data.products);
                } else {
                    showNoResults();
                }
            })
            .catch(error => {
                console.error('Error fetching products:', error);
                hideLoading();
                showNoResults('Error loading products. Please try again later.');
            });
        }
        
        function displayProducts(products) {
            const productsContainer = document.getElementById('products-container');
            const productsGrid = document.getElementById('products-grid');
            
            const html = products.map(product => `
                <div class="product-card">
                    ${product.images && product.images[0] ? 
                        `<img src="${product.images[0].url_thumbnail}" alt="${product.name}" class="product-image">` 
                        : ''
                    }
                    <h3 class="product-name">${product.name}</h3>
                    <div class="product-price">$${product.price || product.calculated_price || 'N/A'}</div>
                    <a href="${product.custom_url?.url || '#'}" class="product-link">View Product</a>
                </div>
            `).join('');
            
            productsGrid.innerHTML = html;
            productsContainer.style.display = 'block';
        }
        
        function showNoResults(message = null) {
            const noResults = document.getElementById('no-results');
            if (message) {
                noResults.querySelector('p').textContent = message;
            }
            noResults.style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>
