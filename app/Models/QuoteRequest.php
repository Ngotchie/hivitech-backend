<?php

namespace App\Models;

use App\Enums\PreferredContactMethod;
use App\Enums\QuoteRequestStatus;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'company_name', 'email', 'phone', 'service_id', 'subject',
        'message', 'budget', 'desired_deadline', 'preferred_contact_method', 'status', 'assigned_to',
        'internal_notes', 'contacted_at', 'quoted_at', 'accepted_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteRequestStatus::class,
            'preferred_contact_method' => PreferredContactMethod::class,
            'budget' => 'decimal:2',
            'desired_deadline' => 'date',
            'contacted_at' => 'datetime',
            'quoted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteRequest $quoteRequest): void {
            $quoteRequest->reference ??= 'PENDING-'.Str::uuid();
            $quoteRequest->status ??= QuoteRequestStatus::New;
        });

        static::created(function (QuoteRequest $quoteRequest): void {
            $quoteRequest->completeReference();
        });

        static::updating(function (QuoteRequest $quoteRequest): void {
            if (! $quoteRequest->isDirty('status')) {
                return;
            }

            match ($quoteRequest->status) {
                QuoteRequestStatus::Quoted => $quoteRequest->quoted_at ??= now(),
                QuoteRequestStatus::Accepted => $quoteRequest->accepted_at ??= now(),
                QuoteRequestStatus::Closed => $quoteRequest->closed_at ??= now(),
                default => null,
            };
        });
    }

    public function completeReference(): void
    {
        if (! str_starts_with($this->reference, 'PENDING-')) {
            return;
        }

        $year = $this->created_at?->format('Y') ?? now()->format('Y');
        $this->reference = sprintf('DEV-%s-%05d', $year, $this->getKey());
        $this->saveQuietly();
    }

    public function transitionTo(QuoteRequestStatus $status): void
    {
        $this->update(['status' => $status]);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<QuoteRequestAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(QuoteRequestAttachment::class);
    }

    /** @return HasMany<QuoteRequestNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(QuoteRequestNote::class)->latest();
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', QuoteRequestStatus::New);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            QuoteRequestStatus::Accepted,
            QuoteRequestStatus::Rejected,
            QuoteRequestStatus::Closed,
        ]);
    }

    public function scopeAssignedTo(Builder $query, User|int $user): Builder
    {
        return $query->where('assigned_to', $user instanceof User ? $user->getKey() : $user);
    }

    public function scopeForService(Builder $query, Service|int $service): Builder
    {
        return $query->where('service_id', $service instanceof Service ? $service->getKey() : $service);
    }
}
