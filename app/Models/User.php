<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Notifications\EmailVerificationCode;
use App\Services\Auth\OneTimePasswords;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * role_id, type and status are deliberately NOT mass-assignable: they are
     * set explicitly by registration/admin services (assignRole()) so a request
     * can never escalate its own role.
     */
    protected $fillable = ['name', 'email', 'phone', 'password'];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    /** @var array<string,bool>|null per-request permission cache */
    protected ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'type' => UserType::class,
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'is_demo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The type picks the portal and the role picks the permissions, so they must agree.
        static::saving(function (User $user) {
            if (! $user->isDirty(['role_id', 'type'])) {
                return;
            }

            $expected = Role::find($user->role_id)?->slugEnum()?->userType();

            if ($expected === null) {
                return; // unknown role_id: the foreign key rejects it
            }

            $user->type ??= $expected;

            if ($user->type !== $expected) {
                throw new LogicException("A {$user->type->value} user cannot have a {$expected->value} role.");
            }
        });
    }

    public function assignRole(RoleSlug $role): static
    {
        $this->role_id = Role::forSlug($role)->id;
        $this->type = $role->userType();
        $this->unsetRelation('role');
        $this->flushPermissionCache();

        return $this;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function oneTimePasswords(): HasMany
    {
        return $this->hasMany(OneTimePassword::class);
    }

    public function roleSlug(): ?RoleSlug
    {
        return $this->role ? RoleSlug::tryFrom($this->role->slug) : null;
    }

    public function hasRole(RoleSlug|string ...$roles): bool
    {
        $mine = $this->role?->slug;

        foreach ($roles as $role) {
            if ($mine === ($role instanceof RoleSlug ? $role->value : $role)) {
                return true;
            }
        }

        return false;
    }

    public function isOwner(): bool
    {
        return $this->type === UserType::Admin && $this->hasRole(RoleSlug::Owner);
    }

    public function isStaff(): bool
    {
        return $this->type === UserType::Admin;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = $this->role
                ? $this->role->permissions()->pluck('slug')->flip()->map(fn () => true)->all()
                : [];
        }

        return isset($this->permissionCache[$permission]);
    }

    public function flushPermissionCache(): void
    {
        $this->permissionCache = null;
    }

    public function dashboardRoute(): string
    {
        return $this->type?->dashboardRoute() ?? 'home';
    }

    /** Email is verified with a 6-digit code instead of a signed link. */
    public function sendEmailVerificationNotification(): void
    {
        $code = app(OneTimePasswords::class)->issue($this, OtpPurpose::EmailVerification);

        $this->notify(new EmailVerificationCode($code, OneTimePasswords::TTL_MINUTES));
    }
}
