<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Services\CacheService;
use App\Services\LoggingService;

class OptimizeApplication extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:optimize 
                            {--clear-cache : Clear all caches}
                            {--clear-logs : Clear log files}
                            {--clear-temp : Clear temporary files}
                            {--optimize-autoloader : Optimize Composer autoloader}
                            {--optimize-config : Optimize configuration}
                            {--optimize-routes : Optimize routes}
                            {--optimize-views : Optimize views}
                            {--all : Run all optimizations}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize the application for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Optimizing CARDSWAP application...');
        $this->newLine();

        $clearCache = $this->option('clear-cache') || $this->option('all');
        $clearLogs = $this->option('clear-logs') || $this->option('all');
        $clearTemp = $this->option('clear-temp') || $this->option('all');
        $optimizeAutoloader = $this->option('optimize-autoloader') || $this->option('all');
        $optimizeConfig = $this->option('optimize-config') || $this->option('all');
        $optimizeRoutes = $this->option('optimize-routes') || $this->option('all');
        $optimizeViews = $this->option('optimize-views') || $this->option('all');

        if ($clearCache) {
            $this->clearCaches();
        }

        if ($clearLogs) {
            $this->clearLogs();
        }

        if ($clearTemp) {
            $this->clearTempFiles();
        }

        if ($optimizeAutoloader) {
            $this->optimizeAutoloader();
        }

        if ($optimizeConfig) {
            $this->optimizeConfig();
        }

        if ($optimizeRoutes) {
            $this->optimizeRoutes();
        }

        if ($optimizeViews) {
            $this->optimizeViews();
        }

        $this->newLine();
        $this->info('✅ Application optimization completed!');
    }

    /**
     * Clear all caches
     */
    private function clearCaches()
    {
        $this->info('🧹 Clearing caches...');
        
        try {
            // Clear Laravel caches
            Artisan::call('cache:clear');
            $this->line('  ✓ Application cache cleared');
            
            Artisan::call('config:clear');
            $this->line('  ✓ Configuration cache cleared');
            
            Artisan::call('route:clear');
            $this->line('  ✓ Route cache cleared');
            
            Artisan::call('view:clear');
            $this->line('  ✓ View cache cleared');
            
            // Clear custom cache
            CacheService::flush();
            $this->line('  ✓ Custom cache cleared');
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error clearing caches: ' . $e->getMessage());
        }
    }

    /**
     * Clear log files
     */
    private function clearLogs()
    {
        $this->info('📝 Clearing log files...');
        
        try {
            $logPath = storage_path('logs');
            
            if (File::exists($logPath)) {
                $files = File::files($logPath);
                $count = 0;
                
                foreach ($files as $file) {
                    if ($file->getExtension() === 'log') {
                        File::delete($file->getPathname());
                        $count++;
                    }
                }
                
                $this->line("  ✓ Cleared {$count} log files");
            } else {
                $this->line('  ✓ No log files to clear');
            }
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error clearing logs: ' . $e->getMessage());
        }
    }

    /**
     * Clear temporary files
     */
    private function clearTempFiles()
    {
        $this->info('🗑️  Clearing temporary files...');
        
        try {
            $tempPaths = [
                storage_path('app/temp'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                public_path('build'),
            ];
            
            $count = 0;
            
            foreach ($tempPaths as $path) {
                if (File::exists($path)) {
                    $files = File::allFiles($path);
                    foreach ($files as $file) {
                        if ($file->getExtension() !== 'gitkeep') {
                            File::delete($file->getPathname());
                            $count++;
                        }
                    }
                }
            }
            
            $this->line("  ✓ Cleared {$count} temporary files");
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error clearing temp files: ' . $e->getMessage());
        }
    }

    /**
     * Optimize Composer autoloader
     */
    private function optimizeAutoloader()
    {
        $this->info('📦 Optimizing Composer autoloader...');
        
        try {
            $result = \Illuminate\Support\Facades\Process::run(['composer', 'dump-autoload', '--optimize']);
            
            if ($result->successful()) {
                $this->line('  ✓ Composer autoloader optimized');
            } else {
                $this->error('  ❌ Error optimizing autoloader: ' . $result->errorOutput());
            }
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error optimizing autoloader: ' . $e->getMessage());
        }
    }

    /**
     * Optimize configuration
     */
    private function optimizeConfig()
    {
        $this->info('⚙️  Optimizing configuration...');
        
        try {
            Artisan::call('config:cache');
            $this->line('  ✓ Configuration cached');
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error optimizing config: ' . $e->getMessage());
        }
    }

    /**
     * Optimize routes
     */
    private function optimizeRoutes()
    {
        $this->info('🛣️  Optimizing routes...');
        
        try {
            Artisan::call('route:cache');
            $this->line('  ✓ Routes cached');
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error optimizing routes: ' . $e->getMessage());
        }
    }

    /**
     * Optimize views
     */
    private function optimizeViews()
    {
        $this->info('👁️  Optimizing views...');
        
        try {
            Artisan::call('view:cache');
            $this->line('  ✓ Views cached');
            
        } catch (\Exception $e) {
            $this->error('  ❌ Error optimizing views: ' . $e->getMessage());
        }
    }
}
