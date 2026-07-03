<?php

declare(strict_types=1);

namespace App\Livewire\Public\Events;

use App\Actions\Orders\ConfirmTicketOrderAction;
use App\DataTransferObjects\Orders\ReserveStockDto;
use App\DataTransferObjects\Orders\ReserveStockItemDto;
use App\Enums\PricingMode;
use App\Models\Event;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PromoCode;
use App\Models\TicketOrder;
use App\Services\PriceCalculator;
use App\Services\PromoCodeValidator;
use App\Services\StockManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public Event $event;

    // Selection properties
    /** @var array<int, int> */
    public array $quantities = []; // product_price_id => quantity
    /** @var array<int, string> */
    public array $passwords = []; // product_id => password entered
    /** @var array<int, bool> */
    public array $unlockedProducts = []; // product_id => true/false
    /** @var array<int, string> */
    public array $passwordErrors = [];

    // Promo code properties
    public string $promoCodeText = '';
    public ?PromoCode $appliedPromoCode = null;
    public string $promoCodeError = '';

    // Buyer properties
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';

    // Waitlist token
    public ?string $waitlistToken = null;

    // Attendee fields and custom answers staging
    public array $customAnswersStaging = [];
    public array $attendeeDetails = [];

    // Flow properties
    public int $step = 1; // 1: Ticket Selection, 2: Buyer & Attendee Info, 3: Reserve & Checkout Simulation
    public ?int $orderId = null;
    public ?string $reservedUntil = null;

    public function mount(Event $event): void
    {
        $this->event = $event;

        // Initialize quantities to 0
        foreach ($this->event->products as $product) {
            foreach ($product->prices as $price) {
                $this->quantities[$price->product_price_id] = 0;
            }
        }

        // Recuperar waitlist_token de la URL
        $this->waitlistToken = request()->query('waitlist_token');

        if ($this->waitlistToken !== null) {
            /** @var \App\Models\WaitlistEntry|null $waitlistEntry */
            $waitlistEntry = \App\Models\WaitlistEntry::query()
                ->where('token', $this->waitlistToken)
                ->where('event_id', $this->event->event_id)
                ->first();

            if ($waitlistEntry === null ||
                $waitlistEntry->status !== \App\Enums\WaitlistStatus::Notified ||
                ($waitlistEntry->expires_at !== null && $waitlistEntry->expires_at->isPast())) {
                $this->addError('reservation', __('The waitlist link is invalid or has expired.'));
                $this->waitlistToken = null;
            } else {
                // Prellenar datos del comprador
                $this->firstName = $waitlistEntry->first_name ?? '';
                $this->lastName = $waitlistEntry->last_name ?? '';
                $this->email = $waitlistEntry->email;

                // Preseleccionar y forzar cantidad = 1 para el product_price_id del waitlist
                $this->quantities[$waitlistEntry->product_price_id] = 1;
                // Forzar 0 en los demás tiers
                foreach ($this->quantities as $id => $qty) {
                    if ($id != $waitlistEntry->product_price_id) {
                        $this->quantities[$id] = 0;
                    }
                }
            }
        }
    }

    public function getAvailableCapacity(ProductPrice $price): int
    {
        return resolve(StockManager::class)->getAvailableCapacity($price, $this->waitlistToken);
    }

    public function applyPromoCode(PromoCodeValidator $validator): void
    {
        $this->resetErrorBag('promoCodeText');
        $this->promoCodeError = '';
        $this->appliedPromoCode = null;

        if (empty($this->promoCodeText)) {
            return;
        }

        /** @var PromoCode|null $promoCode */
        $promoCode = PromoCode::query()
            ->where('event_id', $this->event->event_id)
            ->where('code', strtoupper($this->promoCodeText))
            ->first();

        if ($promoCode === null || !$validator->isValid($promoCode, $this->event->event_id)) {
            $this->promoCodeError = __('Invalid or expired promo code.');
            return;
        }

        $this->appliedPromoCode = $promoCode;
    }

    public function unlockProduct(int $productId): void
    {
        $this->passwordErrors[$productId] = '';
        /** @var Product $product */
        $product = Product::query()->findOrFail($productId);

        $entered = $this->passwords[$productId] ?? '';
        if ($product->password !== null && Hash::check($entered, $product->password)) {
            $this->unlockedProducts[$productId] = true;
        } else {
            $this->passwordErrors[$productId] = __('Incorrect password.');
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            // Validate at least 1 ticket selected
            $totalQty = array_sum($this->quantities);
            if ($totalQty <= 0) {
                $this->addError('tickets', __('Please select at least one ticket.'));
                return;
            }

            // Inicializar las estructuras de asistentes y respuestas para cada ticket seleccionado
            $this->attendeeDetails = [];
            $this->customAnswersStaging = [];
            foreach ($this->quantities as $priceId => $qty) {
                $qty = (int) $qty;
                if ($qty > 0) {
                    for ($seq = 1; $seq <= $qty; $seq++) {
                        // Prellenar el primer ticket con Buyer info si hay token de waitlist
                        $prefill = ($this->waitlistToken !== null && $seq === 1);
                        $this->attendeeDetails[$priceId][$seq] = [
                            'first_name' => $prefill ? $this->firstName : '',
                            'last_name' => $prefill ? $this->lastName : '',
                            'email' => $prefill ? $this->email : '',
                        ];
                        foreach ($this->event->custom_questions ?? [] as $question) {
                            $this->customAnswersStaging[$priceId][$seq][$question['id']] = '';
                        }
                    }
                }
            }

            $this->step = 2;
        }
    }

    public function previousStep(): void
    {
        if ($this->step === 2) {
            $this->step = 1;
        }
    }

    public function reserveAndCheckout(StockManager $stockManager, ConfirmTicketOrderAction $confirmAction): void
    {
        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];

        // Reglas de validación para detalles de cada asistente y preguntas personalizadas
        foreach ($this->quantities as $priceId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                for ($seq = 1; $seq <= $qty; $seq++) {
                    $rules["attendeeDetails.{$priceId}.{$seq}.first_name"] = ['required', 'string', 'max:255'];
                    $rules["attendeeDetails.{$priceId}.{$seq}.last_name"] = ['required', 'string', 'max:255'];
                    $rules["attendeeDetails.{$priceId}.{$seq}.email"] = ['required', 'email', 'max:255'];

                    foreach ($this->event->custom_questions ?? [] as $question) {
                        $qRules = [];
                        if ($question['required'] ?? false) {
                            $qRules[] = 'required';
                        }
                        if (($question['type'] ?? '') === 'select' || ($question['type'] ?? '') === 'radio') {
                            $options = $question['options'] ?? [];
                            $qRules[] = \Illuminate\Validation\Rule::in($options);
                        }
                        $rules["customAnswersStaging.{$priceId}.{$seq}.{$question['id']}"] = $qRules;
                    }
                }
            }
        }

        $this->validate($rules);

        // Map quantities and staging answers to DTOs
        $items = [];
        foreach ($this->quantities as $priceId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                // Estructurar el JSON de staging por secuencia
                $stagingData = [];
                for ($seq = 1; $seq <= $qty; $seq++) {
                    // Si la pregunta es checkbox, agrupamos las claves marcadas
                    $rawAnswers = $this->customAnswersStaging[$priceId][$seq] ?? [];
                    $processedAnswers = [];
                    foreach ($this->event->custom_questions ?? [] as $question) {
                        $qId = $question['id'];
                        if (($question['type'] ?? '') === 'checkbox') {
                            $selected = [];
                            if (isset($rawAnswers[$qId]) && is_array($rawAnswers[$qId])) {
                                foreach ($rawAnswers[$qId] as $opt => $val) {
                                    if ($val) {
                                        $selected[] = $opt;
                                    }
                                }
                            }
                            $processedAnswers[$qId] = $selected;
                        } else {
                            $processedAnswers[$qId] = $rawAnswers[$qId] ?? '';
                        }
                    }

                    $stagingData[$seq] = [
                        'first_name' => $this->attendeeDetails[$priceId][$seq]['first_name'],
                        'last_name' => $this->attendeeDetails[$priceId][$seq]['last_name'],
                        'email' => $this->attendeeDetails[$priceId][$seq]['email'],
                        'answers' => $processedAnswers,
                    ];
                }

                $items[] = new ReserveStockItemDto(
                    productPriceId: (int) $priceId,
                    quantity: $qty,
                    customAnswersStaging: $stagingData
                );
            }
        }

        $dto = new ReserveStockDto(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            promoCodeId: $this->appliedPromoCode?->promo_code_id,
            items: $items,
            waitlistToken: $this->waitlistToken
        );

        try {
            /** @var TicketOrder $order */
            $order = $stockManager->reserve($this->event, $dto);
            $this->orderId = $order->ticket_order_id;
            $this->reservedUntil = $order->reserved_until?->toIso8601String();

            // Si es gratis ($0.00), confirmamos automáticamente de inmediato
            if ($order->total <= 0.00) {
                $confirmAction($order);
                $this->redirectConfirmation($order);
                return;
            }

            $this->step = 3;
        } catch (\Exception $e) {
            $this->addError('reservation', $e->getMessage());
        }
    }

    public function simulatePayment(ConfirmTicketOrderAction $confirmAction): void
    {
        // Solo permitido en local o testing
        if (!app()->environment('local', 'testing')) {
            abort(403, 'Offline payment is only allowed in local/testing environment.');
        }

        if ($this->orderId === null) {
            return;
        }

        /** @var TicketOrder $order */
        $order = TicketOrder::query()->findOrFail($this->orderId);

        $confirmAction($order);
        $this->redirectConfirmation($order);
    }

    public function simulateStripeWebhookPayment(
        \App\Actions\Payments\InitiatePaymentAction $initiateAction,
        \App\Actions\Payments\HandleStripeWebhookAction $webhookAction
    ): void {
        // Solo permitido en local o testing
        if (!app()->environment('local', 'testing')) {
            abort(403, 'Simulation is only allowed in local/testing environment.');
        }

        if ($this->orderId === null) {
            return;
        }

        /** @var TicketOrder $order */
        $order = TicketOrder::query()->findOrFail($this->orderId);

        $providerId = 'pi_mock_web_' . \Illuminate\Support\Str::random(12);

        try {
            $initiateAction($order);
        } catch (\Exception $e) {
            // Si falla por falta de credenciales de Stripe (AuthenticationException / ApiConnectionException),
            // creamos un Payment pendiente simulado directamente para continuar la prueba de webhooks.
            \App\Models\Payment::query()->updateOrCreate(
                ['ticket_order_id' => $order->ticket_order_id, 'status' => \App\Enums\PaymentStatus::Pending],
                [
                    'provider_id' => $providerId,
                    'payment_method' => \App\Enums\PaymentMethod::Stripe,
                    'amount' => $order->total,
                    'currency' => $order->currency ?? 'USD',
                ]
            );
        }

        // Buscar el pago recién creado para obtener su provider_id real o simulado
        $payment = \App\Models\Payment::query()
            ->where('ticket_order_id', $order->ticket_order_id)
            ->where('status', \App\Enums\PaymentStatus::Pending)
            ->first();

        if ($payment !== null && $payment->provider_id !== null) {
            $providerId = $payment->provider_id;
        }

        // 2. Simular el Webhook de Stripe firmándolo
        $payload = json_encode([
            'id' => 'evt_mock_web_' . \Illuminate\Support\Str::random(12),
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $providerId,
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = config('services.stripe.webhook.secret', 'whsec_mock');
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $sigHeader = "t={$timestamp},v1={$signature}";

        // 3. Procesar el webhook usando la acción HandleStripeWebhookAction directamente
        $webhookAction($payload, $sigHeader);

        $this->redirectConfirmation($order);
    }

    private function redirectConfirmation(TicketOrder $order): void
    {
        $url = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addMinutes(30),
            [
                'event' => $this->event->event_id,
                'ticketOrder' => $order->ticket_order_id,
            ]
        );

        $this->redirect($url);
    }

    public function with(PriceCalculator $calculator): array
    {
        // Get active public or unlocked tickets
        $products = Product::query()
            ->where('event_id', $this->event->event_id)
            ->where('status', \App\Enums\ProductStatus::Active)
            ->where(function ($query) {
                $query->where('visibility', \App\Enums\ProductVisibility::Public)
                    ->orWhere('visibility', \App\Enums\ProductVisibility::Password);
            })
            ->with('prices')
            ->orderBy('sort_order')
            ->get();

        // Calculate dynamic live totals
        $subtotal = 0.0;
        $discount = 0.0;
        $total = 0.0;

        foreach ($products as $product) {
            foreach ($product->prices as $price) {
                $qty = (int) ($this->quantities[$price->product_price_id] ?? 0);
                if ($qty > 0) {
                    $calc = $calculator->calculate((float) $price->price, $qty, $this->appliedPromoCode);
                    $subtotal += $calc['subtotal'];
                    $discount += $calc['discount'];
                    $total += $calc['total'];
                }
            }
        }

        return [
            'products' => $products,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ];
    }
};
?>

