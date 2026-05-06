# CSV2POST Load Order

## Purpose

This document records the current CSV2POST load order after the compatibility bootstrap was introduced. It exists so future modernization work can move responsibilities deliberately instead of guessing which file currently owns them.

## Entry Sequence

### 1. `csv-2-post.php`

Responsibilities:

- blocks direct access,
- bails early for login, XML-RPC, and cron requests,
- loads the compatibility bootstrap,
- delegates runtime wiring to `CSV2POST\\Bootstrap\\PluginBootstrap::boot()`.

### 2. `bootstrap.php`

Responsibilities:

- defines plugin constants,
- loads the modern service definitions,
- loads the legacy runtime,
- registers textdomain loading,
- registers `CSV2POST::init()` on `plugins_loaded`,
- registers activation and deactivation hooks.

## Modern Runtime Layer

The modern runtime layer is compatibility-oriented. It currently adds stable seams without replacing legacy behavior.

### `src/ServiceContainer.php`

- central access point for modern wrapper services.

### `src/Services/SourceRepository.php`

- wraps legacy source/project/table access.

### `src/Services/ImportService.php`

- wraps the legacy staged import class.

### `src/Services/ProjectDiagnostics.php`

- provides read-only project health and source inspection.

### `src/Services/SchemaBuilder.php`

- handles schema definition, validation, and comparison.

### `src/Admin/WizardFlow.php`

- exposes the current guided setup flow as structured metadata.

### `src/Admin/ProjectDiagnosticsPage.php`

- modern admin page for viewing project diagnostics and sources.

## Legacy Runtime Layer

The compatibility bootstrap still loads these legacy files directly:

- `functions/functions.debug.php`
- `classes/class-wpdb.php`
- `classes/class-options.php`
- `classes/class-install.php`
- `classes/class-configuration.php`
- `classes/class-csv2post.php`
- `classes/class-schedule.php`
- `classes/class-automation.php`

## Runtime Construction After `plugins_loaded`

`CSV2POST::init()` creates the main plugin object, which then:

- loads configuration and utility classes,
- loads settings,
- registers actions and filters,
- registers custom post types,
- registers schema access,
- loads admin-only UI, help, and menu classes,
- loads the current project and project settings.

## Why This Matters

Future work should move responsibilities out of the legacy runtime in this order:

1. repository and schema access,
2. staged import and generation services,
3. wizard and admin routing,
4. template/builder logic,
5. compatibility cleanup after migration stabilizes.
