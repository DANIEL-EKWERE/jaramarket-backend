<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum UserPermissionsEnum: string
{
    use InvokableCases, Values, Names;

    case ADMIN          = 'admin';
    case SUPER_ADMIN    = 'super_admin';
    case STATE_ADMIN    = 'state_admin';
    case VENDOR_MANAGER = 'vendor_manager';
    case ACCOUNTS       = 'accounts';
    case AUDIT          = 'audit';
    case LOGISTICS      = 'logistics';
    case VENDOR         = 'vendor';
    case CUSTOMER       = 'customer';
    case QA             = 'qa';
    case ACCOUNT        = 'account';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN, self::SUPER_ADMIN => 'Super Admin',
            self::STATE_ADMIN    => 'State Admin',
            self::VENDOR_MANAGER => 'Vendor Manager',
            self::ACCOUNTS       => 'Accounts',
            self::AUDIT          => 'Audit',
            self::LOGISTICS      => 'Logistics',
            self::VENDOR         => 'Vendor',
            self::CUSTOMER       => 'Customer',
            self::QA             => 'Quality Assurance',
            self::ACCOUNT        => 'Account',
        };
    }

    public function isAdminRole(): bool
    {
        return in_array($this, [
            self::ADMIN, self::SUPER_ADMIN, self::STATE_ADMIN,
            self::VENDOR_MANAGER, self::ACCOUNTS, self::AUDIT, self::LOGISTICS,
        ]);
    }

    public static function adminRoles(): array
    {
        return [self::SUPER_ADMIN(), self::ADMIN(), self::STATE_ADMIN(),
            self::VENDOR_MANAGER(), self::ACCOUNTS(), self::AUDIT(), self::LOGISTICS()];
    }

    public function defaultPermissions(): array
    {
        return match ($this) {
            self::ADMIN, self::SUPER_ADMIN => self::allPermissionSlugs(),
            self::STATE_ADMIN => [
                'view_dashboard','view_orders','manage_orders','view_users','manage_users',
                'view_vendors','manage_vendors','view_transactions','view_reports',
                'view_logistics','view_categories','view_products','view_ingredients',
            ],
            self::VENDOR_MANAGER => [
                'view_dashboard','view_vendors','manage_vendors','view_orders','manage_orders',
                'view_reports','view_logistics','view_categories','view_products','view_ingredients',
            ],
            self::ACCOUNTS => [
                'view_dashboard','view_transactions','view_wallets','manage_withdrawals','view_reports',
            ],
            self::AUDIT => ['view_transactions','view_wallets','view_reports','view_orders'],
            self::LOGISTICS => ['view_orders','manage_orders','view_logistics'],
            default => [],
        };
    }

    public static function allPermissionSlugs(): array
    {
        return [
            'view_dashboard','view_orders','manage_orders','view_users','manage_users',
            'view_vendors','manage_vendors','view_transactions','manage_transactions',
            'view_wallets','manage_withdrawals','view_reports','view_logistics',
            'manage_admins','manage_roles','manage_settings','view_categories','manage_categories',
            'view_products','manage_products','view_ingredients','manage_ingredients',
            'view_commissions','manage_commissions',
        ];
    }
}
