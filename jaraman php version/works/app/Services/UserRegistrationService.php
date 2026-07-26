<?php
namespace App\Services;

use App\Models\BankAccount;
use App\Models\Bank;
use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Models\User_otp;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Notifications\OtpNotification;
use App\Notifications\UserCreatedNotification;

class UserRegistrationService
{
    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            $referralCode = $data['referral_code'] ?? null;

            $existing_user = User::where('email', $data['email'])->whereNull('deleted_at')->first();

            if ($existing_user) {
                $existing_user->update(['role' => 'vendor']);
                $this->sendOtp($existing_user->email);
                return $existing_user;
            }

            $user = User::create([
                'firstname'     => $data['firstname'],
                'lastname'      => $data['lastname'],
                'email'         => $data['email'],
                'password'      => $data['password'],
                'role'          => $data['role'],
                'phone_number'  => $data['phone_number'],
                'referral_code' => Str::random(10),
            ]);

            Wallet::create(['user_id' => $user->id]);

            if ($referralCode) {
                $this->handleReferral($user, $referralCode);
            }

            // Queue the OTP email
            $this->sendOtp($user->email);
            return $user;
        });
    }

    public function sendOtp($email)
    {
        $otp = rand(1000, 9999);

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new Exception('No user record found', 404);
        }

        // Delete any previous OTP records for this user
        User_otp::where('email', $user->email)->delete();

        // Create a new OTP record
        User_otp::create([
            'otp'   => $otp,
            'email' => $user->email,
        ]);

        $user->notify(new OtpNotification($otp));
        return $user;
    }

    protected function handleReferral(User $user, string $referralCode): void
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer) return;

        $referrerWallet = Wallet::firstOrCreate(['user_id' => $referrer->id]);

        $referrerWallet->increment('balance', config('app.referral_bonus'));

        $user->update(['referrer_id' => $referrer->id]);
    }

    public function validateOTP(string $email, string $otp)
    {
        return DB::transaction(function () use ($email, $otp) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                throw new Exception('No user record found', 404);
            }
            $otpRecord = User_otp::where('email', $email)
                ->where('otp', $otp)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->first();

            if (!$otpRecord) {
                throw new Exception('Invalid OTP or OTP has expired', 400);
            }
            $otpRecord->delete();
            return $user;
        });
    }

    public function validateEmail($user)
    {
        return DB::transaction(function () use ($user) {
            
            if (!$user) {
                throw new Exception('No user record found', 404);
            }
            $user->update(['email_verified_at' => now(), 'is_active' => 1]);          
            $user->notify(new UserCreatedNotification());

            return $user;
        });
    }

    public function updateProfile($request, $email = null): User
    {
        $profile = User::where('email', $email)->first();
        $user = $profile ?? auth()->user();

        $data = $request->only([
            'business_name',
            'business_address',
            'phone_number',
            'shop_size',
            'country_id',
            'latitude',
            'longitude',
            'firstname',
            'lastname',
            'payment_method',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = upload_image("Users", $request->profile_picture, $request);
            $data['profile_picture'] = $path;
        }

        $this->updateOrCreateBankAccount($user, $request);

        $user->update($data);
        return $user;
    }

    protected function updateOrCreateBankAccount(User $user, $request): void
    {
        if (! ($request->filled('bank_id') && $request->filled('account_number'))) {
            return;
        }

        $bank = Bank::find($request->bank_id);

        if (! $bank) {
            return;
        }

        BankAccount::updateOrCreate(
            [
                'owner_id'   => $user->id,
                'owner_type' => User::class,
            ],
            [
                'bank_name'      => $bank->name,
                'bank_code'      => $bank->code,
                'account_number' => $request->account_number,
                'account_name'   => $user->account_name,
            ]
        );
    }
}