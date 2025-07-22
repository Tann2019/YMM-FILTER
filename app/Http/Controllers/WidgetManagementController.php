<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BigCommerceService;
use Inertia\Inertia;

class WidgetManagementController extends Controller
{
    protected $bigCommerceService;

    public function __construct(BigCommerceService $bigCommerceService)
    {
        $this->bigCommerceService = $bigCommerceService;
    }

    /**
     * Display the widget management page
     */
    public function index(Request $request, $storeHash = null)
    {
        // If accessed from BigCommerce app route, use the route parameter
        // Otherwise, use the config value for standalone access
        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');
        $widgets = $this->getWidgetsData($storeHash);

        return Inertia::render('WidgetManagement', [
            'widgets' => $widgets,
            'storeHash' => $storeHash,
            'currentUrl' => config('app.url'),
            'isAppContext' => !is_null(request()->route('storeHash'))
        ]);
    }

    /**
     * Get all widgets for the store
     */
    public function getWidgets(Request $request, $storeHash = null)
    {
        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');
        $widgets = $this->getWidgetsData($storeHash);

        return response()->json([
            'success' => true,
            'data' => $widgets
        ]);
    }

    /**
     * Install a new widget
     */
    public function install(Request $request, $storeHash = null)
    {
        $request->validate([
            'url' => 'required|url',
            'widget_type' => 'required|in:template,script'
        ]);

        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');
        $url = $request->input('url');
        $widgetType = $request->input('widget_type');

        try {
            // Update config temporarily
            config(['app.url' => rtrim($url, '/')]);

            if ($widgetType === 'template') {
                $result = $this->bigCommerceService->installWidget($storeHash);
            } else {
                $result = $this->bigCommerceService->installWidgetAsScript($storeHash);
            }

            if ($result && isset($result['data'])) {
                return response()->json([
                    'success' => true,
                    'message' => ucfirst($widgetType) . ' installed successfully!',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($widgetType) . ' installation failed - no data returned'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to install ' . $widgetType . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a specific widget
     */
    public function remove(Request $request, $storeHash = null)
    {
        $request->validate([
            'widget_id' => 'required|string',
            'widget_type' => 'required|in:script,template'
        ]);

        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');
        $widgetId = $request->input('widget_id');
        $widgetType = $request->input('widget_type');

        try {
            if ($widgetType === 'script') {
                $result = $this->bigCommerceService->deleteScript($storeHash, $widgetId);
            } else {
                $result = $this->bigCommerceService->deleteWidgetTemplate($storeHash, $widgetId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Widget removed successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove widget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove all YMM widgets
     */
    public function removeAll(Request $request, $storeHash = null)
    {
        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');

        try {
            $results = $this->bigCommerceService->removeAllYmmWidgets($storeHash);

            $removedCount = count($results['scripts']) + count($results['templates']);

            return response()->json([
                'success' => true,
                'message' => "Removed {$removedCount} YMM widgets successfully!",
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove widgets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get widget preview HTML
     */
    public function getPreview(Request $request, $storeHash = null)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');
        $url = $request->input('url');

        // Generate preview HTML
        $previewHtml = $this->generatePreviewHtml($storeHash, $url);

        return response()->json([
            'success' => true,
            'html' => $previewHtml
        ]);
    }

    /**
     * Get widgets data for the store
     */
    private function getWidgetsData($storeHash)
    {
        $widgets = [
            'scripts' => [],
            'templates' => [],
            'ymmWidgets' => []
        ];

        try {
            // Get scripts
            $scripts = $this->bigCommerceService->getScripts($storeHash);
            if (isset($scripts['data'])) {
                $widgets['scripts'] = collect($scripts['data'])->map(function ($script) {
                    return [
                        'id' => $script['uuid'],
                        'name' => $script['name'] ?? 'Unnamed Script',
                        'description' => $script['description'] ?? 'No description',
                        'location' => $script['location'] ?? 'Unknown',
                        'created_at' => $script['date_created'] ?? null,
                        'is_ymm' => $this->isYmmWidget($script['name'], $script['description']),
                        'type' => 'script'
                    ];
                })->toArray();
            }

            // Get widget templates
            $templates = $this->bigCommerceService->getWidgetTemplates($storeHash);
            if (isset($templates['data'])) {
                $widgets['templates'] = collect($templates['data'])->map(function ($template) {
                    return [
                        'id' => $template['uuid'],
                        'name' => $template['name'] ?? 'Unnamed Template',
                        'kind' => $template['kind'] ?? 'Unknown',
                        'created_at' => $template['date_created'] ?? null,
                        'is_ymm' => $this->isYmmWidget($template['name'], ''),
                        'type' => 'template'
                    ];
                })->toArray();
            }

            // Filter YMM widgets
            $allWidgets = array_merge($widgets['scripts'], $widgets['templates']);
            $widgets['ymmWidgets'] = array_filter($allWidgets, function ($widget) {
                return $widget['is_ymm'];
            });

        } catch (\Exception $e) {
            // Handle error silently, return empty arrays
        }

        return $widgets;
    }

    /**
     * Check if a widget is YMM-related
     */
    private function isYmmWidget($name, $description = '')
    {
        $searchTerms = ['YMM', 'Vehicle Filter', 'Year Make Model'];
        
        foreach ($searchTerms as $term) {
            if (stripos($name, $term) !== false || stripos($description, $term) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Create a new YMM widget using the consolidated widget creation approach
     */
    public function createYmmWidget(Request $request, $storeHash = null)
    {
        $request->validate([
            'title' => 'string|max:100',
            'button_text' => 'string|max:50',
            'placeholder_year' => 'string|max:50',
            'placeholder_make' => 'string|max:50',
            'placeholder_model' => 'string|max:50',
            'primary_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'button_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme' => 'string|in:default,modern,compact'
        ]);

        $storeHash = $storeHash ?: config('bigcommerce.local.store_hash');

        if (!$storeHash) {
            return response()->json([
                'success' => false,
                'message' => 'Store hash not found. Please check your configuration.'
            ], 400);
        }

        try {
            // Get the API URL for hardcoding
            $apiUrl = config('app.url');
            
            // Create widget template data with our consolidated schema
            $widgetTemplate = [
                'name' => $request->input('title', 'YMM Vehicle Filter'),
                'template' => '', // Will be populated by the WidgetController method
                'schema' => $this->getConsolidatedWidgetSchema()
            ];

            // Use the same approach as the main dashboard widget creation
            $widgetController = new \App\Http\Controllers\WidgetController($this->bigCommerceService);
            $reflection = new \ReflectionClass($widgetController);
            $method = $reflection->getMethod('getWidgetTemplate');
            $method->setAccessible(true);
            $widgetTemplate['template'] = $method->invoke($widgetController, $apiUrl, $storeHash);

            // Create the widget using our consolidated service method
            $result = $this->bigCommerceService->createWidgetTemplate($storeHash, $widgetTemplate);
            
            if (isset($result['data'])) {
                $widgetType = isset($result['data']['kind']) && $result['data']['kind'] === 'script_widget' ? 'script' : 'template';
                
                return response()->json([
                    'success' => true,
                    'message' => $widgetType === 'script' 
                        ? 'YMM Widget script created successfully! The widget will appear on your storefront.'
                        : 'YMM Widget template created successfully! You can now drag and drop it in Page Builder.',
                    'widget' => [
                        'id' => $result['data']['uuid'],
                        'name' => $result['data']['name'],
                        'type' => $widgetType,
                        'api_url' => $apiUrl,
                        'store_hash' => $storeHash
                    ],
                    'instructions' => $widgetType === 'script' ? [
                        '1. The script has been automatically installed on your store',
                        '2. The widget will appear on your storefront pages',
                        '3. No additional setup required'
                    ] : [
                        '1. Go to your BigCommerce admin panel',
                        '2. Navigate to Storefront → Page Builder',
                        '3. Edit the page where you want to add the widget',
                        '4. Look for "' . $result['data']['name'] . '" in the Custom Widgets section',
                        '5. Drag it to your desired location and configure settings',
                        '6. Save the page'
                    ]
                ]);
            } else {
                throw new \Exception('Widget creation returned no data');
            }
        } catch (\Exception $e) {
            Log::error('YMM Widget creation failed', [
                'error' => $e->getMessage(),
                'store_hash' => $storeHash,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create YMM widget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the consolidated widget schema that matches our WidgetController approach
     */
    private function getConsolidatedWidgetSchema()
    {
        return [
            [
                'type' => 'tab',
                'label' => 'Content',
                'sections' => [
                    [
                        'label' => 'Widget Settings',
                        'settings' => [
                            [
                                'type' => 'input',
                                'label' => 'Widget Title',
                                'id' => 'title',
                                'default' => 'Vehicle Compatibility Filter',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Search Button Text',
                                'id' => 'button_text',
                                'default' => 'Search Compatible Products',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'checkbox',
                                'label' => 'Show Vehicle Images',
                                'id' => 'show_images',
                                'default' => true,
                                'typeName' => 'boolean'
                            ]
                        ]
                    ],
                    [
                        'label' => 'Placeholders',
                        'settings' => [
                            [
                                'type' => 'input',
                                'label' => 'Year Placeholder',
                                'id' => 'placeholder_year',
                                'default' => 'Select Year',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Make Placeholder',
                                'id' => 'placeholder_make',
                                'default' => 'Select Make',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Model Placeholder',
                                'id' => 'placeholder_model',
                                'default' => 'Select Model',
                                'typeName' => 'string'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'type' => 'tab',
                'label' => 'Style',
                'sections' => [
                    [
                        'label' => 'Appearance',
                        'settings' => [
                            [
                                'type' => 'select',
                                'label' => 'Theme',
                                'id' => 'theme',
                                'typeName' => 'string',
                                'options' => [
                                    [
                                        'value' => 'default',
                                        'label' => 'Default'
                                    ],
                                    [
                                        'value' => 'modern',
                                        'label' => 'Modern'
                                    ],
                                    [
                                        'value' => 'compact',
                                        'label' => 'Compact'
                                    ]
                                ],
                                'default' => 'default'
                            ],
                            [
                                'type' => 'color',
                                'label' => 'Primary Color',
                                'id' => 'primary_color',
                                'default' => '#3B82F6',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'color',
                                'label' => 'Button Color',
                                'id' => 'button_color',
                                'default' => '#1D4ED8',
                                'typeName' => 'string'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Generate preview HTML for the widget
     */
    private function generatePreviewHtml($storeHash, $url)
    {
        return "
        <div style='border: 2px solid #007cba; border-radius: 8px; padding: 20px; background: #f8f9fa;'>
            <h4 style='color: #007cba; margin-top: 0;'>🚗 YMM Widget Preview</h4>
            <p style='color: #666; margin-bottom: 15px;'>This is how your widget will appear on the storefront:</p>
            
            <div style='border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: white;'>
                <h5 style='margin: 0 0 10px 0;'>Find Compatible Products</h5>
                <div style='margin-bottom: 10px;'>
                    <select style='width: 100%; padding: 8px; margin-bottom: 8px;'>
                        <option>Select Year</option>
                        <option>2023</option>
                        <option>2022</option>
                        <option>2021</option>
                    </select>
                </div>
                <div style='margin-bottom: 10px;'>
                    <select style='width: 100%; padding: 8px; margin-bottom: 8px;' disabled>
                        <option>Select Make</option>
                    </select>
                </div>
                <div style='margin-bottom: 10px;'>
                    <select style='width: 100%; padding: 8px; margin-bottom: 8px;' disabled>
                        <option>Select Model</option>
                    </select>
                </div>
                <button style='width: 100%; padding: 10px; background: #007cba; color: white; border: none; border-radius: 4px;' disabled>
                    Search Compatible Products
                </button>
            </div>
            
            <div style='margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 4px solid #007cba;'>
                <strong>API URL:</strong> {$url}<br>
                <strong>Store Hash:</strong> {$storeHash}<br>
                <strong>Status:</strong> <span style='color: green;'>Ready to Install</span>
            </div>
        </div>";
    }
}
