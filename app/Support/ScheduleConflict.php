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

    public function message(string $tutorName): string
    {
        if ($this->kind === 'travel') {
            return "{$tutorName} teaches {$this->what} at {$this->when} and needs about "
                ."{$this->travelMinutes} minutes to travel between the two — the earliest realistic start is {$this->earliestStart}.";
        }

        return "{$tutorName} already teaches {$this->what} at {$this->when}.";
    }
}
