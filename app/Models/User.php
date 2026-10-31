<?php

namespace App\Models;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * role_id and status are deliberately NOT mass-assignable: they are set
     * explicitly by registration/admin services so a request can never
     * escalate its own role.
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
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'is_demo' => 'boolean',
        ];
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

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleSlug::SuperAdmin);
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
        return $this->roleSlug()?->dashboardRoute() ?? 'home';
    }
}
