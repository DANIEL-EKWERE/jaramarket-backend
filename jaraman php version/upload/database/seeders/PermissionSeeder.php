<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name'=>'View Dashboard',       'slug'=>'view_dashboard',       'group'=>'dashboard'],
            ['name'=>'View Orders',          'slug'=>'view_orders',          'group'=>'orders'],
            ['name'=>'Manage Orders',        'slug'=>'manage_orders',        'group'=>'orders'],
            ['name'=>'View Users',           'slug'=>'view_users',           'group'=>'users'],
            ['name'=>'Manage Users',         'slug'=>'manage_users',         'group'=>'users'],
            ['name'=>'View Vendors',         'slug'=>'view_vendors',         'group'=>'vendors'],
            ['name'=>'Manage Vendors',       'slug'=>'manage_vendors',       'group'=>'vendors'],
            ['name'=>'View Transactions',    'slug'=>'view_transactions',    'group'=>'finance'],
            ['name'=>'Manage Transactions',  'slug'=>'manage_transactions',  'group'=>'finance'],
            ['name'=>'View Wallets',         'slug'=>'view_wallets',         'group'=>'finance'],
            ['name'=>'Manage Withdrawals',   'slug'=>'manage_withdrawals',   'group'=>'finance'],
            ['name'=>'View Commissions',     'slug'=>'view_commissions',     'group'=>'finance'],
            ['name'=>'Manage Commissions',   'slug'=>'manage_commissions',   'group'=>'finance'],
            ['name'=>'View Reports',         'slug'=>'view_reports',         'group'=>'reports'],
            ['name'=>'View Logistics',       'slug'=>'view_logistics',       'group'=>'logistics'],
            ['name'=>'Manage Admins',        'slug'=>'manage_admins',        'group'=>'admin'],
            ['name'=>'Manage Roles',         'slug'=>'manage_roles',         'group'=>'admin'],
            ['name'=>'Manage Settings',      'slug'=>'manage_settings',      'group'=>'admin'],
            ['name'=>'View Categories',      'slug'=>'view_categories',      'group'=>'catalogue'],
            ['name'=>'Manage Categories',    'slug'=>'manage_categories',    'group'=>'catalogue'],
            ['name'=>'View Products',        'slug'=>'view_products',        'group'=>'catalogue'],
            ['name'=>'Manage Products',      'slug'=>'manage_products',      'group'=>'catalogue'],
            ['name'=>'View Ingredients',     'slug'=>'view_ingredients',     'group'=>'catalogue'],
            ['name'=>'Manage Ingredients',   'slug'=>'manage_ingredients',   'group'=>'catalogue'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['slug' => $perm['slug']], $perm);
        }

        // Give super admin all permissions
        $superAdmin = User::whereIn('role', ['admin', 'super_admin'])->first();
        if ($superAdmin) {
            $superAdmin->syncDefaultPermissions();
        }
    }
}
