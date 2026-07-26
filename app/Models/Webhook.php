<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'webhook';

    protected $primaryKey = 'webhook_id';

    protected $fillable = ['organizer_id', 'endpoint', 'secret', 'enabled'];

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    #[Override]
    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'secret' => 'encrypted'];
    }
}
