<?php declare(strict_types=1);

    use STDW\View\View;


    if ( ! function_exists('view'))
    {
        function view(): View
        {
            return app()->make(View::class);
        }
    }