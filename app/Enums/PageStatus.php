<?php

namespace App\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon', self::Published => 'Publiée', self::Archived => 'Archivée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray', self::Published => 'success', self::Archived => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}
