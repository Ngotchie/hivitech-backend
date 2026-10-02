<?php

namespace App\Notifications;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contactMessage;

        return (new MailMessage)->subject("Nouveau message de contact – {$message->subject}")
            ->line("Nom : {$message->name}")->line("E-mail : {$message->email}")
            ->line('Téléphone : '.($message->phone ?: 'Non renseigné'))->line("Sujet : {$message->subject}")
            ->line($message->message)->line('Reçu le : '.$message->created_at?->format('d/m/Y H:i'))
            ->action('Voir dans Filament', ContactMessageResource::getUrl('edit', ['record' => $message]));
    }
}
