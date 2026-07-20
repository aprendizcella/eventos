<?php

use Livewire\Volt\Component;
use App\Models\PlatformSetting;
use App\Actions\Admin\PlatformSettings\UpdatePlatformSettingsAction;

new class extends Component {
    public array $settings = [];
    public int $lockVersion = 0;

    public function mount()
    {
        $platformSetting = PlatformSetting::current();
        $this->settings = $platformSetting->settings ?? [];
        $this->lockVersion = $platformSetting->lock_version;
    }

    public function save(UpdatePlatformSettingsAction $updateAction)
    {
        $platformSetting = $updateAction($this->settings, $this->lockVersion, auth()->user());
        $this->lockVersion = $platformSetting->lock_version;
        session()->flash('message', 'Settings updated successfully.');
    }
}; ?>

<div>
    <h2 class="text-xl font-bold mb-4">Platform Settings</h2>
    @if(session()->has('message'))
        <div class="bg-green-100 text-green-800 p-2 mb-4">{{ session('message') }}</div>
    @endif
    <form wire:submit="save">
        <div class="mb-4">
            <label>App Name</label>
            <input type="text" wire:model="settings.app_name" class="border p-2 w-full">
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2">Save</button>
    </form>
</div>
