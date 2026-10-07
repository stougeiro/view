<?php

use STDW\View\ViewConfig;
use STDW\View\ViewManager;
use Tests\Support\FakeEngine;

/*
|--------------------------------------------------------------------------
| View Resolution Performance Tests
|--------------------------------------------------------------------------
|
| Pure identifier resolution (no I/O): str_contains, explode, str_replace
| and concatenation only. Thresholds are calibrated with a conservative
| margin to avoid flaky tests on different hardware.
|
*/

beforeEach(function () {
    $config = new ViewConfig([
        'storage' => '/views',
        'aliases' => ['admin' => '/views/admin'],
    ]);

    $this->manager = new ViewManager($config, new FakeEngine());
});

it('resolves a plain view 10000 times', function () {
    $result = $this->measure(fn (int $i) => $this->manager->resolveAlias("home{$i}"), 10000);

    $this->assertPerformanceAbsolute($result, 50, 'resolve plain');
});

it('resolves a dotted view 10000 times', function () {
    $result = $this->measure(fn (int $i) => $this->manager->resolveAlias("pages.about{$i}"), 10000);

    $this->assertPerformanceAbsolute($result, 50, 'resolve dotted');
});

it('resolves an aliased view 10000 times', function () {
    $result = $this->measure(fn (int $i) => $this->manager->resolveAlias("admin:sidebar{$i}"), 10000);

    $this->assertPerformanceAbsolute($result, 100, 'resolve aliased');
});

it('keeps aliased resolution within 3x of plain resolution', function () {
    $plain = $this->measure(fn (int $i) => $this->manager->resolveAlias("home{$i}"), 10000);
    $alias = $this->measure(fn (int $i) => $this->manager->resolveAlias("admin:sidebar{$i}"), 10000);

    $this->assertPerformanceRelative($plain, $alias, 3.0, 'aliased vs plain');
});
