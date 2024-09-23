<?php declare(strict_types=1);

    namespace STDW\View;

    use STDW\View\Contract\ViewInterface;
    use STDW\View\Contract\ViewHandlerInterface;


    class View implements ViewInterface
    {
        protected ViewHandlerInterface $handler;


        public function __construct(ViewHandlerInterface $handler)
        {
            $this->handler = $handler;
        }


        public function setStorage(string $name, string $path): void
        {
            $this->handler->setStorage($name, $path);
        }

        public function compose(array $data): void
        {
            $this->handler->compose($data);
        }

        public function compile(string $filepath, array $data = []): string
        {
            return $this->handler->compile($filepath, $data);
        }

        public function render(string $filepath, array $data = []): void
        {
            echo $this->handler->render($filepath, $data);
        }
    }