<?php declare(strict_types=1);

    namespace STDW\View;

    use STDW\Contract\ServiceProviderAbstracted;
    use STDW\View\Contract\ViewInterface;
    use STDW\View\Contract\ViewHandlerInterface;
    use STDW\View\View;
    use STDW\View\Handler\ViewHandler;


    class ServiceProvider extends ServiceProviderAbstracted
    {
        public function register(): void
        {
            $this->app->singleton(ViewInterface::class, View::class);
            $this->app->singleton(ViewHandlerInterface::class, ViewHandler::class);
        }

        public function boot(): void
        { }

        public function terminate(): void
        { }
    }