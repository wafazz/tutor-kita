<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'commission_rate' => Setting::get('commission_rate', '20'),
                'geocoding_driver' => Setting::get('geocoding_driver', 'manual'),
                'google_maps_api_key' => Setting::get('google_maps_api_key', ''),
                'site_name' => Setting::get('site_name', 'TutorHUB'),
                'billplz_api_key' => Setting::get('billplz_api_key', ''),
                'billplz_collection_id' => Setting::get('billplz_collection_id', ''),
                'billplz_x_signature_key' => Setting::get('billplz_x_signature_key', ''),
                'billplz_sandbox' => Setting::get('billplz_sandbox', '1'),
                'payments_manual_mode' => Setting::get('payments_manual_mode', '0'),
                'resend_api_key' => Setting::get('resend_api_key', ''),
                'resend_from_email' => Setting::get('resend_from_email', 'noreply@tutorhub.my'),
                'resend_from_name' => Setting::get('resend_from_name', 'TutorHUB'),
                'onsend_api_key' => Setting::get('onsend_api_key', ''),
                'onsend_sender_id' => Setting::get('onsend_sender_id', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
            'geocoding_driver' => 'required|in:manual,postcode,google',
            'google_maps_api_key' => 'nullable|string|max:255',
            'site_name' => 'required|string|max:255',
            'billplz_api_key' => 'nullable|string|max:1000',
            'billplz_collection_id' => 'nullable|string|max:1000',
            'billplz_x_signature_key' => 'nullable|string|max:1000',
            'billplz_sandbox' => 'required|in:0,1',
            'payments_manual_mode' => 'required|in:0,1',
            'resend_api_key' => 'nullable|string|max:255',
            'resend_from_email' => 'nullable|string|email|max:255',
            'resend_from_name' => 'nullable|string|max:255',
            'onsend_api_key' => 'nullable|string|max:255',
            'onsend_sender_id' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
