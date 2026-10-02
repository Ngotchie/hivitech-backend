<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Nous avons bien reçu votre message')->greeting("Bonjour {$this->contactMessage->name},")
            ->line("Votre message concernant « {$this->contactMessage->subject} » a bien été reçu.")
            ->line('Merci d’avoir contacté Hivitech. Notre équipe reviendra vers vous.')->salutation('L’équipe Hivitech');
    }
}
