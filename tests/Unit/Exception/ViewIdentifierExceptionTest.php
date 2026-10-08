<?php declare(strict_types=1);

use STDW\View\Exception\ViewIdentifierException;

it('creates empty identifier exception', function () {
    $exception = ViewIdentifierException::emptyIdentifier();

    expect($exception)->toBeInstanceOf(ViewIdentifierException::class)
        ->and($exception->getMessage())->toContain('view identifier must not be empty.');
});

it('creates empty segment exception', function () {
    $exception = ViewIdentifierException::emptySegment('admin:');

    expect($exception)->toBeInstanceOf(ViewIdentifierException::class)
        ->and($exception->getMessage())->toContain("view identifier 'admin:' has an empty segment.");
});

it('creates too many colons exception', function () {
    $exception = ViewIdentifierException::tooManyColons('admin:a:b');

    expect($exception)->toBeInstanceOf(ViewIdentifierException::class)
        ->and($exception->getMessage())->toContain("view identifier 'admin:a:b' must contain at most one ':'.");
});

it('creates invalid characters exception', function () {
    $exception = ViewIdentifierException::invalidCharacters("home\x00.php");

    expect($exception)->toBeInstanceOf(ViewIdentifierException::class)
        ->and($exception->getMessage())->toContain('contains invalid characters.');
});

it('creates unknown alias exception', function () {
    $exception = ViewIdentifierException::unknownAlias('nope');

    expect($exception)->toBeInstanceOf(ViewIdentifierException::class)
        ->and($exception->getMessage())->toContain("alias 'nope' is not registered.");
});

it('extends InvalidArgumentException for generic catches', function () {
    expect(ViewIdentifierException::emptyIdentifier())->toBeInstanceOf(InvalidArgumentException::class);
});
