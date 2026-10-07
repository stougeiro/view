<?php

use STDW\View\Exception\ViewConfigException;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;
use Tests\Support\FakeEngine;

it('uses defaults when only the storage is given', function () {
    $config = new ViewConfig(['storage' => '/views']);

    expect($config->storage())->toBe('/views')
        ->and($config->extension())->toBe('.php')
        ->and($config->aliases())->toBe([]);
});

it('trims trailing slashes and backslashes from the storage', function () {
    $config = new ViewConfig(['storage' => '/views\\']);

    expect($config->storage())->toBe('/views');
});

it('keeps a root storage functional after trimming', function () {
    $config = new ViewConfig(['storage' => '/']);

    expect($config->storage())->toBe('')
        ->and((new ViewManager($config, new FakeEngine()))->resolveAlias('home'))->toBe('/home.php');
});

it('throws when the storage is missing', function () {
    new ViewConfig([]);
})->throws(ViewConfigException::class, '"storage" is required.');

it('throws when the storage is empty', function () {
    new ViewConfig(['storage' => '']);
})->throws(ViewConfigException::class, '"storage" is required.');

it('accepts a custom extension', function () {
    $config = new ViewConfig(['storage' => '/views', 'extension' => '.phtml']);

    expect($config->extension())->toBe('.phtml');
});

it('throws when the extension does not start with a dot', function (string $extension) {
    new ViewConfig(['storage' => '/views', 'extension' => $extension]);
})->with(['', 'php', 'phtml'])->throws(ViewConfigException::class, '"extension" must start with ".".');

it('normalizes alias paths', function () {
    $config = new ViewConfig([
        'storage' => '/views',
        'aliases' => ['admin' => '/views/admin/', 'shared' => 'C:\\views\\shared\\'],
    ]);

    expect($config->aliases())->toBe([
        'admin' => '/views/admin',
        'shared' => 'C:\views\shared',
    ]);
});

it('throws on an empty alias name', function () {
    new ViewConfig(['storage' => '/views', 'aliases' => ['' => '/path']]);
})->throws(ViewConfigException::class, "alias name '' must match [A-Za-z0-9].");

it('throws on an alias name outside the charset', function (string $name) {
    new ViewConfig(['storage' => '/views', 'aliases' => [$name => '/path']]);
})->with(['admin:panel', '.hidden', 'my_alias', 'my-alias', 'admin.panel'])
    ->throws(ViewConfigException::class, 'must match [A-Za-z0-9].');

it('accepts a numeric alias name', function () {
    $config = new ViewConfig(['storage' => '/views', 'aliases' => ['123' => '/path']]);

    expect($config->aliases())->toBe([123 => '/path']);
});

it('throws on an empty alias path', function () {
    new ViewConfig(['storage' => '/views', 'aliases' => ['admin' => '']]);
})->throws(ViewConfigException::class, "alias 'admin' path must be a non-empty string.");

it('throws on a non-string alias path', function () {
    new ViewConfig(['storage' => '/views', 'aliases' => ['admin' => 123]]);
})->throws(ViewConfigException::class, 'path must be a non-empty string.');
