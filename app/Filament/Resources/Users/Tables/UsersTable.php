<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Throwable;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Ruolo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Ruolo')
                    ->options(UserRole::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->modalWidth(Width::Small),

                    Action::make('sendAccessLink')
                        ->label('Invia link di accesso')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Invia link di accesso')
                        ->modalDescription("Verrà inviato un link di accesso all'indirizzo email dell'utente.")
                        ->action(function (User $record): void {
                            $url = app(MagicLinkService::class)->issueLink($record);

                            $record->notify(new MagicLinkNotification($url));

                            Notification::make()
                                ->success()
                                ->title('Link inviato')
                                ->body("Link di accesso inviato a {$record->email}")
                                ->send();
                        }),

                    DeleteAction::make()
                        ->modalDescription(fn (User $record): ?string => self::multiTenantRemovalWarning($record))
                        ->using(fn (User $record): bool => self::removeUser($record)),
                ])
                    ->icon(Heroicon::OutlinedEllipsisVertical),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                            $records->each(function (User $record) use ($action): void {
                                try {
                                    self::removeUser($record) || $action->reportBulkProcessingFailure();
                                } catch (Throwable $exception) {
                                    $action->reportBulkProcessingFailure();

                                    report($exception);
                                }
                            });
                        }),
                ]),
            ])
            ->emptyStateHeading('Nessun utente trovato')
            ->emptyStateDescription('Prova a rimuovere i filtri oppure creane uno nuovo.')
            ->emptyStateIcon(UserResource::getNavigationIcon());
    }

    private static function multiTenantRemovalWarning(User $record): ?string
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant || ! $record->hasTenantsOtherThan($tenant)) {
            return null;
        }

        return "L'utente appartiene anche ad altri saloni: verrà rimossa solo l'appartenenza a questo salone, mentre l'accesso agli altri saloni resterà attivo.";
    }

    /**
     * Detaches the user from the current tenant when they still belong to
     * other tenants, deleting the account only when no other tenant remains.
     */
    private static function removeUser(User $record): bool
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Tenant && $record->hasTenantsOtherThan($tenant)) {
            $record->tenants()->detach($tenant);

            return true;
        }

        return (bool) $record->delete();
    }
}
