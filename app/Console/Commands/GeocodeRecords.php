<?php

namespace App\Console\Commands;

use App\Models\Centre;
use App\Models\Student;
use App\Models\TutorProfile;
use App\Support\Geocoding\Drivers\ManualGeocoder;
use App\Support\Geocoding\GeocoderManager;
use Illuminate\Console\Command;

/**
 * Place existing records on the map.
 *
 * Addresses were added after the records existed, so nothing has coordinates
 * until this runs — and without them radius matching returns nobody.
 */
class GeocodeRecords extends Command
{
    protected $signature = 'geocode:backfill
        {--model= : students, tutors or centres; all three by default}
        {--force : re-resolve records that already have coordinates}
        {--limit=500 : most records to resolve in one run}
        {--dry-run : report what would be resolved without saving}';

    protected $description = 'Resolve addresses to coordinates for existing students, tutors and centres';

    public function handle(GeocoderManager $geocoder): int
    {
        if ($geocoder->driver() instanceof ManualGeocoder) {
            $this->warn('Geocoding driver is "manual" — addresses are expected to be supplied directly, so there is nothing to resolve.');
            $this->line('Set the driver in Admin → Settings (postcode or google) and run this again.');

            return self::SUCCESS;
        }

        $this->info("Using the '{$geocoder->name()}' driver.");

        $targets = [
            'students' => Student::query(),
            'tutors' => TutorProfile::query(),
            'centres' => Centre::query(),
        ];

        if ($only = $this->option('model')) {
            if (! isset($targets[$only])) {
                $this->error("Unknown model '{$only}'. Expected one of: ".implode(', ', array_keys($targets)));

                return self::FAILURE;
            }

            $targets = [$only => $targets[$only]];
        }

        $force = (bool) $this->option('force');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $totalResolved = 0;
        $totalSkipped = 0;

        foreach ($targets as $label => $query) {
            if (! $force) {
                $query->whereNull('latitude')->orWhereNull('longitude');
            }

            $records = $query->limit($limit)->get();

            if ($records->isEmpty()) {
                $this->line("  {$label}: nothing to do");

                continue;
            }

            $resolved = 0;
            $skipped = 0;

            foreach ($records as $record) {
                if ($geocoder->applyTo($record, force: $force)) {
                    $dryRun || $record->save();
                    $resolved++;
                } else {
                    $skipped++;
                }
            }

            $this->line("  {$label}: {$resolved} resolved, {$skipped} could not be placed");
            $totalResolved += $resolved;
            $totalSkipped += $skipped;
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry run] ' : '')."{$totalResolved} resolved, {$totalSkipped} left unplaced.");

        if ($totalSkipped > 0) {
            $this->line('Unplaced records simply do not appear in radius results — they are not matched incorrectly.');
        }

        return self::SUCCESS;
    }
}
