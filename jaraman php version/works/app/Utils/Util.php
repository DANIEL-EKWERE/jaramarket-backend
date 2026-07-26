<?php

namespace App\Utils;

use App\Models\Commission;
use App\Models\Setting;
use App\Models\Order;
use App\Models\Country;
use App\Models\PaymentLog;
use Illuminate\Support\Str;
use App\Enums\Prefix\PrefixEnum;
use Illuminate\Support\Facades\DB;

class Util
{ 
    public static function generate_sub_txn_ref()
    {
        $generated_id = PrefixEnum::DEPOSIT_TXN().mt_rand(1000000000, 999999999999).time();
        $subscription = PaymentLog::where('txn_ref', $generated_id)->exists();

        while ($subscription) {
            self::generate_sub_txn_ref();
        }

        return $generated_id;
    }
    
    public static function generate_code()
    {
        $generated_code = Str::random(5);

        $user = DB::table('users')->where('referral_code', $generated_code)->exists();

        while ($user) {
            self::generate_code();
        }

        return $generated_code;
    }

    public static function convert_to_kobo($amount)
    {
        return $amount * 100;
    }

    public static function convert_to_naira($amount)
    {
        return $amount / 100;
    }

    public static function get_amount_by_currency($amount, $currency)
    {
        if ($currency === 'NGN') {
            return self::convert_to_naira($amount);
        }

        return $amount;
    }

    public static function convert_amount_by_currency($amount, $currency)
    {
        if ($currency === 'NGN') {
            return self::convert_to_kobo($amount);
        }

        return $amount;
    }

    public static function getModifiedEmail(string $email): string
    {
        [$username, $domain] = explode('@', $email);

        return "{$username}+1@{$domain}";
    }

    public static function isEmailModified(string $email): bool
    {
        [$username, $domain] = explode('@', $email);

        // Check if '+1' is present in the username
        return Str::endsWith($username, '+1');
    }

    public static function getOriginalEmail(string $modifiedEmail): string
    {
        [$username, $domain] = explode('@', $modifiedEmail);

        // Assuming the modified email always has a '+1' before the '@'
        $originalUsername = rtrim($username, '+1');

        return "{$originalUsername}@{$domain}";
    }

    public static function VAT(float $amount): float
    {
        $setting = Setting::where('key', 'tax_rate')->first();
        $vat_rate = $setting->value;
    
        if ($vat_rate < 0) {
            return 0;
        }
    
        return $amount * ($vat_rate / 100);
    }

    public static function generate_order_txn_ref(): string
    {
        $generated_id = PrefixEnum::ORDER_REF().mt_rand(100000, 999999).time();
        $order = Order::where('reference', $generated_id)->exists();

        while ($order) {
            self::generate_order_txn_ref();
        }

        return $generated_id;
    }

    public static function getCommission(float $amount, float $total): array
    {
        $threshold = Setting::where('key', 'minimum_order_amount')->first();
        $minOrderAmount = (float) ($threshold?->value ?? 0);

        // If total order is not above threshold, no commission
        if ($total <= $minOrderAmount) {
            return [
                'percentage' => 0,
                'commission' => 0,
            ];
        }

        // Fetch the lowest slab for edge case handling
        $lowestSlab = Commission::query()
            ->orderBy('min_amount', 'asc')
            ->first();

        // If amount is less than the lowest min_amount → use lowest slab percentage
        if ($lowestSlab && $amount < $lowestSlab->min_amount) {
            $percentage = $lowestSlab->percentage;
            $commission_fee = round(($amount * $percentage) / 100, 2);

            return [
                'percentage' => $percentage,
                'commission' => $commission_fee,
            ];
        }
    
         // Normal commission lookup
            $commission = Commission::query()
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')
                ->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->first();

        $percentage = $commission?->percentage ?? 0;
        $commission_fee = round(($amount * $percentage) / 100, 2);

        return [
            'percentage' => $percentage,
            'commission' => $commission_fee,
        ];
    }
}
