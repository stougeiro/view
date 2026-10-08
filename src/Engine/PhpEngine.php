<?php declare(strict_types=1);

    namespace STDW\View\Engine;

    use Closure;
    use STDW\Contract\View\ViewEngineInterface;
    use STDW\View\Exception\ViewNotFoundException;
    use STDW\View\Spec\RenderContext;
    use STDW\View\Spec\TemplateContext;
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

            $__context = $data[RenderContext::DATA_KEY] ?? null;

            unset($data[RenderContext::DATA_KEY]);

            return (static function (string $__view, array $__data, ?TemplateContext $__context): string {
                $__level = ob_get_level();

                ob_start();

                $__include = function () use ($__view, $__data): void {
                    extract($__data, EXTR_SKIP);
                    include $__view;
                };

                if ($__context !== null) {
                    $__include = Closure::bind($__include, $__context, TemplateContext::class) ?? $__include;
                }

                try {
                    $__include();
                } catch (Throwable $__e) {
                    while (ob_get_level() > $__level) {
                        ob_end_clean();
                    }

                    throw $__e;
                }

                while (ob_get_level() > $__level + 1) {
                    ob_end_clean();
                }

                if (ob_get_level() <= $__level) {
                    return '';
                }

                $__output = ob_get_clean();

                return $__output === false ? '' : $__output;
            })($view, $data, $__context instanceof TemplateContext ? $__context : null);
        }
    }
