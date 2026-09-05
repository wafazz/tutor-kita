<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\ClassSession;
use App\Models\Subject;
use App\Models\User;
use App\Support\ScheduleConflictDetector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassSessionController extends Controller
{
    public function index()
    {
        $classes = ClassSession::with(['tutor:id,name', 'subject:id,name', 'centre:id,name,capacity', 'enrolments'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (ClassSession $c) => $this->present($c));

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $classes,
            'tutors' => User::where('role', 'tutor')->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'centres' => Centre::where('is_active', true)->orderBy('name')->get(['id', 'name', 'capacity']),
            'payoutModels' => GroupPayoutModel::options(),
            'deliveryModes' => collect(DeliveryMode::cases())
                ->filter(fn (DeliveryMode $m) => $m->isGroup())
                ->map(fn (DeliveryMode $m) => ['value' => $m->value, 'label' => $m->label()])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        if ($clash = $this->firstScheduleClash($validated)) {
            return redirect()->back()->with('error', $clash);
        }

        ClassSession::create($validated);

        return redirect()->back()->with('success', 'Class created.');
    }

    public function update(Request $request, ClassSession $class)
    {
        $validated = $this->validated($request);

        // Ignore this class when checking, or it would clash with itself.
        if ($clash = $this->firstScheduleClash($validated, $class->id)) {
            return redirect()->back()->with('error', $clash);
        }

        $class->update($validated);

        return redirect()->back()->with('success', 'Class updated.');
    }

    public function destroy(ClassSession $class)
    {
        if ($class->seatsTaken() > 0) {
            return redirect()->back()->with('error', 'This class has students enrolled — cancel it instead of deleting it.');
        }

        $class->delete();

        return redirect()->back()->with('success', 'Class removed.');
    }

    /**
     * Why this tutor cannot teach the proposed class, or null when they can.
     */
    private function firstScheduleClash(array $validated, ?int $ignoreClassId = null): ?string
    {
        $tutor = User::find($validated['tutor_id']);
        $centre = isset($validated['centre_id']) ? Centre::find($validated['centre_id']) : null;

        $conflicts = app(ScheduleConflictDetector::class)->check(
            tutorId: (int) $validated['tutor_id'],
            day: $validated['schedule_day'] ?? null,
            time: $validated['schedule_time'] ?? null,
            durationHours: (float) $validated['duration_hours'],
            mode: DeliveryMode::from($validated['delivery_mode']),
            latitude: $centre?->latitude,
            longitude: $centre?->longitude,
            ignoreClassId: $ignoreClassId,
        );

        return $conflicts->isEmpty() ? null : $conflicts->first()->message($tutor?->name ?? 'That tutor');
    }

    private function present(ClassSession $c): array
    {
        return [
            'id' => $c->id,
            'title' => $c->title,
            'tutor_id' => $c->tutor_id,
            'tutor_name' => $c->tutor?->name,
            'subject_id' => $c->subject_id,
            'subject_name' => $c->subject?->name,
            'centre_id' => $c->centre_id,
            'centre_name' => $c->centre?->name,
            'delivery_mode' => $c->delivery_mode->value,
            'schedule_day' => $c->schedule_day,
            'schedule_time' => $c->schedule_time,
            'duration_hours' => $c->duration_hours,
            'total_sessions' => $c->total_sessions,
            'capacity' => $c->capacity,
            'price_per_student' => $c->price_per_student,
            'payout_model' => $c->payout_model->value,
            'payout_base' => $c->payout_base,
            'payout_per_head' => $c->payout_per_head,
            'payout_head_threshold' => $c->payout_head_threshold,
            'status' => $c->status,
            'seats_taken' => $c->seatsTaken(),
            'seats_left' => $c->seatsLeft(),
            'revenue' => $c->revenue(),
            'tutor_payout' => $c->tutorPayoutTotal(),
            'platform_share' => $c->platformShare(),
            // A fixed payout can exceed what the students are paying; that
            // should be visible before the class runs.
            'is_underwater' => $c->isUnderwater(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'centre_id' => 'nullable|exists:centres,id',
            'delivery_mode' => 'required|in:centre_group,online_group',
            'title' => 'nullable|string|max:255',
            'schedule_day' => 'nullable|string|max:16',
            'schedule_time' => 'nullable|date_format:H:i',
            'duration_hours' => 'required|numeric|min:0.5|max:8',
            'total_sessions' => 'required|integer|min:1|max:100',
            'starts_on' => 'nullable|date',
            'capacity' => 'required|integer|min:1|max:200',
            'price_per_student' => 'required|numeric|min:0.01',
            'payout_model' => 'required|in:per_student,flat,flat_plus_head',
            'payout_base' => 'nullable|required_if:payout_model,flat,flat_plus_head|numeric|min:0',
            'payout_per_head' => 'nullable|required_if:payout_model,flat_plus_head|numeric|min:0',
            'payout_head_threshold' => 'nullable|integer|min:0|max:200',
            'status' => 'required|in:draft,open,closed,completed,cancelled',
        ]);
    }
}
