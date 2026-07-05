<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Actions\Notifications\SendBulkMessageAction;
use App\DataTransferObjects\Notifications\SendBulkMessageDto;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\ProductPrice;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;

    public string $subject = '';
    public string $body = '';
    public string $messageBodyPlaceholder = 'Hello {{first_name}}, ...';
    public ?int $productPriceId = null;
    public string $attendeeStatus = '';
    public string $checkInStatus = '';

    public array $placeholderExamples = [
        'first_name' => '{{first_name}}',
        'last_name' => '{{last_name}}',
        'event_title' => '{{event_title}}',
        'ticket_code' => '{{ticket_code}}',
    ];

    public int $recipientPreviewCount = 0;

    public function mount(): void
    {
        $this->updateRecipientPreview();
    }

    public function updated($property): void
    {
        if (in_array($property, ['productPriceId', 'attendeeStatus', 'checkInStatus'], true)) {
            $this->updateRecipientPreview();
        }
    }

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'productPriceId' => ['nullable', 'integer', 'exists:product_price,product_price_id'],
            'attendeeStatus' => ['nullable', 'string', 'in:active,cancelled'],
            'checkInStatus' => ['nullable', 'string', 'in:checked_in,not_checked_in'],
        ];
    }

    public function updateRecipientPreview(): void
    {
        $filters = [];

        if ($this->productPriceId) {
            $filters['product_price_id'] = $this->productPriceId;
        }

        if ($this->attendeeStatus) {
            $filters['attendee_status'] = $this->attendeeStatus;
        }

        if ($this->checkInStatus) {
            $filters['check_in_status'] = $this->checkInStatus;
        }

        $this->recipientPreviewCount = \App\Models\Attendee::query()
            ->forEventSegment($this->event->event_id, $filters)
            ->count();
    }

    public function sendMessage(SendBulkMessageAction $action): void
    {
        $this->authorize('sendMessages', $this->event);
        $this->validate();

        $dto = new SendBulkMessageDto(
            eventId: $this->event->event_id,
            subject: $this->subject,
            body: $this->body,
            productPriceId: $this->productPriceId,
            attendeeStatus: $this->attendeeStatus ?: null,
            checkInStatus: $this->checkInStatus ?: null,
        );

        $action($dto, (int) auth()->id());

        $this->subject = '';
        $this->body = '';
        $this->productPriceId = null;
        $this->attendeeStatus = '';
        $this->checkInStatus = '';

        $this->updateRecipientPreview();

        session()->flash('success_bulk_message', __('Bulk email campaign queued successfully.'));
    }

    public function with(): array
    {
        $prices = ProductPrice::query()
            ->join('product', 'product_price.product_id', '=', 'product.product_id')
            ->where('product.event_id', $this->event->event_id)
            ->orderBy('product.title')
            ->orderBy('product_price.name')
            ->select('product_price.*')
            ->with('product')
            ->get();

        $history = NotificationLog::query()
            ->where('event_id', $this->event->event_id)
            ->with('sentBy')
            ->latest()
            ->get();

        $ticketTypeOptions = $prices->mapWithKeys(static function (ProductPrice $price): array {
            return [
                $price->product_price_id => $price->product->title . ' - ' . $price->name,
            ];
        })->all();

        return [
            'ticketTypeOptions' => $ticketTypeOptions,
            'attendeeStatusOptions' => [
                'active' => __('Active'),
                'cancelled' => __('Cancelled'),
            ],
            'checkInStatusOptions' => [
                'checked_in' => __('Checked In'),
                'not_checked_in' => __('Not Checked In'),
            ],
            'history' => $history,
        ];
    }
}; ?>

<div class="space-y-6">
    @if (session()->has('success_bulk_message'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400" role="alert">
            <span class="font-medium">{{ session('success_bulk_message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Compose Message') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Send an ad-hoc HTML simple campaign with segmentation and queued delivery.') }}
                </p>
            </div>

            <div class="px-6 py-6">
                <form wire:submit="sendMessage" class="space-y-6">
                    <x-form.input
                        name="subject"
                        :label="__('Subject')"
                        :value="$subject"
                        :placeholder="__('Announcement: Event details inside')"
                        wire:model="subject"
                    />

                    <x-form.textarea
                        name="body"
                        :label="__('Message Body')"
                        :value="$body"
                        :rows="8"
                        :placeholder="$messageBodyPlaceholder"
                        wire:model="body"
                    />

                    <p class="-mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Supported placeholders:') }}
                        <code class="font-mono text-indigo-600 dark:text-indigo-400">{{ $placeholderExamples['first_name'] }}</code>,
                        <code class="font-mono text-indigo-600 dark:text-indigo-400">{{ $placeholderExamples['last_name'] }}</code>,
                        <code class="font-mono text-indigo-600 dark:text-indigo-400">{{ $placeholderExamples['event_title'] }}</code>,
                        <code class="font-mono text-indigo-600 dark:text-indigo-400">{{ $placeholderExamples['ticket_code'] }}</code>.
                    </p>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Recipient Segmentation') }}</h4>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-form.select
                                name="productPriceId"
                                :label="__('Ticket Type')"
                                :options="$ticketTypeOptions"
                                :selected="$productPriceId"
                                :placeholder="__('All Tickets')"
                                wire:model.live="productPriceId"
                            />

                            <x-form.select
                                name="attendeeStatus"
                                :label="__('Attendee Status')"
                                :options="$attendeeStatusOptions"
                                :selected="$attendeeStatus"
                                :placeholder="__('All Ticket Statuses')"
                                wire:model.live="attendeeStatus"
                            />

                            <x-form.select
                                name="checkInStatus"
                                :label="__('Check-In Status')"
                                :options="$checkInStatusOptions"
                                :selected="$checkInStatus"
                                :placeholder="__('All Check-In States')"
                                wire:model.live="checkInStatus"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 border-t border-gray-100 pt-6 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Estimated Recipients:') }} <span class="font-semibold text-gray-900 dark:text-white">{{ $recipientPreviewCount }}</span>
                        </div>

                        <x-ui.button type="submit" class="!w-auto">
                            {{ __('Send Campaign') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Campaign History') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Recent campaigns and delivery status.') }}
                </p>
            </div>

            <div class="px-6 py-6">
                @if($history->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No campaigns sent yet.') }}</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($history as $log)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/30">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $log->subject }}</h4>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $log->body }}</p>
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full px-2 py-1 text-xs font-semibold uppercase @if($log->status->value === 'completed') bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 @elseif($log->status->value === 'failed') bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400 @else bg-yellow-50 text-yellow-800 dark:bg-yellow-950/30 dark:text-yellow-400 @endif">
                                        {{ $log->status->value }}
                                    </span>
                                </div>

                                <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $log->created_at?->diffForHumans() }}</span>
                                    <span class="rounded-full bg-white px-2 py-1 font-medium text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-800">
                                        {{ $log->recipient_count }} {{ __('recipients') }}
                                    </span>
                                </div>

                                <div class="mt-2 text-xs text-gray-400">
                                    {{ __('By:') }} {{ $log->sentBy?->name ?? 'System' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
