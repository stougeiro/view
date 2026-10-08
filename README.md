![PHP](https://img.shields.io/badge/PHP-%20^8.2-777BB4)
![PHPStan-Level](https://img.shields.io/badge/PHPStan-Level%209-224488)
![Pest-php](https://img.shields.io/badge/Tests-Passed-019733)
![License](https://img.shields.io/badge/License-MIT-777)

# View

A light, engine-agnostic view layer for PHP. It resolves view identifiers (dot notation and aliases) into file paths, merges shared and local data, and renders templates through a swappable engine — all behind the `stougeiro/view-contract` interfaces. No global helpers, no hidden state, no cache: pure dependency injection.

## ✨ Features

- **Contract-Driven**  
  Implements `ViewEngineInterface`, `ViewManagerInterface`, and `AliasAwareInterface` from `stougeiro/view-contract`, keeping your application decoupled from the rendering implementation.

- **Swappable Engines**  
  `PhpEngine` is a zero-dependency default. Replace it with any `ViewEngineInterface` implementation — the manager does not care how views are compiled or rendered.

- **Dot Notation and Aliases**  
  `pages.about` resolves inside the storage root, while `admin:sidebar` resolves inside a registered alias directory — both producing a complete file path.

- **Shared Data**  
  `share()` accumulates global data for every render; local data passed to `render()` always wins on key conflicts.

- **No Global Helpers**  
  Everything is wired through constructors, making the package trivial to test and impossible to couple to accidentally.

- **The Proposal, Not a Placeholder**  
  `PhpEngine` is a complete template engine for the simple case: plain-PHP templates, no compiler, no warm-up, no cache. No decorations, no comfort features. A respectable performance baseline delivered with maximum simplicity.

Curious how it stacks up against Twig, Blade, Plates and friends? See the [comparison](COMPARISON.md).

---

## 📦 Installation

Install via Composer:

```bash
composer require stougeiro/view
```

## 🚀 Usage Example

### Basic Usage

```php
use STDW\View\Engine\PhpEngine;
use STDW\View\ViewConfig;
use STDW\View\ViewManager;

$config = new ViewConfig([
    'storage' => __DIR__ . '/views',
    'extension'    => '.php',          // optional (default)
    'aliases'      => [                // optional
        'admin' => __DIR__ . '/views/admin',
    ],
]);

$view = new ViewManager($config, new PhpEngine());

// Data shared with every render
$view->share(['site' => 'My App', 'locale' => 'pt_BR']);

// Dot notation → {storage}/pages/about.php
echo $view->render('pages.about', ['title' => 'About']);

// Alias → {alias}/sidebar.php
echo $view->render('admin:sidebar');

// Aliases can also be registered at runtime
$view->alias('blog', __DIR__ . '/blog/views');
echo $view->render('blog:post', ['slug' => 'hello-world']);
```

### Templates

Templates are plain PHP files. Data arrives as variables:

```php
<!-- {storage}/pages/about.php -->
<h1><?= $this->echo($title) ?></h1>
<p>Locale: <?= $locale ?></p>
```

Directives are native PHP — `if`, `foreach`, `echo`, everything. No `{{ }}` to learn. Templates also receive `$this`, a `TemplateContext` with six methods for layout inheritance and safe output:

| Method | Role |
|---|---|
| `echo($value)` | Escapes the value (`ENT_QUOTES \| ENT_SUBSTITUTE`, UTF-8) and returns HTML-safe output. |
| `extends('view.name')` | Declares the parent layout. The first call wins. |
| `include('admin:sidebar')` | Renders another view by identifier and echoes it. |
| `block('name')` | Opens a block. Blocks cannot be nested. |
| `endblock()` | Closes the block. The first definition wins. |
| `yield('name', $default)` | Outputs a block, or `$default` when no one defined it. |

Native `echo` prints raw content; `$this->echo($value)` is the escape by default. Prefer `<?= $this->echo($var) ?>` for anything that can carry user data, and keep raw `echo` only for markup you explicitly trust.

```php
<!-- {storage}/layouts/main.php -->
<html>
<head>
    <title><?= $this->yield('title', 'Untitled') ?></title>
</head>
<body>
    <?= $this->yield('content') ?>
    <?php $this->include('admin:sidebar') ?>
</body>
</html>
```

```php
<!-- {storage}/pages/home.php -->
<?php $this->extends('layouts.main') ?>

<?php $this->block('title') ?>Home<?php $this->endblock() ?>

<?php $this->block('content') ?>
    <p>Hello <?= $this->echo($name) ?></p>
<?php $this->endblock() ?>
```

`extends` chains through any number of levels and cycles are detected. A view without `extends` still renders as a standalone template.

Those six methods are all a template can call — `$this` is a facade. The rendering machinery behind it (`takeParent`, `enterPass`, block bookkeeping) lives in the internal `STDW\View\Spec\RenderContext` and is unreachable from a template; calling an internal method raises a plain PHP `Error`.

There is a single entry point for variables: `render($view, $data)` plus `share()`. Every layout pass and every `include()` receives exactly that data — `include()` takes no extra variables. One consequence: a variable reassigned locally in a template (e.g. `$title = strtoupper($title)`) is not visible to the included template. Blocks are captured *output*; a block defined in a template without `extends` only appears through `yield()` in the same file.

### Error Handling

Every failure is a named exception under `STDW\View\Exception`, all extending the SPL types you already catch:

| Exception | Extends | Thrown when |
|---|---|---|
| `ViewConfigException` | `InvalidArgumentException` | The config or an `alias()` registration is invalid (storage and extension must be non-empty strings, alias name matching `[A-Za-z0-9]`). |
| `ViewIdentifierException` | `InvalidArgumentException` | The view identifier is empty, malformed, contains control characters, has more than one `:`, or references an unknown alias. |
| `ViewNotFoundException` | `RuntimeException` | The resolved template file does not exist on disk. |
| `ViewRenderException` | `RuntimeException` | Template inheritance or block usage is invalid (cyclic `extends`/`include`, nested/stray/unclosed blocks, `extends()` inside an `include()`). |

```php
use STDW\View\Exception\ViewIdentifierException;

try {
    echo $view->render('admin:sidebar');
} catch (ViewIdentifierException $e) {
    echo $e->getMessage(); // "alias 'admin' is not registered."
}
```

---

## 🔒 Security Boundaries

- **Templates are code.** Whoever can create or edit a template runs PHP. Treat template authorship as trusted; never let end users author template files.
- **Aliases declare trusted directories.** `alias($name, $path)` points at a root you control — the path is a developer decision, never user input.
- **Identifiers are untrusted input.** `..`, absolute paths, backslashes, percent-encoded traversal and stream wrappers (`php://`, `phar://`) cannot escape the storage root or the alias root: `..` segments are removed, the identifier is always prefixed and `is_file`-verified, and control bytes are rejected with `ViewIdentifierException`.
- **The facade is the only template surface.** Templates see exactly six methods (`extends`, `include`, `block`, `endblock`, `yield`, `echo`) — the internal rendering machinery is unreachable and the internal `__context` data key cannot be spoofed from `$data`.
- **Raw output is free, escaped output is opt-in.** `yield()`, `include()`, native `echo` and captured blocks flow raw. `$this->echo($value)` escapes for HTML — attribute-safe (`ENT_QUOTES`), UTF-8, double-encoding on — but it is not context-aware: escaping for inline JavaScript or URL query strings stays a manual decision. Escape user data with `$this->echo`; reserve raw output for template-authored markup.

---

## ⏳ Long-running runtimes

Under PHP-FPM every request starts from a clean process, so a single instance works. In long-running runtimes — Swoole, RoadRunner, FrankenPHP — the process is reused, so treat the mutable parts as per-request:

- **Create one `ViewManager` per request** (or reset it). `share()` and a runtime `alias()` mutate the instance; reusing it across requests would leak shared data and aliases between them.
- **`PhpEngine` and `ViewConfig` are safe to share.** They hold no per-request state — `ViewConfig` is immutable after construction.
- **Enable the opcode cache for the CLI SAPI** (`opcache.enable_cli=1`). These runtimes use the CLI SAPI, where OPcache is off by default; without it every render reparses the template.
- **Keep templates synchronous.** Rendering runs inside output buffers and returns a string — pass everything in through `render($view, $data)` rather than suspending a coroutine (e.g. an I/O call) from inside a template.

---

## 🧠 Why?

Because a view layer should do two things well — resolve a view identifier to a file, and render it with data — without becoming a framework. `PhpEngine` is the proposal itself, not a stepping stone toward a fancier engine: templates are plain PHP, so there is nothing to compile or warm up. The six `$this` methods are the whole template surface, escape included.

The design draws a hard line: no compilation cache, no global functions, no configuration discovery, no comfort features. Just `include` under control — a basic, performant way to cover a simple and sufficient template engine.

That boundary is intentional. If you outgrow it — compiled templates, components, caching — you swap the engine behind `ViewEngineInterface` and leave the manager and your views untouched.

The goal is to offer a view layer that:
- depends only on `stougeiro/view-contract`,
- is trivially testable through constructor injection,
- stays predictable and easy to debug,
- and can evolve (caching, components, compilation) by swapping the engine instead of rewriting the manager.

---

## 🤝 Contributions

Contributions are welcome.
Feel free to open issues or submit pull requests.

<br>

[<img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" width="170"/>](https://www.buymeacoffee.com/stougeiro)
