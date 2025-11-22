<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Product code copied'),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->description(fn ($record) => $record->description ? \Illuminate\Support\Str::limit($record->description, 50) : null),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('minimum_amount')
                    ->label('Minimum Amount')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->updated_at->format('M j, Y g:i A'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All products')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                SelectFilter::make('created_by')
                    ->label('Created By')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('info')
                        ->infolist(\App\Filament\Resources\Products\Schemas\ProductInfolist::getInfolistComponents()),

                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('warning')
                        ->form(\App\Filament\Resources\Products\Schemas\ProductForm::getFormComponents())
                        ->using(function ($record, array $data, EditAction $action): Product {
                            try {
                                $record->update($data);

                                return $record;
                            } catch (UniqueConstraintViolationException $e) {
                                // Send error notification
                                Notification::make()
                                    ->title('Product name already exists')
                                    ->body('A product with this name already exists (case-insensitive match). Please use a different name.')
                                    ->danger()
                                    ->send();

                                // Halt the action to keep the modal open
                                $action->halt();
                            }
                        }),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('gray')
                        ->form(\App\Filament\Resources\Products\Schemas\ProductForm::getFormComponents())
                        ->fillForm(fn ($record) => [
                            'name' => $record->name.' (Copy)',
                            'unit' => $record->unit,
                            'price' => $record->price,
                            'minimum_amount' => $record->minimum_amount,
                            'description' => $record->description,
                            'is_active' => $record->is_active,
                        ])
                        ->action(function (array $data, Action $action) {
                            try {
                                Product::create($data);
                            } catch (UniqueConstraintViolationException $e) {
                                // Send error notification
                                Notification::make()
                                    ->title('Product already exists')
                                    ->body('A product with this name already exists (case-insensitive match). Please use a different name.')
                                    ->danger()
                                    ->send();

                                // Halt the action to keep the modal open
                                $action->halt();
                            }
                        })
                        ->successNotificationTitle('Product duplicated successfully'),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Product')
                        ->modalDescription('Are you sure you want to delete this product? This action cannot be undone.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->modalHeading('Delete Products')
                        ->modalDescription('This action will permanently delete the selected products. This cannot be undone.')
                        ->modalSubmitActionLabel('Delete Products')
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
                                ->title('Products Deleted')
                                ->body(count($records).' product(s) have been deleted successfully.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
