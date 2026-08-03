<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SweepAccountLifecycle extends Command
{
    protected $signature = 'accounts:sweep-lifecycle';

    protected $description = 'Automatically disable, archive, and delete user accounts based on inactivity';

    public function handle(): int
    {
        $disableAfterDays = (int) config('accounts.inactivity.disable_after_days', 15);
        $archiveAfterDays = (int) config('accounts.inactivity.archive_after_days', 25);
        $deleteAfterDays = (int) config('accounts.inactivity.delete_after_days', 30);

        $disabled = 0;
        $archived = 0;
        $deleted = 0;
        $skipped = 0;

        User::query()->chunkById(50, function ($users) use (
            $disableAfterDays,
            $archiveAfterDays,
            $deleteAfterDays,
            &$disabled,
            &$archived,
            &$deleted,
            &$skipped
        ) {
            foreach ($users as $user) {
                $inactiveSince = $user->last_login_at ?? $user->created_at;
                $daysInactive = $inactiveSince->diffInDays(now());

                if ($daysInactive < $disableAfterDays) {
                    continue;
                }

                // Never let the automatic sweep lock the system out of its own admin panel.
                if ($user->isSoleActiveAdmin()) {
                    $skipped++;
                    continue;
                }

                if ($daysInactive >= $deleteAfterDays) {
                    if (!$user->disabled_at) {
                        $user->disable();
                    }
                    if (!$user->archived_at) {
                        $user->archive();
                    }
                    $user->delete();
                    $deleted++;
                    continue;
                }

                if ($daysInactive >= $archiveAfterDays) {
                    if (!$user->archived_at) {
                        if (!$user->disabled_at) {
                            $user->disable();
                        }
                        $user->archive();
                        $archived++;
                    }
                    continue;
                }

                if (!$user->disabled_at) {
                    $user->disable();
                    $disabled++;
                }
            }
        });

        $this->info("Account lifecycle sweep complete: {$disabled} disabled, {$archived} archived, {$deleted} deleted, {$skipped} skipped (last active admin protection).");

        return self::SUCCESS;
    }
}
