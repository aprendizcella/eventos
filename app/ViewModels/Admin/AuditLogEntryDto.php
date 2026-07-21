<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

readonly class AuditLogEntryDto
{
    public function __construct(
        public int $id,
        public string $logName,
        public string $event,
        public string $description,
        public string $actorName,
        public string $resourceName,
        public string $timestamp,
    ) {}
}
