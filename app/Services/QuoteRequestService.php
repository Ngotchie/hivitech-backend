<?php

namespace App\Services;

use App\Enums\QuoteRequestStatus;
use App\Models\QuoteRequest;
use App\Notifications\NewQuoteRequestNotification;
use App\Notifications\QuoteRequestReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class QuoteRequestService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function create(array $data, array $attachments = []): QuoteRequest
    {
        $storedPaths = [];

        try {
            $quoteRequest = DB::transaction(function () use ($data, $attachments, &$storedPaths): QuoteRequest {
                $quoteRequest = QuoteRequest::query()->create([
                    ...$data,
                    'status' => QuoteRequestStatus::New,
                ]);
                $quoteRequest->completeReference();

                foreach ($attachments as $attachment) {
                    $extension = strtolower($attachment->guessExtension() ?: $attachment->extension());
                    $filename = Str::uuid().($extension ? ".{$extension}" : '');
                    $path = $attachment->storeAs(
                        "quotes/{$quoteRequest->getKey()}/attachments",
                        $filename,
                        's3',
                    );

                    if (! $path) {
                        throw new RuntimeException('La pièce jointe n’a pas pu être enregistrée.');
                    }

                    $storedPaths[] = $path;
                    $quoteRequest->attachments()->create([
                        'original_name' => $attachment->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $attachment->getMimeType(),
                        'size' => $attachment->getSize(),
                    ]);
                }

                return $quoteRequest->load('service');
            });
        } catch (Throwable $exception) {
            Storage::disk('s3')->delete($storedPaths);
            throw $exception;
        }

        $this->sendNotifications($quoteRequest);

        return $quoteRequest;
    }

    public function transition(QuoteRequest $quoteRequest, QuoteRequestStatus $status): void
    {
        $quoteRequest->transitionTo($status);
    }

    protected function sendNotifications(QuoteRequest $quoteRequest): void
    {
        Notification::route('mail', $quoteRequest->email)->notify(new QuoteRequestReceived($quoteRequest));

        $internalEmail = config('hivitech.quote_notification_email');

        if (filled($internalEmail)) {
            Notification::route('mail', $internalEmail)->notify(new NewQuoteRequestNotification($quoteRequest));
        }
    }
}
