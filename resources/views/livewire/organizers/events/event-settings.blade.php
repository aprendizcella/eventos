<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Http\Requests\EventSettingsRequest;
use App\Models\NotificationTemplate;
use Livewire\Volt\Component;
use App\Models\Event;

new class extends Component {
    public Event $event;

    // Configuración del evento
    public bool $auto_notify_waitlist = false;
    public bool $auto_reminders = false;
    public string $sender_email = '';
    public string $sender_name = '';
    public bool $invoice_enabled = false;
    public string $invoice_notes = '';

    // CRUD de Plantillas
    public ?int $editingTemplateId = null;
    public string $template_name = '';
    public string $template_subject = '';
    public string $template_body = '';

    public function mount(): void
    {
        $this->authorize('manageSettings', $this->event);

        $settings = $this->event->settings ?? [];
        $this->auto_notify_waitlist = (bool) ($settings['auto_notify_waitlist'] ?? false);
        $this->auto_reminders = (bool) ($settings['auto_reminders'] ?? false);
        $this->sender_email = (string) ($settings['sender_email'] ?? auth()->user()?->email ?? '');
        $this->sender_name = (string) ($settings['sender_name'] ?? auth()->user()?->name ?? '');
        $this->invoice_enabled = (bool) ($settings['invoice_enabled'] ?? false);
        $this->invoice_notes = (string) ($settings['invoice_notes'] ?? '');
    }

    protected function rules(): array
    {
        return EventSettingsRequest::rules() + [
            'template_name' => ['nullable', 'string', 'max:255'],
            'template_subject' => ['nullable', 'string', 'max:255'],
            'template_body' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function saveSettings(): void
    {
        $this->authorize('manageSettings', $this->event);

        $rules = [
            ...EventSettingsRequest::rules(),
            'invoice_enabled' => ['boolean'],
            'invoice_notes' => ['nullable', 'string', 'max:1000'],
        ];

        $validated = $this->validate($rules);

        $existing = $this->event->settings ?? [];

        $this->event->update([
            'settings' => [
                ...$existing,
                ...$validated,
            ],
        ]);

        session()->flash('success_settings', __('Event settings updated successfully.'));
    }

    public function saveTemplate(): void
    {
        $this->authorize('manageSettings', $this->event);

        $this->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'template_subject' => ['required', 'string', 'max:255'],
            'template_body' => ['required', 'string', 'max:5000'],
        ]);

        if ($this->editingTemplateId) {
            $template = $this->event->notificationTemplates()
                ->whereKey($this->editingTemplateId)
                ->firstOrFail();

            $template->update([
                'name' => $this->template_name,
                'subject' => $this->template_subject,
                'body' => $this->template_body,
            ]);

            session()->flash('success_template', __('Template updated successfully.'));
        } else {
            $this->event->notificationTemplates()->create([
                'name' => $this->template_name,
                'subject' => $this->template_subject,
                'body' => $this->template_body,
            ]);

            session()->flash('success_template', __('Template created successfully.'));
        }

        $this->resetTemplateForm();
    }

    public function editTemplate(int $id): void
    {
        $this->authorize('manageSettings', $this->event);

        /** @var NotificationTemplate $template */
        $template = $this->event->notificationTemplates()->whereKey($id)->firstOrFail();

        $this->editingTemplateId = $template->notification_template_id;
        $this->template_name = $template->name;
        $this->template_subject = $template->subject;
        $this->template_body = $template->body;
    }

    public function deleteTemplate(int $id): void
    {
        $this->authorize('manageSettings', $this->event);

        $template = $this->event->notificationTemplates()->whereKey($id)->firstOrFail();

        $template->delete();

        session()->flash('success_template', __('Template deleted successfully.'));
    }

    public function resetTemplateForm(): void
    {
        $this->editingTemplateId = null;
        $this->template_name = '';
        $this->template_subject = '';
        $this->template_body = '';
    }

    public function with(): array
    {
        return [
            'templates' => $this->event->notificationTemplates()->latest()->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Event Settings Column -->
        <div class="space-y-6 self-start rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
            <h3 class="border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:text-white dark:border-gray-700">
                ⚙️ {{ __('Advanced Settings') }}
            </h3>

            @if (session()->has('success_settings'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400">
                    {{ session('success_settings') }}
                </div>
            @endif

            <form wire:submit="saveSettings" class="space-y-6">
                <x-form.checkbox
                    name="auto_notify_waitlist"
                    :label="__('Auto-notify Waitlist')"
                    :help="__('Automatically notify waitlist members when spots open.')"
                    wire:model="auto_notify_waitlist"
                />

                <x-form.checkbox
                    name="auto_reminders"
                    :label="__('Auto-reminders')"
                    :help="__('Send automated email reminders before the event starts.')"
                    wire:model="auto_reminders"
                />

                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <x-form.input
                        name="sender_email"
                        type="email"
                        :label="__('Sender Email')"
                        :value="$sender_email"
                        wire:model="sender_email"
                    />

                    <x-form.input
                        name="sender_name"
                        :label="__('Sender Name')"
                        :value="$sender_name"
                        wire:model="sender_name"
                    />
                </div>

                {{-- Billing Section --}}
                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                        💳 {{ __('Billing / Facturación') }}
                    </h4>

                    <x-form.checkbox
                        name="invoice_enabled"
                        :label="__('Enable invoices for this event')"
                        :help="__('When enabled, invoices will be generated automatically for completed payments.')"
                        wire:model="invoice_enabled"
                    />

                    <x-form.input
                        name="invoice_notes"
                        :label="__('Invoice Notes')"
                        :help="__('Optional notes or terms that will appear on generated invoices.')"
                        :value="$invoice_notes"
                        wire:model="invoice_notes"
                    />
                </div>

                <x-ui.button type="submit" class="!w-auto">
                    {{ __('Save Settings') }}
                </x-ui.button>
            </form>
        </div>

        <!-- Notification Templates Column -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Composer / Form -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="border-b border-gray-200 pb-4 text-lg font-semibold text-gray-900 dark:text-white dark:border-gray-700">
                    {{ $editingTemplateId ? __('Edit Template') : __('Create Notification Template') }}
                </h3>

                @if (session()->has('success_template'))
                    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400">
                        {{ session('success_template') }}
                    </div>
                @endif

                <form wire:submit="saveTemplate" class="mt-4 space-y-4">
                    <x-form.input
                        name="template_name"
                        :label="__('Template Name')"
                        :value="$template_name"
                        :placeholder="__('e.g. Day Before Reminder')"
                        wire:model="template_name"
                    />

                    <x-form.input
                        name="template_subject"
                        :label="__('Subject')"
                        :value="$template_subject"
                        wire:model="template_subject"
                    />

                    <x-form.textarea
                        name="template_body"
                        :label="__('Body')"
                        :value="$template_body"
                        :rows="5"
                        wire:model="template_body"
                    />

                    <div class="flex justify-end gap-2">
                        @if($editingTemplateId)
                            <x-ui.button type="button" wire:click="resetTemplateForm" class="!w-auto !bg-gray-100 !text-gray-700 hover:!bg-gray-200 dark:!bg-gray-800 dark:!text-gray-200">
                                {{ __('Cancel') }}
                            </x-ui.button>
                        @endif
                        <x-ui.button type="submit" class="!w-auto">
                            {{ $editingTemplateId ? __('Update') : __('Save Template') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📂 {{ __('Templates List') }}</h3>
                </div>

                <div class="px-6 py-6">
                    @if($templates->isEmpty())
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No templates configured.') }}</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">{{ __('Name') }}</th>
                                        <th scope="col" class="px-6 py-3">{{ __('Subject') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($templates as $tpl)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $tpl->name }}</td>
                                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $tpl->subject }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="inline-flex gap-3">
                                                    <button type="button" wire:click="editTemplate({{ $tpl->notification_template_id }})" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                                        {{ __('Edit') }}
                                                    </button>
                                                    <button type="button" wire:click="deleteTemplate({{ $tpl->notification_template_id }})" onclick="return confirm('Delete this template?')" class="text-xs font-semibold text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>