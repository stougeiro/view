<?php

use STDW\View\Exception\ViewIdentifierException;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;
use Tests\Support\FakeEngine;

/*
|--------------------------------------------------------------------------
| Identifier Resolution Security
|--------------------------------------------------------------------------
|
| The resolver treats view identifiers as untrusted input. These tests pin
| the guarantee: no identifier escapes the storage (or alias) root, can
| reach a stream wrapper, or carries control bytes.
|
*/

beforeEach(function () {
    $this->manager = new ViewManager(
        new ViewConfig([
            'storage' => '/views',
            'aliases' => ['admin' => '/sec/admin'],
        ]),
        new FakeEngine(),
    );
});

it('keeps every adversarial identifier inside the storage', function (string $view) {
    $resolved = $this->manager->resolveAlias($view);

    expect($resolved)->toStartWith('/views/')
        ->not->toContain('..')
        ->toEndWith('.php');
})->with([
    '../etc/passwd',
    '../..',
    '..%2f..%2fsecret',
    '....//secret',
    'a/../../b',
    'pages/../secret',
    'a/..\\..\\b',
    '..\\..\\secret',
    '/etc/passwd',
    '.../',
]);

it('keeps aliased identifiers inside the alias root', function (string $view) {
    $resolved = $this->manager->resolveAlias($view);

    expect($resolved)->toStartWith('/sec/admin/')
        ->not->toContain('..')
        ->toEndWith('.php');
})->with([
    'admin:..\\x',
    'admin:..\\..\\x',
    'admin:/etc/passwd',
    'admin:...',
]);

it('keeps edge identifiers inside the storage', function (string $view) {
    $resolved = $this->manager->resolveAlias($view);

    expect($resolved)->toStartWith('/views/')
        ->not->toContain('..')
        ->toEndWith('.php');
})->with([
    '.',
    '..',
    'a..b',
    'home.',
    'a.b.c.',
    '...',
]);

it('rejects identifiers with control characters', function (string $view) {
    $this->manager->resolveAlias($view);
})->with([
    "home\x00.php",
    "home\nsecret",
    "home\x1Fsecret",
    "home\tsecret",
])->throws(ViewIdentifierException::class, 'contains invalid characters');

it('never resolves to a stream wrapper', function (string $view) {
    $this->manager->resolveAlias($view);
})->with([
    'php://filter/convert.base64-encode/resource=/etc/passwd',
    'phar:///etc/passwd',
    'data://text/plain;base64,PD9waHAg',
])->throws(ViewIdentifierException::class);