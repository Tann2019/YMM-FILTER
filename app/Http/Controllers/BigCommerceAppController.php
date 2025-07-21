<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BigCommerceStore;
use App\Services\BigCommerceService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BigCommerceAppController extends Controller
{
    protected $bigCommerceService;

    public function __construct(BigCommerceService $bigCommerceService)
    {
        $this->bigCommerceService = $bigCommerceService;
    }

    /**
     * Handle app installation callback from BigCommerce
     * This handles both GET (auth) and POST (install) requests
     */
    public function install(Request $request)
    {
        try {
            Log::info('BigCommerce Install/Auth called', [
                'method' => $request->method(),
                'query' => $request->query(),
                'all' => $request->all()
            ]);

            // For GET requests (auth callback), BigCommerce sends query parameters
            if ($request->isMethod('GET')) {
                $code = $request->query('code');
                $scope = $request->query('scope');
                $context = $request->query('context');

                if (!$code || !$context) {
                    throw new \Exception('Missing required parameters: code or context');
                }

                // Extract store hash from context (format: stores/{hash} or stores/{hash}/...)
                preg_match('/stores\/([a-z0-9]+)/', $context, $matches);
                $storeHash = $matches[1] ?? null;

                if (!$storeHash) {
                    throw new \Exception('Could not extract store hash from context: ' . $context);
                }

                // Exchange code for access token
                $tokenData = $this->bigCommerceService->getAccessToken($code, $context, $scope);

                // Store the installation data
                $store = BigCommerceStore::updateOrCreate(
                    ['store_hash' => $storeHash],
                    [
                        'store_name' => '', // Will be filled when we make first API call
                        'access_token' => $tokenData['access_token'],
                        'user_id' => $tokenData['user']['id'] ?? null,
                        'user_email' => $tokenData['user']['email'] ?? null,
                        'owner_id' => $tokenData['owner']['id'] ?? null,
                        'owner_email' => $tokenData['owner']['email'] ?? null,
                        'scope' => explode(' ', $scope),
                        'installed_at' => now(),
                        'active' => true
                    ]
                );

                // Create default settings
                $this->createDefaultSettings($store);

                Log::info('App installed successfully', ['store_hash' => $storeHash]);

                // Return a pretty HTML success page instead of JSON
                return response()->view('bigcommerce.install-success', [
                    'store_hash' => $storeHash,
                    'message' => 'YMM Filter App installed successfully!',
                    'next_steps' => [
                        'Click on the app in your Apps menu to get started',
                        'Configure your vehicle compatibility data',
                        'Customize the filter widget for your store'
                    ]
                ]);
            }

            // For POST requests (webhook install), verify payload
            $payload = $this->bigCommerceService->verifyInstallPayload($request);

            // Store the installation data
            $store = BigCommerceStore::updateOrCreate(
                ['store_hash' => $payload['store_hash']],
                [
                    'store_name' => $payload['store_name'] ?? '',
                    'access_token' => $payload['access_token'],
                    'user_id' => $payload['user']['id'],
                    'user_email' => $payload['user']['email'],
                    'owner_id' => $payload['owner']['id'],
                    'owner_email' => $payload['owner']['email'],
                    'scope' => explode(' ', $payload['scope']),
                    'installed_at' => now(),
                    'active' => true
                ]
            );

            // Create default settings
            $this->createDefaultSettings($store);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('BigCommerce App Installation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Installation failed: ' . $e->getMessage()], 400);
            }

            return redirect('/')->with('error', 'Installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle app load (when merchant clicks on app in their admin)
     */
    public function load(Request $request)
    {
        try {
            Log::info('BigCommerce Load called', [
                'method' => $request->method(),
                'query' => $request->query(),
                'all' => $request->all(),
                'url' => $request->fullUrl()
            ]);

            // For GET requests (load callback), BigCommerce sends signed_payload_jwt
            $signedPayload = $request->query('signed_payload_jwt');

            if (!$signedPayload) {
                Log::warning('Load callback missing signed_payload_jwt', [
                    'available_params' => array_keys($request->query())
                ]);
                throw new \Exception('Missing signed_payload_jwt parameter');
            }

            // Decode the signed payload JWT
            $payload = $this->bigCommerceService->verifyLoadPayload($request);

            // Get store hash from the decoded payload
            $storeHash = $payload['store_hash'] ?? null;

            if (!$storeHash) {
                throw new \Exception('Could not extract store hash from JWT payload');
            }

            // Update last accessed timestamp
            $store = BigCommerceStore::where('store_hash', $storeHash)->first();
            if ($store) {
                $store->updateLastAccessed();

                Log::info('App load successful, rendering dashboard', [
                    'store_hash' => $storeHash,
                    'user' => $payload['user'] ?? null
                ]);

                // Render the dashboard directly in the iFrame (don't redirect)
                return $this->dashboard($request, $storeHash);
            }

            Log::error('Store not found during load', ['store_hash' => $storeHash]);
            return response()->json(['error' => 'Store not found'], 404);
        } catch (\Exception $e) {
            Log::error('BigCommerce App Load Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Load failed: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Handle app uninstall
     */
    public function uninstall(Request $request)
    {
        try {
            $payload = $this->bigCommerceService->verifyUninstallPayload($request);

            // Mark store as inactive instead of deleting (for data retention)
            BigCommerceStore::where('store_hash', $payload['store_hash'])
                ->update(['active' => false]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('BigCommerce App Uninstall Error: ' . $e->getMessage());
            return response()->json(['error' => 'Uninstall failed'], 400);
        }
    }

    /**
     * Handle user removal (when store owner revokes user access)
     */
    public function removeUser(Request $request)
    {
        try {
            Log::info('BigCommerce Remove User called', [
                'method' => $request->method(),
                'query' => $request->query(),
                'all' => $request->all()
            ]);

            // Verify the JWT payload
            $payload = $this->bigCommerceService->verifyLoadPayload($request);
            $storeHash = $payload['store_hash'] ?? null;
            $userId = $payload['user']['id'] ?? null;

            if (!$storeHash || !$userId) {
                throw new \Exception('Missing store hash or user ID in payload');
            }

            Log::info('User access revoked', [
                'store_hash' => $storeHash,
                'user_id' => $userId
            ]);

            // Handle user removal logic here (e.g., remove from local database)
            // For now, just log it

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('BigCommerce Remove User Error: ' . $e->getMessage());
            return response()->json(['error' => 'Remove user failed'], 400);
        }
    }

    /**
     * Display the app dashboard for a specific store
     */
    public function dashboard(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        // Get vehicle statistics
        $vehicleStats = [
            'total_vehicles' => $store->vehicles()->count(),
            'unique_makes' => $store->vehicles()->distinct('make')->count('make'),
            'unique_models' => $store->vehicles()->distinct('model')->count('model'),
        ];

        return Inertia::render('App/Dashboard', [
            'store' => $store,
            'stats' => $vehicleStats,
            'settings' => $store->settings ?? $this->getDefaultAppSettings()
        ]);
    }

    /**
     * Create default settings for a new store installation
     */
    private function createDefaultSettings($store)
    {
        $defaultSettings = $this->getDefaultAppSettings();

        $store->update([
            'settings' => $defaultSettings
        ]);
    }

    /**
     * Get default app settings
     */
    private function getDefaultAppSettings()
    {
        return [
            'widget_enabled' => true,
            'widget_position' => 'product_options', // product_options, product_description, custom
            'filter_style' => 'dropdown', // dropdown, modal, sidebar
            'show_year_first' => true,
            'enable_search' => true,
            'auto_filter_products' => true,
            'show_no_match_message' => true,
            'no_match_message' => 'No compatible products found for your vehicle.',
            'widget_title' => 'Vehicle Compatibility Filter',
            'primary_color' => '#007bff',
            'custom_css' => ''
        ];
    }
}
