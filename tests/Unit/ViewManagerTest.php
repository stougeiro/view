<?php

use STDW\View\Exception\ViewConfigException;
use STDW\View\Exception\ViewIdentifierException;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;
use Tests\Support\FakeEngine;

it('resolves a plain view against the storage', function () {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    expect($manager->resolveAlias('home'))->toBe('/views/home.php')
        ->and($manager->resolveAlias('pages.about'))->toBe('/views/pages/about.php');
});

it('resolves an aliased view against its alias root', function () {
    $config = new ViewConfig([
        'storage' => '/views',
        'aliases' => ['admin' => '/views/admin'],
    ]);
    $manager = new ViewManager($config, new FakeEngine());

    expect($manager->resolveAlias('admin:sidebar'))->toBe('/views/admin/sidebar.php')
        ->and($manager->resolveAlias('admin:pages.index'))->toBe('/views/admin/pages/index.php');
});

it('uses a custom extension when resolving', function () {
    $config = new ViewConfig(['storage' => '/views', 'extension' => '.phtml']);
    $manager = new ViewManager($config, new FakeEngine());

    expect($manager->resolveAlias('home'))->toBe('/views/home.phtml');
});

it('strips parent directory traversal from the view', function () {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    expect($manager->resolveAlias('pages/../secret'))->not->toContain('..');
});

it('throws on an unknown alias', function () {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    $manager->resolveAlias('nope:home');
})->throws(ViewIdentifierException::class, "alias 'nope' is not registered.");

it('throws on an empty view identifier', function () {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    $manager->resolveAlias('');
})->throws(ViewIdentifierException::class, 'view identifier must not be empty.');

it('throws when a segment of the identifier is empty', function (string $view) {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    $manager->resolveAlias($view);
})->with([':home', 'admin:'])->throws(ViewIdentifierException::class, 'has an empty segment.');

it('throws when the identifier contains more than one colon', function () {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    $manager->resolveAlias('nope:a:b');
})->throws(ViewIdentifierException::class, "view identifier 'nope:a:b' must contain at most one ':'.");

it('seeds aliases from the config', function () {
    $config = new ViewConfig([
        'storage' => '/views',
        'aliases' => ['admin' => '/views/admin'],
    ]);
    $manager = new ViewManager($config, new FakeEngine());

    expect($manager->alias('admin'))->toBe('/views/admin')
        ->and($manager->alias('missing'))->toBeNull();
});

it('registers an alias at runtime', function () {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    expect($manager->alias('blog', '/var/blog/'))->toBeNull()
        ->and($manager->alias('blog'))->toBe('/var/blog')
        ->and($manager->resolveAlias('blog:post'))->toBe('/var/blog/post.php');
});

it('throws when registering an invalid alias', function (string $name, string $path) {
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, new FakeEngine());

    $manager->alias($name, $path);
})->with([
    ['', '/path'],
    ['admin:panel', '/path'],
    ['.hidden', '/path'],
    ['admin.panel', '/path'],
    ['admin', ''],
])->throws(ViewConfigException::class);

it('merges shared and local data with local taking precedence', function () {
    $engine = new FakeEngine('rendered');
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, $engine);

    $manager->share(['locale' => 'pt', 'user' => 'global']);
    $manager->share(['role' => 'admin']);

    $result = $manager->render('home', ['user' => 'local', 'title' => 'Hi']);

    expect($result)->toBe('rendered')
        ->and($engine->calls)->toHaveCount(1)
        ->and($engine->calls[0]['view'])->toBe('/views/home.php')
        ->and($engine->calls[0]['data'])->toMatchArray([
            'locale' => 'pt',
            'user' => 'local',
            'role' => 'admin',
            'title' => 'Hi',
        ]);
});

it('lets a later share overwrite an earlier value', function () {
    $engine = new FakeEngine();
    $config = new ViewConfig(['storage' => '/views']);
    $manager = new ViewManager($config, $engine);

    $manager->share(['a' => 1]);
    $manager->share(['a' => 2]);
    $manager->render('home');

    expect($engine->calls[0]['data'])->toBe(['a' => 2]);
});
