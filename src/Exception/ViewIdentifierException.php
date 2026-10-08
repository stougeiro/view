<?php declare(strict_types=1);

    namespace STDW\View\Exception;

    use InvalidArgumentException;


    class ViewIdentifierException extends InvalidArgumentException
    {
        /**
         * @return ViewIdentifierException
         */
        public static function emptyIdentifier(): self
        {
            return new self('view identifier must not be empty.');
        }

        /**
         * @param string $view
         * @return ViewIdentifierException
         */
        public static function emptySegment(string $view): self
        {
            return new self("view identifier '{$view}' has an empty segment.");
        }

        /**
         * @param string $view
         * @return ViewIdentifierException
         */
        public static function tooManyColons(string $view): self
        {
            return new self("view identifier '{$view}' must contain at most one ':'.");
        }

        /**
         * @param string $view
         * @return ViewIdentifierException
         */
        public static function invalidCharacters(string $view): self
        {
            return new self("view identifier '{$view}' contains invalid characters.");
        }

        /**
         * @param string $name
         * @return ViewIdentifierException
         */
        public static function unknownAlias(string $name): self
        {
            return new self("alias '{$name}' is not registered.");
        }
    }
