# Job Application Tracker — Design Spec

## Purpose

A multi-user CRUD application for tracking job applications and their progress through the hiring pipeline. Built with the TALL stack (Tailwind CSS, Alpine.js, Livewire 4, Laravel 13) using Flux UI components.

## Data Model

### `job_applications` table

| Column | Type | Constraints |
|---|---|---|
| `id` | bigIncrements | PK |
| `user_id` | foreignId | constrained, cascadeOnDelete |
| `company_name` | string | required |
| `job_title` | string | required |
| `job_url` | string, nullable | optional link to job posting |
| `location` | string, nullable | e.g. "Remote", "NYC", "Hybrid - Austin" |
| `date_applied` | date | defaults to today |
| `status` | string | backed by enum, default: Applied |
| `salary_min` | unsignedInteger, nullable | in whole dollars |
| `salary_max` | unsignedInteger, nullable | in whole dollars |
| `interest` | unsignedTinyInteger | 1–5 rating, required |
| `notes` | text, nullable | free-form notes |
| `created_at` | timestamp | standard |
| `updated_at` | timestamp | standard |

### `ApplicationStatus` enum

```
Applied (blue)
PreliminaryInterview (yellow)
TechnicalInterview (indigo)
Offer (green)
Accepted (green)
Rejected (red)
Ghosted (zinc)
```

Each case has a `label()` method (human-readable name) and a `color()` method (Flux badge color string).

### `JobApplication` model

- Belongs to `User`
- Casts: `status` → `ApplicationStatus`, `date_applied` → `date`
- Scoped to authenticated user via all queries
- Accessor: `salary_range` — formats min/max into "$120k – $150k" or "—" if both null
- Factory and seeder included

## UI Architecture

### Single Livewire component: `JobApplications`

One full-page Livewire component handles the entire CRUD workflow. Mounted at `/applications` (named route `applications`).

### Table View

- **Flux UI table** (`flux:table`) with built-in pagination (15 per page)
- **Sortable columns:** Company, Job Title, Status, Applied, Salary (sorts by `salary_min`), Interest
- **Non-sortable columns:** Location, Actions
- **Search:** `flux:input` with `wire:model.live.debounce.300ms` — filters by company name and job title
- **Status filter:** `flux:select` with `wire:model.live` — filters by status enum value
- **Status display:** `flux:badge` with color from enum's `color()` method
- **Interest display:** Star characters (★/☆) rendered from the 1–5 integer
- **Salary display:** Formatted as "$140k – $180k", or "—" if not provided
- **Row actions:** Edit (pencil-square icon) and Delete (trash icon) as `flux:button variant="ghost" size="sm"`

### Create/Edit Modal

- **Trigger:** "New Application" button opens `flux:modal`
- **Same modal** reused for create and edit (component tracks `$editingId`)
- **Form fields:**
  - Company Name — `flux:input`, required
  - Job Title — `flux:input`, required
  - Job URL — `flux:input` type="url", optional
  - Location — `flux:input`, optional
  - Date Applied — `flux:input` type="date", defaults to today
  - Status — `flux:select` with enum options
  - Salary Min — `flux:input` type="number", optional
  - Salary Max — `flux:input` type="number", optional
  - Interest — `flux:select` with options 1–5
  - Notes — `flux:textarea`, optional
- **Validation:** Real-time via `wire:model.blur` on required fields
- **Submit:** Saves and closes modal, refreshes table

### Delete Confirmation

- `flux:modal` triggered by delete button
- Shows company name and job title
- Confirm/cancel buttons
- Deletes and refreshes table

## Routing

- `/applications` — main table view (auth middleware, named `applications`)
- Dashboard redirects to `/applications` or sidebar links to it

## Authorization

- All queries scoped to `auth()->id()` — no policy needed for v1 since users can only access their own data
- Auth middleware on the route

## Testing

- Feature tests using Pest
- Factory-driven test data
- Tests cover: CRUD operations, user scoping (user A can't see user B's data), sorting, filtering, validation
