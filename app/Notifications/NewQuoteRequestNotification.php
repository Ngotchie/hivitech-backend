<?php

namespace App\Notifications;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteRequestNotification extends Notification implements ShouldQueue
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
        $quoteRequest = $this->quoteRequest;
        $mail = (new MailMessage)
            ->subject("Nouvelle demande de devis – {$quoteRequest->reference}")
            ->line("Référence : {$quoteRequest->reference}")
            ->line("Demandeur : {$quoteRequest->first_name} {$quoteRequest->last_name}")
            ->line('Entreprise : '.($quoteRequest->company_name ?: 'Non renseignée'))
            ->line('Service : '.($quoteRequest->service?->name ?: 'Non renseigné'))
            ->line("E-mail : {$quoteRequest->email}")
            ->line('Téléphone : '.($quoteRequest->phone ?: 'Non renseigné'))
            ->line('Reçue le : '.$quoteRequest->created_at?->format('d/m/Y H:i'));

        if ($quoteRequest->exists) {
            $mail->action('Voir dans Filament', QuoteRequestResource::getUrl('edit', ['record' => $quoteRequest]));
        }

        return $mail->salutation('Hivitech');
    }
}