<div class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="mb-8 text-center sm:text-left">
        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $event->title }}</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            📅 {{ $event->starts_at?->format('F d, Y - H:i') ?? __('Date not set') }}
        </p>
    </div>

    {{-- Error de reservación --}}
    @error('reservation')
        <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950/30 dark:text-red-400 font-semibold">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-8 lg:grid-cols-12 items-start">
        {{-- Main Area --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- STEP 1: Ticket Selection --}}
            @if($step === 1)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Step 1: Select Tickets') }}</h3>
                    
                    @error('tickets') 
                        <div class="mb-4 text-sm text-red-600 font-semibold">{{ $message }}</div> 
                    @enderror

                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($products as $product)
                            @php
                                $isPasswordProtected = $product->visibility->value === 'password';
                                $isUnlocked = isset($unlockedProducts[$product->product_id]) && $unlockedProducts[$product->product_id] === true;
                            @endphp

                            <div class="py-6 first:pt-0 last:pb-0">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $product->title }}</h4>
                                        <p class="text-xs text-gray-400 mt-1">{{ $product->description }}</p>
                                    </div>

                                    @if($isPasswordProtected && !$isUnlocked)
                                        {{-- Password Lock Form --}}
                                        <div class="flex flex-col items-end gap-1.5">
                                            <div class="flex gap-2">
                                                <input type="password" wire:model="passwords.{{ $product->product_id }}" placeholder="{{ __('Access Password') }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                <button type="button" wire:click="unlockProduct({{ $product->product_id }})" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500">
                                                    {{ __('Unlock') }}
                                                </button>
                                            </div>
                                            @if(!empty($passwordErrors[$product->product_id]))
                                                <span class="text-xxs text-red-600 font-medium">{{ $passwordErrors[$product->product_id] }}</span>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Standard Ticket Tiers list --}}
                                        <div class="space-y-4 w-48 text-right">
                                            @foreach($product->prices as $price)
                                                @php
                                                    $availableQty = $this->getAvailableCapacity($price);
                                                    $isSoldOut = $price->capacity !== null && $price->quantity_sold >= $price->capacity;
                                                @endphp
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="text-xs text-right">
                                                        <span class="font-medium text-gray-700 dark:text-gray-300 block">{{ $price->name }}</span>
                                                        <span class="text-gray-500">
                                                            @if($product->pricing_mode->value === 'free')
                                                                {{ __('Free') }}
                                                            @elseif($product->pricing_mode->value === 'donation')
                                                                {{ __('Donation') }}
                                                            @else
                                                                ${{ number_format($price->price, 2) }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    
                                                    @if($isSoldOut || $availableQty <= 0)
                                                        <div class="flex flex-col items-end gap-1">
                                                            <span class="text-xs font-semibold text-red-600 uppercase">{{ __('Sold Out') }}</span>
                                                            <a href="{{ route('public.events.join-waitlist', ['event' => $event->event_id, 'price' => $price->product_price_id]) }}" class="text-xxs text-blue-600 hover:underline font-semibold">
                                                                {{ __('Join Waitlist') }}
                                                            </a>
                                                        </div>
                                                    @else
                                                        <select wire:model.live="quantities.{{ $price->product_price_id }}" @disabled($waitlistToken !== null) class="rounded-lg border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white disabled:opacity-50">
                                                            @if($waitlistToken !== null && isset($quantities[$price->product_price_id]) && $quantities[$price->product_price_id] == 1)
                                                                <option value="1">1</option>
                                                            @else
                                                                @for($i = 0; $i <= min($availableQty, $product->max_qty); $i++)
                                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                                @endfor
                                                            @endif
                                                        </select>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex justify-end">
                    <button type="button" wire:click="nextStep" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        {{ __('Next Step') }}
                    </button>
                </div>
            @endif

            {{-- STEP 2: Buyer & Attendee Info --}}
            @if($step === 2)
                <form wire:submit.prevent="reserveAndCheckout" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Step 2: Buyer Information') }}</h3>
                        <p class="text-xs text-gray-400 mt-1">{{ __('This is where we will send your order details and tickets.') }}</p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('First Name') }}</label>
                            <input type="text" wire:model="firstName" id="firstName" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" @disabled($waitlistToken !== null)>
                            @error('firstName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Last Name') }}</label>
                            <input type="text" wire:model="lastName" id="lastName" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" @disabled($waitlistToken !== null)>
                            @error('lastName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Email Address') }}</label>
                        <input type="email" wire:model="email" id="email" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="you@example.com" @disabled($waitlistToken !== null)>
                        @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Attendees Section --}}
                    <div class="border-t border-gray-100 pt-6 dark:border-gray-800 space-y-8">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Attendee Information') }}</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enter the name and email for each ticket holder.') }}</p>
                        </div>

                        @foreach($quantities as $priceId => $qty)
                            @php
                                $qty = (int) $qty;
                                $priceObj = \App\Models\ProductPrice::query()->find($priceId);
                            @endphp
                            @if($qty > 0 && $priceObj !== null)
                                @for($seq = 1; $seq <= $qty; $seq++)
                                    <div class="p-6 rounded-xl border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-950/40 space-y-6">
                                        <h4 class="font-bold text-gray-800 dark:text-gray-200">
                                            Ticket #{{ $seq }} - {{ $priceObj->product->title }} ({{ $priceObj->name }})
                                        </h4>

                                        <div class="grid gap-6 sm:grid-cols-2">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('First Name') }} *</label>
                                                <input type="text" wire:model="attendeeDetails.{{ $priceId }}.{{ $seq }}.first_name" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                @error("attendeeDetails.{$priceId}.{$seq}.first_name") <span class="text-xxs text-red-600">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('Last Name') }} *</label>
                                                <input type="text" wire:model="attendeeDetails.{{ $priceId }}.{{ $seq }}.last_name" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                @error("attendeeDetails.{$priceId}.{$seq}.last_name") <span class="text-xxs text-red-600">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('Email Address') }} *</label>
                                            <input type="email" wire:model="attendeeDetails.{{ $priceId }}.{{ $seq }}.email" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            @error("attendeeDetails.{$priceId}.{$seq}.email") <span class="text-xxs text-red-600">{{ $message }}</span> @enderror
                                        </div>

                                        {{-- Custom Questions --}}
                                        @if(!empty($event->custom_questions))
                                            <div class="border-t border-gray-200/60 pt-4 dark:border-gray-800 space-y-4">
                                                <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Custom Questions') }}</h5>

                                                @foreach(collect($event->custom_questions)->sortBy('position') as $question)
                                                    <div class="space-y-1.5">
                                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            {{ $question['label'] }}
                                                            @if($question['required'] ?? false) * @endif
                                                        </label>

                                                        @if(($question['type'] ?? 'text') === 'text')
                                                            <input type="text" wire:model="customAnswersStaging.{{ $priceId }}.{{ $seq }}.{{ $question['id'] }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                        @elseif($question['type'] === 'textarea')
                                                            <textarea wire:model="customAnswersStaging.{{ $priceId }}.{{ $seq }}.{{ $question['id'] }}" rows="3" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                                                        @elseif($question['type'] === 'select')
                                                            <select wire:model="customAnswersStaging.{{ $priceId }}.{{ $seq }}.{{ $question['id'] }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                                <option value="">{{ __('Select an option') }}</option>
                                                                @foreach($question['options'] ?? [] as $option)
                                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($question['type'] === 'radio')
                                                            <div class="space-y-2">
                                                                @foreach($question['options'] ?? [] as $option)
                                                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                                                        <input type="radio" wire:model="customAnswersStaging.{{ $priceId }}.{{ $seq }}.{{ $question['id'] }}" value="{{ $option }}" class="text-blue-600 focus:ring-blue-500">
                                                                        {{ $option }}
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        @elseif($question['type'] === 'checkbox')
                                                            <div class="space-y-2">
                                                                @foreach($question['options'] ?? [] as $option)
                                                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                                                        <input type="checkbox" wire:model="customAnswersStaging.{{ $priceId }}.{{ $seq }}.{{ $question['id'] }}.{{ $option }}" class="rounded text-blue-600 focus:ring-blue-500">
                                                                        {{ $option }}
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        @error("customAnswersStaging.{$priceId}.{$seq}.{$question['id']}")
                                                            <span class="text-xxs text-red-600 block mt-0.5">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endfor
                            @endif
                        @endforeach
                    </div>

                    <div class="flex justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button type="button" wire:click="previousStep" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Back') }}
                        </button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                            {{ __('Reserve Tickets & Continue') }}
                        </button>
                    </div>
                </form>
            @endif

            {{-- STEP 3: Reserve & Checkout Simulation --}}
            @if($step === 3)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 text-center space-y-6">
                    <span class="text-5xl">⏳</span>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Tickets Reserved!') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            {{ __('Your tickets are reserved for 10 minutes. Please complete payment to confirm your order.') }}
                        </p>
                    </div>

                    @if(app()->environment('local', 'testing'))
                        <div class="p-4 rounded-lg bg-yellow-50 border border-yellow-200 dark:bg-yellow-950/20 dark:border-yellow-900/30 max-w-md mx-auto space-y-4">
                            <h4 class="text-sm font-bold text-yellow-800 dark:text-yellow-400">{{ __('Offline & Payment Simulation') }}</h4>
                            <p class="text-xs text-yellow-700 dark:text-yellow-500 mt-1">
                                {{ __('Local/Testing Mode: You can simulate checkout flows using the buttons below.') }}
                            </p>
                            <div class="flex flex-col gap-2">
                                <button type="button" wire:click="simulatePayment" class="rounded-lg bg-yellow-600 px-4 py-2 text-xs font-bold text-white hover:bg-yellow-500">
                                    {{ __('Simulate Simple Payment') }}
                                </button>
                                <button type="button" wire:click="simulateStripeWebhookPayment" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-500">
                                    {{ __('Simulate Stripe Webhook Payment') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-lg bg-blue-50 border border-blue-200 dark:bg-blue-950/20 dark:border-blue-900/30 max-w-md mx-auto">
                            <p class="text-xs text-blue-700 dark:text-blue-500 font-semibold">
                                {{ __('Payments are currently offline. Real payments will be enabled in Sprint 2.3 with Stripe.') }}
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Order Summary Sidebar (always visible except in step 3) --}}
        @if($step < 3)
            <div class="lg:col-span-4 space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">{{ __('Order Summary') }}</h3>

                    <div class="space-y-4">
                        @php $hasSelection = false; @endphp
                        @foreach($products as $product)
                            @foreach($product->prices as $price)
                                @php $qty = $quantities[$price->product_price_id] ?? 0; @endphp
                                @if($qty > 0)
                                    @php $hasSelection = true; @endphp
                                    <div class="flex justify-between text-sm">
                                        <div class="text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold">{{ $qty }}x</span> {{ $product->title }} ({{ $price->name }})
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            @if($product->pricing_mode->value === 'free')
                                                $0.00
                                            @else
                                                ${{ number_format($price->price * $qty, 2) }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        @endforeach

                        @if(!$hasSelection)
                            <p class="text-xs text-gray-400 italic text-center py-4">{{ __('No tickets selected.') }}</p>
                        @endif

                        {{-- Promo Code --}}
                        @if($step === 1 && $hasSelection)
                            <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Promo Code') }}</label>
                                <div class="flex gap-2">
                                    <input type="text" wire:model="promoCodeText" placeholder="CODE" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white uppercase">
                                    <button type="button" wire:click="applyPromoCode" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-750">
                                        {{ __('Apply') }}
                                    </button>
                                </div>
                                @if($appliedPromoCode)
                                    <div class="text-xs text-green-600 font-semibold mt-1">
                                        ✓ {{ __('Code applied: :code', ['code' => $appliedPromoCode->code]) }}
                                    </div>
                                @endif
                                @if($promoCodeError)
                                    <div class="text-xs text-red-600 font-semibold mt-1">{{ $promoCodeError }}</div>
                                @endif
                            </div>
                        @endif

                        {{-- Price breakdown --}}
                        @if($hasSelection)
                            <div class="border-t border-gray-100 pt-4 dark:border-gray-800 space-y-2">
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>{{ __('Subtotal') }}</span>
                                    <span>${{ number_format($subtotal, 2) }}</span>
                                </div>
                                @if($discount > 0)
                                    <div class="flex justify-between text-xs text-green-600 font-semibold">
                                        <span>{{ __('Discount') }}</span>
                                        <span>-${{ number_format($discount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-sm font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-100 dark:border-gray-800">
                                    <span>{{ __('Total') }}</span>
                                    <span>${{ number_format($total, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
