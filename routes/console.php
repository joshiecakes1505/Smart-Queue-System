<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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
