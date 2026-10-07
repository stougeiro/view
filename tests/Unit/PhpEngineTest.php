<?php

use STDW\View\Engine\PhpEngine;
use STDW\View\Exception\ViewNotFoundException;

it('renders a template file with data', function () {
    $file = $this->writeView('hello.php', 'Hello, <?= $name ?>!');
    $engine = new PhpEngine();

    expect($engine->render($file, ['name' => 'World']))->toBe('Hello, World!');
});

it('renders a template without data', function () {
    $file = $this->writeView('plain.php', 'Just text.');
    $engine = new PhpEngine();

    expect($engine->render($file))->toBe('Just text.');
});

it('throws when the file does not exist', function () {
    $engine = new PhpEngine();

    $engine->render($this->testStorage . '/missing.php');
})->throws(ViewNotFoundException::class, 'not found');

it('throws when the path points to a directory', function () {
    $engine = new PhpEngine();

    $engine->render($this->testStorage);
})->throws(ViewNotFoundException::class, 'not found');

it('exposes view and data keys to the template', function () {
    $file = $this->writeView('keys.php', '<?= $data ?>|<?= $view ?>');
    $engine = new PhpEngine();

    expect($engine->render($file, ['data' => 'D', 'view' => 'V']))->toBe('D|V');
});

it('preserves falsy output such as zero', function () {
    $file = $this->writeView('zero.php', '<?= $value ?>');
    $engine = new PhpEngine();

    expect($engine->render($file, ['value' => 0]))->toBe('0');
});

it('keeps output buffers balanced when the template throws', function () {
    $file = $this->writeView('boom.php', '<?= $greeting ?><?php throw new RuntimeException("boom"); ?>');
    $engine = new PhpEngine();
    $level = ob_get_level();

    try {
        $engine->render($file, ['greeting' => 'hi']);
    } catch (RuntimeException) {
        // expected
    }

    expect(ob_get_level())->toBe($level);
});

it('drains buffers opened by the template when it throws', function () {
    $file = $this->writeView('nested-buffer.php', '<?php ob_start(); echo "partial"; throw new RuntimeException("boom"); ?>');
    $engine = new PhpEngine();
    $level = ob_get_level();

    try {
        $engine->render($file);
    } catch (RuntimeException) {
        // expected
    }

    expect(ob_get_level())->toBe($level);
});

it('returns an empty string when the template clears the buffer itself', function () {
    $file = $this->writeView('self-clear.php', '<?php ob_get_clean(); ?>');
    $engine = new PhpEngine();
    $level = ob_get_level();

    expect($engine->render($file))->toBe('')
        ->and(ob_get_level())->toBe($level);
});
