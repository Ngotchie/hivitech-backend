<?php

namespace Tests\Feature\Foundation;

use App\Enums\ContactMessageStatus;
use App\Enums\PageStatus;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Setting;
use App\Notifications\ContactMessageReceived;
use App\Notifications\NewContactMessageNotification;
use App\Services\SettingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FoundationModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_slugs_are_automatic_and_unique(): void
    {
        $first = Page::factory()->create(['title' => 'À propos', 'slug' => null]);
        $second = Page::factory()->create(['title' => 'À propos', 'slug' => null]);
        $this->assertSame('a-propos', $first->slug);
        $this->assertSame('a-propos-2', $second->slug);
    }

    public function test_page_published_scope_excludes_draft_archived_and_future_pages(): void
    {
        $published = Page::factory()->published()->create();
        $draft = Page::factory()->draft()->create();
        $archived = Page::factory()->create(['status' => PageStatus::Archived]);
        $future = Page::factory()->create(['status' => PageStatus::Published, 'published_at' => now()->addDay()]);
        $pages = Page::published()->get();
        $this->assertTrue($pages->contains($published));
        $this->assertFalse($pages->contains($draft));
        $this->assertFalse($pages->contains($archived));
        $this->assertFalse($pages->contains($future));
    }

    public function test_page_api_only_exposes_published_pages_and_expected_fields(): void
    {
        $published = Page::factory()->published()->create();
        $draft = Page::factory()->draft()->create();
        $archived = Page::factory()->create(['status' => PageStatus::Archived]);
        $future = Page::factory()->create(['status' => PageStatus::Published, 'published_at' => now()->addDay()]);
        $this->getJson("/api/pages/{$published->slug}")->assertOk()->assertJsonPath('data.slug', $published->slug)
            ->assertJsonMissingPath('data.status')->assertJsonMissingPath('data.sort_order');
        $this->getJson("/api/pages/{$draft->slug}")->assertNotFound();
        $this->getJson("/api/pages/{$archived->slug}")->assertNotFound();
        $this->getJson("/api/pages/{$future->slug}")->assertNotFound();
    }

    public function test_setting_key_is_unique_and_public_scope_works(): void
    {
        Setting::query()->create(['key' => 'public_key', 'value' => 'visible', 'group' => 'general', 'is_public' => true]);
        Setting::query()->create(['key' => 'private_key', 'value' => 'secret', 'group' => 'internal', 'is_public' => false]);
        $this->assertSame(['public_key'], Setting::public()->pluck('key')->all());
        $this->expectException(UniqueConstraintViolationException::class);
        Setting::query()->create(['key' => 'public_key']);
    }

    public function test_settings_api_is_grouped_and_never_exposes_private_settings(): void
    {
        Setting::query()->create(['key' => 'company_name', 'value' => 'Hivitech', 'group' => 'company', 'is_public' => true]);
        Setting::query()->create(['key' => 'secret', 'value' => 'private', 'group' => 'internal', 'is_public' => false]);
        $this->getJson('/api/settings')->assertOk()->assertJsonPath('company.company_name', 'Hivitech')
            ->assertJsonMissingPath('internal.secret')->assertJsonMissing(['id' => 1]);
    }

    public function test_settings_cache_is_used_and_invalidated_after_update(): void
    {
        $setting = Setting::query()->create(['key' => 'company_name', 'value' => 'Hivitech', 'group' => 'company', 'is_public' => true]);
        $service = app(SettingService::class);
        $this->assertSame('Hivitech', $service->publicSettings()['company']['company_name']);
        $setting->update(['value' => 'Hivitech SARL']);
        $this->assertSame('Hivitech SARL', $service->publicSettings()['company']['company_name']);
    }

    public function test_contact_api_creates_new_message_ignores_admin_fields_and_notifies(): void
    {
        Notification::fake();
        $response = $this->postJson('/api/contact', $this->contactPayload() + [
            'status' => 'closed', 'assigned_to' => 999, 'internal_notes' => 'Injecté', 'closed_at' => now()->toISOString(),
        ]);
        $response->assertCreated()->assertJson(['message' => 'Votre message a bien été envoyé.']);
        $this->assertDatabaseHas('contact_messages', ['email' => 'jean@example.com', 'status' => 'new', 'assigned_to' => null, 'internal_notes' => null]);
        Notification::assertSentOnDemand(ContactMessageReceived::class);
        Notification::assertSentOnDemand(NewContactMessageNotification::class);
    }

    public function test_contact_api_validates_email_subject_and_message(): void
    {
        Notification::fake();
        $this->postJson('/api/contact', $this->contactPayload(['email' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/contact', $this->contactPayload(['subject' => '']))->assertUnprocessable()->assertJsonValidationErrors('subject');
        $this->postJson('/api/contact', $this->contactPayload(['message' => 'court']))->assertUnprocessable()->assertJsonValidationErrors('message');
    }

    public function test_contact_status_transitions_set_dates_and_scopes_work(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);
        $closed = ContactMessage::factory()->create(['status' => ContactMessageStatus::Closed]);
        $this->assertTrue(ContactMessage::new()->get()->contains($message));
        $this->assertTrue(ContactMessage::open()->get()->contains($message));
        $this->assertFalse(ContactMessage::open()->get()->contains($closed));
        $message->transitionTo(ContactMessageStatus::Read);
        $this->assertNotNull($message->read_at);
        $message->transitionTo(ContactMessageStatus::Replied);
        $this->assertNotNull($message->replied_at);
        $message->transitionTo(ContactMessageStatus::Closed);
        $this->assertNotNull($message->closed_at);
    }

    public function test_contact_endpoint_is_throttled(): void
    {
        Notification::fake();
        foreach (range(1, 5) as $index) {
            $this->postJson('/api/contact', $this->contactPayload(['email' => "client{$index}@example.com"]))->assertCreated();
        }
        $this->postJson('/api/contact', $this->contactPayload(['email' => 'blocked@example.com']))->assertTooManyRequests();
    }

    /** @param array<string, mixed> $overrides */
    private function contactPayload(array $overrides = []): array
    {
        return array_replace(['name' => 'Jean Fokam', 'email' => 'jean@example.com', 'phone' => '+237600000000',
            'subject' => 'Demande d’information', 'message' => 'Bonjour, je souhaiterais obtenir plus d’informations sur vos services.'], $overrides);
    }
}
