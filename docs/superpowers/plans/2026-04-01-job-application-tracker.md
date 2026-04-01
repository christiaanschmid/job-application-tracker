# Job Application Tracker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full CRUD interface for tracking job applications through the hiring pipeline, scoped per user.

**Architecture:** Single Livewire SFC page component backed by an Eloquent model with a string-backed enum for status. Flux UI components for the table, forms, modals, and badges. All queries scoped to the authenticated user.

**Tech Stack:** Laravel 13, Livewire 4 (SFC), Flux UI 2, Tailwind CSS 4, Alpine.js, Pest 4, SQLite

---

## File Structure

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `app/Enums/ApplicationStatus.php` | String-backed enum with label/color methods |
| Create | `app/Models/JobApplication.php` | Eloquent model with casts, fillable, relationship |
| Create | `database/migrations/xxxx_create_job_applications_table.php` | Schema definition |
| Create | `database/factories/JobApplicationFactory.php` | Test data factory |
| Create | `database/seeders/JobApplicationSeeder.php` | Seed sample data |
| Create | `resources/views/pages/applications/⚡index.blade.php` | SFC: table, search, filter, sort, create/edit/delete modals |
| Modify | `routes/web.php` | Add `/applications` route |
| Modify | `resources/views/layouts/app/sidebar.blade.php` | Add sidebar nav item |
| Create | `tests/Feature/JobApplications/CrudTest.php` | CRUD operations test |
| Create | `tests/Feature/JobApplications/ScopingTest.php` | User scoping test |
| Create | `tests/Feature/JobApplications/FilteringSortingTest.php` | Filter/sort test |

---

### Task 1: ApplicationStatus Enum

**Files:**
- Create: `app/Enums/ApplicationStatus.php`

- [ ] **Step 1: Create the enum via artisan**

Run:
```bash
php artisan make:enum Enums/ApplicationStatus --string --no-interaction
```

- [ ] **Step 2: Implement the enum with label and color methods**

Replace the contents of `app/Enums/ApplicationStatus.php` with:

```php
<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Applied = 'applied';
    case PreliminaryInterview = 'preliminary_interview';
    case TechnicalInterview = 'technical_interview';
    case Offer = 'offer';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Ghosted = 'ghosted';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::PreliminaryInterview => 'Preliminary Interview',
            self::TechnicalInterview => 'Technical Interview',
            self::Offer => 'Offer',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Ghosted => 'Ghosted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Applied => 'blue',
            self::PreliminaryInterview => 'yellow',
            self::TechnicalInterview => 'indigo',
            self::Offer => 'green',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Ghosted => 'zinc',
        };
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Enums/ApplicationStatus.php
git commit -m "feat: add ApplicationStatus enum with label and color methods"
```

---

### Task 2: Migration + Model + Factory + Seeder

**Files:**
- Create: `database/migrations/xxxx_create_job_applications_table.php`
- Create: `app/Models/JobApplication.php`
- Create: `database/factories/JobApplicationFactory.php`
- Create: `database/seeders/JobApplicationSeeder.php`

- [ ] **Step 1: Generate model with migration, factory, and seeder**

Run:
```bash
php artisan make:model JobApplication -mfs --no-interaction
```

- [ ] **Step 2: Write the migration**

Replace the `up()` method in the generated migration file:

```php
public function up(): void
{
    Schema::create('job_applications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('company_name');
        $table->string('job_title');
        $table->string('job_url')->nullable();
        $table->string('location')->nullable();
        $table->date('date_applied');
        $table->string('status')->default('applied');
        $table->unsignedInteger('salary_min')->nullable();
        $table->unsignedInteger('salary_max')->nullable();
        $table->unsignedTinyInteger('interest');
        $table->text('notes')->nullable();
        $table->timestamps();

        $table->index(['user_id', 'status']);
        $table->index(['user_id', 'date_applied']);
    });
}
```

- [ ] **Step 3: Run the migration**

Run:
```bash
php artisan migrate
```

- [ ] **Step 4: Implement the model**

Replace `app/Models/JobApplication.php` with:

