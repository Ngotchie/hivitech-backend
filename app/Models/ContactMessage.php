<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'status', 'assigned_to', 'internal_notes', 'read_at', 'replied_at', 'closed_at'];

    protected function casts(): array
    {
        return ['status' => ContactMessageStatus::class, 'read_at' => 'datetime', 'replied_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (ContactMessage $message) => $message->status ??= ContactMessageStatus::New);
        static::updating(function (ContactMessage $message): void {
            if (! $message->isDirty('status')) {
                return;
            }
            match ($message->status) {
                ContactMessageStatus::Read => $message->read_at ??= now(),
                ContactMessageStatus::Replied => $message->replied_at ??= now(),
                ContactMessageStatus::Closed => $message->closed_at ??= now(),
                default => null,
            };
        });
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function transitionTo(ContactMessageStatus $status): void
    {
        $this->update(['status' => $status]);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', ContactMessageStatus::New);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [ContactMessageStatus::Closed, ContactMessageStatus::Spam]);
    }

    public function scopeAssignedTo(Builder $query, User|int $user): Builder
    {
        return $query->where('assigned_to', $user instanceof User ? $user->getKey() : $user);
    }
}
