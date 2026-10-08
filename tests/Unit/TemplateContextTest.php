<?php

use STDW\View\Spec\RenderContext;
use STDW\View\Spec\TemplateContext;

it('exposes only the template methods', function () {
    $methods = get_class_methods(TemplateContext::class);
    sort($methods);

    expect($methods)->toBe(['__construct', 'block', 'echo', 'endblock', 'extends', 'include', 'yield']);
});

it('does not expose internal methods', function () {
    $context = (new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        fn (string $path, TemplateContext $view): string => "out:{$path}",
    ))->template();

    foreach (['takeParent', 'enterPass', 'openBlockName', 'template'] as $method) {
        expect(method_exists($context, $method))->toBeFalse();
    }
});

it('rejects calls to internal methods', function () {
    $context = (new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        fn (string $path, TemplateContext $view): string => "out:{$path}",
    ))->template();

    $context->takeParent();
})->throws(Error::class, 'Call to undefined method');

it('delegates the six methods to the render context', function () {
    $context = (new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        fn (string $path, TemplateContext $view): string => "out:{$path}",
    ))->template();

    $context->extends('layouts.main');

    $context->block('title');
    echo 'Hello';
    $context->endblock();

    expect($context->yield('title'))->toBe('Hello')
        ->and($context->yield('missing', 'fallback'))->toBe('fallback')
        ->and($context->echo('<b>&'))->toBe('&lt;b&gt;&amp;');
});

it('reuses the same facade instance across passes', function () {
    $state = new RenderContext(
        fn (string $view): string => "/views/{$view}.php",
        fn (string $path, TemplateContext $view): string => "out:{$path}",
    );

    expect($state->template())->toBe($state->template());
});