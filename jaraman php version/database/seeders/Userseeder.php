<?php

namespace Database\Seeders;

use App\Enums\UserPermissionsEnum;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class Userseeder extends Seeder
{
    public function run()
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@jaramarket.com'],
            [
                'password'          => 'Admin@123',
                'firstname'         => 'Super',
                'lastname'          => 'Admin',
                'phone_number'      => '08000000000',
                'role'              => UserPermissionsEnum::SUPER_ADMIN(),
                'email_verified_at' => now(),
                'is_active'         => true,
                'referral_code'     => Str::random(10),
            ]
        );
        Wallet::firstOrCreate(['user_id' => $user->id]);

        // Legacy admin (keeps old email working)
        $legacy = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'password'          => 'admin',
                'firstname'         => 'admin',
                'lastname'          => 'admin',
                'phone_number'      => '07068628887',
                'role'              => UserPermissionsEnum::ADMIN(),
                'email_verified_at' => now(),
                'is_active'         => true,
                'referral_code'     => Str::random(10),
            ]
        );
        Wallet::firstOrCreate(['user_id' => $legacy->id]);
    }
}