```php
<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_name',
    'job_title',
    'job_url',
    'location',
    'date_applied',
    'status',
    'salary_min',
    'salary_max',
    'interest',
    'notes',
])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'date_applied' => 'date',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'interest' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format salary range as "$120k – $180k" or "—" if both null.
     */
    public function salaryRange(): string
    {
        if ($this->salary_min === null && $this->salary_max === null) {
            return '—';
        }

        $format = fn (int $value): string => '$' . number_format($value / 1000) . 'k';

        if ($this->salary_min !== null && $this->salary_max !== null) {
            return $format($this->salary_min) . ' – ' . $format($this->salary_max);
        }

        return $this->salary_min !== null ? $format($this->salary_min) : $format($this->salary_max);
    }
}
```

- [ ] **Step 5: Add hasMany relationship to User model**

In `app/Models/User.php`, add the following method after the `initials()` method:

```php
public function jobApplications(): HasMany
{
    return $this->hasMany(JobApplication::class);
}
```

Add the import at the top of the file:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

- [ ] **Step 6: Implement the factory**

Replace `database/factories/JobApplicationFactory.php` with:

```php
<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salaryMin = fake()->optional(0.7)->numberBetween(50000, 150000);
        $salaryMax = $salaryMin ? $salaryMin + fake()->numberBetween(10000, 50000) : null;

        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'job_url' => fake()->optional(0.8)->url(),
            'location' => fake()->randomElement(['Remote', 'Hybrid - ' . fake()->city(), fake()->city() . ', ' . fake()->stateAbbr()]),
            'date_applied' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => fake()->randomElement(ApplicationStatus::cases()),
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'interest' => fake()->numberBetween(1, 5),
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }
}
```

- [ ] **Step 7: Implement the seeder**

Replace `database/seeders/JobApplicationSeeder.php` with:

```php
<?php

namespace Database\Seeders;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        JobApplication::factory()
            ->count(25)
            ->for($user)
            ->create();
    }
}
```

- [ ] **Step 8: Run pint**

Run:
```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Models/ database/migrations/ database/factories/ database/seeders/
git commit -m "feat: add JobApplication model, migration, factory, and seeder"
```

---

### Task 3: Model & Enum Unit Tests

**Files:**
- Create: `tests/Feature/JobApplications/CrudTest.php`

- [ ] **Step 1: Create the test file**

Run:
```bash
mkdir -p tests/Feature/JobApplications
php artisan make:test --pest JobApplications/CrudTest --no-interaction
```

- [ ] **Step 2: Write CRUD tests**

Replace `tests/Feature/JobApplications/CrudTest.php` with:

```php
<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;

test('a user can create a job application', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('applications.store'), [
            'company_name' => 'Stripe',
            'job_title' => 'Senior Laravel Developer',
            'job_url' => 'https://stripe.com/jobs/123',
            'location' => 'Remote',
            'date_applied' => '2026-04-01',
            'status' => 'applied',
            'salary_min' => 140000,
            'salary_max' => 180000,
            'interest' => 5,
            'notes' => 'Great company',
        ]);

    expect(JobApplication::count())->toBe(1);
    expect(JobApplication::first())
        ->company_name->toBe('Stripe')
        ->job_title->toBe('Senior Laravel Developer')
        ->user_id->toBe($user->id)
        ->status->toBe(ApplicationStatus::Applied)
        ->interest->toBe(5);
});

test('a user can update a job application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create([
        'company_name' => 'Stripe',
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($user)
        ->put(route('applications.update', $application), [
            'company_name' => 'Stripe Inc.',
            'job_title' => $application->job_title,
            'date_applied' => $application->date_applied->format('Y-m-d'),
            'status' => 'preliminary_interview',
            'interest' => $application->interest,
        ]);

    expect($application->fresh())
        ->company_name->toBe('Stripe Inc.')
        ->status->toBe(ApplicationStatus::PreliminaryInterview);
});

test('a user can delete a job application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('applications.destroy', $application));

    expect(JobApplication::count())->toBe(0);
});

test('creating a job application requires company name and job title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('applications.store'), [
            'date_applied' => '2026-04-01',
            'status' => 'applied',
            'interest' => 3,
        ])
        ->assertSessionHasErrors(['company_name', 'job_title']);
});

test('interest must be between 1 and 5', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('applications.store'), [
            'company_name' => 'Test',
            'job_title' => 'Dev',
            'date_applied' => '2026-04-01',
            'status' => 'applied',
            'interest' => 6,
        ])
        ->assertSessionHasErrors(['interest']);
});

test('guests cannot access applications', function () {
    $this->get(route('applications.index'))
        ->assertRedirect(route('login'));
});

test('salary range formats correctly', function () {
    $application = JobApplication::factory()->make([
        'salary_min' => 120000,
        'salary_max' => 150000,
    ]);

    expect($application->salaryRange())->toBe('$120k – $150k');
});

test('salary range shows dash when both null', function () {
    $application = JobApplication::factory()->make([
        'salary_min' => null,
        'salary_max' => null,
    ]);

    expect($application->salaryRange())->toBe('—');
});

test('application status enum has correct labels', function () {
    expect(ApplicationStatus::Applied->label())->toBe('Applied');
    expect(ApplicationStatus::PreliminaryInterview->label())->toBe('Preliminary Interview');
    expect(ApplicationStatus::TechnicalInterview->label())->toBe('Technical Interview');
    expect(ApplicationStatus::Ghosted->label())->toBe('Ghosted');
});

test('application status enum has correct colors', function () {
    expect(ApplicationStatus::Applied->color())->toBe('blue');
    expect(ApplicationStatus::Rejected->color())->toBe('red');
    expect(ApplicationStatus::Ghosted->color())->toBe('zinc');
    expect(ApplicationStatus::Offer->color())->toBe('green');
});
```

