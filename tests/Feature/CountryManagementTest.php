<?php

namespace Tests\Feature;

use App\Http\Controllers\SendcloudController;
use App\Models\Country;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CountryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_countries_start_with_requested_priority_order(): void
    {
        $this->seed(CountrySeeder::class);

        $this->assertSame(249, Country::query()->count());
        $this->assertSame(
            ['FR', 'IT', 'ES', 'PT', 'US'],
            Country::available()->ordered()->limit(5)->pluck('code')->all(),
        );
    }

    public function test_country_changes_survive_reseeding_and_control_shipping_availability(): void
    {
        $this->seed(CountrySeeder::class);
        Country::query()->where('code', 'US')->update([
            'name' => 'États-Unis (test)',
            'sort' => 1,
            'active' => false,
        ]);

        $this->seed(CountrySeeder::class);

        $country = Country::query()->where('code', 'US')->firstOrFail();
        $this->assertFalse($country->active);
        $this->assertSame('États-Unis (test)', $country->name);
        $this->assertSame(1, $country->sort);
        $this->assertFalse(Country::available()->where('code', 'US')->exists());

        $this->expectException(ValidationException::class);
        app(SendcloudController::class)->zone('US');
    }
}
