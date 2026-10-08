<?php

use STDW\View\Engine\PhpEngine;
use STDW\View\Exception\ViewRenderException;
use STDW\View\Spec\TemplateContext;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;

beforeEach(function () {
    $this->manager = new ViewManager(
        new ViewConfig(['storage' => $this->testStorage]),
        new PhpEngine(),
    );
});

it('renders a child view against its layout', function () {
    $this->writeView('layouts/main.php', <<<'PHP'
<title><?= $this->yield('title', 'Default') ?></title>
<main><?= $this->yield('content') ?></main>
PHP);
    $this->writeView('home.php', <<<'PHP'
<?php $this->extends('layouts.main') ?>
<?php $this->block('title') ?>Home<?php $this->endblock() ?>
<?php $this->block('content') ?><p>Hello <?= htmlspecialchars($name) ?></p><?php $this->endblock() ?>
PHP);

    $output = $this->manager->render('home', ['name' => 'World']);

    expect($output)->toBe('<title>Home</title>' . "\n" . '<main><p>Hello World</p></main>');
});

it('yields the default when the child does not define the block', function () {
    $this->writeView('layouts/plain.php', <<<'PHP'
[<?= $this->yield('title', 'Untitled') ?>]
PHP);
    $this->writeView('page.php', <<<'PHP'
<?php $this->extends('layouts/plain') ?>
PHP);

    expect($this->manager->render('page'))->toBe('[Untitled]');
});

it('renders a three level chain with the child winning', function () {
    $this->writeView('g.php', <<<'PHP'
<?= $this->yield('title', 'G') ?>|<?= $this->yield('body') ?>
PHP);
    $this->writeView('m.php', <<<'PHP'
<?php $this->extends('g') ?>
<?php $this->block('body') ?>MID<?php $this->endblock() ?>
PHP);
    $this->writeView('h.php', <<<'PHP'
<?php $this->extends('m') ?>
<?php $this->block('body') ?>HOME<?php $this->endblock() ?>
PHP);

    expect($this->manager->render('h'))->toBe('G|HOME');
});

it('throws when extends forms a cycle', function () {
    $this->writeView('a.php', "<?php \$this->extends('b') ?>");
    $this->writeView('b.php', "<?php \$this->extends('a') ?>");

    expect(fn () => $this->manager->render('a'))
        ->toThrow(ViewRenderException::class, 'cyclic extends detected: a → b → a');
});

it('keeps the first extends declaration', function () {
    $this->writeView('layout-one.php', 'ONE');
    $this->writeView('layout-two.php', 'TWO');
    $this->writeView('child-two.php', <<<'PHP'
<?php $this->extends('layout-one') ?>
<?php $this->extends('layout-two') ?>
PHP);

    expect($this->manager->render('child-two'))->toBe('ONE');
});

it('includes a partial by identifier and alias', function () {
    $this->writeView('partials/box.php', 'box:<?= $user ?>');
    $this->writeView('shell.php', <<<'PHP'
<?php $this->extends('layouts/minimal') ?>
<?php $this->block('content') ?><?php $this->include('partials.box') ?><?php $this->endblock() ?>
PHP);
    $this->writeView('layouts/minimal.php', '<?= $this->yield("content") ?>');

    $manager = new ViewManager(
        new ViewConfig([
            'storage' => $this->testStorage,
            'aliases' => ['admin' => $this->testStorage . '/admin'],
        ]),
        new PhpEngine(),
    );
    $this->writeView('admin/sidebar.php', 'side:<?= $user ?>');

    $output = $manager->render('shell', ['user' => 'ana']);

    expect($output)->toContain('box:ana')
        ->and($manager->render('admin:sidebar', ['user' => 'ana']))->toBe('side:ana');
});

it('gives every include exactly the entry render data', function () {
    $this->writeView('outer.php', '<?php $this->include("inner") ?><?= $title ?>');
    $this->writeView('inner.php', '<?= $title ?>|');

    expect($this->manager->render('outer', ['title' => 'T']))->toBe('T|T');
});

