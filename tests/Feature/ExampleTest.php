<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="update-dialog"', false);
        $response->assertSee('id="update-dialog-message"', false);
    }
    public function test_image_catalog_is_loaded_separately(): void
    {
        $response = $this->get('/image-catalog?page=1&per_page=48');

        $response->assertOk()->assertJsonStructure([
            'images',
            'total',
            'page',
            'per_page',
            'has_more',
        ]);
    }
    public function test_renderer_ready_marker_is_accepted(): void
    {
        $this->post('/startup/renderer-ready')->assertNoContent();
    }

    public function test_startup_log_can_be_opened(): void
    {
        \Native\Desktop\Facades\Shell::fake();

        $this->post('/startup-log/open')->assertNoContent();

        \Native\Desktop\Facades\Shell::assertOpenedFile(app(\App\Services\StartupLog::class)->path());
    }
}
