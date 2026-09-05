<?php

namespace Database\Seeders;

use App\Models\Postcode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads the Malaysian postcode directory from database/data/postcodes.csv.
 *
 * Upserts rather than inserts, so running it again refreshes the directory
 * without duplicating it or discarding coordinates added since.
 */
class PostcodeSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/postcodes.csv');

        if (! is_readable($path)) {
            $this->command?->warn("Postcode directory not found at {$path} — skipping.");

            return;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return;
        }

        $rows = [];
        $now = now();

        while (($line = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $line);

            $postcode = trim($row['postcode'] ?? '');
            $city = trim($row['city'] ?? '');
            $state = trim($row['state'] ?? '');

            if ($postcode === '' || $city === '' || $state === '') {
                continue;
            }

            $rows[] = [
                'postcode' => $postcode,
                'city' => $city,
                'state' => $state,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        // Chunked so a few thousand rows do not build one enormous statement.
        // Coordinates are deliberately not in the update list: the directory
        // has none, and re-seeding must not wipe any that were added later.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('postcodes')->upsert($chunk, ['postcode', 'city'], ['state', 'updated_at']);
        }

        $this->command?->info(count($rows).' postcodes loaded ('.Postcode::distinct('postcode')->count('postcode').' distinct).');
    }
}
