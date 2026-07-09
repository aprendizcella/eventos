<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Organizers;

final readonly class BillingSettings
{
    private function __construct(
        public bool $invoiceEnabled,
        public ?string $taxId = null,
        public ?string $taxName = null,
        public ?int $taxRate = null,
        public ?int $platformFeePercentage = null,
        public ?int $platformFeeFixed = null,
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function fromArray(array $settings): self
    {
        return new self(
            invoiceEnabled: (bool) ($settings['invoice_enabled'] ?? false),
            taxId: isset($settings['tax_id']) ? (string) $settings['tax_id'] : null,
            taxName: isset($settings['tax_name']) ? (string) $settings['tax_name'] : null,
            taxRate: isset($settings['tax_rate']) ? (int) $settings['tax_rate'] : null,
            platformFeePercentage: isset($settings['platform_fee_percentage']) ? (int) $settings['platform_fee_percentage'] : null,
            platformFeeFixed: isset($settings['platform_fee_fixed']) ? (int) $settings['platform_fee_fixed'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'invoice_enabled' => $this->invoiceEnabled,
            'tax_id' => $this->taxId,
            'tax_name' => $this->taxName,
            'tax_rate' => $this->taxRate,
            'platform_fee_percentage' => $this->platformFeePercentage,
            'platform_fee_fixed' => $this->platformFeeFixed,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $existingSettings
     * @return array<string, mixed>
     */
    public function mergeInto(array $existingSettings): array
    {
        return array_merge($existingSettings, $this->toArray());
    }
}
