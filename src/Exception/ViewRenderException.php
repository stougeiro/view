<?php declare(strict_types=1);

    namespace STDW\View\Exception;

    use RuntimeException;


    class ViewRenderException extends RuntimeException
    {
        /**
         * @param string $chain Identifiers joined with an arrow (e.g., "home → layout → home").
         * @return ViewRenderException
         */
        public static function cyclicExtends(string $chain): self
        {
            return new self("cyclic extends detected: {$chain}");
        }

        /**
         * @param string $chain Identifiers joined with an arrow (e.g., "a → b → a").
         * @return ViewRenderException
         */
        public static function cyclicInclude(string $chain): self
        {
            return new self("cyclic include detected: {$chain}");
        }

        /**
         * @param string $name
         * @return ViewRenderException
         */
        public static function nestedBlock(string $name): self
        {
            return new self("block '{$name}' cannot be nested inside another block");
        }

        /**
         * @return ViewRenderException
         */
        public static function strayEndblock(): self
        {
            return new self("endblock() called without a matching block()");
        }

        /**
         * @param string $name
         * @return ViewRenderException
         */
        public static function unclosedBlock(string $name): self
        {
            return new self("block '{$name}' was not closed with endblock()");
        }

        /**
         * @param string $view
         * @return ViewRenderException
         */
        public static function extendsInInclude(string $view): self
        {
            return new self("extends('{$view}') is not allowed inside an include()");
        }
    }
