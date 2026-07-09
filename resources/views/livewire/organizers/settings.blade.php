<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Actions\Organizers\UpdateOrganizerAction;
use App\DataTransferObjects\Organizers\UpdateOrganizerDto;
use Livewire\Volt\Component;

new class extends Component {
    public Organizer $organizer;

    public string $name = '';
    public string $slug = '';
    public ?string $domain = '';

    // Address fields
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $zip = '';
    public string $country = '';

    // Social fields
    public string $facebook = '';
    public string $twitter = '';
    public string $instagram = '';
    public string $linkedin = '';

    // Defaults fields
    public string $currency = 'USD';
    public string $timezone = 'UTC';

    // Billing fields
    public string $tax_name = '';
    public ?string $tax_rate = null;
    public string $tax_id = '';
    public ?string $platform_fee_percentage = null;
    public ?string $platform_fee_fixed = null;

    public function mount(): void
    {
        $this->name = $this->organizer->name;
        $this->slug = $this->organizer->slug;
        $this->domain = $this->organizer->domain;

        $settings = $this->organizer->settings ?? [];

        // Address
        $addr = $settings['address'] ?? [];
        $this->address = $addr['address'] ?? '';
        $this->city = $addr['city'] ?? '';
        $this->state = $addr['state'] ?? '';
        $this->zip = $addr['zip'] ?? '';
        $this->country = $addr['country'] ?? '';

        // Social
        $social = $settings['social'] ?? [];
        $this->facebook = $social['facebook'] ?? '';
        $this->twitter = $social['twitter'] ?? '';
        $this->instagram = $social['instagram'] ?? '';
        $this->linkedin = $social['linkedin'] ?? '';

        // Defaults
        $defaults = $settings['defaults'] ?? [];
        $this->currency = $defaults['currency'] ?? 'USD';
        $this->timezone = $defaults['timezone'] ?? 'UTC';

        // Billing
        $billing = $settings['billing'] ?? [];
        $this->tax_name = (string) ($billing['tax_name'] ?? '');
        $this->tax_rate = isset($billing['tax_rate']) ? (string) round($billing['tax_rate'] / 100, 2) : null;
        $this->tax_id = (string) ($billing['tax_id'] ?? '');
        $this->platform_fee_percentage = isset($billing['platform_fee_percentage']) ? (string) round($billing['platform_fee_percentage'] / 100, 2) : null;
        $this->platform_fee_fixed = isset($billing['platform_fee_fixed']) ? (string) $billing['platform_fee_fixed'] : null;
    }

    public function saveSettings(UpdateOrganizerAction $updateAction): void
    {
        $this->authorize('update', $this->organizer);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizers,slug,' . $this->organizer->id],
            'domain' => ['nullable', 'string', 'max:255', 'unique:organizers,domain,' . $this->organizer->id],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'currency' => ['required', 'string', 'max:3'],
            'timezone' => ['required', 'string', 'max:100'],
            'tax_name' => ['nullable', 'string', 'max:255'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'platform_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'platform_fee_fixed' => ['nullable', 'integer', 'min:0'],
        ]);

        $existingSettings = $this->organizer->settings ?? [];

        $billing = [];

        if ($this->tax_name !== '') {
            $billing['tax_name'] = $this->tax_name;
        }

        if ($this->tax_rate !== null && $this->tax_rate !== '') {
            $billing['tax_rate'] = (int) round(((float) $this->tax_rate) * 100);
        }

        if ($this->tax_id !== '') {
            $billing['tax_id'] = $this->tax_id;
        }

        if ($this->platform_fee_percentage !== null && $this->platform_fee_percentage !== '') {
            $billing['platform_fee_percentage'] = (int) round(((float) $this->platform_fee_percentage) * 100);
        }

        if ($this->platform_fee_fixed !== null && $this->platform_fee_fixed !== '') {
            $billing['platform_fee_fixed'] = (int) $this->platform_fee_fixed;
        }

        $existingSettings['address'] = [
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
        ];

        $existingSettings['social'] = [
            'facebook' => $this->facebook,
            'twitter' => $this->twitter,
            'instagram' => $this->instagram,
            'linkedin' => $this->linkedin,
        ];

        $existingSettings['defaults'] = [
            'currency' => $this->currency,
            'timezone' => $this->timezone,
        ];

        $existingSettings['billing'] = $billing;

        $settings = $existingSettings;

        $dto = new UpdateOrganizerDto(
            name: $this->name,
            slug: $this->slug,
            domain: empty($this->domain) ? '' : $this->domain,
            settings: $settings
        );

        $user = auth()->user();
        if (!$user instanceof User) {
            abort(403);
        }

        $updateAction($this->organizer, $dto, $user);

        session()->flash('success', __('Organizer settings updated successfully.'));
    }
};
?>

