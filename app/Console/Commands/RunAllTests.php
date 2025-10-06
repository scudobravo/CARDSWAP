<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class RunAllTests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:all 
                            {--coverage : Generate coverage report}
                            {--parallel : Run tests in parallel}
                            {--stop-on-failure : Stop on first failure}';

    /**
     * The console command description.
     */
    protected $description = 'Run all tests (PHPUnit + Vitest) with optional coverage and parallel execution';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Running all tests for CARDSWAP...');
        $this->newLine();

        $coverage = $this->option('coverage');
        $parallel = $this->option('parallel');
        $stopOnFailure = $this->option('stop-on-failure');

        $phpUnitExitCode = $this->runPhpUnitTests($coverage, $stopOnFailure);
        $vitestExitCode = $this->runVitestTests($parallel, $stopOnFailure);

        $this->newLine();
        
        if ($phpUnitExitCode === 0 && $vitestExitCode === 0) {
            $this->info('✅ All tests passed successfully!');
            return 0;
        } else {
            $this->error('❌ Some tests failed. Please check the output above.');
            return 1;
        }
    }

    /**
     * Run PHPUnit tests
     */
    private function runPhpUnitTests(bool $coverage, bool $stopOnFailure): int
    {
        $this->info('🔧 Running PHPUnit tests...');
        
        $command = ['php', 'artisan', 'test'];
        
        if ($coverage) {
            $command[] = '--coverage';
        }
        
        if ($stopOnFailure) {
            $command[] = '--stop-on-failure';
        }

        $result = Process::run($command);
        
        $this->line($result->output());
        
        if ($result->failed()) {
            $this->error('PHPUnit tests failed!');
            $this->line($result->errorOutput());
        } else {
            $this->info('✅ PHPUnit tests passed!');
        }
        
        return $result->exitCode();
    }

    /**
     * Run Vitest tests
     */
    private function runVitestTests(bool $parallel, bool $stopOnFailure): int
    {
        $this->info('🎯 Running Vitest tests...');
        
        $command = ['npm', 'run', 'test:unit:run'];
        
        if ($parallel) {
            $command = ['npm', 'run', 'test:unit'];
        }

        $result = Process::run($command);
        
        $this->line($result->output());
        
        if ($result->failed()) {
            $this->error('Vitest tests failed!');
            $this->line($result->errorOutput());
        } else {
            $this->info('✅ Vitest tests passed!');
        }
        
        return $result->exitCode();
    }
}
