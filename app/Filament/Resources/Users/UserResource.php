<?php

namespace App\Filament\Resources\Users;

use App\Audit\Audit;
use App\Auth\PermissionMatrix;
use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\StaffAccessLink;
use App\Models\User;
use App\Policies\UserPolicy;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * Staff accounts (Super Admin only). Nobody types a password for someone else: new staff get an emailed link to set
 * their own. The last active Super Admin cannot be removed, demoted or switched off, and nobody can do that to
 * themselves, so the dashboard can never be locked out by mistake. Roles come from App\Auth\PermissionMatrix.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Staff member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Staff and permissions');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    /** @return array<string, string> */
    public static function roleOptions(): array
    {
        return collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => __($r->label())])->all();
    }

    /** True if changing this account's role or status would lock someone out. */
    public static function isProtected(?User $record): bool
    {
        return $record && (auth()->user()?->is($record) || UserPolicy::isLastSuperAdmin($record));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make(__('Account'))->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(120),
                TextInput::make('email')->label(__('Email'))->required()->email()->maxLength(190)->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
                Select::make('role')->label(__('Role'))->required()->native(false)->options(fn () => self::roleOptions())
                    ->disabled(fn (?User $record) => self::isProtected($record))->dehydrated(fn (?User $record) => ! self::isProtected($record))
                    ->helperText(fn (?User $record) => self::isProtected($record) ? __('You cannot change your own role, or that of the only Super Admin.') : __('See “Roles and permissions” on the list page for what each role can do.')),
                Select::make('admin_locale')->label(__('Dashboard language'))->required()->native(false)->default('ar')->options(['ar' => 'العربية', 'en' => 'English']),
                Toggle::make('is_active')->label(__('Can sign in'))->default(true)
                    ->disabled(fn (?User $record) => self::isProtected($record))->dehydrated(fn (?User $record) => ! self::isProtected($record))
                    ->helperText(__('Switch off to block access while keeping the account and its history.')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable()->description(fn (User $u) => $u->email),
                TextColumn::make('role')->label(__('Role'))->badge()->state(fn (User $u) => $u->roles->map(fn ($r) => __(Role::tryFrom($r->name)?->label() ?? $r->name))->all()),
                IconColumn::make('is_active')->label(__('Can sign in'))->boolean(),
                IconColumn::make('mfa')->label(__('Two-step verification'))->boolean()->state(fn (User $u) => filled($u->app_authentication_secret)),
                TextColumn::make('last_login_at')->label(__('Last sign-in'))->since()->sortable()->placeholder(__('Never')),
            ])
            ->filters([
                SelectFilter::make('role')->label(__('Role'))->options(fn () => self::roleOptions())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($x, $v) => $x->role($v))),
                TernaryFilter::make('is_active')->label(__('Can sign in')),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('sendLink')->label(__('Send password link'))->icon('heroicon-o-envelope')->color('gray')
                        ->requiresConfirmation()->modalDescription(fn (User $r) => __('Emails :email a link to choose a new password.', ['email' => $r->email]))
                        ->action(function (User $record) {
                            self::sendAccessLink($record, 'password_link')
                                ? Notification::make()->success()->title(__('Link sent to :email', ['email' => $record->email]))->send()
                                : Notification::make()->danger()->title(__('Sending failed'))->send();
                        }),
                    Action::make('resetMfa')->label(__('Reset two-step'))->icon('heroicon-o-shield-exclamation')->color('warning')
                        ->visible(fn (User $r) => filled($r->app_authentication_secret) && ! auth()->user()?->is($r))
                        ->requiresConfirmation()->modalDescription(__('For someone who lost their phone. They set it up again at their next sign-in.'))
                        ->action(function (User $record) {
                            abort_if(auth()->user()?->is($record), 403);
                            $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->saveQuietly();
                            Audit::record('mfa_reset', $record);
                            Notification::make()->success()->title(__('Two-step verification reset'))->send();
                        }),
                    DeleteAction::make()->visible(fn (User $r) => ! self::isProtected($r))
                        ->before(fn (User $record) => abort_if(self::isProtected($record), 403)),
                ])->label(__('More'))->color('gray'),
            ]);
    }

    /** Emails a time-limited link to the dashboard's own "choose a password" page. Never exposes the token elsewhere. */
    public static function sendAccessLink(User $user, string $event): bool
    {
        try {
            $url = Filament::getResetPasswordUrl(Password::broker()->createToken($user), $user);
            Mail::to($user->email, $user->name)->send(new StaffAccessLink($user, $url, $event === 'invited'));
            Audit::record($event, $user);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Translated role × permission table for the "Roles and permissions" window. @return array<string, mixed> */
    public static function matrix(): array
    {
        return ['roles' => self::roleOptions(), 'permissions' => collect(PermissionMatrix::all())->map(fn (array $roles, string $name) => [
            'label' => __("permission.{$name}"),
            'roles' => array_map(fn (Role $r) => $r->value, $roles),
        ])->all()];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
