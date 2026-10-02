<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => fake()->optional()->phoneNumber(),
            'subject' => fake()->sentence(5), 'message' => fake()->paragraphs(2, true),
            'status' => fake()->randomElement(ContactMessageStatus::cases()), 'assigned_to' => null];
    }
}
