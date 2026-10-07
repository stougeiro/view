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

- **Path Guard**  
  Parent-directory traversal (`..`) is stripped from user-supplied view identifiers before resolution.

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
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<p>Locale: <?= $locale ?></p>
```

### Error Handling

Every failure is a named exception under `STDW\View\Exception`, all extending the SPL types you already catch:

| Exception | Extends | Thrown when |
|---|---|---|
| `ViewConfigException` | `InvalidArgumentException` | The config or an `alias()` registration is invalid (storage, extension, alias name matching `[A-Za-z0-9]`). |
| `ViewIdentifierException` | `InvalidArgumentException` | The view identifier is empty, malformed, has more than one `:`, or references an unknown alias. |
| `ViewNotFoundException` | `RuntimeException` | The resolved template file does not exist on disk. |

```php
use STDW\View\Exception\ViewIdentifierException;

try {
    echo $view->render('admin:sidebar');
} catch (ViewIdentifierException $e) {
    echo $e->getMessage(); // "alias 'admin' is not registered."
}
```

---

## 🧠 Why?

Because a view layer should do two things well — resolve a view identifier to a file, and render it with data — without becoming a framework. This package draws a hard line at rendering: no compilation cache (opcache and dedicated engines like the future `stougeiro/view-stela` handle that), no global functions, no configuration discovery.

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
