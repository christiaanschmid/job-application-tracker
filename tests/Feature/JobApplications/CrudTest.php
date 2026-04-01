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
