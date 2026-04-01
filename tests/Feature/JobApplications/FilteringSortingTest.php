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
