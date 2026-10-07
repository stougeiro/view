<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $testStorage;

    protected function setUp(): void
    {
        $this->testStorage = sys_get_temp_dir() . '/view_test_' . uniqid('', true);
        mkdir($this->testStorage, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testStorage);
    }

    protected function writeView(string $relativePath, string $content): string
    {
        $file = $this->testStorage . '/' . $relativePath;
        $directory = dirname($file);

        if ( ! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($file, $content);

        return $file;
    }

    protected function removeDirectory(string $directory): void
    {
        if ( ! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $file->isDir()
                ? rmdir($file->getRealPath())
                : unlink($file->getRealPath());
        }

        rmdir($directory);
    }
}
