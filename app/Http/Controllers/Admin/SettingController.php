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
                'site_name' => Setting::get('site_name', 'TutorHUB'),
                'bayarcash_api_key' => Setting::get('bayarcash_api_key', ''),
                'bayarcash_secret_key' => Setting::get('bayarcash_secret_key', ''),
                'bayarcash_portal_key' => Setting::get('bayarcash_portal_key', ''),
                'bayarcash_sandbox' => Setting::get('bayarcash_sandbox', '1'),
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
            'site_name' => 'required|string|max:255',
            'bayarcash_api_key' => 'nullable|string|max:1000',
            'bayarcash_secret_key' => 'nullable|string|max:1000',
            'bayarcash_portal_key' => 'nullable|string|max:1000',
            'bayarcash_sandbox' => 'required|in:0,1',
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
