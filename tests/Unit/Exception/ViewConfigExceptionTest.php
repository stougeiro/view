<?php declare(strict_types=1);

use STDW\View\Exception\ViewConfigException;

it('creates missing view storage exception', function () {
    $exception = ViewConfigException::missingStorage();

    expect($exception)->toBeInstanceOf(ViewConfigException::class)
        ->and($exception->getMessage())->toContain('"storage" is required.');
});

it('creates invalid extension exception', function () {
    $exception = ViewConfigException::invalidExtension();

    expect($exception)->toBeInstanceOf(ViewConfigException::class)
        ->and($exception->getMessage())->toContain('"extension" must start with ".".');
});

it('creates invalid alias name exception', function () {
    $exception = ViewConfigException::invalidAliasName('admin.panel');

    expect($exception)->toBeInstanceOf(ViewConfigException::class)
        ->and($exception->getMessage())->toContain("alias name 'admin.panel' must match [A-Za-z0-9].");
});

it('creates invalid alias path exception', function () {
    $exception = ViewConfigException::invalidAliasPath('admin');

    expect($exception)->toBeInstanceOf(ViewConfigException::class)
        ->and($exception->getMessage())->toContain("alias 'admin' path must be a non-empty string.");
});

it('extends InvalidArgumentException for generic catches', function () {
    expect(ViewConfigException::missingStorage())->toBeInstanceOf(InvalidArgumentException::class);
});
