<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Job Applications')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public string $sortBy = 'date_applied';
    public string $sortDirection = 'desc';

    // Form state
    public ?int $editingId = null;
    public string $company_name = '';
    public string $job_title = '';
    public string $job_url = '';
    public string $location = '';
    public string $date_applied = '';
    public string $status = 'applied';
    public ?int $salary_min = null;
    public ?int $salary_max = null;
    public int $interest = 3;
    public string $notes = '';

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    #[Computed]
    public function applications()
    {
        return JobApplication::query()
            ->where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('company_name', 'like', '%' . $this->search . '%')
                      ->orWhere('job_title', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->date_applied = now()->format('Y-m-d');
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $application = JobApplication::where('user_id', auth()->id())->findOrFail($id);

        $this->editingId = $application->id;
        $this->company_name = $application->company_name;
        $this->job_title = $application->job_title;
        $this->job_url = $application->job_url ?? '';
        $this->location = $application->location ?? '';
        $this->date_applied = $application->date_applied->format('Y-m-d');
        $this->status = $application->status->value;
        $this->salary_min = $application->salary_min;
        $this->salary_max = $application->salary_max;
        $this->interest = $application->interest;
        $this->notes = $application->notes ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'job_url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'date_applied' => ['required', 'date'],
            'status' => ['required', 'string', 'in:' . implode(',', array_column(ApplicationStatus::cases(), 'value'))],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'interest' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $validated = array_merge($validated, [
            'job_url' => $validated['job_url'] ?: null,
            'location' => $validated['location'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        if ($this->editingId) {
            $application = JobApplication::where('user_id', auth()->id())->findOrFail($this->editingId);
            $application->update($validated);
        } else {
            auth()->user()->jobApplications()->create($validated);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->applications);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            JobApplication::where('user_id', auth()->id())
                ->where('id', $this->deletingId)
                ->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
        unset($this->applications);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->company_name = '';
        $this->job_title = '';
        $this->job_url = '';
        $this->location = '';
        $this->date_applied = '';
        $this->status = 'applied';
        $this->salary_min = null;
        $this->salary_max = null;
        $this->interest = 3;
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Render star display for interest rating.
     */
    public function stars(int $rating): string
    {
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Job Applications') }}</flux:heading>
            <flux:button  class="cursor-pointer" icon="plus" variant="primary" wire:click="openCreateModal">
                {{ __('New Application') }}
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex items-center justify-between gap-3">
            <div class="w-1/2 shrink-0 grow-0">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search company or title"
                    icon="magnifying-glass"
                />
            </div>
            <div>
                <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    @foreach (ApplicationStatus::cases() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->applications">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'company_name'" :direction="$sortDirection" wire:click="sort('company_name')">Company</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'job_title'" :direction="$sortDirection" wire:click="sort('job_title')">Job Title</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'date_applied'" :direction="$sortDirection" wire:click="sort('date_applied')">Applied</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'salary_min'" :direction="$sortDirection" wire:click="sort('salary_min')">Salary</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'interest'" :direction="$sortDirection" wire:click="sort('interest')">Interest</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->applications as $application)
                    <flux:table.row :key="$application->id">
                        <flux:table.cell variant="strong">{{ $application->company_name }}</flux:table.cell>
                        <flux:table.cell>{{ $application->job_title }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$application->status->color()" inset="top bottom">
                                {{ $application->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $application->date_applied->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $application->salaryRange() }}</flux:table.cell>
                        <flux:table.cell class="text-amber-500">{{ $this->stars($application->interest) }}</flux:table.cell>
                        <flux:table.cell>{{ $application->location ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $application->id }})" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $application->id }})" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showFormModal" class="max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit Application') : __('New Application') }}
            </flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="company_name" label="Company Name" required />
                <flux:input wire:model="job_title" label="Job Title" required />
                <flux:input wire:model="job_url" label="Job URL" type="url" />
                <flux:input wire:model="location" label="Location" placeholder="e.g. Remote, NYC, Hybrid - Austin" />
                <flux:input wire:model="date_applied" label="Date Applied" type="date" required />

                <flux:select wire:model="status" label="Status" required>
                    @foreach (ApplicationStatus::cases() as $statusOption)
                        <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="salary_min" label="Salary Min ($)" type="number" min="0" />
                    <flux:input wire:model="salary_max" label="Salary Max ($)" type="number" min="0" />
                </div>

                <flux:select wire:model="interest" label="Interest (1-5)" required>
                    @for ($i = 1; $i <= 5; $i++)
                        <flux:select.option value="{{ $i }}">{{ $i }} — {{ str_repeat('★', $i) . str_repeat('☆', 5 - $i) }}</flux:select.option>
                    @endfor
                </flux:select>

                <flux:textarea wire:model="notes" label="Notes" rows="3" />

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showFormModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? __('Update') : __('Create') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Application') }}</flux:heading>
            <flux:text>{{ __('Are you sure you want to delete this application? This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
