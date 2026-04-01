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
            'location' => fake()->randomElement(['Remote', 'Hybrid - '.fake()->city(), fake()->city().', '.fake()->stateAbbr()]),
            'date_applied' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => fake()->randomElement(ApplicationStatus::cases()),
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'interest' => fake()->numberBetween(1, 5),
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }
}
