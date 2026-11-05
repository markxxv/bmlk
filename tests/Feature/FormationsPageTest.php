<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_formations_page_is_public_and_links_to_real_contact_and_events(): void
    {
        $this->get(route('formations'))
            ->assertOk()
            ->assertSee('SMIRNOVA SCHOOL')
            ->assertSee('WAX MANICURE')
            ->assertSee('POP-IT ARCHITECT')
            ->assertSee('INSTAGRAM CREATOR')
            ->assertSee('img/formations/wax-tin.jpg')
            ->assertSee('mailto:office.blackmilk@gmail.com')
            ->assertSee(route('events'))
            ->assertSee('Programme de formation');
    }

    public function test_course_catalog_is_clean_and_prices_are_numeric(): void
    {
        $courses = array_merge(config('formations.online'), config('formations.live'));
        $this->assertCount(10, $courses);
        $this->assertCount(10, array_unique(array_column($courses, 'slug')));

        foreach ($courses as $course) {
            $this->assertArrayHasKey('image', $course);
            $this->assertTrue($course['price'] === null || is_int($course['price']));
        }
    }
}
