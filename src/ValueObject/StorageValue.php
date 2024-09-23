<?php declare(strict_types=1);

    namespace STDW\View\ValueObject;

    use STDW\ValueObject\ValueObjectAbstracted;


    final class StorageValue extends ValueObjectAbstracted
    {
        public function __construct(
            protected string $path)
        { }


        public function get(): string
        {
            return $this->path;
        }

        public function isValid(): bool
        {
            if (
                   ! file_exists($this->path)
                || ! is_dir($this->path)
                || ! is_writable($this->path))
            {
                return false;
            }

            return true;
        }

        public function __toString(): string
        {
            return $this->get();
        }
    }
