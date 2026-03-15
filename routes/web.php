<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HqProfileController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ParentController as AdminParentController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TutorController as AdminTutorController;
use App\Http\Controllers\ParentUser\BookingController as ParentBookingController;
use App\Http\Controllers\ParentUser\DashboardController as ParentDashboardController;
use App\Http\Controllers\ParentUser\PaymentController as ParentPaymentController;
use App\Http\Controllers\ParentUser\ReviewController as ParentReviewController;
use App\Http\Controllers\ParentUser\SessionController as ParentSessionController;
use App\Http\Controllers\ParentUser\StudentController;
use App\Http\Controllers\ParentUser\TutorRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TutorBrowseController;
use App\Http\Controllers\Tutor\BookingController as TutorBookingController;
use App\Http\Controllers\Tutor\DashboardController as TutorDashboardController;
use App\Http\Controllers\Tutor\EarningController;
use App\Http\Controllers\Tutor\JobController;
use App\Http\Controllers\Tutor\ProfileController as TutorProfileController;
use App\Http\Controllers\Tutor\ReviewController as TutorReviewController;
use App\Http\Controllers\Tutor\SessionController as TutorSessionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/tutors', TutorBrowseController::class)->name('tutors.browse');

Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $role = $user->role ?? null;
    return match ($role) {
        'admin' => redirect('/admin/dashboard'),
        'tutor' => redirect('/tutor/dashboard'),
        'parent' => redirect('/parent/dashboard'),
        default => Inertia::render('Dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    Route::resource('subjects', SubjectController::class);
    Route::get('/tutors', [AdminTutorController::class, 'index'])->name('tutors.index');
    Route::get('/tutors/create', [AdminTutorController::class, 'create'])->name('tutors.create');
    Route::post('/tutors', [AdminTutorController::class, 'store'])->name('tutors.store');
    Route::get('/tutors/{tutor}', [AdminTutorController::class, 'show'])->name('tutors.show');
    Route::post('/tutors/{tutor}/commission', [AdminTutorController::class, 'updateCommission'])->name('tutors.commission');
    Route::post('/tutors/{tutor}/verify', [AdminTutorController::class, 'verify'])->name('tutors.verify');

    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{tutorRequest}', [AdminRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{tutorRequest}/match', [AdminRequestController::class, 'match'])->name('requests.match');

    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');

    Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [AdminSessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/manual-check-in', [AdminSessionController::class, 'manualCheckIn'])->name('sessions.manual-check-in');
    Route::post('/sessions/{session}/manual-check-out', [AdminSessionController::class, 'manualCheckOut'])->name('sessions.manual-check-out');
    Route::post('/sessions/{session}/cancel', [AdminSessionController::class, 'cancel'])->name('sessions.cancel');

    Route::get('/parents', [AdminParentController::class, 'index'])->name('parents.index');
    Route::get('/parents/{parent}', [AdminParentController::class, 'show'])->name('parents.show');

    Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');

    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/mark-paid', [AdminPaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::post('/payments/{payment}/mark-failed', [AdminPaymentController::class, 'markFailed'])->name('payments.mark-failed');

    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/create', [AdminPayoutController::class, 'create'])->name('payouts.create');
    Route::post('/payouts', [AdminPayoutController::class, 'store'])->name('payouts.store');
    Route::get('/payouts/{payout}', [AdminPayoutController::class, 'show'])->name('payouts.show');
    Route::post('/payouts/{payout}/processing', [AdminPayoutController::class, 'markProcessing'])->name('payouts.processing');
    Route::post('/payouts/{payout}/paid', [AdminPayoutController::class, 'markPaid'])->name('payouts.paid');

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/hq-profile', [HqProfileController::class, 'index'])->name('hq-profile.index');
    Route::post('/hq-profile/company', [HqProfileController::class, 'updateCompany'])->name('hq-profile.update-company');
    Route::post('/hq-profile/password', [HqProfileController::class, 'updatePassword'])->name('hq-profile.update-password');
});

// Tutor routes
Route::middleware(['auth', 'verified', 'role:tutor'])->prefix('tutor')->name('tutor.')->group(function () {
    Route::get('/dashboard', TutorDashboardController::class)->name('dashboard');

    Route::get('/profile', [TutorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [TutorProfileController::class, 'update'])->name('profile.update');

    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{tutorRequest}', [JobController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{tutorRequest}/accept', [JobController::class, 'accept'])->name('jobs.accept');
    Route::post('/jobs/{tutorRequest}/reject', [JobController::class, 'reject'])->name('jobs.reject');

    Route::get('/bookings', [TutorBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [TutorBookingController::class, 'show'])->name('bookings.show');

    Route::get('/sessions', [TutorSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [TutorSessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/check-in', [TutorSessionController::class, 'checkIn'])->name('sessions.check-in');
    Route::post('/sessions/{session}/upload-proof', [TutorSessionController::class, 'uploadProof'])->name('sessions.upload-proof');
    Route::post('/sessions/{session}/remove-proof', [TutorSessionController::class, 'removeProof'])->name('sessions.remove-proof');
    Route::post('/sessions/{session}/check-out', [TutorSessionController::class, 'checkOut'])->name('sessions.check-out');
    Route::post('/bookings/{booking}/generate-sessions', [TutorSessionController::class, 'generateSessions'])->name('bookings.generate-sessions');

    Route::get('/earnings', [EarningController::class, 'index'])->name('earnings.index');

    Route::get('/reviews', [TutorReviewController::class, 'index'])->name('reviews.index');
});

// Parent routes
Route::middleware(['auth', 'verified', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', ParentDashboardController::class)->name('dashboard');

    Route::resource('students', StudentController::class);

    Route::get('/requests', [TutorRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [TutorRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [TutorRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{tutorRequest}', [TutorRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{tutorRequest}/cancel', [TutorRequestController::class, 'cancel'])->name('requests.cancel');

    Route::get('/bookings', [ParentBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [ParentBookingController::class, 'show'])->name('bookings.show');

    Route::get('/sessions', [ParentSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [ParentSessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/confirm', [ParentSessionController::class, 'confirm'])->name('sessions.confirm');

    Route::get('/payments', [ParentPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/callback', [ParentPaymentController::class, 'callback'])->name('payments.callback');
    Route::get('/payments/{payment}', [ParentPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/pay', [ParentPaymentController::class, 'pay'])->name('payments.pay');

    Route::get('/reviews/{booking}/create', [ParentReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews/{booking}', [ParentReviewController::class, 'store'])->name('reviews.store');
});

require __DIR__.'/auth.php';
