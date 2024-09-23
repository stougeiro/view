<?php declare(strict_types=1);

    use STDW\View\Contract\ViewInterface;


    if ( ! function_exists('view'))
    {
        function view(): ViewInterface
        {
            return app()->make(ViewInterface::class);
        }
    }