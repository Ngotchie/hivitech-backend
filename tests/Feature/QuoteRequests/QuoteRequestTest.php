<?php

namespace Tests\Feature\QuoteRequests;

use App\Enums\PreferredContactMethod;
use App\Enums\QuoteRequestStatus;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAttachment;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NewQuoteRequestNotification;
use App\Notifications\QuoteRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_quote_request_gets_a_unique_server_generated_reference(): void
    {
        $first = QuoteRequest::factory()->create();
        $second = QuoteRequest::factory()->create();

        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{5,}$/', $first->reference);
        $this->assertNotSame($first->reference, $second->reference);
        $this->assertSame(QuoteRequestStatus::class, get_class($first->status));
    }

    public function test_quote_request_relations_work(): void
    {
        $service = Service::factory()->create();
        $assignee = User::factory()->create();
        $quoteRequest = QuoteRequest::factory()->create([
            'service_id' => $service,
            'assigned_to' => $assignee,
        ]);
        $attachment = $quoteRequest->attachments()->create([
            'original_name' => 'brief.pdf', 'path' => 'quotes/1/attachments/file.pdf',
        ]);
        $note = $quoteRequest->notes()->create(['user_id' => $assignee->getKey(), 'content' => 'À analyser.']);

        $this->assertTrue($quoteRequest->service->is($service));
        $this->assertTrue($quoteRequest->assignee->is($assignee));
        $this->assertTrue($quoteRequest->attachments->contains($attachment));
        $this->assertTrue($quoteRequest->notes->contains($note));
        $this->assertTrue($service->quoteRequests->contains($quoteRequest));
        $this->assertTrue($assignee->assignedQuoteRequests->contains($quoteRequest));
        $this->assertTrue($note->user->is($assignee));
        $this->assertTrue($attachment->quoteRequest->is($quoteRequest));
    }

    public function test_new_and_open_scopes_work(): void
    {
        $new = QuoteRequest::factory()->create(['status' => QuoteRequestStatus::New]);
        $reviewing = QuoteRequest::factory()->create(['status' => QuoteRequestStatus::Reviewing]);
        $accepted = QuoteRequest::factory()->create(['status' => QuoteRequestStatus::Accepted]);

        $this->assertTrue(QuoteRequest::new()->get()->contains($new));
        $this->assertTrue(QuoteRequest::open()->get()->contains($new));
        $this->assertTrue(QuoteRequest::open()->get()->contains($reviewing));
        $this->assertFalse(QuoteRequest::open()->get()->contains($accepted));
    }

    public function test_status_transitions_set_business_dates(): void
    {
        $quoteRequest = QuoteRequest::factory()->create(['status' => QuoteRequestStatus::New]);

        $quoteRequest->transitionTo(QuoteRequestStatus::Quoted);
        $this->assertNotNull($quoteRequest->quoted_at);
        $quoteRequest->transitionTo(QuoteRequestStatus::Accepted);
        $this->assertNotNull($quoteRequest->accepted_at);
        $quoteRequest->transitionTo(QuoteRequestStatus::Closed);
        $this->assertNotNull($quoteRequest->closed_at);
    }

    public function test_public_api_creates_a_new_request_and_dispatches_notifications(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/quote-requests', $this->validPayload() + [
            'status' => QuoteRequestStatus::Accepted->value,
            'assigned_to' => User::factory()->create()->getKey(),
            'internal_notes' => 'Ne doit jamais être accepté.',
            'reference' => 'HACKED',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.first_name', 'Jean')
            ->assertJsonPath('data.status', QuoteRequestStatus::New->value)
            ->assertJsonMissingPath('data.internal_notes')
            ->assertJsonMissingPath('data.assigned_to');
        $this->assertDatabaseHas('quote_requests', ['email' => 'jean@example.com', 'status' => 'new', 'assigned_to' => null]);
        $this->assertDatabaseMissing('quote_requests', ['reference' => 'HACKED']);
        Notification::assertSentOnDemand(QuoteRequestReceived::class);
        Notification::assertSentOnDemand(NewQuoteRequestNotification::class);
    }

    public function test_api_validates_email_message_and_service(): void
    {
        Notification::fake();

        $this->postJson('/api/quote-requests', $this->validPayload(['email' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/quote-requests', $this->validPayload(['message' => 'Trop court']))->assertUnprocessable()->assertJsonValidationErrors('message');
        $this->postJson('/api/quote-requests', $this->validPayload(['service_id' => 999999]))->assertUnprocessable()->assertJsonValidationErrors('service_id');
    }

    public function test_api_rejects_too_many_or_forbidden_attachments(): void
    {
        Storage::fake('s3');
        Notification::fake();
        $files = collect(range(1, 6))->map(fn (int $index): UploadedFile => UploadedFile::fake()->create("file-{$index}.txt", 1, 'text/plain'))->all();

        $this->post('/api/quote-requests', $this->validPayload(['attachments' => $files]), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('attachments');
        $this->post('/api/quote-requests', $this->validPayload([
            'attachments' => [UploadedFile::fake()->create('script.php', 1, 'application/x-php')],
        ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('attachments.0');
    }

    public function test_valid_attachment_is_stored_privately_with_metadata(): void
    {
        Storage::fake('s3');
        Notification::fake();
        $file = UploadedFile::fake()->create('cahier-des-charges.pdf', 100, 'application/pdf');

        $this->post('/api/quote-requests', $this->validPayload(['attachments' => [$file]]), ['Accept' => 'application/json'])
            ->assertCreated();

        $attachment = QuoteRequestAttachment::query()->sole();
        Storage::disk('s3')->assertExists($attachment->path);
        $this->assertSame('cahier-des-charges.pdf', $attachment->original_name);
        $this->assertStringNotContainsString('cahier-des-charges', $attachment->path);
    }

    public function test_public_endpoint_is_throttled(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $index) {
            $this->postJson('/api/quote-requests', $this->validPayload(['email' => "client{$index}@example.com"]))->assertCreated();
        }

        $this->postJson('/api/quote-requests', $this->validPayload(['email' => 'blocked@example.com']))->assertTooManyRequests();
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Jean',
            'last_name' => 'Fokam',
            'email' => 'jean@example.com',
            'phone' => '+237600000000',
            'subject' => 'Création de site internet',
            'message' => 'Je souhaite obtenir un devis pour la création d’un site internet professionnel.',
            'preferred_contact_method' => PreferredContactMethod::WhatsApp->value,
        ], $overrides);
    }
}
