<?php

namespace App\Models;

use App\Enums\UserPermissionsEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'firstname','lastname','password','email','role',
        'referral_code','referrer_id','referral_count','email_verified_at',
        'phone_number','is_active','pin','last_login','profile_picture',
        'country_id','business_name','business_address','shop_size',
        'latitude','longitude','bank_name','bank_code','receipient_code',
        'account_number','account_name','payment_method','state_id','lga_id','fcm_token','is_verified',
    ];

    protected $hidden = ['password','remember_token','pin'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'is_verified'       => 'boolean',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => trim("{$this->firstname} {$this->lastname}"));
    }

    // ── Role checks ───────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return UserPermissionsEnum::from($this->role)->isAdminRole();
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->role, [UserPermissionsEnum::SUPER_ADMIN(), UserPermissionsEnum::ADMIN()]);
    }

    public function isStateAdmin(): bool    { return $this->role === UserPermissionsEnum::STATE_ADMIN(); }
    public function isVendorManager(): bool { return $this->role === UserPermissionsEnum::VENDOR_MANAGER(); }
    public function isAccounts(): bool      { return $this->role === UserPermissionsEnum::ACCOUNTS(); }
    public function isAudit(): bool         { return $this->role === UserPermissionsEnum::AUDIT(); }
    public function isLogistics(): bool     { return $this->role === UserPermissionsEnum::LOGISTICS(); }
    public function isVendor(): bool        { return $this->role === UserPermissionsEnum::VENDOR(); }
    public function isCustomer(): bool      { return $this->role === UserPermissionsEnum::CUSTOMER(); }

    // ── Permission checks ─────────────────────────────────────────────

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) return true;
        return $this->userPermissions->contains('slug', $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) return true;
        }
        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (!$this->hasPermission($perm)) return false;
        }
        return true;
    }

    public function syncDefaultPermissions(): void
    {
        $slugs   = UserPermissionsEnum::from($this->role)->defaultPermissions();
        $permIds = Permission::whereIn('slug', $slugs)->pluck('id');
        $this->userPermissions()->sync($permIds);
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function userPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')->withTimestamps();
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function referrer()
    {
        return $this->belongsTo(self::class, 'referrer_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function bankAccounts(): MorphMany
    {
        return $this->morphMany(BankAccount::class, 'owner');
    }

    public function transfers()
    {
        return $this->morphMany(Transfer::class, 'owner');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', UserPermissionsEnum::adminRoles());
    }

    public function scopeVendors($query)
    {
        return $query->where('role', UserPermissionsEnum::VENDOR());
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', UserPermissionsEnum::CUSTOMER());
    }

    public function scopeInState($query, ?int $stateId)
    {
        return $stateId ? $query->where('state_id', $stateId) : $query;
    }
}
