<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name'            => ['required', 'string', 'max:255'],
            'site_description'     => ['nullable', 'string'],
            'contact_email'        => ['required', 'email'],
            'contact_phone'        => ['nullable', 'string'],
            'support_email'        => ['nullable', 'string'],
            'address'              => ['nullable', 'string'],
            'currency'             => ['required', 'string'],
            'tax_rate'             => ['required', 'numeric', 'min:0', 'max:100'],
            'shipping_fee'         => ['required', 'numeric', 'min:0'],
            'social_facebook'      => ['nullable', 'url'],
            'social_twitter'       => ['nullable', 'url'],
            'social_instagram'     => ['nullable', 'url'],
            'social_youtube'       => ['nullable', 'url'],
            'social_tiktok'        => ['nullable', 'url'],
            'payment_methods'      => ['nullable', 'array'],
            'order_statuses'       => ['nullable'],
            'minimum_order_amount' => ['nullable', 'numeric'],
            'first_order_bonus'    => ['nullable', 'numeric'],
            'repeat_order_bonus'   => ['nullable', 'numeric'],
            'timezone'             => ['nullable', 'timezone'],
            'company_logo'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:1024'],
            'favicon_logo'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:1024'],

            // Storage settings
            'storage_disk'         => ['nullable', 'in:public,s3'],
            's3_bucket'            => ['nullable', 'string'],
            's3_region'            => ['nullable', 'string'],
            's3_access_key'        => ['nullable', 'string'],
            's3_secret_key'        => ['nullable', 'string'],
            's3_url'               => ['nullable', 'string'],
            's3_endpoint'          => ['nullable', 'string'],
        ]);

        if ($request->hasFile('company_logo')) {
            $validated['company_logo'] = upload_image('logo', $request->company_logo);
        }

        if ($request->hasFile('favicon_logo')) {
            $validated['favicon_logo'] = upload_image('logo', $request->favicon_logo);
        }

        if (isset($validated['payment_methods'])) {
            $validated['payment_methods'] = implode(',', $validated['payment_methods']);
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        // Flush cached storage disk so helpers pick up new setting
        Cache::forget('storage_disk');

        // If switching to S3, dynamically reconfigure the S3 disk for this request
        if (($validated['storage_disk'] ?? 'public') === 's3') {
            $this->reconfigureS3($validated);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Reconfigure the S3 filesystem disk at runtime using settings from the DB.
     * This lets admins switch storage via the dashboard without touching .env.
     */
    public static function reconfigureS3(array $settings = []): void
    {
        if (empty($settings)) {
            $settings = Setting::whereIn('key', [
                's3_bucket', 's3_region', 's3_access_key', 's3_secret_key', 's3_url', 's3_endpoint',
            ])->pluck('value', 'key')->toArray();
        }

        $config = [
            'driver'   => 's3',
            'key'      => $settings['s3_access_key']  ?? env('AWS_ACCESS_KEY_ID', ''),
            'secret'   => $settings['s3_secret_key']  ?? env('AWS_SECRET_ACCESS_KEY', ''),
            'region'   => $settings['s3_region']      ?? env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket'   => $settings['s3_bucket']      ?? env('AWS_BUCKET', ''),
            'url'      => $settings['s3_url']         ?? env('AWS_URL', ''),
            'endpoint' => !empty($settings['s3_endpoint']) ? $settings['s3_endpoint'] : null,
            'use_path_style_endpoint' => !empty($settings['s3_endpoint']),
        ];

        Config::set('filesystems.disks.s3', $config);
    }
}
