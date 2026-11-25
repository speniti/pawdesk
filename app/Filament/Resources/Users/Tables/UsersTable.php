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
use Illuminate\Support\Facades\DB;
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
                        ->using(function (User $record, DeleteAction $action): bool {
                            $wasSharedWithOtherTenants = self::isSharedWithOtherTenants($record);

                            if (! self::removeUser($record)) {
                                return false;
                            }

                            if ($wasSharedWithOtherTenants) {
                                $action->successNotificationTitle('Rimosso da questo salone');
                            }

                            return true;
                        }),
                ])
                    ->icon(Heroicon::OutlinedEllipsisVertical),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords()
                        ->modalDescription(function (EloquentCollection|Collection|LazyCollection $records): ?string {
                            $sharedCount = $records
                                ->filter(fn (User $record): bool => self::isSharedWithOtherTenants($record))
                                ->count();

                            if ($sharedCount === 0) {
                                return null;
                            }

                            return trans_choice(
                                ':count utente selezionato appartiene anche ad altri saloni: verrà rimosso solo da questo salone e manterrà l\'accesso agli altri saloni.|:count utenti selezionati appartengono anche ad altri saloni: verranno rimossi solo da questo salone e manterranno l\'accesso agli altri saloni.',
                                $sharedCount,
                            );
                        })
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

    private static function isSharedWithOtherTenants(User $record): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Tenant && $record->hasTenantsOtherThan($tenant);
    }

    private static function multiTenantRemovalWarning(User $record): ?string
    {
        if (! self::isSharedWithOtherTenants($record)) {
            return null;
        }

        return "L'utente appartiene anche ad altri saloni: verrà rimossa solo l'appartenenza a questo salone, mentre l'accesso agli altri saloni resterà attivo.";
    }

    /**
     * Detaches the user from the current tenant, then deletes the account
     * only if no other tenant remains. The user row is locked for the
     * transaction's duration so concurrent membership changes cannot
     * interleave with the delete decision.
     */
    private static function removeUser(User $record): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return DB::transaction(function () use ($record, $tenant): bool {
            $user = User::query()->whereKey($record->getKey())->lockForUpdate()->first();

            if ($user === null) {
                return false;
            }

            $user->tenants()->detach($tenant);

            if ($user->tenants()->exists()) {
                return true;
            }

            return (bool) $user->delete();
        });
    }
}
