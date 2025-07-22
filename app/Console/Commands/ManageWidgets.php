<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BigCommerceService;

class ManageWidgets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:widgets 
                            {action : Action to perform (list, remove, install)}
                            {store : Store hash (e.g., rgp5uxku7h or store-rgp5uxku7h-1)}
                            {--url= : Your ngrok or app URL for installation}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage BigCommerce YMM widgets per store - list, remove, or install';

    protected $bigCommerceService;

    public function __construct(BigCommerceService $bigCommerceService)
    {
        parent::__construct();
        $this->bigCommerceService = $bigCommerceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $storeHash = $this->argument('store');
        
        if (!$storeHash) {
            $this->error('Store hash is required as an argument.');
            $this->info('Usage: php artisan bigcommerce:widgets {action} {store}');
            $this->info('Example: php artisan bigcommerce:widgets list rgp5uxku7h');
            return 1;
        }

        // Clean store hash (remove stores/ prefix if present)
        $storeHash = str_replace('stores/', '', $storeHash);
        $storeHash = str_replace('store-', '', $storeHash);
        $storeHash = str_replace('-1', '', $storeHash); // Remove trailing -1 if present

        $this->info("Working with store: {$storeHash}");
        $this->newLine();

        switch ($action) {
            case 'list':
                return $this->listWidgets($storeHash);
            case 'remove':
                return $this->removeWidgets($storeHash);
            case 'install':
                $url = $this->option('url');
                if (!$url) {
                    $this->error('URL is required for installation. Use --url option');
                    $this->info('Example: php artisan bigcommerce:widgets install rgp5uxku7h --url=https://abc123.ngrok-free.app');
                    return 1;
                }
                return $this->installWidget($storeHash, $url);
            default:
                $this->error('Invalid action. Use: list, remove, or install');
                $this->info('Examples:');
                $this->info('  php artisan bigcommerce:widgets list rgp5uxku7h');
                $this->info('  php artisan bigcommerce:widgets remove rgp5uxku7h');
                $this->info('  php artisan bigcommerce:widgets install rgp5uxku7h --url=https://abc123.ngrok-free.app');
                return 1;
        }
    }

    private function listWidgets($storeHash)
    {
        $this->info("Listing widgets for store: {$storeHash}");
        $this->newLine();

        try {
            // List scripts
            $this->info('🔍 Scripts:');
            $scripts = $this->bigCommerceService->getScripts($storeHash);
            
            if (isset($scripts['data']) && count($scripts['data']) > 0) {
                $this->table(
                    ['ID', 'Name', 'Description', 'Location'],
                    collect($scripts['data'])->map(function ($script) {
                        return [
                            $script['uuid'],
                            $script['name'] ?? 'N/A',
                            $script['description'] ?? 'N/A',
                            $script['location'] ?? 'N/A'
                        ];
                    })->toArray()
                );
            } else {
                $this->warn('No scripts found.');
            }

            $this->newLine();

            // List widget templates
            $this->info('🧩 Widget Templates:');
            $templates = $this->bigCommerceService->getWidgetTemplates($storeHash);
            
            if (isset($templates['data']) && count($templates['data']) > 0) {
                $this->table(
                    ['ID', 'Name', 'Kind'],
                    collect($templates['data'])->map(function ($template) {
                        return [
                            $template['uuid'],
                            $template['name'] ?? 'N/A',
                            $template['kind'] ?? 'N/A'
                        ];
                    })->toArray()
                );
            } else {
                $this->warn('No widget templates found.');
            }

        } catch (\Exception $e) {
            $this->error('Failed to list widgets: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function removeWidgets($storeHash)
    {
        $this->info("🗑️  Removing YMM widgets from store: {$storeHash}");
        $this->newLine();

        // Show what will be removed first
        $this->info("Scanning for YMM widgets to remove...");
        
        try {
            $toRemove = $this->scanForYmmWidgets($storeHash);
            
            if (empty($toRemove['scripts']) && empty($toRemove['templates'])) {
                $this->warn('No YMM widgets found in this store.');
                return 0;
            }

            // Show what will be removed
            $this->info("Found YMM widgets to remove:");
            if (!empty($toRemove['scripts'])) {
                $this->info("📜 Scripts:");
                foreach ($toRemove['scripts'] as $script) {
                    $this->line("  - {$script['name']} (ID: {$script['uuid']})");
                }
            }
            if (!empty($toRemove['templates'])) {
                $this->info("🧩 Widget Templates:");
                foreach ($toRemove['templates'] as $template) {
                    $this->line("  - {$template['name']} (ID: {$template['uuid']})");
                }
            }

            $this->newLine();

            // Confirmation (unless --force is used)
            if (!$this->option('force')) {
                if (!$this->confirm("Are you sure you want to remove ALL YMM widgets from store '{$storeHash}'?")) {
                    $this->info('Cancelled.');
                    return 0;
                }
            }

            // Perform removal
            $results = $this->bigCommerceService->removeAllYmmWidgets($storeHash);

            $this->newLine();
            $this->info('🗑️  Removal Results:');
            $this->newLine();

            if (count($results['scripts']) > 0) {
                $this->info('Scripts removed:');
                foreach ($results['scripts'] as $script) {
                    $status = $script['deleted'] ? '✅' : '❌';
                    $this->line("{$status} {$script['name']} (ID: {$script['id']})");
                }
            } else {
                $this->warn('No YMM scripts found to remove.');
            }

            $this->newLine();

            if (count($results['templates']) > 0) {
                $this->info('Widget templates removed:');
                foreach ($results['templates'] as $template) {
                    $status = $template['deleted'] ? '✅' : '❌';
                    $this->line("{$status} {$template['name']} (ID: {$template['id']})");
                }
            } else {
                $this->warn('No YMM widget templates found to remove.');
            }

        } catch (\Exception $e) {
            $this->error('Failed to remove widgets: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info("✅ Widget removal completed for store: {$storeHash}!");
        return 0;
    }

    private function scanForYmmWidgets($storeHash)
    {
        $toRemove = ['scripts' => [], 'templates' => []];

        // Scan scripts
        try {
            $scripts = $this->bigCommerceService->getScripts($storeHash);
            if (isset($scripts['data'])) {
                foreach ($scripts['data'] as $script) {
                    if (stripos($script['name'], 'YMM') !== false || 
                        stripos($script['description'], 'YMM') !== false ||
                        stripos($script['name'], 'Vehicle Filter') !== false) {
                        $toRemove['scripts'][] = $script;
                    }
                }
            }
        } catch (\Exception $e) {
            // Continue even if scripts scan fails
        }

        // Scan widget templates
        try {
            $templates = $this->bigCommerceService->getWidgetTemplates($storeHash);
            if (isset($templates['data'])) {
                foreach ($templates['data'] as $template) {
                    if (stripos($template['name'], 'YMM') !== false || 
                        stripos($template['name'], 'Vehicle Filter') !== false) {
                        $toRemove['templates'][] = $template;
                    }
                }
            }
        } catch (\Exception $e) {
            // Continue even if templates scan fails
        }

        return $toRemove;
    }

    private function installWidget($storeHash, $url)
    {
        $this->info("🚀 Installing YMM widget for store: {$storeHash}");
        $this->info("Using URL: {$url}");
        $this->newLine();

        // Update the config temporarily for this installation
        config(['app.url' => rtrim($url, '/')]);

        try {
            $result = $this->bigCommerceService->installWidget($storeHash);

            if ($result && isset($result['data'])) {
                $this->info('✅ Widget installed successfully!');
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['Store', $storeHash],
                        ['Widget ID', $result['data']['uuid'] ?? 'N/A'],
                        ['Name', $result['data']['name'] ?? 'N/A'],
                        ['Location', $result['data']['location'] ?? 'N/A'],
                        ['API URL', $url],
                        ['Status', 'Active']
                    ]
                );

                $this->newLine();
                $this->info('📋 Next Steps:');
                $this->line('1. The widget script has been installed in your store');
                $this->line('2. Add the widget container to your theme where you want it to appear:');
                $this->line('   <div id="ymm-filter-container"></div>');
                $this->line('3. Test the widget on your storefront');
                $this->line('4. Ensure your ngrok tunnel is running and accessible');

            } else {
                $this->error('Widget installation failed - no data returned');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("Failed to install widget for store '{$storeHash}': " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
