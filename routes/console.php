<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('deploy:check-assets', function () {
    $buildDirectory = public_path('build');
    $manifestPath = $buildDirectory.'/manifest.json';

    if (! File::exists($manifestPath)) {
        $this->error('Missing Vite manifest: '.$manifestPath);
        $this->line('Run `npm run build` and deploy the `public/build` directory.');

        return 1;
    }

    $manifestContent = File::get($manifestPath);
    $manifest = json_decode($manifestContent, true);

    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($manifest)) {
        $this->error('Invalid JSON in Vite manifest.');
        $this->line('File: '.$manifestPath);
        $this->line('Error: '.json_last_error_msg());

        return 1;
    }

    $missingFiles = [];
    $missingImports = [];

    foreach ($manifest as $chunkName => $chunk) {
        if (! is_array($chunk)) {
            continue;
        }

        if (isset($chunk['file'])) {
            $assetPath = $buildDirectory.'/'.ltrim($chunk['file'], '/');
            if (! File::exists($assetPath)) {
                $missingFiles[] = [
                    'chunk' => $chunkName,
                    'type' => 'file',
                    'path' => $chunk['file'],
                ];
            }
        }

        foreach ($chunk['css'] ?? [] as $cssFile) {
            $assetPath = $buildDirectory.'/'.ltrim($cssFile, '/');
            if (! File::exists($assetPath)) {
                $missingFiles[] = [
                    'chunk' => $chunkName,
                    'type' => 'css',
                    'path' => $cssFile,
                ];
            }
        }

        foreach ($chunk['assets'] ?? [] as $staticAsset) {
            $assetPath = $buildDirectory.'/'.ltrim($staticAsset, '/');
            if (! File::exists($assetPath)) {
                $missingFiles[] = [
                    'chunk' => $chunkName,
                    'type' => 'asset',
                    'path' => $staticAsset,
                ];
            }
        }

        foreach ($chunk['imports'] ?? [] as $importChunk) {
            if (! array_key_exists($importChunk, $manifest)) {
                $missingImports[] = [
                    'chunk' => $chunkName,
                    'missing_import' => $importChunk,
                ];
            }
        }

        foreach ($chunk['dynamicImports'] ?? [] as $dynamicImportChunk) {
            if (! array_key_exists($dynamicImportChunk, $manifest)) {
                $missingImports[] = [
                    'chunk' => $chunkName,
                    'missing_import' => $dynamicImportChunk,
                ];
            }
        }
    }

    $this->info('Manifest found: '.$manifestPath);
    $this->line('Chunks: '.count($manifest));
    $this->line('APP_URL: '.config('app.url'));
    $this->newLine();

    if (! empty($missingFiles)) {
        $this->error('Missing build files detected: '.count($missingFiles));
        $this->table(['Chunk', 'Type', 'Missing Path'], $missingFiles);
    } else {
        $this->info('All manifest files exist under public/build.');
    }

    if (! empty($missingImports)) {
        $this->error('Broken manifest imports detected: '.count($missingImports));
        $this->table(['Chunk', 'Missing Import Entry'], $missingImports);
    } else {
        $this->info('All manifest imports resolve correctly.');
    }

    $entry = $manifest['resources/js/app.js']['file'] ?? null;
    if ($entry) {
        $this->newLine();
        $this->line('Expected app entry URL: '.rtrim(config('app.url'), '/').'/build/'.ltrim($entry, '/'));
    }

    if (! empty($missingFiles) || ! empty($missingImports)) {
        $this->newLine();
        $this->line('Fix by rebuilding and deploying latest public/build, then clear caches.');

        return 1;
    }

    $this->newLine();
    $this->info('Asset check passed.');

    return 0;
})->purpose('Validate Vite manifest and deployed build assets');

// NOTE: nothing currently drives Laravel's scheduler in production. The
// Windows Task Scheduler entry that ran `artisan schedule:run` every minute
// (SmartQueue-LaravelScheduler) was removed because it popped a visible
// cmd.exe window every minute on the host. None of the Schedule::command
// entries below fire unless that (or `schedule:work`) is reinstated some
// other way — e.g. a hidden/silent scheduled task, or `schedule:work` kept
// running as a background service.
//
// queues:auto-reinstate relies on this file: the sweep instead runs
// opportunistically from DisplayController@data on every display-board poll
// (see QueueService::autoReinstateSweep). accounts:sweep-lifecycle and the
// backup:* jobs below still assume a scheduler process and will NOT run
// until one exists again.
Schedule::command('accounts:sweep-lifecycle')->dailyAt('02:00')->withoutOverlapping();

Schedule::command('backup:run --only-db')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run --only-files')->dailyAt('01:10')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('01:40')->withoutOverlapping();
