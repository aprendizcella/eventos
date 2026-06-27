<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Organizers;

final readonly class UpdateOrganizerDto
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $domain = null,
        public ?array $settings = null,
        public ?string $status = null,
    ) {}
}
