<?php

namespace Tests\Feature;

use App\Models\Postcode;
use App\Models\Setting;
use App\Support\Geocoding\GeocoderManager;
use Database\Seeders\PostcodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Malaysian postcode directory maps a postcode to a city and state. It
 * carries no coordinates, so it cannot place anyone on a map — and must not
 * pretend to.
 */
class PostcodeDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function loadDirectory(): void
    {
        $this->seed(PostcodeSeeder::class);
    }

    public function test_the_directory_loads_and_covers_the_whole_country(): void
    {
        $this->loadDirectory();

        $this->assertGreaterThan(2900, Postcode::count());

        // Thirteen states and three federal territories.
        $this->assertSame(16, Postcode::distinct('state')->count('state'));

        // Both halves of Malaysia, not just the peninsula.
        foreach (['Sabah', 'Sarawak', 'W.P. Kuala Lumpur', 'Perlis', 'Johor'] as $state) {
            $this->assertTrue(Postcode::where('state', $state)->exists(), "{$state} is missing");
        }
    }

    public function test_a_known_postcode_resolves_to_its_city_and_state(): void
    {
        $this->loadDirectory();

        $this->assertSame('Kangar', Postcode::lookup('01000')?->city);
        $this->assertSame('Perlis', Postcode::lookup('01000')?->state);
    }

    public function test_a_postcode_spanning_two_cities_keeps_both(): void
    {
        $this->loadDirectory();

        // 40160 genuinely serves Shah Alam and Sungai Buloh.
        $cities = Postcode::where('postcode', '40160')->orderBy('city')->pluck('city');

        $this->assertCount(2, $cities);
        $this->assertSame(['Shah Alam', 'Sungai Buloh'], $cities->all());
    }

    public function test_states_are_stored_without_stray_whitespace(): void
    {
        $this->loadDirectory();

        foreach (Postcode::distinct('state')->pluck('state') as $state) {
            $this->assertSame(trim($state), $state, "'{$state}' has stray whitespace");
        }
    }

    public function test_seeding_twice_neither_duplicates_nor_grows_the_table(): void
    {
        $this->loadDirectory();
        $before = Postcode::count();

        $this->loadDirectory();

        $this->assertSame($before, Postcode::count());
    }

    public function test_re_seeding_does_not_discard_coordinates_added_later(): void
    {
        $this->loadDirectory();

        // A coordinate obtained from somewhere else must survive a refresh of
        // the directory, which carries none of its own.
        Postcode::where('postcode', '01000')->update(['latitude' => 6.4414, 'longitude' => 100.1986]);

        $this->loadDirectory();

        $entry = Postcode::lookup('01000');
        $this->assertEqualsWithDelta(6.4414, $entry->latitude, 0.0001);
    }

    public function test_the_directory_alone_cannot_place_anyone_on_a_map(): void
    {
        $this->loadDirectory();
        Setting::set('geocoding_driver', 'postcode');

        // Every row is coordinate-less, so this must resolve nothing rather
        // than inventing a point.
        $this->assertSame(0, Postcode::whereNotNull('latitude')->count());
        $this->assertNull((new GeocoderManager)->geocode('1 Jalan Test, 01000 Kangar'));
    }

    public function test_it_resolves_once_a_postcode_has_coordinates(): void
    {
        $this->loadDirectory();
        Setting::set('geocoding_driver', 'postcode');

        Postcode::where('postcode', '46000')->update(['latitude' => 3.1073, 'longitude' => 101.6067]);

        $result = (new GeocoderManager)->geocode('12 Jalan Test, 46000 Petaling Jaya');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(3.1073, $result->latitude, 0.001);
        $this->assertSame('postcode', $result->source);
    }

    public function test_an_unknown_postcode_resolves_to_nothing(): void
    {
        $this->loadDirectory();
        Setting::set('geocoding_driver', 'postcode');

        $this->assertNull(Postcode::lookup('99999'));
        $this->assertNull((new GeocoderManager)->geocode('somewhere, 99999'));
    }
}
