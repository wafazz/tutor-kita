<?php

namespace App\Support;

use App\Models\TutorRequest;
use App\Models\User;

/**
 * Whether a tutor may take a request at all, kept separate from how good a
 * match they are.
 *
 * A tutor who fails a mandatory requirement is excluded, not ranked lower.
 * Anything softer is reported as a warning so an admin can weigh it, because
 * the alternative — quietly ranking a disqualified tutor near the bottom — is
 * how someone ends up assigned to a subject they do not teach.
 *
 * Which requirements are mandatory is a deliberate policy, stated here rather
 * than left implicit:
 *
 *   MANDATORY  verification, subject, weekday availability
 *   MANDATORY  gender, when the parent asked and the tutor's is known
 *   WARNING    budget, because the platform sets the price, not the tutor
 *   WARNING    gender, when the parent asked and the tutor's is unknown
 */
class TutorEligibility
{
    /**
     * @return array{eligible: bool, blockers: array<int, string>, warnings: array<int, string>}
     */
    public function assess(User $tutor, TutorRequest $request): array
    {
        $profile = $tutor->tutorProfile;
        $blockers = [];
        $warnings = [];

        if (! $profile || $profile->verification_status !== 'verified') {
            $blockers[] = 'not a verified tutor';
        }

        if (! $this->teachesSubject($profile?->subjects, $request->subject?->name)) {
            $blockers[] = 'does not teach '.($request->subject?->name ?? 'this subject');
        }

        if ($day = $this->requestedDay($request)) {
            if (! $this->availableOn($profile?->availability, $day)) {
                $blockers[] = 'not available on '.ucfirst($day);
            }
        }

        $wanted = $request->preferred_tutor_gender;

        if (filled($wanted) && $wanted !== 'any') {
            if (blank($tutor->gender)) {
                $warnings[] = 'gender not recorded, so the preference cannot be checked';
            } elseif ($tutor->gender !== $wanted) {
                $blockers[] = 'parent asked for a '.$wanted.' tutor';
            }
        }

        if ($over = $this->overBudget($profile?->hourly_rate, $request)) {
            $warnings[] = $over;
        }

        return [
            'eligible' => $blockers === [],
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    /**
     * Subjects are stored as names rather than ids, so matching is by name,
     * trimmed and case-insensitive. A tutor with nothing recorded teaches
     * nothing as far as matching is concerned — silence is not consent.
     */
    private function teachesSubject(?array $taught, ?string $subject): bool
    {
        if (blank($subject) || blank($taught)) {
            return false;
        }

        $normalise = fn ($v) => mb_strtolower(trim((string) $v));

        return in_array($normalise($subject), array_map($normalise, $taught), true);
    }

    /**
     * Availability is recorded as weekdays only, so this is a day-level check.
     * It cannot tell whether a tutor is free at 14:00 on a Saturday they work,
     * which is what the schedule conflict detector is for.
     */
    private function availableOn(?array $availability, string $day): bool
    {
        // Nothing recorded is treated as no constraint rather than as refusing
        // every day, which would exclude every tutor who has not filled it in.
        if (blank($availability)) {
            return true;
        }

        $days = array_map(fn ($d) => mb_strtolower(trim((string) $d)), $availability);

        return in_array(mb_strtolower($day), $days, true);
    }

    private function requestedDay(TutorRequest $request): ?string
    {
        return filled($request->schedule_day)
            ? mb_strtolower($request->schedule_day)
            : (filled($request->preferred_schedule) ? mb_strtolower($request->preferred_schedule) : null);
    }

    /**
     * Budget is a warning, not a bar.
     *
     * The platform prices lessons from the subject's rate, so a tutor's own
     * hourly_rate is indicative rather than what the parent will be charged.
     * Excluding on it would hide tutors over a budget they may not even cost.
     */
    private function overBudget($rate, TutorRequest $request): ?string
    {
        $max = $request->budget_max;

        if (blank($max) || blank($rate) || (float) $rate <= (float) $max) {
            return null;
        }

        return sprintf('usual rate RM%s is above the parent\'s budget of RM%s', number_format((float) $rate, 2), number_format((float) $max, 2));
    }
}
