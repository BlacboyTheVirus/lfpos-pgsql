<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('info')
                        ->infolist(\App\Filament\Resources\Users\Schemas\UserInfolist::getInfolistComponents()),

                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('warning')
                        ->schema(\App\Filament\Resources\Users\Schemas\UserForm::getFormComponents()),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete User')
                        ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->modalHeading('Delete Users')
                        ->modalDescription('This action will permanently delete the selected users. This cannot be undone.')
                        ->modalSubmitActionLabel('Delete Users')
                        ->modalWidth('sm')
                        ->modalAlignment('center')
                        ->form([
                            TextInput::make('confirmation')
                                ->label('Type "DELETE" to confirm')
                                ->placeholder('DELETE')
                                ->required()
                                ->autocomplete(false)
                                ->rules(['in:DELETE'])
                                ->validationMessages([
                                    'in' => 'You must type "DELETE" exactly to confirm deletion.',
                                ])
                                ->helperText('This action cannot be undone. Type "DELETE" to confirm.')
                                ->autofocus(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            if ($data['confirmation'] !== 'DELETE') {
                                return;
                            }
                            $records->each(function ($record) {
                                $record->delete();
                            });
                            \Filament\Notifications\Notification::make()
                                ->title('Users Deleted')
                                ->body(count($records).' user(s) have been deleted successfully.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