it('does not leak template local mutations into an include', function () {
    $this->writeView('mutator.php', "<?php \$title = 'CHANGED' ?><?php \$this->include('print') ?>");
    $this->writeView('print.php', '<?= $title ?>');

    expect($this->manager->render('mutator', ['title' => 'ORIGINAL']))->toBe('ORIGINAL');
});

it('shares data with includes and layout passes', function () {
    $this->writeView('who.php', '<?= $user ?>');
    $this->writeView('shared-child.php', <<<'PHP'
<?php $this->extends('layouts/s') ?>
<?php $this->block('content') ?><?php $this->include('who') ?><?php $this->endblock() ?>
PHP);
    $this->writeView('layouts/s.php', '<?= $this->yield("content") ?>');

    $this->manager->share(['user' => 'admin']);

    expect($this->manager->render('shared-child'))->toBe('admin');
});

it('throws when an include references itself', function () {
    $this->writeView('self-inc.php', '<?php $this->include("self-inc") ?>');

    expect(fn () => $this->manager->render('self-inc'))
        ->toThrow(ViewRenderException::class, 'cyclic include detected: self-inc → self-inc');
});

it('throws when includes form a cycle', function () {
    $this->writeView('inc-a.php', '<?php $this->include("inc-b") ?>');
    $this->writeView('inc-b.php', '<?php $this->include("inc-a") ?>');

    expect(fn () => $this->manager->render('inc-a'))
        ->toThrow(ViewRenderException::class, 'cyclic include detected: inc-a → inc-b → inc-a');
});

it('throws when an include calls extends', function () {
    $this->writeView('layout-ok.php', 'OK');
    $this->writeView('main-evil.php', <<<'PHP'
<?php $this->extends('layout-ok') ?>
<?php $this->include('evil') ?>
PHP);
    $this->writeView('evil.php', "<?php \$this->extends('other') ?>");

    expect(fn () => $this->manager->render('main-evil'))
        ->toThrow(ViewRenderException::class, "extends('other') is not allowed inside an include()");
});

it('captures an include inside a block', function () {
    $this->writeView('pt.php', 'PARTIAL');
    $this->writeView('holder.php', <<<'PHP'
<?php $this->extends('layouts/brackets') ?>
<?php $this->block('c') ?><?php $this->include('pt') ?><?php $this->endblock() ?>
PHP);
    $this->writeView('layouts/brackets.php', '[<?= $this->yield("c") ?>]');

    expect($this->manager->render('holder'))->toBe('[PARTIAL]');
});

it('uses blocks without extends through yield', function () {
    $this->writeView('solo.php', <<<'PHP'
<?php $this->block('a') ?>AAA<?php $this->endblock() ?>[<?= $this->yield('a', 'D') ?>]
PHP);

    expect($this->manager->render('solo'))->toBe('[AAA]');
});

it('throws when blocks are nested', function () {
    $this->writeView('nested.php', <<<'PHP'
<?php $this->block('a') ?>
<?php $this->block('b') ?>
PHP);

    expect(fn () => $this->manager->render('nested'))
        ->toThrow(ViewRenderException::class, "block 'b' cannot be nested inside another block");
});

it('throws when endblock has no matching block', function () {
    $this->writeView('stray.php', '<?php $this->endblock() ?>');

    expect(fn () => $this->manager->render('stray'))
        ->toThrow(ViewRenderException::class, 'endblock() called without a matching block()');
});

it('throws on an unclosed block and keeps output buffers balanced', function () {
    $this->writeView('unclosed.php', <<<'PHP'
<?php $this->extends('layouts/min2') ?>
<?php $this->block('x') ?>open
PHP);
    $this->writeView('layouts/min2.php', '<?= $this->yield("x") ?>');

    $level = ob_get_level();

    expect(fn () => $this->manager->render('unclosed'))
        ->toThrow(ViewRenderException::class, "block 'x' was not closed with endblock()");

    expect(ob_get_level())->toBe($level);
});

