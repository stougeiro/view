<?php declare(strict_types=1);

    namespace STDW\View\Exception;

    use InvalidArgumentException;


    class ViewConfigException extends InvalidArgumentException
    {
        /**
         * @return ViewConfigException
         */
        public static function missingStorage(): self
        {
            return new self('"storage" is required.');
        }

        /**
         * @return ViewConfigException
         */
        public static function invalidExtension(): self
        {
            return new self('"extension" must start with ".".');
        }

        /**
         * @param string $name
         * @return ViewConfigException
         */
        public static function invalidAliasName(string $name): self
        {
            return new self("alias name '{$name}' must match [A-Za-z0-9].");
        }

        /**
         * @param string $name
         * @return ViewConfigException
         */
        public static function invalidAliasPath(string $name): self
        {
            return new self("alias '{$name}' path must be a non-empty string.");
        }
    }
