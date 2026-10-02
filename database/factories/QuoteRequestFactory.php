<?php

namespace Database\Factories;

use App\Enums\PreferredContactMethod;
use App\Enums\QuoteRequestStatus;
use App\Models\QuoteRequest;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuoteRequest> */
class QuoteRequestFactory extends Factory
{
    protected $model = QuoteRequest::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => fake()->optional()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'service_id' => fake()->boolean(70) ? Service::factory() : null,
            'subject' => fake()->optional()->sentence(5),
            'message' => fake()->paragraphs(2, true),
            'budget' => fake()->optional()->randomFloat(2, 0, 5000000),
            'desired_deadline' => fake()->optional()->dateTimeBetween('+1 week', '+1 year'),
            'preferred_contact_method' => fake()->randomElement(PreferredContactMethod::cases()),
            'status' => fake()->randomElement(QuoteRequestStatus::cases()),
        ];
    }
}
