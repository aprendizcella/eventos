<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Models\Event;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;

    /** @var array<int, array<string, mixed>> */
    public array $questions = [];

    // Fields for the creation modal
    public bool $showCreateModal = false;
    public string $newLabel = '';
    public string $newType = 'text';
    public bool $newRequired = false;
    public string $newOptionsText = ''; // Comma or line separated options

    // Fields for the edit state
    public ?string $editingId = null;
    public string $editLabel = '';
    public string $editType = 'text';
    public bool $editRequired = false;
    public string $editOptionsText = '';

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->questions = collect($this->event->custom_questions ?? [])
            ->sortBy('position')
            ->values()
            ->all();
    }

    public function openCreateModal(): void
    {
        $this->resetNewForm();
        $this->showCreateModal = true;
    }

    public function addQuestion(): void
    {
        $this->validate([
            'newLabel' => ['required', 'string', 'max:255'],
            'newType' => ['required', 'string', 'in:text,textarea,select,radio,checkbox'],
        ]);

        $options = [];
        if (in_array($this->newType, ['select', 'radio', 'checkbox'], true)) {
            $options = array_filter(array_map('trim', explode("\n", $this->newOptionsText)));
        }

        $nextPosition = count($this->questions) > 0 ? (max(array_column($this->questions, 'position')) + 1) : 1;

        $newQuestion = [
            'id' => 'q_' . Str::random(10), // ID estable e inmutable
            'label' => $this->newLabel,
            'type' => $this->newType,
            'required' => $this->newRequired,
            'options' => $options,
            'position' => $nextPosition,
        ];

        $this->questions[] = $newQuestion;
        $this->saveQuestions();

        $this->showCreateModal = false;
        $this->resetNewForm();
    }

    public function startEdit(string $id): void
    {
        $q = collect($this->questions)->firstWhere('id', $id);
        if ($q !== null) {
            $this->editingId = $id;
            $this->editLabel = $q['label'] ?? '';
            $this->editType = $q['type'] ?? 'text';
            $this->editRequired = (bool) ($q['required'] ?? false);
            $this->editOptionsText = implode("\n", $q['options'] ?? []);
        }
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function updateQuestion(): void
    {
        $this->validate([
            'editLabel' => ['required', 'string', 'max:255'],
            'editType' => ['required', 'string', 'in:text,textarea,select,radio,checkbox'],
        ]);

        $options = [];
        if (in_array($this->editType, ['select', 'radio', 'checkbox'], true)) {
            $options = array_filter(array_map('trim', explode("\n", $this->editOptionsText)));
        }

        foreach ($this->questions as $index => $q) {
            if ($q['id'] === $this->editingId) {
                // Mantenemos el ID original e inmutable
                $this->questions[$index]['label'] = $this->editLabel;
                $this->questions[$index]['type'] = $this->editType;
                $this->questions[$index]['required'] = $this->editRequired;
                $this->questions[$index]['options'] = $options;
                break;
            }
        }

        $this->saveQuestions();
        $this->editingId = null;
    }

    public function deleteQuestion(string $id): void
    {
        $this->questions = collect($this->questions)
            ->reject(fn ($q) => $q['id'] === $id)
            ->values()
            ->all();

        $this->reorderPositions();
        $this->saveQuestions();
    }

    public function moveUp(string $id): void
    {
        $index = collect($this->questions)->search(fn ($q) => $q['id'] === $id);
        if ($index !== false && $index > 0) {
            $temp = $this->questions[$index - 1];
            $this->questions[$index - 1] = $this->questions[$index];
            $this->questions[$index] = $temp;

            $this->reorderPositions();
            $this->saveQuestions();
        }
    }

    public function moveDown(string $id): void
    {
        $index = collect($this->questions)->search(fn ($q) => $q['id'] === $id);
        if ($index !== false && $index < count($this->questions) - 1) {
            $temp = $this->questions[$index + 1];
            $this->questions[$index + 1] = $this->questions[$index];
            $this->questions[$index] = $temp;

            $this->reorderPositions();
            $this->saveQuestions();
        }
    }

    private function reorderPositions(): void
    {
        foreach ($this->questions as $index => $q) {
            $this->questions[$index]['position'] = $index + 1;
        }
    }

    private function saveQuestions(): void
    {
        $this->event->update([
            'custom_questions' => $this->questions,
        ]);
        session()->flash('success_questions', __('Custom questions updated successfully.'));
    }

    private function resetNewForm(): void
    {
        $this->newLabel = '';
        $this->newType = 'text';
        $this->newRequired = false;
        $this->newOptionsText = '';
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Custom Questions for Checkout') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Collect custom information from buyers and attendees when they purchase tickets.') }}
            </p>
        </div>
        <button type="button" wire:click="openCreateModal" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-500">
            ➕ {{ __('Add Custom Question') }}
        </button>
    </div>

    @if (session('success_questions'))
        <div class="rounded-lg bg-green-50 p-4 text-xs font-semibold text-green-800 dark:bg-green-950/20 dark:text-green-400">
            {{ session('success_questions') }}
        </div>
    @endif

    {{-- Questions List --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        @if (empty($questions))
            <div class="text-center py-12 text-gray-400 italic text-sm">
                {{ __('No custom questions defined yet for this event.') }}
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Question') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Required') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Options') }}</th>
                        <th class="px-6 py-3 text-center font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Order') }}</th>
                        <th class="px-6 py-3 text-right font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($questions as $index => $q)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ $q['label'] }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 capitalize">
                                {{ $q['type'] }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ ($q['required'] ?? false) ? __('Yes') : __('No') }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                @if(!empty($q['options']))
                                    <span class="inline-flex flex-wrap gap-1">
                                        @foreach($q['options'] as $option)
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xxs dark:bg-gray-800 dark:text-gray-400">{{ $option }}</span>
                                        @endforeach
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex gap-1 justify-center">
                                    <button type="button" wire:click="moveUp('{{ $q['id'] }}')" @disabled($index === 0) class="text-gray-400 hover:text-gray-700 disabled:opacity-30">
                                        ▲
                                    </button>
                                    <button type="button" wire:click="moveDown('{{ $q['id'] }}')" @disabled($index === count($questions) - 1) class="text-gray-400 hover:text-gray-700 disabled:opacity-30">
                                        ▼
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <button type="button" wire:click="startEdit('{{ $q['id'] }}')" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ __('Edit') }}
                                    </button>
                                    <button type="button" wire:click="deleteQuestion('{{ $q['id'] }}')" onsubmit="return confirm('Delete this question?');" class="text-red-600 dark:text-red-400 hover:underline">
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Create Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900 space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Add Custom Question') }}</h3>
                </div>

                <form wire:submit.prevent="addQuestion" class="space-y-4">
                    <div>
                        <label for="newLabel" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Question Text') }} *</label>
                        <input type="text" wire:model="newLabel" id="newLabel" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('newLabel') <span class="text-xxs text-red-600 font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="newType" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Question Type') }} *</label>
                        <select wire:model.live="newType" id="newType" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="text">{{ __('Short Answer (Text)') }}</option>
                            <option value="textarea">{{ __('Long Answer (Paragraph)') }}</option>
                            <option value="select">{{ __('Dropdown (Select)') }}</option>
                            <option value="radio">{{ __('Multiple Choice (Radio Buttons)') }}</option>
                            <option value="checkbox">{{ __('Multiple Selection (Checkboxes)') }}</option>
                        </select>
                    </div>

                    @if(in_array($newType, ['select', 'radio', 'checkbox'], true))
                        <div>
                            <label for="newOptions" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Options (One per line)') }} *</label>
                            <textarea wire:model="newOptionsText" id="newOptions" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="newRequired" id="newRequired" class="rounded text-blue-600 focus:ring-blue-500">
                        <label for="newRequired" class="text-xs text-gray-700 dark:text-gray-300 font-medium">{{ __('Mark as required') }}</label>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-500">
                            {{ __('Add Question') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Form --}}
    @if ($editingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900 space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Edit Custom Question') }}</h3>
                </div>

                <form wire:submit.prevent="updateQuestion" class="space-y-4">
                    <div>
                        <label for="editLabel" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Question Text') }} *</label>
                        <input type="text" wire:model="editLabel" id="editLabel" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('editLabel') <span class="text-xxs text-red-600 font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="editType" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Question Type') }} *</label>
                        <select wire:model.live="editType" id="editType" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="text">{{ __('Short Answer (Text)') }}</option>
                            <option value="textarea">{{ __('Long Answer (Paragraph)') }}</option>
                            <option value="select">{{ __('Dropdown (Select)') }}</option>
                            <option value="radio">{{ __('Multiple Choice (Radio Buttons)') }}</option>
                            <option value="checkbox">{{ __('Multiple Selection (Checkboxes)') }}</option>
                        </select>
                    </div>

                    @if(in_array($editType, ['select', 'radio', 'checkbox'], true))
                        <div>
                            <label for="editOptions" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Options (One per line)') }} *</label>
                            <textarea wire:model="editOptionsText" id="editOptions" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="editRequired" id="editRequired" class="rounded text-blue-600 focus:ring-blue-500">
                        <label for="editRequired" class="text-xs text-gray-700 dark:text-gray-300 font-medium">{{ __('Mark as required') }}</label>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button type="button" wire:click="cancelEdit" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-500">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
