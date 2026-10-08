<?php

use STDW\View\Engine\PhpEngine;
use STDW\View\Exception\ViewNotFoundException;
use STDW\View\Spec\TemplateContext;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;

/*
|--------------------------------------------------------------------------
| Render Security
|--------------------------------------------------------------------------
|
| End-to-end through a real PhpEngine: traversal cannot reach files outside
| the storage, templates are isolated between renders, the facade cannot be
| forged through data, and user data stays escaped in HTML contexts.
|
*/

beforeEach(function () {
    $this->manager = new ViewManager(
        new ViewConfig(['storage' => $this->testStorage]),
        new PhpEngine(),
    );
});

it('cannot render a file placed outside the storage', function () {
    $outside = $this->testStorage . '-outside/owned.php';
    mkdir(dirname($outside), 0755, true);
    file_put_contents($outside, 'OUTSIDE_SECRET');

    expect(fn () => $this->manager->render('../owned'))
        ->toThrow(ViewNotFoundException::class, 'not found');
});

it('cannot reach outside files through include()', function () {
    $outside = $this->testStorage . '-outside/owned.php';
    mkdir(dirname($outside), 0755, true);
    file_put_contents($outside, 'OUTSIDE_SECRET');

    $this->writeView('esc.php', '<?php $this->include("../owned") ?>');
    $level = ob_get_level();

    expect(fn () => $this->manager->render('esc'))
        ->toThrow(ViewNotFoundException::class, 'not found');

    expect(ob_get_level())->toBe($level);
});

it('isolates block state between consecutive renders', function () {
    $this->writeView('a.php', '<?php $this->block("x") ?>AAA<?php $this->endblock() ?>ok');
    $this->writeView('b.php', '[<?= $this->yield("x", "NOPE") ?>]');

    $this->manager->render('a');

    expect($this->manager->render('b'))->toBe('[NOPE]');
});

it('isolates extends state between consecutive renders', function () {
    $this->writeView('layout.php', 'L-<?= $this->yield("c", "-") ?>');
    $this->writeView('child.php', '<?php $this->extends("layout") ?><?php $this->block("c") ?>C<?php $this->endblock() ?>');
    $this->writeView('solo.php', 'SOLO');

    $this->manager->render('child');

    expect($this->manager->render('solo'))->toBe('SOLO');
});

it('ignores a spoofed context key in the data', function () {
    $this->writeView('spoof.php', '<?= get_class($this) ?>::<?= isset($__context) ? "LEAK" : "STRIPPED" ?>');

    $output = $this->manager->render('spoof', ['__context' => 'spoofed-value']);

    expect($output)->toBe(TemplateContext::class . '::STRIPPED');
});

it('keeps user data escaped in an attribute context', function () {
    $this->writeView('attr.php', '<input value="<?= $this->echo($value) ?>">');

    $output = $this->manager->render('attr', ['value' => '"><script>x</script>']);

    expect($output)->toBe('<input value="&quot;&gt;&lt;script&gt;x&lt;/script&gt;">');
});

it('keeps user data escaped in an inline script', function () {
    $this->writeView('js.php', '<script>var n = "<?= $this->echo($name) ?>";</script>');

    $output = $this->manager->render('js', ['name' => '</script><script>alert(1)</script>']);

    expect($output)->toBe('<script>var n = "&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;";</script>');
});

it('substitutes invalid UTF-8 instead of echoing it raw', function () {
    $this->writeView('utf.php', '<?= $this->echo($bits) ?>');

    expect($this->manager->render('utf', ['bits' => "\xC3"]))->toBe("\xEF\xBF\xBD");
});