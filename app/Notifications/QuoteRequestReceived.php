<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public QuoteRequest $quoteRequest) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Votre demande de devis Hivitech – {$this->quoteRequest->reference}")
            ->greeting("Bonjour {$this->quoteRequest->first_name},")
            ->line('Merci d’avoir contacté Hivitech.')
            ->line("Votre demande de devis a bien été reçue sous la référence {$this->quoteRequest->reference}.");

        if ($this->quoteRequest->service) {
            $mail->line("Service concerné : {$this->quoteRequest->service->name}.");
        }

        return $mail
            ->line('Notre équipe étudiera votre demande et reviendra vers vous.')
            ->salutation('L’équipe Hivitech');
    }
}
