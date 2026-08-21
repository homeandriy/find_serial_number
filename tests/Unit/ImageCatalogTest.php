<?php
namespace Tests\Unit;
use App\Services\ImageCatalog;
use Tests\TestCase;
final class ImageCatalogTest extends TestCase
{
    public function test_it_lists_only_supported_images_and_resolves_them_inside_the_configured_directory(): void
    {
        $directory = storage_path('framework/testing/images');
        @mkdir($directory, 0777, true);
        file_put_contents($directory.'/serial-10.jpg', 'image');
        file_put_contents($directory.'/note.txt', 'not an image');
        config()->set('serial-number.image_directory', $directory);
        $catalog = app(ImageCatalog::class);
        $images = $catalog->all();
        $this->assertCount(1, $images);
        $this->assertSame('serial-10.jpg', $images[0]['name']);
        $this->assertSame(realpath($directory.'/serial-10.jpg'), $catalog->pathFor($images[0]['id']));
    }
    public function test_it_paginates_the_image_catalog(): void
    {
        $directory = storage_path('framework/testing/paginated-images');
        @mkdir($directory, 0777, true);
        file_put_contents($directory.'/one.jpg', 'one');
        file_put_contents($directory.'/two.jpg', 'two');
        file_put_contents($directory.'/three.jpg', 'three');
        config()->set('serial-number.image_directory', $directory);

        $page = app(ImageCatalog::class)->page(1, 2);

        $this->assertSame(3, $page['total']);
        $this->assertCount(2, $page['images']);
        $this->assertTrue($page['has_more']);
        $this->assertSame(2, $page['per_page']);
    }
}
