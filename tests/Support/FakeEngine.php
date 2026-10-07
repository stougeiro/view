<?php

namespace Tests\Support;

use STDW\Contract\View\ViewEngineInterface;

class FakeEngine implements ViewEngineInterface
{
    /** @var array<int, array{view: string, data: array<string, mixed>}>
     */
    public array $calls = [];

    protected string $output;

    public function __construct(string $output = '')
    {
        $this->output = $output;
    }

    /**
     * @param string $view
     * @param array<string, mixed> $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        $this->calls[] = ['view' => $view, 'data' => $data];

        return $this->output;
    }
}
