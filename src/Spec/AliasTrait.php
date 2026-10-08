<?php declare(strict_types=1);

    namespace STDW\View\Spec;

    use STDW\View\Exception\ViewConfigException;


    trait AliasTrait
    {
        /**
         * @param string $name Alias name.
         * @param string $path Aliased directory path.
         * @return string Normalized directory path.
         * @throws ViewConfigException When the alias name or path is invalid.
         */
        protected function normalizeAlias(string $name, string $path): string
        {
            if (preg_match('/^[A-Za-z0-9]+$/', $name) !== 1) {
                throw ViewConfigException::invalidAliasName($name);
            }

            if ($path === '') {
                throw ViewConfigException::invalidAliasPath($name);
            }

            return rtrim($path, '/\\');
        }
    }