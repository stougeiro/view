<?php declare(strict_types=1);

use STDW\View\Exception\ViewRenderException;

it('creates cyclic extends exception', function () {
    $exception = ViewRenderException::cyclicExtends('a → b → a');

    expect($exception)->toBeInstanceOf(ViewRenderException::class)
        ->and($exception->getMessage())->toContain('cyclic extends detected: a → b → a');
});

it('creates cyclic include exception', function () {
    $exception = ViewRenderException::cyclicInclude('a → b → a');

    expect($exception)->toBeInstanceOf(ViewRenderException::class)
        ->and($exception->getMessage())->toContain('cyclic include detected: a → b → a');
});

it('creates nested block exception', function () {
    $exception = ViewRenderException::nestedBlock('inner');

    expect($exception)->toBeInstanceOf(ViewRenderException::class)
        ->and($exception->getMessage())->toContain("block 'inner' cannot be nested inside another block");
});

it('creates stray endblock exception', function () {
    $exception = ViewRenderException::strayEndblock();

    expect($exception)->toBeInstanceOf(ViewRenderException::class)
        ->and($exception->getMessage())->toContain('endblock() called without a matching block()');
});

it('creates unclosed block exception', function () {
    $exception = ViewRenderException::unclosedBlock('content');

    expect($exception)->toBeInstanceOf(ViewRenderException::class)
        ->and($exception->getMessage())->toContain("block 'content' was not closed with endblock()");
});

it('creates extends in include exception', function () {
    $exception = ViewRenderException::extendsInInclude('layouts.main');

    expect($exception)->toBeInstanceOf(ViewRenderException::class)
        ->and($exception->getMessage())->toContain("extends('layouts.main') is not allowed inside an include()");
});

it('extends RuntimeException for generic catches', function () {
    expect(ViewRenderException::cyclicExtends('a → a'))->toBeInstanceOf(RuntimeException::class);
});