<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name', modifyQueryUsing: fn ($query) => $query->whereNot('name', 'super_admin')->whereNot('name', 'panel_user'))
                    ->saveRelationshipsUsing(function (Select $component, $state, $record) {
                        // Get the selected role IDs
                        $roles = collect($state);

                        // Add panel_user role
                        $panelUserRole = \Spatie\Permission\Models\Role::where('name', 'panel_user')->first();
                        if ($panelUserRole && !$roles->contains($panelUserRole->id)) {
                            $roles->push($panelUserRole->id);
                        }

                        // Sync all roles
                        $record->roles()->sync($roles->toArray());
                    })
                    ->multiple()
                    ->maxItems(1)
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At'),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255)
                    ->same('password_confirmation')
                    ->validationAttribute('password')
                    ->placeholder('Leave blank to keep current password'),
                TextInput::make('password_confirmation')
                    ->password()
                    ->dehydrated(false)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255)
                    ->placeholder('Confirm your password')
                    ->label('Password Confirmation'),
            ]);
    }

    public static function getFormComponents(): array
    {
        return static::configure(Schema::make())->getComponents();
    }
}
