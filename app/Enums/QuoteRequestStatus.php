<?php

namespace App\Enums;

enum QuoteRequestStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case WaitingCustomer = 'waiting_customer';
    case Qualified = 'qualified';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::Reviewing => 'En analyse',
            self::WaitingCustomer => 'En attente du client',
            self::Qualified => 'Qualifiée',
            self::Quoted => 'Devis envoyé',
            self::Accepted => 'Acceptée',
            self::Rejected => 'Refusée',
            self::Closed => 'Clôturée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New, self::Quoted => 'info',
            self::Reviewing => 'warning',
            self::WaitingCustomer, self::Closed => 'gray',
            self::Qualified => 'primary',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}
