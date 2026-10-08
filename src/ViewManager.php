<?php declare(strict_types=1);

    namespace STDW\View;

    use STDW\Contract\View\AliasAwareInterface;
    use STDW\Contract\View\ViewEngineInterface;
    use STDW\View\Exception\ViewConfigException;
    use STDW\View\Exception\ViewIdentifierException;
    use STDW\View\Exception\ViewNotFoundException;
    use STDW\View\Exception\ViewRenderException;
    use STDW\View\Spec\AliasTrait;
    use STDW\View\Spec\RenderContext;
    use STDW\View\Spec\TemplateContext;


    class ViewManager implements AliasAwareInterface
    {
        use AliasTrait;


        /** @var array<string, mixed>
         */
        protected array $shared = [];

        /** @var array<string, string>
         */
        protected array $aliases;


        public function __construct(
            protected ViewConfig $config,
            protected ViewEngineInterface $engine)
        {
            $this->aliases = $this->config->aliases();
        }


        /**
         * @param array<string, mixed> $data
         * @return void
         */
        public function share(array $data): void
        {
            $this->shared = [...$this->shared, ...$data];
        }

        /**
         * @param string $view View identifier (e.g., "admin:index" or "pages.about").
         * @param array<string, mixed> $data Local data passed to the view.
         * @return string Rendered output.
         * @throws ViewIdentifierException When the view identifier cannot be resolved.
         * @throws ViewNotFoundException When the engine cannot find the template file.
         * @throws ViewRenderException When template inheritance or block usage is invalid.
         */
        public function render(string $view, array $data = []): string
        {
            $data = [...$this->shared, ...$data];

            $render = function (string $path, TemplateContext $context) use ($data): string {
                return $this->engine->render($path, [...$data, RenderContext::DATA_KEY => $context]);
            };

            $state = new RenderContext(
                fn (string $view): string => $this->resolveAlias($view),
                $render,
            );

            $context = $state->template();

            $path = $this->resolveAlias($view);
            $visited = [$path => $view];

            while (true) {
                $state->enterPass($path, $view);

                $output = $render($path, $context);
                $openBlock = $state->openBlockName();

                if ($openBlock !== null) {
                    throw ViewRenderException::unclosedBlock($openBlock);
                }

                $parent = $state->takeParent();

                if ($parent === null) {
                    return $output;
                }

                $path = $this->resolveAlias($parent);

                if (isset($visited[$path])) {
                    throw ViewRenderException::cyclicExtends(
                        implode(' → ', [...array_values($visited), $parent]),
                    );
                }

                $visited[$path] = $parent;
                $view = $parent;
            }
        }

        /**
         * @param string $name Alias name.
         * @param null|string $path Directory path (null when retrieving).
         * @return null|string Returns the directory path when used as a getter.
         * @throws ViewConfigException When the alias name or path is invalid.
         */
        public function alias(string $name, ?string $path = null): ?string
        {
            if ($path === null) {
                return $this->aliases[$name] ?? null;
            }

            $this->aliases[$name] = $this->normalizeAlias($name, $path);

            return null;
        }

        /**
         * @param string $view View identifier.
         * @return string Complete file path of the view template.
         * @throws ViewIdentifierException When the identifier is empty, malformed, or references an unknown alias.
         */
        public function resolveAlias(string $view): string
        {
            if ($view === '') {
                throw ViewIdentifierException::emptyIdentifier();
            }

            if (preg_match('/[\x00-\x1F]/', $view) === 1) {
                throw ViewIdentifierException::invalidCharacters($view);
            }

            $prefix = $this->config->storage();
            $path = $view;

            if (str_contains($view, ':')) {
                [$name, $path] = explode(':', $view, 2);

                if ($name === '' || $path === '') {
                    throw ViewIdentifierException::emptySegment($view);
                }

                if (str_contains($path, ':')) {
                    throw ViewIdentifierException::tooManyColons($view);
                }

                if ( ! isset($this->aliases[$name])) {
                    throw ViewIdentifierException::unknownAlias($name);
                }

                $prefix = $this->aliases[$name];
            }

            $path = str_replace('..', '', $path);
            $path = str_replace('.', '/', $path);

            return $prefix . '/' . $path . $this->config->extension();
        }
    }