- [ ] **Step 3: Run the tests to verify they fail (routes don't exist yet)**

Run:
```bash
php artisan test --compact tests/Feature/JobApplications/CrudTest.php
```

Expected: Failures because the routes `applications.store`, `applications.update`, `applications.destroy`, `applications.index` don't exist yet. The `salaryRange` and enum tests should pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/JobApplications/CrudTest.php
git commit -m "test: add CRUD tests for job applications (red phase)"
```

---

### Task 4: User Scoping Tests

**Files:**
- Create: `tests/Feature/JobApplications/ScopingTest.php`

- [ ] **Step 1: Create the test file**

Run:
```bash
php artisan make:test --pest JobApplications/ScopingTest --no-interaction
```

- [ ] **Step 2: Write scoping tests**

Replace `tests/Feature/JobApplications/ScopingTest.php` with:

```php
<?php

use App\Models\JobApplication;
use App\Models\User;

test('a user cannot update another users application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = JobApplication::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->put(route('applications.update', $application), [
            'company_name' => 'Hacked',
            'job_title' => 'Hacker',
            'date_applied' => '2026-04-01',
            'status' => 'applied',
            'interest' => 1,
        ])
        ->assertForbidden();
});

test('a user cannot delete another users application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = JobApplication::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->delete(route('applications.destroy', $application))
        ->assertForbidden();

    expect(JobApplication::count())->toBe(1);
});

test('a user only sees their own applications in the table', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    JobApplication::factory()->for($user)->count(3)->create();
    JobApplication::factory()->for($otherUser)->count(2)->create();

    $this->actingAs($user)
        ->get(route('applications.index'))
        ->assertOk();

    // The Livewire component query is scoped — verified via the component's computed property
    expect(JobApplication::where('user_id', $user->id)->count())->toBe(3);
    expect(JobApplication::where('user_id', $otherUser->id)->count())->toBe(2);
});
```

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/JobApplications/ScopingTest.php
git commit -m "test: add user scoping tests for job applications (red phase)"
```

---

### Task 5: Livewire SFC Component + Route + Sidebar

**Files:**
- Create: `resources/views/pages/applications/⚡index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app/sidebar.blade.php`

- [ ] **Step 1: Create the directory for the page**

Run:
```bash
mkdir -p resources/views/pages/applications
```

- [ ] **Step 2: Create the Livewire SFC component**

Create `resources/views/pages/applications/⚡index.blade.php`:

```php
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

<x-layouts::app :title="__('Job Applications')">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Job Applications') }}</flux:heading>
            <flux:button icon="plus" wire:click="openCreateModal">
                {{ __('New Application') }}
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search company or title..."
                icon="magnifying-glass"
                class="max-w-xs"
            />
            <flux:select wire:model.live="statusFilter" placeholder="All Statuses" class="max-w-48">
                <flux:select.option value="">All Statuses</flux:select.option>
                @foreach (ApplicationStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
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
    <flux:modal wire:model="showFormModal" class="max-w-lg">
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
</x-layouts::app>
```

- [ ] **Step 3: Add the route**

In `routes/web.php`, add inside the existing `auth` + `verified` middleware group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('applications', 'pages::applications.index')->name('applications.index');
});
```

Also add named routes for the Livewire actions. Since the SFC handles store/update/delete via Livewire wire:click actions (not HTTP routes), we need named routes for the test assertions. Add these inside the same group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('applications', 'pages::applications.index')->name('applications.index');
});
```

