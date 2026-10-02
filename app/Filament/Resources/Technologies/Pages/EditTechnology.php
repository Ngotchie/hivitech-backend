<?php

namespace App\Filament\Resources\Technologies\Pages;

use App\Filament\Resources\Technologies\TechnologyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTechnology extends EditRecord
{
    protected static string $resource = TechnologyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->portfolioProjects()->exists())
                ->tooltip(fn (): ?string => $this->record->portfolioProjects()->exists()
                    ? 'Cette technologie est associée à des réalisations.'
                    : null),
        ];
    }
}
