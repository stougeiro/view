<?php declare(strict_types=1);

    namespace STDW\View\Exception;

    use RuntimeException;


    class ViewNotFoundException extends RuntimeException
    {
        /**
         * @param string $file
         * @return ViewNotFoundException
         */
        public static function fileNotFound(string $file): self
        {
            return new self("View file '{$file}' not found");
        }
    }
