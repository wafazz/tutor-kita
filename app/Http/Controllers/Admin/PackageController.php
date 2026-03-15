<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PackageController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Packages/Index', [
            'packages' => Package::with('subjects')->orderBy('sort_order')->orderBy('name')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'package_type' => 'required|in:all,specific',
            'subject_ids' => 'required_if:package_type,specific|array',
            'subject_ids.*' => 'exists:subjects,id',
            'description' => 'nullable|string|max:1000',
            'total_sessions' => 'required|integer|min:1',
            'duration_hours' => 'required|numeric|min:0.5|max:8',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);

        $package = Package::create($validated);

        if ($validated['package_type'] === 'specific' && !empty($subjectIds)) {
            $package->subjects()->sync($subjectIds);
        }

        return redirect()->back()->with('success', 'Package created successfully.');
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'package_type' => 'required|in:all,specific',
            'subject_ids' => 'required_if:package_type,specific|array',
            'subject_ids.*' => 'exists:subjects,id',
            'description' => 'nullable|string|max:1000',
            'total_sessions' => 'required|integer|min:1',
            'duration_hours' => 'required|numeric|min:0.5|max:8',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);

        $package->update($validated);

        if ($validated['package_type'] === 'specific') {
            $package->subjects()->sync($subjectIds);
        } else {
            $package->subjects()->detach();
        }

        return redirect()->back()->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->back()->with('success', 'Package deleted successfully.');
    }
}
