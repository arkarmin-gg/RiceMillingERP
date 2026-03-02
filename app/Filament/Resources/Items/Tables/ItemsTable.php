<?php

namespace App\Filament\Resources\Items\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withSum('stockBalances', 'quantity'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('stock_balances_sum_quantity')
                    ->label('Total Quantity')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                TextColumn::make('unit')
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($action, $records) {
                            foreach ($records as $record) {
                                if ($record->stockBalances()->sum('quantity') > 0) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Cannot delete items')
                                        ->body('One or more items have existing stock quantity.')
                                        ->send();

                                    $action->cancel();
                                }
                            }
                        }),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
