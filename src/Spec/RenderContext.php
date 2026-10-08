<?php declare(strict_types=1);

    namespace STDW\View\Spec;

    use Closure;
    use STDW\View\Exception\ViewRenderException;
    use Stringable;


    class RenderContext
    {
        /** Reserved data key the engine consumes to bind this context as $this.
         */
        public const DATA_KEY = '__context';

        /** @var Closure(string): string Resolves a view identifier to a file path.
         */
        private Closure $resolver;

        /** @var Closure(string, TemplateContext): string Renders a resolved path with the entry data.
         */
        private Closure $renderer;

        /** @var null|TemplateContext Cached facade exposed to templates.
         */
        private ?TemplateContext $template = null;

        /** @var array<string, string> Resolved path => view identifier (include chain).
         */
        private array $pathStack = [];

        /** @var array<string, string> Captured block content; first definition wins.
         */
        private array $blocks = [];

        /** @var null|string
         */
        private ?string $openBlock = null;

        /** @var null|int
         */
        private ?int $openBlockLevel = null;

        /** @var null|string
         */
        private ?string $extends = null;

        /** @var int
         */
        private int $includeDepth = 0;

        /** @var string
         */
        private string $currentPath = '';

        /** @var string
         */
        private string $currentId = '';


        /**
         * @param Closure(string): string $resolver
         * @param Closure(string, TemplateContext): string $renderer
         */
        public function __construct(Closure $resolver, Closure $renderer)
        {
            $this->resolver = $resolver;
            $this->renderer = $renderer;
        }


        /**
         * The facade exposed to templates as $this. The same instance is
         * reused for every pass and include of a render chain.
         *
         * @internal Used by ViewManager.
         * @return TemplateContext
         */
        public function template(): TemplateContext
        {
            return $this->template ??= new TemplateContext($this);
        }

        /**
         * Declares the parent layout. The first call wins.
         *
         * @param string $view View identifier (e.g., "layouts.main" or "admin:shell").
         * @return void
         * @throws ViewRenderException When called inside an include().
         */
        public function extends(string $view): void
        {
            if ($this->includeDepth > 0) {
                throw ViewRenderException::extendsInInclude($view);
            }

            $this->extends ??= $view;
        }

        /**
         * Renders another view by identifier and echoes it. The included
         * template receives exactly the data of the entry render().
         *
         * @param string $view View identifier (e.g., "partials.row" or "admin:sidebar").
         * @return void
         * @throws ViewRenderException When the include chain is cyclic.
         */
        public function include(string $view): void
        {
            $path = ($this->resolver)($view);

            if ($path === $this->currentPath || isset($this->pathStack[$path])) {
                $chain = implode(' → ', [$this->currentId, ...array_values($this->pathStack), $view]);

                throw ViewRenderException::cyclicInclude($chain);
            }

            $this->pathStack[$path] = $view;
            $this->includeDepth++;

            try {
                echo ($this->renderer)($path, $this->template());
            } finally {
                $this->includeDepth--;
                unset($this->pathStack[$path]);
            }
        }

        /**
         * Opens a block. Blocks cannot be nested.
         *
         * @param string $name
         * @return void
         * @throws ViewRenderException When another block is already open.
         */
        public function block(string $name): void
        {
            if ($this->openBlock !== null) {
                throw ViewRenderException::nestedBlock($name);
            }

            ob_start();

            $this->openBlock = $name;
            $this->openBlockLevel = ob_get_level();
        }

        /**
         * Closes the current block, capturing its content. The first
         * definition of a block wins; later writes are discarded.
         *
         * @return void
         * @throws ViewRenderException When no block is open.
         */
        public function endblock(): void
        {
            $name = $this->openBlock;

            if ($name === null) {
                throw ViewRenderException::strayEndblock();
            }

            $level = $this->openBlockLevel;

            $this->openBlock = null;
            $this->openBlockLevel = null;

            while ($level !== null && ob_get_level() > $level) {
                ob_end_clean();
            }

            if ($level === null || ob_get_level() < $level) {
                $captured = '';
            } else {
                $__output = ob_get_clean();
                $captured = $__output === false ? '' : $__output;
            }

            $this->blocks[$name] ??= $captured;
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
            return $this->blocks[$name] ?? $default;
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
            $target = match (true) {
                $value instanceof Stringable => (string) $value,
                is_scalar($value) => (string) $value,
                default => '',
            };

            return htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        /**
         * Returns and clears the declared parent view. Used by the manager
         * to consume the declaration once per pass.
         *
         * @internal Used by ViewManager.
         * @return string|null
         */
        public function takeParent(): ?string
        {
            $parent = $this->extends;

            $this->extends = null;

            return $parent;
        }

        /**
         * Records the path and identifier of the pass being rendered.
         *
         * @internal Used by ViewManager.
         * @param string $path
         * @param string $id
         * @return void
         */
        public function enterPass(string $path, string $id): void
        {
            $this->currentPath = $path;
            $this->currentId = $id;
        }

        /**
         * The name of the block left open, if any.
         *
         * @internal Used by ViewManager.
         * @return string|null
         */
        public function openBlockName(): ?string
        {
            return $this->openBlock;
        }
    }
