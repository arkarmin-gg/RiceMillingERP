<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->before(function ($action, $record) {
                    if ($record->stockBalances()->sum('quantity') > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete item')
                            ->body('This item has existing stock quantity.')
                            ->send();

                        $action->cancel();
                    }
                }),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