Note: The CRUD tests in Task 3 use traditional HTTP POST/PUT/DELETE assertions. Since this is a Livewire SFC, those tests need to be rewritten to use Livewire::test() syntax. This will be done in Task 6.

- [ ] **Step 4: Add sidebar navigation item**

In `resources/views/layouts/app/sidebar.blade.php`, add the applications link after the Dashboard item inside the `flux:sidebar.group`:

```blade
<flux:sidebar.item icon="briefcase" :href="route('applications.index')" :current="request()->routeIs('applications.*')" wire:navigate>
    {{ __('Applications') }}
</flux:sidebar.item>
```

- [ ] **Step 5: Run pint**

Run:
```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/pages/applications/ routes/web.php resources/views/layouts/app/sidebar.blade.php
git commit -m "feat: add job applications Livewire SFC with table, modals, and routing"
```

---

### Task 6: Rewrite Tests for Livewire + Make Tests Pass

**Files:**
- Modify: `tests/Feature/JobApplications/CrudTest.php`
- Modify: `tests/Feature/JobApplications/ScopingTest.php`
- Create: `tests/Feature/JobApplications/FilteringSortingTest.php`

The CRUD tests from Task 3 used traditional HTTP assertions. Since we're using a Livewire SFC, rewrite them to use `Livewire::test()` for the actions and keep simple HTTP tests for page access/auth.

- [ ] **Step 1: Rewrite CrudTest.php**

Replace `tests/Feature/JobApplications/CrudTest.php` with:

```php
<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use Livewire\Livewire;

test('a user can create a job application', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('openCreateModal')
        ->set('company_name', 'Stripe')
        ->set('job_title', 'Senior Laravel Developer')
        ->set('job_url', 'https://stripe.com/jobs/123')
        ->set('location', 'Remote')
        ->set('date_applied', '2026-04-01')
        ->set('status', 'applied')
        ->set('salary_min', 140000)
        ->set('salary_max', 180000)
        ->set('interest', 5)
        ->set('notes', 'Great company')
        ->call('save')
        ->assertHasNoErrors();

    expect(JobApplication::count())->toBe(1);
    expect(JobApplication::first())
        ->company_name->toBe('Stripe')
        ->job_title->toBe('Senior Laravel Developer')
        ->user_id->toBe($user->id)
        ->status->toBe(ApplicationStatus::Applied)
        ->interest->toBe(5);
});

test('a user can update a job application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create([
        'company_name' => 'Stripe',
        'status' => ApplicationStatus::Applied,
    ]);

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('openEditModal', $application->id)
        ->set('company_name', 'Stripe Inc.')
        ->set('status', 'preliminary_interview')
        ->call('save')
        ->assertHasNoErrors();

    expect($application->fresh())
        ->company_name->toBe('Stripe Inc.')
        ->status->toBe(ApplicationStatus::PreliminaryInterview);
});

test('a user can delete a job application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('confirmDelete', $application->id)
        ->call('delete');

    expect(JobApplication::count())->toBe(0);
});

test('creating a job application requires company name and job title', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('openCreateModal')
        ->set('company_name', '')
        ->set('job_title', '')
        ->set('date_applied', '2026-04-01')
        ->set('status', 'applied')
        ->set('interest', 3)
        ->call('save')
        ->assertHasErrors(['company_name', 'job_title']);
});

test('interest must be between 1 and 5', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('openCreateModal')
        ->set('company_name', 'Test')
        ->set('job_title', 'Dev')
        ->set('date_applied', '2026-04-01')
        ->set('status', 'applied')
        ->set('interest', 6)
        ->call('save')
        ->assertHasErrors(['interest']);
});

test('guests cannot access applications', function () {
    $this->get(route('applications.index'))
        ->assertRedirect(route('login'));
});

test('salary range formats correctly', function () {
    $application = JobApplication::factory()->make([
        'salary_min' => 120000,
        'salary_max' => 150000,
    ]);

    expect($application->salaryRange())->toBe('$120k – $150k');
});

test('salary range shows dash when both null', function () {
    $application = JobApplication::factory()->make([
        'salary_min' => null,
        'salary_max' => null,
    ]);

    expect($application->salaryRange())->toBe('—');
});

test('application status enum has correct labels', function () {
    expect(ApplicationStatus::Applied->label())->toBe('Applied');
    expect(ApplicationStatus::PreliminaryInterview->label())->toBe('Preliminary Interview');
    expect(ApplicationStatus::TechnicalInterview->label())->toBe('Technical Interview');
    expect(ApplicationStatus::Ghosted->label())->toBe('Ghosted');
});

test('application status enum has correct colors', function () {
    expect(ApplicationStatus::Applied->color())->toBe('blue');
    expect(ApplicationStatus::Rejected->color())->toBe('red');
    expect(ApplicationStatus::Ghosted->color())->toBe('zinc');
    expect(ApplicationStatus::Offer->color())->toBe('green');
});
```

