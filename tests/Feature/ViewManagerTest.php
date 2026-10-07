<?php

use STDW\View\Engine\PhpEngine;
use STDW\View\Exception\ViewIdentifierException;
use STDW\View\Exception\ViewNotFoundException;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;

it('renders a view with shared and local data', function () {
    $this->writeView('home.php', 'Hello, <?= $name ?> from <?= $site ?>');
    $config = new ViewConfig(['storage' => $this->testStorage]);
    $manager = new ViewManager($config, new PhpEngine());

    $manager->share(['site' => 'Stougeiro']);
    $output = $manager->render('home', ['name' => 'World']);

    expect($output)->toBe('Hello, World from Stougeiro');
});

it('renders a nested view using dot notation', function () {
    $this->writeView('pages/about.php', 'About <?= $company ?>');
    $config = new ViewConfig(['storage' => $this->testStorage]);
    $manager = new ViewManager($config, new PhpEngine());

    expect($manager->render('pages.about', ['company' => 'ACME']))->toBe('About ACME');
});

it('renders a view through an alias pointing outside the storage', function () {
    $this->writeView('admin/sidebar.php', 'Sidebar');
    $config = new ViewConfig([
        'storage' => $this->testStorage,
        'aliases' => ['admin' => $this->testStorage . '/admin'],
    ]);
    $manager = new ViewManager($config, new PhpEngine());

    expect($manager->render('admin:sidebar'))->toBe('Sidebar');
});

it('renders a view through a runtime-registered alias', function () {
    $this->writeView('blog/post.php', 'Post: <?= $title ?>');
    $config = new ViewConfig(['storage' => $this->testStorage]);
    $manager = new ViewManager($config, new PhpEngine());

    $manager->alias('blog', $this->testStorage . '/blog');

    expect($manager->render('blog:post', ['title' => 'Hello']))->toBe('Post: Hello');
});

it('throws when the view file does not exist', function () {
    $config = new ViewConfig(['storage' => $this->testStorage]);
    $manager = new ViewManager($config, new PhpEngine());

    $manager->render('missing');
})->throws(ViewNotFoundException::class, 'not found');

it('throws when the alias is not registered', function () {
    $config = new ViewConfig(['storage' => $this->testStorage]);
    $manager = new ViewManager($config, new PhpEngine());

    $manager->render('nope:home');
})->throws(ViewIdentifierException::class, "alias 'nope' is not registered.");
