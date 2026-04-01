<?php

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('a user cannot edit another users application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = JobApplication::factory()->for($otherUser)->create();

    expect(fn () => Livewire::actingAs($user)
        ->test('pages::applications.index')
        ->call('openEditModal', $application->id)
    )->toThrow(ModelNotFoundException::class);
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
