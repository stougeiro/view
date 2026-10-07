<?php

use STDW\View\Engine\PhpEngine;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;

/*
|--------------------------------------------------------------------------
| View Rendering Performance Tests
|--------------------------------------------------------------------------
|
| End-to-end rendering through the real PhpEngine: resolve + data merge +
| is_file + output buffering + include. Templates are compiled fresh on
| every iteration (opcache is disabled for CLI), which is the worst-case
| scenario. Thresholds are calibrated with a conservative margin to avoid
| flaky tests on different hardware.
|
*/

beforeEach(function () {
    $this->writeView('perf-small.php', 'Hello, <?= $name ?>!');
    $this->writeView('perf-loop.php', '<ul><?php foreach ($items as $item): ?><li><?= htmlspecialchars($item, ENT_QUOTES, "UTF-8") ?></li><?php endforeach; ?></ul>');

    $this->config = new ViewConfig(['storage' => $this->testStorage]);
});

it('renders a small template 1000 times', function () {
    $manager = new ViewManager($this->config, new PhpEngine());

    $result = $this->measure(
        fn (int $i) => $manager->render('perf-small', ['name' => "User{$i}"]),
        1000
    );

    $this->assertPerformanceAbsolute($result, 200, 'render small');
});

it('renders a template with a loop 500 times', function () {
    $manager = new ViewManager($this->config, new PhpEngine());
    $items = array_map(fn (int $i) => "Item number {$i}", range(1, 20));

    $result = $this->measure(
        fn () => $manager->render('perf-loop', ['items' => $items]),
        500
    );

    $this->assertPerformanceAbsolute($result, 200, 'render loop');
});

it('renders with 50 shared keys 1000 times', function () {
    $manager = new ViewManager($this->config, new PhpEngine());

    $shared = [];
    for ($i = 0; $i < 50; $i++) {
        $shared["key{$i}"] = "value{$i}";
    }

    $manager->share($shared);

    $result = $this->measure(
        fn (int $i) => $manager->render('perf-small', ['name' => "User{$i}"]),
        1000
    );

    $this->assertPerformanceAbsolute($result, 200, 'render with shared data');
});
