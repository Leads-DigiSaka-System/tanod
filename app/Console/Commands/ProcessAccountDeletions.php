<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessAccountDeletions extends Command
{
    protected $signature = 'accounts:process-deletions';

    protected $description = 'Permanently delete user accounts whose 7-day grace period has expired';

    public function handle(): int
    {
        $users = User::query()
            ->whereNotNull('deletion_scheduled_for')
            ->where('deletion_scheduled_for', '<=', now())
            ->get();

        if ($users->isEmpty()) {
            $this->info('No accounts to delete.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($users as $user) {
            // Clean up profile photo
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Revoke all tokens
            $user->tokens()->delete();

            // Clear FCM token
            $user->update([
                'fcm_token' => null,
                'is_active' => false,
            ]);

            // Soft delete
            $user->delete();

            $count++;
            $this->line("  Deleted: {$user->name} ({$user->email})");
        }

        $this->info("Processed {$count} account deletion(s).");

        return self::SUCCESS;
    }
}
