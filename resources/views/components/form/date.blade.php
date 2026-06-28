@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'help' => null,
    'success' => false,
    'min' => null,
    'max' => null,
])

@php
    $id = $id ?? $name;
    $value = old($name, $value);
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $errors->has($name);
    $baseClasses = 'block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 dark:bg-gray-800 dark:text-gray-100';
    $stateClasses = $hasError
        ? 'border-red-400 focus:border-red-500 focus:ring-red-200 dark:border-red-500 dark:focus:ring-red-800'
        : ($success
            ? 'border-green-400 focus:border-green-500 focus:ring-green-200 dark:border-green-500 dark:focus:ring-green-800'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200 dark:border-gray-600 dark:focus:ring-blue-800');
    $disabledClasses = $disabled ? 'cursor-not-allowed bg-gray-100 opacity-60 dark:bg-gray-900' : '';
@endphp

<div class="mb-4">
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif

    <div
        class="relative"
        x-data="{
            open: false,
            value: @js($value),
            min: @js($min),
            max: @js($max),
            today: new Date(),
            viewing: null,
            position: 'bottom-left',
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            weekDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            init() {
                const selected = this.parseDate(this.value);
                this.viewing = selected ?? new Date(this.today.getFullYear(), this.today.getMonth(), 1);
                
                window.addEventListener('resize', () => {
                    if (this.open) {
                        this.updatePosition();
                    }
                });
            },
            updatePosition() {
                if (!this.open) return;

                const input = this.$refs.input;
                const rect = input.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                const calendarWidth = 320;
                const calendarHeight = 400;

                const spaceRight = viewportWidth - rect.right;
                const spaceLeft = rect.left;
                const spaceBottom = viewportHeight - rect.bottom;
                const spaceTop = rect.top;

                const alignRight = spaceRight < calendarWidth && spaceLeft > spaceRight;
                const alignTop = spaceBottom < calendarHeight && spaceTop > spaceBottom;

                this.position = `${alignTop ? 'top' : 'bottom'}-${alignRight ? 'right' : 'left'}`;
            },
            parseDate(date) {
                if (! date) {
                    return null;
                }

                const parts = date.split('-').map(Number);

                if (parts.length !== 3 || parts.some(Number.isNaN)) {
                    return null;
                }

                return new Date(parts[0], parts[1] - 1, parts[2]);
            },
            formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            },
            get monthLabel() {
                return `${this.monthNames[this.viewing.getMonth()]} ${this.viewing.getFullYear()}`;
            },
            get days() {
                const year = this.viewing.getFullYear();
                const month = this.viewing.getMonth();
                const firstDay = new Date(year, month, 1);
                const start = new Date(year, month, 1 - firstDay.getDay());

                return Array.from({ length: 42 }, (_, index) => {
                    const date = new Date(start);
                    date.setDate(start.getDate() + index);
                    const value = this.formatDate(date);

                    return {
                        date,
                        value,
                        day: date.getDate(),
                        currentMonth: date.getMonth() === month,
                        selected: value === this.value,
                        today: value === this.formatDate(this.today),
                        disabled: this.isDisabled(value),
                    };
                });
            },
            isDisabled(value) {
                return (this.min && value < this.min) || (this.max && value > this.max);
            },
            previousMonth() {
                this.viewing = new Date(this.viewing.getFullYear(), this.viewing.getMonth() - 1, 1);
            },
            nextMonth() {
                this.viewing = new Date(this.viewing.getFullYear(), this.viewing.getMonth() + 1, 1);
            },
            select(day) {
                if (day.disabled) {
                    return;
                }

                this.value = day.value;
                this.open = false;
                this.$nextTick(() => {
                    const textInput = this.$refs.input;
                    if (textInput) {
                        textInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.updatePosition());
                }
            },
        }"
        x-on:keydown.escape.prevent.stop="open = false"
    >
        <input type="hidden" name="{{ $name }}" x-bind:value="value" @if ($disabled) disabled @endif />

        <input
            id="{{ $id }}"
            type="text"
            readonly
            placeholder="Select date"
            x-bind:value="value"
            x-on:click="toggle()"
            x-on:keydown.enter.prevent="toggle()"
            x-on:keydown.space.prevent="toggle()"
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => trim("$baseClasses $stateClasses $disabledClasses pr-10")]) }}
            role="combobox"
            aria-haspopup="dialog"
            @if ($required) aria-required="true" @endif
            x-bind:aria-expanded="open.toString()"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
            x-ref="input"
        />

        <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 transition hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-60 dark:text-gray-400 dark:hover:text-gray-200"
            x-on:click="toggle()"
            @if ($disabled) disabled @endif
            aria-label="Toggle date picker"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M5 8h14M5 5h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-on:click.outside="open = false"
            x-bind:class="{
                'absolute left-0 top-full mt-2': position === 'bottom-left',
                'absolute right-0 top-full mt-2': position === 'bottom-right',
                'absolute left-0 bottom-full mb-2': position === 'top-left',
                'absolute right-0 bottom-full mb-2': position === 'top-right',
            }"
            class="z-50 w-80 rounded-2xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="mb-5 flex items-center justify-between">
                <button type="button" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200" x-on:click="previousMonth()" aria-label="Previous month">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18 9 12l6-6" />
                    </svg>
                </button>

                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="monthLabel"></p>

                <button type="button" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200" x-on:click="nextMonth()" aria-label="Next month">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </div>

            <div class="mb-3 grid grid-cols-7 gap-1 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                <template x-for="weekDay in weekDays" x-bind:key="weekDay">
                    <span x-text="weekDay"></span>
                </template>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-sm font-semibold">
                <template x-for="day in days" x-bind:key="day.value">
                    <button
                        type="button"
                        class="flex aspect-square items-center justify-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-40"
                        x-bind:disabled="day.disabled"
                        x-bind:class="{
                            'bg-blue-600 text-white shadow-sm hover:bg-blue-700': day.selected,
                            'text-gray-900 hover:bg-blue-50 hover:text-blue-600 dark:text-gray-100 dark:hover:bg-blue-900/30 dark:hover:text-blue-300': ! day.selected && day.currentMonth,
                            'text-gray-400 dark:text-gray-600': ! day.selected && ! day.currentMonth,
                            'ring-1 ring-blue-200 dark:ring-blue-800': day.today && ! day.selected,
                        }"
                        x-on:click="select(day)"
                    >
                        <span x-text="day.day"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    @if ($help && ! $hasError)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" id="{{ $id }}-error" />
    @endif
</div>