<div class="space-y-6" x-data="{ activeTab: 'basic' }">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            {{ __('Organizer Settings') }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Manage basic information, contact details, social links, and platform defaults.') }}
        </p>
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
                @click="activeTab = 'basic'"
                :class="activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                🏢 {{ __('Basic Info') }}
            </button>
            <button
                @click="activeTab = 'address'"
                :class="activeTab === 'address' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                📍 {{ __('Address') }}
            </button>
            <button
                @click="activeTab = 'social'"
                :class="activeTab === 'social' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                🌐 {{ __('Social Links') }}
            </button>
            <button
                @click="activeTab = 'defaults'"
                :class="activeTab === 'defaults' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                ⚙️ {{ __('Defaults') }}
            </button>
            <button
                @click="activeTab = 'billing'"
                :class="activeTab === 'billing' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                💰 {{ __('Billing & Taxes') }}
            </button>
            <button
                @click="activeTab = 'comisiones'"
                :class="activeTab === 'comisiones' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                📊 {{ __('Comisiones') }}
            </button>
            <button
                @click="activeTab = 'danger'"
                :class="activeTab === 'danger' ? 'border-red-500 text-red-600 dark:border-red-400 dark:text-red-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
            >
                ⚠️ {{ __('Danger Zone') }}
            </button>
        </nav>
    </div>

    {{-- Form --}}
    <form wire:submit="saveSettings" class="space-y-6">
        @if (session()->has('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tab 1: Basic Info --}}
        <div x-show="activeTab === 'basic'" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                    <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Slug') }}</label>
                    <input type="text" id="slug" wire:model="slug" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('slug') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Custom Domain') }}</label>
                <input type="text" id="domain" wire:model="domain" placeholder="events.yourdomain.com" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @error('domain') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Tab 2: Address --}}
        <div x-show="activeTab === 'address'" class="space-y-4" x-cloak>
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Street Address') }}</label>
                <input type="text" id="address" wire:model="address" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @error('address') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('City') }}</label>
                    <input type="text" id="city" wire:model="city" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('city') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('State / Region') }}</label>
                    <input type="text" id="state" wire:model="state" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('state') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('ZIP / Postal Code') }}</label>
                    <input type="text" id="zip" wire:model="zip" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('zip') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Country') }}</label>
                <input type="text" id="country" wire:model="country" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @error('country') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Tab 3: Social Links --}}
        <div x-show="activeTab === 'social'" class="space-y-4" x-cloak>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="facebook" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Facebook URL</label>
                    <input type="url" id="facebook" wire:model="facebook" placeholder="https://facebook.com/..." class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('facebook') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="twitter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Twitter / X URL</label>
                    <input type="url" id="twitter" wire:model="twitter" placeholder="https://twitter.com/..." class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('twitter') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="instagram" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Instagram URL</label>
                    <input type="url" id="instagram" wire:model="instagram" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('instagram') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="linkedin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">LinkedIn URL</label>
                    <input type="url" id="linkedin" wire:model="linkedin" placeholder="https://linkedin.com/in/..." class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('linkedin') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Tab 4: Defaults --}}
        <div x-show="activeTab === 'defaults'" class="space-y-4" x-cloak>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default Currency') }}</label>
                    <select id="currency" wire:model="currency" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                        <option value="COP">COP ($)</option>
                    </select>
                    @error('currency') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default Timezone') }}</label>
                    <select id="timezone" wire:model="timezone" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="UTC">UTC</option>
                        <option value="America/Bogota">America/Bogota</option>
                        <option value="Europe/Madrid">Europe/Madrid</option>
                        <option value="America/New_York">America/New_York</option>
                    </select>
                    @error('timezone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Tab 5: Billing & Taxes --}}
        <div x-show="activeTab === 'billing'" class="space-y-4" x-cloak>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                    💰 {{ __('Tax Configuration') }}
                </h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Configure your tax and platform fee settings. These values will be used in invoices and reports.') }}
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tax_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tax Name') }}</label>
                        <input type="text" id="tax_name" wire:model="tax_name" placeholder="e.g. VAT" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('The display name for the tax (e.g. VAT, IVA, Sales Tax).') }}</p>
                        @error('tax_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tax Rate (%)') }}</label>
                        <input type="number" id="tax_rate" wire:model="tax_rate" placeholder="e.g. 21" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Percentage applied to ticket prices. Use decimal format (e.g. 21 for 21%).') }}</p>
                        @error('tax_rate') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tax ID / VAT Number') }}</label>
                    <input type="text" id="tax_id" wire:model="tax_id" placeholder="e.g. EU123456789" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Your business tax identification number.') }}</p>
                    @error('tax_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                    📊 {{ __('Platform Fees') }}
                </h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Configure platform/service fees applied to ticket sales. These will be tracked in reports.') }}
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="platform_fee_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Fee Percentage (%)') }}</label>
                        <input type="number" id="platform_fee_percentage" wire:model="platform_fee_percentage" placeholder="e.g. 5" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Percentage-based fee on each transaction.') }}</p>
                        @error('platform_fee_percentage') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="platform_fee_fixed" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Fixed Fee (cents)') }}</label>
                        <input type="number" id="platform_fee_fixed" wire:model="platform_fee_fixed" placeholder="e.g. 99" min="0" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Fixed fee in cents added to each transaction (e.g. 99 = $0.99).') }}</p>
                        @error('platform_fee_fixed') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 6: Comisiones (Commission Simulation) --}}
        <div x-show="activeTab === 'comisiones'" class="space-y-4" x-cloak
             x-data="{
                exampleAmount: 10000,
                get feePercent() { return parseFloat(document.getElementById('platform_fee_percentage')?.value || '5'); },
                get feeFixed() { return parseInt(document.getElementById('platform_fee_fixed')?.value || '0'); },
                get percentageAmount() { return Math.round(this.exampleAmount * this.feePercent / 100); },
                get totalCommission() { return Math.min(this.exampleAmount, this.percentageAmount + this.feeFixed); },
                get netAmount() { return Math.max(0, this.exampleAmount - this.totalCommission); }
             }"
        >
            {{-- Read-only Fee Summary --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                    📊 {{ __('Current Commission Policy') }}
                </h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('These values are configured in the Billing & Taxes tab. Changes there are reflected here.') }}
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/30">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Fee Percentage') }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $platform_fee_percentage ?? '0' }}<span class="text-base font-normal text-gray-500">%</span>
                        </p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/30">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Fixed Fee') }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $platform_fee_fixed ?? '0' }}<span class="text-base font-normal text-gray-500"> {{ __('cents') }}</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Commission Simulator --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                    🧮 {{ __('Commission Simulator') }}
                </h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Enter an example ticket amount to see how the commission and net payout would be calculated.') }}
                </p>
                <div class="mb-4">
                    <label for="sim-example-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Example Amount (cents)') }}
                    </label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input type="number" id="sim-example-amount" x-model="exampleAmount" min="0" step="1"
                               class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                               placeholder="e.g. 10000">
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Amount in cents (e.g. 10000 = $100.00).') }}
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-100 bg-green-50 p-4 dark:border-green-900/20 dark:bg-green-950/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-600 dark:text-green-400">{{ __('Gross Amount') }}</p>
                        <p class="mt-1 text-xl font-bold text-green-800 dark:text-green-300" x-text="(exampleAmount / 100).toFixed(2)"></p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-amber-50 p-4 dark:border-amber-900/20 dark:bg-amber-950/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-amber-600 dark:text-amber-400">{{ __('Commission') }}</p>
                        <p class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-300" x-text="'− ' + (totalCommission / 100).toFixed(2)"></p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">
                            <span x-text="'(' + feePercent + '% + ' + feeFixed + '¢'"></span>)
                        </p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-blue-50 p-4 dark:border-blue-900/20 dark:bg-blue-950/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">{{ __('Net Payout') }}</p>
                        <p class="mt-1 text-xl font-bold text-blue-800 dark:text-blue-300" x-text="(netAmount / 100).toFixed(2)"></p>
                    </div>
                </div>
            </div>

            {{-- Stripe Connect CTA --}}
            <div class="rounded-lg border border-dashed border-blue-300 bg-blue-50 p-6 dark:border-blue-700 dark:bg-blue-950/20">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="size-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300">
                            {{ __('Stripe Connect — Coming Soon') }}
                        </h4>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                            {{ __('Actual payment settlement will be handled through Stripe Connect in a future update. The commission simulation above shows how fees will be calculated once Connect is active.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 7: Danger Zone --}}
        <div id="danger-zone" x-show="activeTab === 'danger'" class="space-y-4" x-cloak>
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-950/20">
                <h4 class="text-sm font-semibold text-red-800 dark:text-red-300">{{ __('Delete Organization') }}</h4>
                <p class="mt-1 text-xs text-red-700 dark:text-red-400">
                    {{ __('Once you delete this organization, all of its events and data will be permanently removed. This action cannot be undone.') }}
                </p>
                <div class="mt-4">
                    <button type="button" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 focus:outline-none cursor-pointer">
                        {{ __('Delete permanently') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Submit Button (hide on danger zone and comisiones) --}}
        <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-800" x-show="activeTab !== 'danger' && activeTab !== 'comisiones'">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none cursor-pointer">
                {{ __('Save Changes') }}
            </button>
        </div>
    </form>
</div>
