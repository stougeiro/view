<?php

use STDW\View\Exception\ViewRenderException;
use STDW\View\Spec\RenderContext;
use STDW\View\Spec\TemplateContext;

beforeEach(function () {
    $this->context = new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        fn (string $path, TemplateContext $context): string => "out:{$path}",
    );
});

it('has no parent view by default', function () {
    expect($this->context->takeParent())->toBeNull();
});

it('keeps the first extends declaration', function () {
    $this->context->extends('layouts.one');
    $this->context->extends('layouts.two');

    expect($this->context->takeParent())->toBe('layouts.one');
});

it('rejects extends inside an include', function () {
    $context = new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        function (string $path, TemplateContext $context): string {
            $context->extends('layouts.late');

            return '';
        },
    );

    expect(fn () => $context->include('partials.x'))
        ->toThrow(ViewRenderException::class, "extends('layouts.late') is not allowed inside an include()");
});

it('renders and echoes an include', function () {
    ob_start();
    $this->context->include('partials.row');
    $output = ob_get_clean();

    expect($output)->toBe('out:/views/partials.row.php');
});

it('detects a self-referencing include', function () {
    $this->context->enterPass('/views/a.php', 'a');

    expect(fn () => $this->context->include('a'))
        ->toThrow(ViewRenderException::class, 'cyclic include detected: a → a');
});

it('detects a cyclic include chain', function () {
    $context = new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        function (string $path, TemplateContext $context): string {
            if (str_contains($path, '/b.php')) {
                $context->include('a');
            }

            return '';
        },
    );
    $context->enterPass('/views/a.php', 'a');

    expect(fn () => $context->include('b'))
        ->toThrow(ViewRenderException::class, 'cyclic include detected: a → b → a');
});

it('restores include state when the renderer throws', function () {
    $context = new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        fn (): string => throw new RuntimeException('boom'),
    );

    try {
        $context->include('partials.x');
    } catch (RuntimeException) {
        // expected
    }

    $context->extends('layouts.ok');

    expect($context->takeParent())->toBe('layouts.ok');
});

it('captures block content and yields it', function () {
    $this->context->block('title');
    echo 'Hello';
    $this->context->endblock();

    expect($this->context->yield('title'))->toBe('Hello')
        ->and($this->context->yield('missing', 'default'))->toBe('default')
        ->and($this->context->openBlockName())->toBeNull();
});

it('keeps the first definition of a block', function () {
    $this->context->block('x');
    echo 'first';
    $this->context->endblock();

    $this->context->block('x');
    echo 'second';
    $this->context->endblock();

    expect($this->context->yield('x'))->toBe('first');
});

it('rejects nested blocks', function () {
    $level = ob_get_level();

    $this->context->block('outer');

    expect(fn () => $this->context->block('inner'))
        ->toThrow(ViewRenderException::class, "block 'inner' cannot be nested inside another block");

    $this->context->endblock();

    expect(ob_get_level())->toBe($level);
});

it('rejects endblock without an open block', function () {
    expect(fn () => $this->context->endblock())
        ->toThrow(ViewRenderException::class, 'endblock() called without a matching block()');
});

it('reports a block left open', function () {
    $this->context->block('open');

    expect($this->context->openBlockName())->toBe('open');

    $this->context->endblock();
});

it('drains buffers the block content left open', function () {
    $level = ob_get_level();

    $this->context->block('x');
    echo 'inner';
    ob_start();
    echo 'stray';
    $this->context->endblock();

    expect(ob_get_level())->toBe($level)
        ->and($this->context->yield('x'))->toBe('inner');
});

it('treats a cleared block buffer as empty content', function () {
    $level = ob_get_level();

    $this->context->block('x');

    while (ob_get_level() > $level) {
        ob_end_clean();
    }

    $this->context->endblock();

    expect($this->context->yield('x', 'fallback'))->toBe('');
});

it('captures include output inside a block', function () {
    $this->context->block('c');
    $this->context->include('partials.y');
    $this->context->endblock();

    expect($this->context->yield('c'))->toBe('out:/views/partials.y.php');
});

it('escapes output for safe HTML', function () {
    expect($this->context->echo('<script>alert(1)</script>'))
        ->toBe('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($this->context->echo('"quoted" & \'single\''))
        ->toBe('&quot;quoted&quot; &amp; &#039;single&#039;');
});

it('casts scalar values before escaping', function () {
    expect($this->context->echo(42))->toBe('42')
        ->and($this->context->echo(1.5))->toBe('1.5')
        ->and($this->context->echo(null))->toBe('');
});

it('re-escapes already escaped ampersands by default', function () {
    expect($this->context->echo('a &amp; b'))->toBe('a &amp;amp; b');
});