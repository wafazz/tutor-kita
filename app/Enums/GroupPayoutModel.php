<?php

namespace App\Enums;

/**
 * How a tutor is paid for a group class.
 *
 * Held per class rather than platform-wide so the choice can differ between a
 * trial class and a full-size one, and so moving between models is data rather
 * than a deployment.
 */
enum GroupPayoutModel: string
{
    /** Each student's payment is split as usual; the tutor earns more as the group fills. */
    case PerStudent = 'per_student';

    /** A fixed amount per session, whatever the headcount; the platform keeps the rest. */
    case Flat = 'flat';

    /** A guaranteed floor, plus an amount for each student past a threshold. */
    case FlatPlusHead = 'flat_plus_head';

    public function label(): string
    {
        return match ($this) {
            self::PerStudent => 'Per student enrolled',
            self::Flat => 'Flat rate per class',
            self::FlatPlusHead => 'Flat rate plus a per-head bonus',
        };
    }

    /** Whether the tutor's earnings follow the students' payments directly. */
    public function followsEnrolmentRevenue(): bool
    {
        return $this === self::PerStudent;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $m) => ['value' => $m->value, 'label' => $m->label()], self::cases());
    }
}
