<?php

namespace App\Support;

/** A single reason a tutor cannot take a proposed slot. */
final class ScheduleConflict
{
    public function __construct(
        public readonly string $kind,        // 'overlap' or 'travel'
        public readonly string $what,        // the existing commitment
        public readonly string $when,        // its time range
        public readonly ?int $travelMinutes = null,
        public readonly ?string $earliestStart = null,
    ) {}

    /**
     * The same clash reads differently depending on whose diary it is: a tutor
     * teaches a lesson, a student attends one.
     */
    public function message(string $name, bool $isTutor = true): string
    {
        $present = $isTutor ? 'teaches' : 'has';
        $already = $isTutor ? 'already teaches' : 'already has';

        if ($this->kind === 'travel') {
            return "{$name} {$present} {$this->what} at {$this->when} and needs about "
                ."{$this->travelMinutes} minutes to travel between the two — the earliest realistic start is {$this->earliestStart}.";
        }

        return "{$name} {$already} {$this->what} at {$this->when}.";
    }

    /** Phrased for the student rather than the tutor. */
    public function messageForStudent(string $name): string
    {
        return $this->message($name, isTutor: false);
    }
}
