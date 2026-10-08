<?php declare(strict_types=1);

    namespace STDW\View\Spec;


    /**
     * The surface exposed to templates as $this. Templates can call these
     * five methods and nothing else: the internal machinery of RenderContext
     * is unreachable from a template.
     */
    final class TemplateContext
    {
        /**
         * @param RenderContext $context
         */
        public function __construct(
            private RenderContext $context)
        { }


        /**
         * Declares the parent layout. The first call wins.
         *
         * @param string $view View identifier (e.g., "layouts.main" or "admin:shell").
         * @return void
         */
        public function extends(string $view): void
        {
            $this->context->extends($view);
        }

        /**
         * Renders another view by identifier and echoes it.
         *
         * @param string $view View identifier (e.g., "partials.row" or "admin:sidebar").
         * @return void
         */
        public function include(string $view): void
        {
            $this->context->include($view);
        }

        /**
         * Opens a block. Blocks cannot be nested.
         *
         * @param string $name
         * @return void
         */
        public function block(string $name): void
        {
            $this->context->block($name);
        }

        /**
         * Closes the current block, capturing its content.
         *
         * @return void
         */
        public function endblock(): void
        {
            $this->context->endblock();
        }

        /**
         * Returns the captured content of a block, or the default.
         *
         * @param string $name
         * @param string $default
         * @return string
         */
        public function yield(string $name, string $default = ''): string
        {
            return $this->context->yield($name, $default);
        }

        /**
         * Returns an HTML-safe escaped version of a value. Native `echo`
         * prints raw content; this is the escape by default.
         *
         * @param mixed $value
         * @return string
         */
        public function echo(mixed $value): string
        {
            return $this->context->echo($value);
        }
    }