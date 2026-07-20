<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property array<string, mixed>|null $settings
 * @property int $lock_version
 */
final class PlatformSetting extends Model
{
    use LogsActivity;

    protected $table = 'platform_setting';

    protected $fillable = [
        'settings',
        'lock_version',
        'is_singleton',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['is_singleton' => true],
            ['settings' => [], 'lock_version' => 0],
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function setting(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function apply(Builder $query): Builder
    {
        return $query->where('is_singleton', true)->limit(1);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'lock_version' => 'integer',
            'is_singleton' => 'boolean',
        ];
    }
}
