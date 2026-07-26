<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Setting;


class SettingsController extends Controller
{
    public function index()
    {
        return Setting::pluck('value', 'key');
    }

    public function store(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }
}
