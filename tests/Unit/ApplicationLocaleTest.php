<?php

namespace Tests\Unit;

use App\Services\ApplicationLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ApplicationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_the_locale_column_for_an_existing_database(): void
    {
        Schema::table('application_state', function ($table): void {
            $table->dropColumn('locale');
        });

        $locale = app(ApplicationLocale::class);

        self::assertSame('en', $locale->update('en'));
        $this->assertDatabaseHas('application_state', ['id' => 1, 'locale' => 'en']);
    }

    public function test_it_persists_a_supported_locale_in_the_application_state(): void
    {
        $locale = app(ApplicationLocale::class);

        self::assertSame('uk', $locale->current());
        self::assertSame('pl', $locale->update('pl'));
        self::assertSame('pl', $locale->current());
        $this->assertDatabaseHas('application_state', ['id' => 1, 'locale' => 'pl']);
    }
}
