<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

/**
 * Represents a sign-in account and its clinic roles, profile links, and effective permission rules.
 */
#[Fillable(['name', 'email', 'phone', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token', 'avatar_path'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** @var array<int, string>|null */
    private ?array $userPermissionOverrides = null;

    /** @var array<int, true>|null */
    private ?array $rolePermissionDenials = null;

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Defines the doctor association as an Eloquent one-to-one link to the Doctor model.
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Defines the patient association as an Eloquent one-to-one link to the Patient model.
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Applies per-user allow/deny overrides and role permissions before returning the access decision.
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $wildcardClass = $this->getWildcardClass();

        try {
            $permission = $this->filterPermission($permission, $guardName);
        } catch (PermissionDoesNotExist $exception) {
            if ($wildcardClass !== '') {
                return $this->hasWildcardPermission($permission, $guardName);
            }

            throw $exception;
        }

        $permissionId = (int) $permission->getKey();
        $this->loadCustomPermissionRules();

        if (array_key_exists($permissionId, $this->userPermissionOverrides ?? [])) {
            return $this->userPermissionOverrides[$permissionId] === 'allow';
        }

        if (isset($this->rolePermissionDenials[$permissionId])) {
            return false;
        }

        if ($wildcardClass !== '') {
            return $this->hasWildcardPermission($permission, $guardName);
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Clears this instance?s cached permission overrides after its access rules change.
     */
    public function forgetCustomPermissionRules(): void
    {
        $this->userPermissionOverrides = null;
        $this->rolePermissionDenials = null;
    }

    /**
     * Returns a versioned profile-photo URL or the shared default avatar when no photo is stored.
     */
    public function avatarUrl(): string
    {
        return $this->avatar_path !== null
            ? route('profile.photo', ['v' => substr(hash('sha256', $this->avatar_path), 0, 12)])
            : asset('images/avatar-default.svg');
    }

    /**
     * Loads user overrides and role denials once, caching them for subsequent permission checks.
     */
    private function loadCustomPermissionRules(): void
    {
        if ($this->userPermissionOverrides !== null && $this->rolePermissionDenials !== null) {
            return;
        }

        $this->userPermissionOverrides = DB::table('user_permission_overrides')
            ->where('user_id', $this->getKey())
            ->pluck('effect', 'permission_id')
            ->mapWithKeys(fn (string $effect, int|string $permissionId): array => [(int) $permissionId => $effect])
            ->all();

        $roleIds = $this->loadMissing('roles')->roles->modelKeys();

        $this->rolePermissionDenials = $roleIds === []
            ? []
            : DB::table('role_permission_denials')
                ->whereIn('role_id', $roleIds)
                ->pluck('permission_id')
                ->mapWithKeys(fn (int|string $permissionId): array => [(int) $permissionId => true])
                ->all();
    }
}
