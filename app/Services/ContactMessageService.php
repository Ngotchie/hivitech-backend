<?php

namespace App\Services;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ContactMessageService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): ContactMessage
    {
        $message = DB::transaction(fn (): ContactMessage => ContactMessage::query()->create([...$data, 'status' => ContactMessageStatus::New]));
        Notification::route('mail', $message->email)->notify(new ContactMessageReceived($message));
        if (filled($email = config('hivitech.contact_notification_email'))) {
            Notification::route('mail', $email)->notify(new NewContactMessageNotification($message));
        }

        return $message;
    }
}
