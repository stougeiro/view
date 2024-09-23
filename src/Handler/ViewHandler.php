<?php declare(strict_types=1);

    namespace STDW\View\Handler;

    use STDW\View\Contract\ViewHandlerInterface;
    use STDW\View\ValueObject\StorageValue;
    use STDW\Support\Str;


    class ViewHandler implements ViewHandlerInterface
    {
        protected array $storage = [];

        protected string $storage_separator = ':';

        protected string $file_extension = '.php';


        public function __construct()
        { }


        public function setStorage(string $name, string $path): void
        {
            $path = StorageValue::create($path);

            if ( ! $path->isValid()) {
                throw new ViewException("View: '{$path->get()}' not found or not is a valid directory");
            }

            if (Str::empty($name) || in_array($name, array_keys($this->storage))) {
                throw new ViewException("View: '{$name}' is empty or already exists in storage collection");
            }

            $this->storage[$name] = $path->get();
        }


        public function compile(string $filepath, array $data = []): string
        {
            if (strpos($filepath, $this->storage_separator)) {
                list($storage, $filepath) = explode($this->storage_separator, $filepath);

                $filepath = ($this->storage[$storage] ?? null) . $filepath;
            }

            if ( ! strpos($filepath, $this->file_extension)) {
                $filepath .= $this->file_extension;
            }

            if ( ! file_exists($filepath)) {
                throw new ViewException("View: '{$filepath}' not found");
            }

            ob_start();
                extract($data);
                include $filepath;

                $output = ob_get_contents();
            ob_end_clean();

            return $output;
        }

        public function render(string $filepath, array $data = []): void
        {
            echo $this->compile($filepath, $data);
        }
    }