<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('description')
                                    ->maxLength(1000),
                            ])->columnSpanFull(),
                    ])->columnSpanFull(),
                Section::make('Permissions')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->relationship(
                                name: 'permissions',
                                titleAttribute: 'action',
                                modifyQueryUsing: fn($query) => $query
                                    ->with('module')
                                    ->orderBy('module_id')
                                    ->orderBy('action')
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn(Permission $permission): string => ($permission->module?->name ? $permission->module->name . ' - ' : '') . $permission->action
                            )
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(4)
                            ->gridDirection('row'),
                    ])->columnSpanFull(),
            ]);
    }
}
