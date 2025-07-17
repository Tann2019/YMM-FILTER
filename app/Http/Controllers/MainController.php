<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;

use GuzzleHttp\Exception\RequestException;

use GuzzleHttp\Client;
use App\Models\BigCommerceStore;

class MainController extends BaseController
{
    protected $baseURL;

    public function __construct()
    {
        $this->baseURL = env('APP_URL');
    }

    public function getRedirectUri(Request $request)
    {
        // If the request came through ngrok, use the ngrok URL
        if ($request->hasHeader('x-forwarded-host')) {
            $forwardedHost = $request->header('x-forwarded-host');
            if (strpos($forwardedHost, 'ngrok') !== false) {
                return 'https://' . $forwardedHost . '/auth';
            }
        }
        
        // Fallback to configured APP_URL
        return $this->baseURL . '/auth';
    }

    public function getAppClientId()
    {
        // For OAuth installation flow, always use App credentials from Developer Portal
        return config('bigcommerce.app.client_id');
    }

    public function getAppSecret(Request $request)
    {
        // For OAuth installation flow, always use App credentials from Developer Portal
        return config('bigcommerce.app.secret');
    }

    public function getAccessToken(Request $request)
    {
        if (config('app.env') === 'local') {
            return config('bigcommerce.local.access_token');
        } else {
            return $request->session()->get('access_token');
        }
    }

    public function getStoreHash(Request $request)
    {
        if (config('app.env') === 'local' && config('bigcommerce.local.store_hash')) {
            return config('bigcommerce.local.store_hash');
        } else {
            return $request->session()->get('store_hash');
        }
    }

