<?php declare(strict_types=1);

use STDW\View\Exception\ViewNotFoundException;

it('creates file not found exception', function () {
    $exception = ViewNotFoundException::fileNotFound('/views/home.php');

    expect($exception)->toBeInstanceOf(ViewNotFoundException::class)
        ->and($exception->getMessage())->toContain("View file '/views/home.php' not found");
});

it('extends RuntimeException for generic catches', function () {
    expect(ViewNotFoundException::fileNotFound('/views/home.php'))->toBeInstanceOf(RuntimeException::class);
});