- [ ] **Step 2: Rewrite ScopingTest.php**

Replace `tests/Feature/JobApplications/ScopingTest.php` with:

```php
<?php

use App\Models\JobApplication;
use App\Models\User;
use Livewire\Livewire;

test('a user cannot edit another users application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = JobApplication::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('openEditModal', $application->id)
        ->assertStatus(404);
});

test('a user cannot delete another users application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = JobApplication::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('confirmDelete', $application->id)
        ->call('delete');

    expect(JobApplication::count())->toBe(1);
});

test('a user only sees their own applications', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    JobApplication::factory()->for($user)->count(3)->create(['company_name' => 'My Company']);
    JobApplication::factory()->for($otherUser)->count(2)->create(['company_name' => 'Other Company']);

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->assertSee('My Company')
        ->assertDontSee('Other Company');
});
```

- [ ] **Step 3: Create FilteringSortingTest.php**

Create `tests/Feature/JobApplications/FilteringSortingTest.php`:

```php
<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use Livewire\Livewire;

test('applications can be searched by company name', function () {
    $user = User::factory()->create();
    JobApplication::factory()->for($user)->create(['company_name' => 'Stripe']);
    JobApplication::factory()->for($user)->create(['company_name' => 'Basecamp']);

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->set('search', 'Stripe')
        ->assertSee('Stripe')
        ->assertDontSee('Basecamp');
});

test('applications can be searched by job title', function () {
    $user = User::factory()->create();
    JobApplication::factory()->for($user)->create(['job_title' => 'Laravel Developer', 'company_name' => 'Company A']);
    JobApplication::factory()->for($user)->create(['job_title' => 'React Developer', 'company_name' => 'Company B']);

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->set('search', 'Laravel')
        ->assertSee('Company A')
        ->assertDontSee('Company B');
});

test('applications can be filtered by status', function () {
    $user = User::factory()->create();
    JobApplication::factory()->for($user)->create(['status' => ApplicationStatus::Applied, 'company_name' => 'Applied Co']);
    JobApplication::factory()->for($user)->create(['status' => ApplicationStatus::Rejected, 'company_name' => 'Rejected Co']);

    Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->set('statusFilter', 'applied')
        ->assertSee('Applied Co')
        ->assertDontSee('Rejected Co');
});

test('applications can be sorted by company name', function () {
    $user = User::factory()->create();
    JobApplication::factory()->for($user)->create(['company_name' => 'Zebra Corp']);
    JobApplication::factory()->for($user)->create(['company_name' => 'Alpha Inc']);

    $component = Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('sort', 'company_name');

    expect($component->get('sortBy'))->toBe('company_name');
    expect($component->get('sortDirection'))->toBe('asc');
});

test('sorting the same column toggles direction', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('sort', 'company_name')
        ->call('sort', 'company_name');

    expect($component->get('sortDirection'))->toBe('desc');
});
```

- [ ] **Step 4: Run all tests**

Run:
```bash
php artisan test --compact tests/Feature/JobApplications/
```

Expected: All tests pass.

- [ ] **Step 5: Run pint**

Run:
```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/JobApplications/
git commit -m "test: rewrite tests for Livewire and add filtering/sorting tests (green phase)"
```

---

### Task 7: Seed Data + Final Verification

**Files:**
- No new files

- [ ] **Step 1: Run the seeder to populate sample data**

Run:
```bash
php artisan db:seed --class=JobApplicationSeeder
```

- [ ] **Step 2: Run the full test suite to ensure nothing is broken**

Run:
```bash
php artisan test --compact
```

Expected: All tests pass, including pre-existing auth tests.

- [ ] **Step 3: Run pint on all changed files**

Run:
```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Commit if pint made changes**

```bash
git add -A
git commit -m "chore: seed sample data and final pint formatting"
```