it('propagates a throw inside a block and keeps output buffers balanced', function () {
    $this->writeView('boom.php', <<<'PHP'
<?php $this->block('x') ?><?php throw new RuntimeException('boom'); ?>
PHP);

    $level = ob_get_level();

    expect(fn () => $this->manager->render('boom'))
        ->toThrow(RuntimeException::class, 'boom');

    expect(ob_get_level())->toBe($level);
});

it('exposes the context as $this inside a native include', function () {
    $this->writeView('entry.php', '<?php include __DIR__ . "/native-sub.php"; ?>');
    $this->writeView('native-sub.php', '<?= $this->yield("unset", "BOUND") ?>');

    expect($this->manager->render('entry'))->toBe('BOUND');
});

it('binds the facade, not the internal context, as $this', function () {
    $this->writeView('face.php', '<?= get_class($this) ?>');

    expect($this->manager->render('face'))->toBe(TemplateContext::class);
});

it('escapes variables rendered through the facade', function () {
    $this->writeView('esc.php', '<?= $this->echo($name) ?>');

    expect($this->manager->render('esc', ['name' => '<b>Hi</b>']))->toBe('&lt;b&gt;Hi&lt;/b&gt;');
});

it('escapes variables inside a native include', function () {
    $this->writeView('esc-entry.php', '<?php include __DIR__ . "/esc-sub.php"; ?>');
    $this->writeView('esc-sub.php', '<?= $this->echo($payload) ?>');

    expect($this->manager->render('esc-entry', ['payload' => 'a & b']))->toBe('a &amp; b');
});

it('yields a block only the layout defines', function () {
    $this->writeView('layouts/with-foot.php', <<<'PHP'
<?php $this->block('foot') ?><footer>F</footer><?php $this->endblock() ?><main><?= $this->yield('content') ?></main><?= $this->yield('foot') ?>
PHP);
    $this->writeView('no-foot.php', <<<'PHP'
<?php $this->extends('layouts/with-foot') ?>
<?php $this->block('content') ?>C<?php $this->endblock() ?>
PHP);

    expect($this->manager->render('no-foot'))->toBe('<main>C</main><footer>F</footer>');
});

it('gives the child block precedence over the same block in the layout', function () {
    $this->writeView('layouts/dup.php', <<<'PHP'
<?php $this->block('x') ?>L<?php $this->endblock() ?>
[<?= $this->yield('x') ?>]
PHP);
    $this->writeView('dup-child.php', <<<'PHP'
<?php $this->extends('layouts/dup') ?>
<?php $this->block('x') ?>C<?php $this->endblock() ?>
PHP);

    expect($this->manager->render('dup-child'))->toBe('[C]');
});

it('captures a block defined by a partial included from a block', function () {
    $this->writeView('part.php', "<?php \$this->block('extra') ?>X<?php \$this->endblock() ?>P");
    $this->writeView('compose.php', <<<'PHP'
<?php $this->include('part') ?>
[<?= $this->yield('extra', 'NONE') ?>]
PHP);

    expect($this->manager->render('compose'))->toBe('P[X]');
});

it('renders a six level extends chain', function () {
    $this->writeView('d1.php', '<?= $this->yield("title", "ROOT") ?>|<?= $this->yield("body") ?>');

    for ($level = 2; $level <= 5; $level++) {
        $parent = 'd' . ($level - 1);
        $this->writeView("d{$level}.php", "<?php \$this->extends('{$parent}') ?><?php \$this->block('body') ?>D{$level}<?php \$this->endblock() ?>");
    }

    $this->writeView('leaf.php', <<<'PHP'
<?php $this->extends('d5') ?>
<?php $this->block('title') ?>T<?php $this->endblock() ?>
<?php $this->block('body') ?>LEAF<?php $this->endblock() ?>
PHP);

    expect($this->manager->render('leaf'))->toBe('T|LEAF');
});