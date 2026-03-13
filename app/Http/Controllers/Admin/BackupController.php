<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function downloadLatest(): StreamedResponse|RedirectResponse
    {
        $configuredDisks = config('backup.backup.destination.disks', ['local']);
        $diskName = (string) (Arr::first(is_array($configuredDisks) ? $configuredDisks : []) ?? 'local');

        if (! config()->has("filesystems.disks.{$diskName}")) {
            return back()->withErrors([
                'backup' => "Backup destination disk [{$diskName}] is not configured.",
            ]);
        }

        $disk = Storage::disk($diskName);
        $backupRoot = trim((string) config('backup.backup.name', config('app.name', 'laravel-backup')), '/');

        try {
            $allFiles = collect($disk->allFiles())
                ->filter(fn (string $path) => str_ends_with(strtolower($path), '.zip'));

            if ($backupRoot !== '') {
                $allFiles = $allFiles->filter(fn (string $path) => str_starts_with($path, $backupRoot.'/'));
            }

            $latestBackupPath = $allFiles
                ->sortByDesc(fn (string $path) => $disk->lastModified($path))
                ->first();
        } catch (Throwable $exception) {
            return back()->withErrors([
                'backup' => 'Backup destination is currently unavailable. Please try again later.',
            ]);
        }

        if (! $latestBackupPath) {
            return back()->withErrors([
                'backup' => 'No backup file found yet. Run a backup first.',
            ]);
        }

        $stream = $disk->readStream($latestBackupPath);

        if (! is_resource($stream)) {
            return back()->withErrors([
                'backup' => 'Unable to read backup file from storage.',
            ]);
        }

        $downloadName = basename($latestBackupPath);

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $downloadName, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
