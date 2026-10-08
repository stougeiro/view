# How stougeiro/view compares

`stougeiro/view` is not a template *language* — it is a small view layer: a resolver that turns an identifier (`pages.about`, `admin:sidebar`) into a file path, plus a native-PHP engine (`PhpEngine`) that renders it with a six-method `$this` facade. There is no DSL, no compiler, no cache and no globals.

This page is deliberately fair. We win a little and we lose a little; the point is to state what we propose and what we give up, so you can pick the right tool — even when that tool is not ours.

## What it is / isn't

- **Is:** a swappable engine contract, a resolver with dot notation and aliases, a path guard, shared data, and a complete plain-PHP engine for the simple case.
- **Isn't:** a DSL, a compiled/cached engine, a sandbox for untrusted templates, or a feature platform (no filters, macros, i18n or components).

## At a glance

| | Language | Default escaping | Compiles / caches | Inheritance | Sandbox (untrusted templates) | Aliases / namespaces | Footprint |
|---|---|---|---|---|---|---|---|
| **stougeiro/view** | Native PHP | ❌ opt-in (`$this->echo`) | ❌ none | `extends` + `block` + `yield` | ❌ | ✅ dot + `alias:view`, built in | 1 dep (`view-contract`) |
| **Raw PHP** | Native PHP | ❌ | ❌ | manual | ❌ | ❌ | none |
| **Plates** | Native PHP | ❌ helper `$this->e()` | ❌ | `layout` / `section` / `start` | ❌ | pluggable resolver | small |
| **Smarty** | DSL `{$x}` | ⚠️ configurable | ✅ → PHP + cache | ✅ | ⚠️ via plugins | ✅ | medium |
| **Twig** | DSL `{{ }}` | ✅ auto, context-aware | ✅ → PHP + cache | ✅ `parent()`, macros | ✅ `SandboxExtension` | ✅ `@namespace/path` | medium |
| **Latte** | DSL `{= }` | ✅ auto, context-aware | ✅ → PHP + cache | ✅ | ✅ sandbox | ✅ | medium |
| **Blade** | DSL `{{ }}` | ✅ auto | ✅ → PHP + cache | ✅ components / `@include` | ❌ | ❌ | Laravel-oriented |
| **Mustache** | DSL, logic-less | ✅ `{{ }}`, raw `{{{ }}}` | ⚠️ compiles + cache | ❌ (partials only) | ❌ | ❌ | small |
| **Fenom** | DSL | ✅ | ✅ → PHP + cache | ✅ | ✅ sandbox | ⚠️ | medium |

## Where it fits

- **Auditable simplicity.** ~10 source files, PHPStan level 9, no magic and no global state. A very small attack surface and a codebase you can read in one sitting.
- **No DSL cold-start.** Templates are `.php`, so there is no template parser or AST to build — PHP already parses them. Every render costs roughly the same.
- **Resolution is part of the package.** Twig, Blade and Plates give you an *engine*; you still wire the loader, namespaces and path safety yourself. Here `admin:sidebar` resolves within a registered alias root, with `..` stripped, the identifier always prefixed, and `is_file` verified.
- **A real seam.** The engine is a contract (`ViewEngineInterface`); you can plug Twig, Blade or your own behind the same manager without touching resolution or `share()`.

## Where it trades off

Each of these is a decision, not a bug:

- **Escaping is opt-in.** Native `echo` prints raw; `$this->echo($value)` escapes. Twig, Latte and Blade escape by default (context-aware for HTML/JS/URL). With `stougeiro/view`, raw-printing untrusted data is an XSS footgun you must avoid by discipline.
- **No sandbox.** Templates are trusted code. Twig (`SandboxExtension`) and Latte can render templates from untrusted authors; we cannot, and we say so rather than pretend.
- **No compilation or cache.** Compiled engines are near-native once warm; on very hot paths with large loops they will beat an engine that re-resolves and buffers every render. For page-sized templates the difference is negligible.
- **`include()` takes no arguments.** Data has a single scope: `render()` plus `share()`. Twig `include('x', {...})` and Blade `@include('x', [...])` can pass per-include variables; we cannot.
- **Simple blocks.** `extends` / `block` / `endblock` / `yield` with first-definition-wins. No `parent()` to reach the parent block's content, no nested blocks.
- **No resource library.** No filters, functions, pipes, macros or i18n — those are the DSL engines' territory.

## Performance

Measured on this package (`tests/Performance/RenderPerformanceTest.php`):

| Operation | Approx. |
|---|---|
| Resolve an identifier | ~0.25 µs/op |
| Render a simple template | ~10 µs |
| Render a 2-level `extends` chain | ~29 µs |

For reference, compiled engines are roughly tied with each other after warm-up — e.g. Smarty 5.4 vs Twig 3.11 over 1M iterations (2024): ~9.5s vs ~9.2s, close to plain PHP. Their edge shows up under **volume with a warm cache**; ours is **uniform and cache-free** — no warm-up, no cache invalidation, no stale compiled artifacts.

## When to choose something else

- **Need auto-escaping by default or a sandbox?** → Twig or Latte.
- **All-in on Laravel?** → Blade.
- **Want native PHP templates with a more mature ecosystem?** → Plates (same philosophy as ours, more years behind it).

## Sources

- Smarty 5 vs Twig 3 benchmark (2024) — https://github.com/smarty-php/smarty-vs-twig-benchmark
- PHP template engine benchmark (Blade, Latte, Smarty, Twig) — https://github.com/huqis/template-engine-benchmark
- Template engines comparator — http://phpbenchmarks.com/en/comparator/templating
- Smarty, Twig & co. with auto-escaping on — https://github.com/invench/phpbench-template-engines