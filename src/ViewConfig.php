<?php declare(strict_types=1);

    namespace STDW\View;

    use STDW\View\Exception\ViewConfigException;
    use STDW\View\Spec\AliasTrait;


    class ViewConfig
    {
        use AliasTrait;


        /** @var string
         */
        protected string $storage;

        /** @var string
         */
        protected string $extension;

        /** @var array<string, string>
         */
        protected array $aliases;


        /**
         * @param array{
         *    storage?: string,
         *    extension?: string,
         *    aliases?: array<string, string>,
         * } $config
         * @throws ViewConfigException When the configuration is invalid.
         */
        public function __construct(array $config)
        {
            $defaults = [
                'storage' => '',
                'extension' => '.php',
                'aliases' => [],
            ];

            $config = array_replace($defaults, $config);

            $this->storage = $this->validateStorage($config['storage']);
            $this->extension = $this->validateExtension($config['extension']);
            $this->aliases = $this->validateAliases($config['aliases']);
        }


        /** @return string
         */
        public function storage(): string
        {
            return $this->storage;
        }

        /** @return string
         */
        public function extension(): string
        {
            return $this->extension;
        }

        /** @return array<string, string>
         */
        public function aliases(): array
        {
            return $this->aliases;
        }


        /**
         * @param string $path
         * @return string
         */
        protected function validateStorage(string $path): string
        {
            if ($path === '') {
                throw ViewConfigException::missingStorage();
            }

            return rtrim($path, '/\\');
        }

        /**
         * @param string $extension
         * @return string
         */
        protected function validateExtension(string $extension): string
        {
            if ($extension === '' || ! str_starts_with($extension, '.')) {
                throw ViewConfigException::invalidExtension();
            }

            return $extension;
        }

        /**
         * @param array<mixed> $aliases
         * @return array<string, string>
         */
        protected function validateAliases(array $aliases): array
        {
            $validated = [];

            foreach ($aliases as $name => $path) {
                $name = (string) $name;

                if ( ! is_string($path)) {
                    throw ViewConfigException::invalidAliasPath($name);
                }

                $validated[$name] = $this->normalizeAlias($name, $path);
            }

            return $validated;
        }
    }
