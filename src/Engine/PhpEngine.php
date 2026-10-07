<?php declare(strict_types=1);

    namespace STDW\View\Engine;

    use STDW\Contract\View\ViewEngineInterface;
    use STDW\View\Exception\ViewNotFoundException;
    use Throwable;


    class PhpEngine implements ViewEngineInterface
    {
        /**
         * @param string $view Fully-qualified path to the PHP template file.
         * @param array<string, mixed> $data Variables made available to the view.
         * @return string Rendered output produced by the template.
         * @throws ViewNotFoundException When the template file does not exist.
         */
        public function render(string $view, array $data = []): string
        {
            if ( ! is_file($view)) {
                throw ViewNotFoundException::fileNotFound($view);
            }

            return (static function (string $__view, array $__data): string {
                $__level = ob_get_level();

                ob_start();

                try {
                    extract($__data, EXTR_SKIP);
                    include $__view;
                } catch (Throwable $__e) {
                    while (ob_get_level() > $__level) {
                        ob_end_clean();
                    }

                    throw $__e;
                }

                if (ob_get_level() <= $__level) {
                    return '';
                }

                $__output = ob_get_clean();

                return $__output === false ? '' : $__output;
            })($view, $data);
        }
    }
