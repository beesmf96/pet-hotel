<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * .bun-version is the one Bun version for CI and the Docker node service. CI
 * reads the file directly; docker-compose.yml cannot, so its image tag is a
 * hand-kept copy that this test holds in step.
 */
class BunVersionTest extends TestCase
{
    private function root(string $path): string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }

    private function pinnedVersion(): string
    {
        return trim(file_get_contents($this->root('.bun-version')));
    }

    public function test_the_pinned_version_is_an_exact_release(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $this->pinnedVersion());
    }

    public function test_the_docker_node_image_uses_the_pinned_version(): void
    {
        $compose = file_get_contents($this->root('docker-compose.yml'));

        $this->assertStringContainsString(
            'image: oven/bun:'.$this->pinnedVersion().'-alpine',
            $compose,
        );
    }

    public function test_every_ci_bun_setup_reads_the_version_file(): void
    {
        $ci = file_get_contents($this->root('.github/workflows/ci.yml'));

        $setups = substr_count($ci, 'uses: oven-sh/setup-bun@');

        $this->assertGreaterThan(0, $setups);
        $this->assertSame($setups, substr_count($ci, 'bun-version-file: .bun-version'));
        $this->assertStringNotContainsString('bun-version: ', $ci);
    }
}
