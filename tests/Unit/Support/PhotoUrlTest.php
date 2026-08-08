<?php

namespace Tests\Unit\Support;

use App\Support\PhotoUrl;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Photo columns hold either a storage path (Filament upload) or an absolute URL
 * (PetHotelSeeder's remote demo images), so resolution has to cope with both.
 */
class PhotoUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.photos' => 'test-bucket']);
        Storage::fake('test-bucket', ['url' => 'https://cdn.example.com']);
    }

    public function test_a_storage_path_becomes_a_url_on_the_uploads_disk(): void
    {
        $this->assertSame(
            'https://cdn.example.com/hotel-photos/kennel.jpg',
            PhotoUrl::resolve('hotel-photos/kennel.jpg'),
        );
    }

    public function test_an_absolute_url_is_left_alone(): void
    {
        $url = 'https://picsum.photos/seed/3-1/800/600';

        $this->assertSame($url, PhotoUrl::resolve($url));
    }

    public function test_a_protocol_relative_url_is_left_alone(): void
    {
        $this->assertSame('//images.example.com/x.jpg', PhotoUrl::resolve('//images.example.com/x.jpg'));
    }

    /**
     * Already root-relative to this app — pushing it through the disk again
     * would produce https://cdn.example.com//storage/x.jpg.
     */
    public function test_a_root_relative_path_is_left_alone(): void
    {
        $this->assertSame('/storage/x.jpg', PhotoUrl::resolve('/storage/x.jpg'));
    }

    public function test_null_and_blank_resolve_to_null(): void
    {
        $this->assertNull(PhotoUrl::resolve(null));
        $this->assertNull(PhotoUrl::resolve(''));
        $this->assertNull(PhotoUrl::resolve('   '));
    }
}
