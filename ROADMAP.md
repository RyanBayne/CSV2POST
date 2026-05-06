# CSV2POST Roadmap

## Purpose

CSV2POST still has a strong product idea: import CSV data into plugin-managed database tables first, then generate or update posts from staged data. That separation remains the plugin's best differentiator and should be preserved while the surrounding framework is modernized.

This roadmap prioritizes standards, safety, and long-term maintainability without discarding the legacy workflow that made the plugin useful.

## Current State

### Strengths

- Dedicated source and project tables already exist in the install layer.
- Imported rows are staged before post generation.
- Progress tracking exists for source imports.
- Template-driven title, content, taxonomy, and custom field generation already exists.
- The plugin already supports a project/source mental model rather than a one-off import screen.

### Key Evidence In Current Code

- Bootstrap and activation flow: `csv-2-post.php`
- Core install/schema logic: `classes/class-install.php`
- CSV-to-table import flow: `classes/class-data.php`
- Post generation flow: `classes/class-csv2post.php`
- Legacy admin information architecture: `classes/class-pluginmenu.php`
- Template setup views: `views/setupdesign.php`, `views/setupposts.php`
- Imported data monitoring views: `views/infoprojectsdata.php`, `views/infoprojectstable.php`

### Standards Gaps

- No namespaces or modern autoloading.
- Heavy legacy global state and manual class loading.
- Custom SQL layer needs review for prepared queries and input handling.
- Admin UX is fragmented across many legacy views and request handlers.
- Install/uninstall lifecycle is older than current WordPress expectations.
- No clear boundary between import pipeline, mapping rules, and post generation services.
- Documentation and tests are not aligned with current WPSeed standards.

## Product Direction

CSV2POST should be rebuilt as a staged import platform, not just a CSV-to-post shortcut.

Core promise:

- ingest source data safely,
- inspect and validate imported rows,
- build content templates against real staged data,
- generate posts in controlled batches,
- reconcile later source changes without losing control.

The current guided setup flow should remain a first-class experience. The plugin already behaves like a wizard through its sequence of CSV file, project, rules, import, design, and posts. That should be preserved unless a concrete usability or maintenance problem justifies replacing it.

Alternative interfaces are encouraged where they improve visibility or speed for advanced users, but they should sit beside the guided wizard path rather than displacing it early.

## Non-Negotiable Legacy Behaviors To Preserve

- Projects remain a first-class concept.
- Sources remain a first-class concept.
- CSV rows can still be imported into a plugin-managed data table before post creation.
- The guided setup wizard flow remains available as a primary interface.
- Existing project settings and source metadata must remain readable during transition.
- Existing users must still be able to create posts from already imported data.
- Existing table ownership and migration rules must be documented before schema changes.

## Modernization Principles

- Introduce namespaces and a predictable loader.
- Keep compatibility readers for legacy options, tables, and request flows during transition.
- Move to explicit service boundaries: Import, Mapping, Preview, Generation, Update Sync.
- Prefer prepared queries and WordPress APIs where practical.
- Use WPSeed as the standard reference for documentation, install/uninstall discipline, admin separation, and optional background processing patterns.
- Avoid dragging over unrelated WPSeed boilerplate that does not help the staged import product.

## Phase 1: Architecture Baseline

### 1.1 Bootstrap And Load Order Audit

- Document current bootstrap sequence from `csv-2-post.php`.
- Record all always-loaded classes, admin-only classes, and request handlers.
- Identify legacy globals and cross-class coupling that block modularization.

### 1.2 Namespace And Autoload Plan

- Introduce a plugin namespace for new code.
- Keep legacy class aliases or compatibility wrappers where needed.
- Start with new services rather than mass-renaming the entire plugin in one pass.

### 1.3 Data Ownership Review

- Document `c2pprojects`, `c2psources`, and imported source tables.
- Define which schema is legacy-but-supported and which schema becomes the modern target.
- Define migration rules for existing installs before structural changes.

## Phase 2: Import Pipeline Stabilization

### 2.1 Safe Source Registration

- Normalize source registration and validation.
- Harden file-path handling, separator detection, and duplicate-source logic.
- Add explicit validation results instead of mixed notices and side effects.

### 2.2 Staged Import Service

- Extract CSV-to-table import into a dedicated service.
- Preserve row progress tracking.
- Add better import diagnostics: row counts, invalid rows, duplicate IDs, schema mismatches.
- Prepare for batch execution via Action Scheduler or an equivalent queued pattern.

### 2.3 Data Table Introspection

- Add a formal layer for inspecting staged tables and sample rows.
- Make table structure visible in admin so users understand what was imported.

## Phase 3: Builder And Preview Rebuild

### 3.1 Real-Data Preview

- Build a preview tool that renders title, content, custom fields, taxonomy, and dates using actual staged rows.
- Allow sample-row switching without saving unrelated settings.
- Show token resolution errors before post generation.

### 3.2 Mapping/Template Builder

- Replace scattered template screens with a unified builder.
- Support field mapping for title, content, excerpt, slug, status, author, featured image, taxonomy, and custom fields.
- Preserve advanced design-rule logic, but surface it through clearer UI.
- Keep a wizard-oriented guided mode and add an advanced direct-access mode if both can share the same underlying services.

### 3.3 Validation Before Generation

- Add a preflight checklist that verifies required mappings are complete.
- Show how many usable rows are available before generation begins.

## Phase 4: Post Generation And Updates

### 4.1 Batched Post Creation

- Move post creation to explicit batch jobs.
- Add resumable generation with progress and failure reporting.
- Avoid large single-request post creation flows.

### 4.2 Update And Reconciliation Tools

- Make row-to-post linkage explicit and inspectable.
- Support dry-run updates before touching posts.
- Add update rules for changed source rows, missing posts, and locked content.

### 4.3 Auditability

- Record import events, generation events, and update events in a structured way.
- Add rollback guidance for high-impact operations.

## Phase 5: Standards And Safety Refresh

- Add capability checks and nonces to all mutating actions.
- Review all custom SQL for preparation and escaping.
- Modernize uninstall behavior.
- Align asset loading and admin page registration with current standards.
- Add developer documentation and operator documentation.

## Phase 6: Feature Opportunities

These are secondary to the standards and pipeline rebuild, but they are strong growth opportunities.

- Mapping presets for common CSV provider formats.
- Source schema comparison and change warnings.
- Rich preview diff when updating existing posts.
- Scheduled sync jobs for recurring source imports.
- REST API endpoints for headless or external integration.
- AI-assisted mapping suggestions using detected headers and sample rows.
- Import recipes for custom post types and SEO plugins.

## First Implementation Slice

The first code implementation should be narrow and architecture-first:

1. Document current bootstrap/load order.
2. Isolate the DB/import subsystem from the legacy controller layer.
3. Document schema ownership and migration rules.
4. Design the new preview/builder around imported-row access.

This is the right starting point because it protects the plugin's real differentiator before UI rewrites begin.

## Risks

- Legacy installs may depend on table names and serialized settings exactly as they are.
- UI rewrites could accidentally remove advanced template behavior if not mapped carefully.
- Import/update logic is likely coupled to legacy request handlers and notices.
- Large imports will remain fragile unless batch execution is introduced early.

## Verification Expectations

- Activation and schema creation must still work on an existing install.
- Existing projects and sources must remain readable after the first modernization slices.
- Imported data must still be visible before post generation.
- A sample staged row must be usable to preview title/content output before creating posts.
- Batch creation and update flows must be testable with small and large datasets.