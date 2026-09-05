<?php

namespace App\Enums;

/**
 * How a lesson is delivered.
 *
 * Deliberately a PHP enum backed by a plain string column rather than a database
 * enum: adding a mode later is a code change and a rate row, not a migration and
 * a table rebuild.
 */
enum DeliveryMode: string
{
    case HomeStudent = 'home_student';   // tutor travels to the student
    case HomeTutor = 'home_tutor';       // student travels to the tutor
    case CentreGroup = 'centre_group';   // students travel to a centre, group class
    case OnlineSolo = 'online_solo';     // one to one, remote
    case OnlineGroup = 'online_group';   // group class, remote

    public function label(): string
    {
        return match ($this) {
            self::HomeStudent => "Tutor travels to student's home",
            self::HomeTutor => "Student travels to tutor's home",
            self::CentreGroup => 'Group class at a centre',
            self::OnlineSolo => 'Online, one to one',
            self::OnlineGroup => 'Online group class',
        };
    }

    /** Several students share one session, so the rate is charged per student. */
    public function isGroup(): bool
    {
        return in_array($this, [self::CentreGroup, self::OnlineGroup], true);
    }

    public function isOnline(): bool
    {
        return in_array($this, [self::OnlineSolo, self::OnlineGroup], true);
    }

    /** Whether matching has to consider how far someone travels. */
    public function needsGeo(): bool
    {
        return ! $this->isOnline();
    }

    /** Who covers the distance — decides which address the radius is measured from. */
    public function traveller(): ?string
    {
        return match ($this) {
            self::HomeStudent => 'tutor',
            self::HomeTutor, self::CentreGroup => 'student',
            default => null,
        };
    }

    /**
     * Mode to price against when this one has no rate of its own, so a partly
     * configured subject still prices rather than falling to zero.
     */
    public function pricingFallback(): ?self
    {
        return match ($this) {
            self::HomeTutor, self::CentreGroup => self::HomeStudent,
            self::OnlineGroup => self::OnlineSolo,
            // An unpriced online rate must not fall to zero — every subject
            // shipped with hourly_rate_online = 0, which charged the parent
            // nothing and earned the tutor nothing.
            self::OnlineSolo => self::HomeStudent,
            self::HomeStudent => null,
        };
    }

    /** Legacy two-column rates, for reading existing data. */
    public function legacyRateColumn(): string
    {
        return $this->isOnline() ? 'hourly_rate_online' : 'hourly_rate_home';
    }

    /** @return array<int, array{value: string, label: string, group: bool, online: bool}> */
    public static function options(): array
    {
        return array_map(fn (self $m) => [
            'value' => $m->value,
            'label' => $m->label(),
            'group' => $m->isGroup(),
            'online' => $m->isOnline(),
        ], self::cases());
    }
}