    public function install(Request $request): RedirectResponse
    {
        // Add comprehensive debugging
        \Log::info("=== BIGCOMMERCE INSTALL REQUEST ===");
        \Log::info("Request method: " . $request->method());
        \Log::info("Request URL: " . $request->fullUrl());
        \Log::info("Request headers: " . json_encode($request->headers->all()));
        \Log::info("Request query params: " . json_encode($request->query->all()));
        \Log::info("Request body: " . $request->getContent());
        \Log::info("User agent: " . $request->userAgent());
        \Log::info("IP address: " . $request->ip());
        \Log::info("===================================");

        // Make sure all required query params have been passed
        if (!$request->has('code') || !$request->has('scope') || !$request->has('context')) {
            $missing = [];
            if (!$request->has('code')) $missing[] = 'code';
            if (!$request->has('scope')) $missing[] = 'scope';
            if (!$request->has('context')) $missing[] = 'context';
            
            \Log::error("Missing required parameters for BigCommerce app installation: " . implode(', ', $missing));
            return redirect()->action([MainController::class, 'error'], ['error_message' => 'Not enough information was passed to install this app. Missing: ' . implode(', ', $missing)]);
        }

        try {
            $client = new Client();
            
            // Log the OAuth token request details
            $redirectUri = $this->getRedirectUri($request);
            $oauthRequestData = [
                'client_id' => $this->getAppClientId(),
                'client_secret' => substr($this->getAppSecret($request), 0, 10) . '...', // Only log first 10 chars
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $request->input('code'),
                'scope' => $request->input('scope'),
                'context' => $request->input('context'),
            ];
            \Log::info("Base URL from env: " . env('APP_URL'));
            \Log::info("Base URL from controller: " . $this->baseURL);
            \Log::info("X-Forwarded-Host: " . $request->header('x-forwarded-host'));
            \Log::info("Final redirect_uri: " . $redirectUri);
            \Log::info("Making OAuth token request to BigCommerce with data: " . json_encode($oauthRequestData));
            
            $result = $client->request('POST', 'https://login.bigcommerce.com/oauth2/token', [
                'json' => [
                    'client_id' => $this->getAppClientId(),
                    'client_secret' => $this->getAppSecret($request),
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                    'code' => $request->input('code'),
                    'scope' => $request->input('scope'),
                    'context' => $request->input('context'),
                ]
            ]);

            $statusCode = $result->getStatusCode();
            $data = json_decode($result->getBody(), true);
            
            \Log::info("OAuth token response status: " . $statusCode);
            \Log::info("OAuth token response body: " . $result->getBody());

            if ($statusCode == 200) {
                // Extract store hash from context (format: "stores/abc123xyz")
                $storeHash = str_replace('stores/', '', $data['context']);
                
                // Debug: Log the OAuth response data
                error_log("OAuth Success - Context: " . $data['context']);
                error_log("OAuth Success - Extracted Store Hash: " . $storeHash);
                error_log("OAuth Success - Access Token: " . substr($data['access_token'], 0, 10) . '...');
                
                // Save store credentials to database for multi-shop support
                $this->saveStoreCredentials($storeHash, $data, $request);
                
                $request->session()->put('store_hash', $storeHash);
                $request->session()->put('access_token', $data['access_token']);
                $request->session()->put('user_id', $data['user']['id']);
                $request->session()->put('user_email', $data['user']['email']);

                // If the merchant installed the app via an external link, redirect back to the 
                // BC installation success page for this app
                if ($request->has('external_install')) {
                    return Redirect::to('https://login.bigcommerce.com/app/' . $this->getAppClientId() . '/install/succeeded');
                }
            }

            return Redirect::to('/dashboard');
        } catch (RequestException $e) {
            \Log::error("BigCommerce OAuth token request failed");
            \Log::error("Exception message: " . $e->getMessage());
            
            $statusCode = null;
            $errorMessage = "An error occurred during app installation.";
            $responseBody = null;

            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();
                \Log::error("Response status code: " . $statusCode);
                \Log::error("Response body: " . $responseBody);
                
                if ($statusCode != 500) {
                    $errorMessage = "BigCommerce API returned status " . $statusCode . ": " . $responseBody;
                }
            } else {
                \Log::error("No response received from BigCommerce");
            }

            // If the merchant installed the app via an external link, redirect back to the 
            // BC installation failure page for this app
            if ($request->has('external_install')) {
                return Redirect::to('https://login.bigcommerce.com/app/' . $this->getAppClientId() . '/install/failed');
            } else {
                return redirect()->action([MainController::class, 'error'], ['error_message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            \Log::error("Unexpected error during BigCommerce app installation: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            return redirect()->action([MainController::class, 'error'], ['error_message' => 'Unexpected error: ' . $e->getMessage()]);
        }
    }

    public function load(Request $request): View
    {
        \Log::info("=== BIGCOMMERCE LOAD REQUEST ===");
        \Log::info("Request method: " . $request->method());
        \Log::info("Request URL: " . $request->fullUrl());
        \Log::info("Request headers: " . json_encode($request->headers->all()));
        \Log::info("Request query params: " . json_encode($request->query->all()));
        \Log::info("Request body: " . $request->getContent());
        \Log::info("User agent: " . $request->userAgent());
        \Log::info("IP address: " . $request->ip());
        \Log::info("=================================");

        $signedPayload = $request->input('signed_payload');
        if (!empty($signedPayload)) {
            $verifiedSignedRequestData = $this->verifySignedRequest($signedPayload, $request);
            if ($verifiedSignedRequestData !== null) {
                $request->session()->put('user_id', $verifiedSignedRequestData['user']['id']);
                $request->session()->put('user_email', $verifiedSignedRequestData['user']['email']);
                $request->session()->put('owner_id', $verifiedSignedRequestData['owner']['id']);
                $request->session()->put('owner_email', $verifiedSignedRequestData['owner']['email']);
                
                // Extract store hash from context (format: "stores/abc123xyz")
                $storeHash = str_replace('stores/', '', $verifiedSignedRequestData['context']);
                $request->session()->put('store_hash', $storeHash);
                
            } else {
                return redirect()->action([MainController::class, 'error'], ['error_message' => 'The signed request from BigCommerce could not be validated.']);
            }
        } else {
            return redirect()->action([MainController::class, 'error'], ['error_message' => 'The signed request from BigCommerce was empty.']);
        }

        $request->session()->regenerate();

        return view('bigcommerce.app');
    }

    public function error(Request $request)
    {
        \Log::error("=== BIGCOMMERCE ERROR PAGE ===");
        \Log::error("Error message: " . $request->get('error_message', 'Unknown error'));
        \Log::error("Request URL: " . $request->fullUrl());
        \Log::error("Request query params: " . json_encode($request->query->all()));
        \Log::error("User agent: " . $request->userAgent());
        \Log::error("IP address: " . $request->ip());
        \Log::error("===============================");

        $errorMessage = "Internal Application Error";

        if ($request->session()->has('error_message')) {
            $errorMessage = $request->session()->get('error_message');
        }

        echo '<h4>An issue has occurred:</h4> <p>' . $errorMessage . '</p> <a href="' . $this->baseURL . '">Go back to home</a>';
    }

    private function verifySignedRequest($signedRequest, $appRequest)
    {
        list($encodedData, $encodedSignature) = explode('.', $signedRequest, 2);

        // decode the data
        $signature = base64_decode($encodedSignature);
        $jsonStr = base64_decode($encodedData);
        $data = json_decode($jsonStr, true);

        // confirm the signature
        $expectedSignature = hash_hmac('sha256', $jsonStr, $this->getAppSecret($appRequest), $raw = false);
        if (!hash_equals($expectedSignature, $signature)) {
            error_log('Bad signed request from BigCommerce!');
            return null;
        }
        return $data;
    }

    public function makeBigCommerceAPIRequest(Request $request, $endpoint)
    {
        $storeHash = $this->getStoreHash($request);
        
        // Simple debug - write to Laravel log
        \Log::info("=== STORE HASH DEBUG ===");
        \Log::info("APP_ENV: " . config('app.env'));
        \Log::info("BC_LOCAL_STORE_HASH: " . config('bigcommerce.local.store_hash'));
        \Log::info("Session store_hash: " . $request->session()->get('store_hash'));
        \Log::info("Final storeHash: " . ($storeHash ?: 'EMPTY'));
        \Log::info("========================");
        
        if (empty($storeHash)) {
            throw new \Exception('Store hash is empty. Cannot make BigCommerce API request.');
        }
        
        $requestConfig = [
            'headers' => [
                'X-Auth-Client' => $this->getAppClientId(),
                'X-Auth-Token'  => $this->getAccessToken($request),
                'Content-Type'  => 'application/json',
            ]
        ];

        if ($request->method() === 'PUT') {
            $requestConfig['body'] = $request->getContent();
        }

        $client = new Client();
        $queryString = $request->getQueryString() ? "?{$request->getQueryString()}" : '';
        $result = $client->request($request->method(), 'https://api.bigcommerce.com/stores/' . $storeHash .'/'. $endpoint . $queryString, $requestConfig);
        
        return $result;
    }

    public function proxyBigCommerceAPIRequest(Request $request, $endpoint)
    {
        if (strrpos($endpoint, 'v2') !== false) {
            // For v2 endpoints, add a .json to the end of each endpoint, to normalize against the v3 API standards
            $endpoint .= '.json';
        }

        $result = $this->makeBigCommerceAPIRequest($request, $endpoint);
        
        $rateLimitHeaders = [
            'X-Rate-Limit-Time-Reset-Ms' => $result->getHeader('X-Rate-Limit-Time-Reset-Ms')[0] ?? null,
            'X-Rate-Limit-Time-Window-Ms' => $result->getHeader('X-Rate-Limit-Time-Window-Ms')[0] ?? null,
            'X-Rate-Limit-Requests-Left' => $result->getHeader('X-Rate-Limit-Requests-Left')[0] ?? null,
            'X-Rate-Limit-Requests-Quota' => $result->getHeader('X-Rate-Limit-Requests-Quota')[0] ?? null,
        ];

        return response($result->getBody(), $result->getStatusCode())
            ->header('Content-Type', 'application/json')
            ->withHeaders($rateLimitHeaders);
    }

    /**
     * Save store credentials to database for multi-shop support
     */
    private function saveStoreCredentials($storeHash, $oauthData, Request $request)
    {
        try {
            BigCommerceStore::updateOrCreate(
                ['store_hash' => $storeHash],
                [
                    'access_token' => $oauthData['access_token'],
                    'user_id' => $oauthData['user']['id'] ?? null,
                    'user_email' => $oauthData['user']['email'] ?? null,
                    'scope' => explode(' ', $oauthData['scope'] ?? ''),
                    'installed_at' => now(),
                    'last_accessed_at' => now(),
                    'active' => true
                ]
            );
            
            \Log::info("Store credentials saved for store hash: " . $storeHash);
        } catch (\Exception $e) {
            \Log::error("Failed to save store credentials: " . $e->getMessage());
        }
    }
}
