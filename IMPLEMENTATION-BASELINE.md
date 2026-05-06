# CSV2POST Implementation Baseline

## Purpose

This document captures the current CSV2POST architecture that must be preserved or explicitly migrated during modernization. It is the baseline for the first implementation slice.

## Current Bootstrap Flow

Entry file: `csv-2-post.php`

Current responsibilities in the entry file:

- direct-access guard,
- early request bail for login, XML-RPC, and cron,
- plugin constant definitions,
- loading debug helpers,
- loading key legacy classes,
- registering `CSV2POST::init()` on `plugins_loaded`,
- loading the text domain,
- activation/deactivation hook registration.

## Current Runtime Construction

The main runtime object is created through `CSV2POST::init()` in `classes/class-csv2post.php`.

During construction it currently:

- loads configuration, database, PHP, install, files, and automation classes,
- loads settings,
- registers actions and filters,
- registers custom post types,
- registers custom schema through the install class,
- loads admin-only UI, help, and tab menu classes,
- loads current project and current project settings into memory.

## Current Wizard-Like Flow

The plugin already behaves like a wizard even if it is not labeled that way everywhere.

The setup path in `classes/class-pluginmenu.php` is sequential:

1. CSV file
2. Project
3. Data rules
4. Import data
5. Design
6. Posts

This guided flow should be preserved as a primary interface unless a concrete usability or maintenance problem requires major change.

Alternative interfaces are allowed, but they should complement the guided setup path rather than replace it prematurely.

## Current Data Ownership

Install/schema logic is in `classes/class-install.php`.

Known core tables:

- `c2pprojects`
- `c2psources`
- `webtechglobal_schedule`

Functional roles:

- `c2pprojects` stores project identity and project settings.
- `c2psources` stores source metadata, progress, rules, file paths, and target staged table names.
- staged source tables hold imported CSV rows before post creation.

## Current Import Path

The staged import logic is in `classes/class-data.php`.

Observed behavior:

- source rows are fetched from `c2psources`,
- CSV files are read through `SplFileObject`,
- headers are normalized into SQL-ready column names,
- rows are inserted or updated in the staged table,
- rules are applied after initial row preparation,
- source progress is updated,
- the user receives detailed notices about inserted and updated rows.

This staged import path is a product feature, not just an internal detail.

## Current Post Generation Path

Post generation is handled in `classes/class-csv2post.php` and related request/view classes.

Observed behavior:

- post data is built from project settings and row tokens,
- title and content templates are applied,
- custom field templates are applied,
- rows are linked back to posts for later updates,
- WordPress posts are inserted with `wp_insert_post()`.

## Current Risks

- Bootstrap is doing too much directly in the plugin entry file.
- Global class loading is difficult to reason about.
- Data layer and UI layer are tightly coupled through notices and object construction.
- The wizard path is valuable but hard to evolve because the admin framework is legacy-heavy.
- Large imports and post-generation flows likely need queued execution for reliability.

## Modernization Constraints

- Preserve the setup wizard path.
- Preserve staged import into database tables before post generation.
- Preserve read access to existing projects, sources, and staged tables.
- Add alternative interfaces only where they reduce complexity or improve visibility.
- Introduce a new bootstrap path without removing the legacy runtime all at once.

## First Code Slice Goals

1. Move entry-file responsibility into a compatibility-safe bootstrap.
2. Keep `CSV2POST::init()` working unchanged.
3. Keep activation, deactivation, textdomain loading, and legacy class loading intact.
4. Use this as the base for later namespace and service extraction work.

## New Modern Seams Added

The compatibility bootstrap now loads a small modern wrapper layer for future work:

- `src/ServiceContainer.php`
- `src/Services/SourceRepository.php`
- `src/Services/ImportService.php`
- `src/Admin/WizardFlow.php`

These wrappers do not replace the legacy runtime yet. They provide stable modern entry points for future migration while preserving the current plugin behavior.

## Second Modern Seam Layer Added

The diagnostics and schema services expand the wrapper layer for project inspection and schema work:

- `src/Services/ProjectDiagnostics.php` — read-only project health, source inspection, and staged data preview.
- `src/Services/SchemaBuilder.php` — schema definition, validation, and comparison without touching legacy setup.
- `src/Admin/ProjectDiagnosticsPage.php` — modern admin page for viewing project diagnostics using the service layer.

These services are wired into `ServiceContainer` and accessible through the main `CSV2POST` class as:

- `$csv2post->diagnostics()` — project diagnostics service
- `$csv2post->schema_builder()` — schema builder service
- `$csv2post->diagnostics_page()` — diagnostics admin page handler

### Key Characteristics of New Services

- **Read-only by default**: Diagnostics and schema services query existing state without modification.
- **WordPress admin integration**: ProjectDiagnosticsPage registers submenu and AJAX endpoints for modern admin UX.
- **No legacy rewrites**: Services wrap and access existing data structures; they don't modify legacy code paths.
- **Foundation for future UI**: These services are designed to support alternative admin interfaces alongside the legacy wizard.

## Next Modernization Steps

With stable data access, diagnostics, and schema inspection now available:

1. **REST API layer** — expose services through WordPress REST for JavaScript-based interfaces.
2. **Modern admin pages** — create additional pages for schema management, import preview, or data transformation.
3. **Advanced import features** — build retry logic, batch processing, or conditional import rules on top of ImportService.
4. **Real-time monitoring** — use diagnostics service to power dashboard widgets or notifications.
5. **Multi-project management** — use schema comparison for bulk operations or project templates.