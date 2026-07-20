<?php

declare(strict_types=1);

namespace App\Actions\Admin\PlatformSettings;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdatePlatformSettingsAction
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function __invoke(array $settings, int $lockVersion, User $actor): PlatformSetting
    {
        return DB::transaction(function () use ($settings, $lockVersion, $actor) {
            $platformSetting = PlatformSetting::current();

            if ($platformSetting->lock_version !== $lockVersion) {
                throw ValidationException::withMessages([
                    'lock_version' => 'The settings have been updated by another user. Please refresh and try again.',
                ]);
            }

            // Using compare-and-swap to ensure concurrency safety
            $updated = PlatformSetting::query()
                ->where('id', $platformSetting->id)
                ->where('lock_version', $lockVersion)
                ->update([
                    'settings' => $settings,
                    'lock_version' => $platformSetting->lock_version + 1,
                ]);

            if ($updated === 0) {
                throw ValidationException::withMessages([
                    'lock_version' => 'The settings have been updated by another user. Please refresh and try again.',
                ]);
            }

            $platformSetting->refresh();

            activity()
                ->performedOn($platformSetting)
                ->causedBy($actor)
                ->log('updated');

            return $platformSetting;
        });
    }
}
