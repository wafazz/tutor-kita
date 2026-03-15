<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class HqProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/HqProfile/Index', [
            'hq' => [
                'company_name' => Setting::get('company_name', ''),
                'ssm_number' => Setting::get('ssm_number', ''),
                'ssm_details' => Setting::get('ssm_details', ''),
                'address' => Setting::get('hq_address', ''),
                'phone' => Setting::get('hq_phone', ''),
                'contact_email' => Setting::get('hq_contact_email', ''),
            ],
        ]);
    }

    public function updateCompany(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'ssm_number' => 'nullable|string|max:50',
            'ssm_details' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
        ]);

        Setting::set('company_name', $validated['company_name']);
        Setting::set('ssm_number', $validated['ssm_number'] ?? '');
        Setting::set('ssm_details', $validated['ssm_details'] ?? '');
        Setting::set('hq_address', $validated['address'] ?? '');
        Setting::set('hq_phone', $validated['phone'] ?? '');
        Setting::set('hq_contact_email', $validated['contact_email'] ?? '');

        return redirect()->back()->with('success', 'Company profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('password_success', 'Password updated successfully.');
    }
}
