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
    $this->writeView('perf-layout.php', '<title><?= $this->yield("title", "Untitled") ?></title><?= $this->yield("content") ?>');
    $this->writeView('perf-child.php', '<?php $this->extends("perf-layout") ?><?php $this->block("title") ?>T<?php $this->endblock() ?><?php $this->block("content") ?>Hello, <?= $name ?>!<?php $this->endblock() ?>');

    $this->writeView('perf-chain-1.php', '<title><?= $this->yield("title", "Untitled") ?></title><?= $this->yield("content") ?>');

    for ($level = 2; $level <= 5; $level++) {
        $parent = 'perf-chain-' . ($level - 1);
        $this->writeView("perf-chain-{$level}.php", "<?php \$this->extends('{$parent}') ?><?php \$this->block('content') ?>L{$level}<?php \$this->endblock() ?>");
    }

    $this->writeView('perf-chain-6.php', '<?php $this->extends("perf-chain-5") ?><?php $this->block("title") ?>T<?php $this->endblock() ?><?php $this->block("content") ?>Hello, <?= $name ?>!<?php $this->endblock() ?>');

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

it('renders an extends chain 500 times', function () {
    $manager = new ViewManager($this->config, new PhpEngine());

    $result = $this->measure(
        fn (int $i) => $manager->render('perf-child', ['name' => "User{$i}"]),
        500
    );

    $this->assertPerformanceAbsolute($result, 300, 'render extends chain');
});

it('renders a six level extends chain 200 times', function () {
    $manager = new ViewManager($this->config, new PhpEngine());

    $result = $this->measure(
        fn (int $i) => $manager->render('perf-chain-6', ['name' => "User{$i}"]),
        200
    );

    $this->assertPerformanceAbsolute($result, 300, 'render six level chain');
});
