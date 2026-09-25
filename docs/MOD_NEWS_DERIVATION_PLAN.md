# `mod_news` / `mod_news_category` Derivation Plan

This document defines the implementation plan for deriving `mod_news` and `mod_news_category` modules from existing `mod_content` and `mod_category` modules, while keeping the original modules intact.

The objective is not to add new features, but to create a dedicated module family for news while preserving existing runtime behavior.

## Goals

- Keep `mod_content` intact.
- Keep `mod_category` intact.
- Create a new `mod_news` module based on `mod_content`.
- Create a new `mod_news_category` module based on `mod_category`.
- Configure `mod_news` to use `mod_news_category` instead of `mod_category` as its category source.
- In the initial phase, replicate behavior without functional expansion.

## Rationale

In this framework, a module name is not merely a folder name. The following layers depend on the module name:

- PHP class names
- `Load::model()` and `Load::lang()` calls
- Permission keys
- Installer registrations
- SQL table names
- Admin and frontend template paths
- Static asset directories
- String-based dependencies of other modules

Therefore, a derivation approach is safer than an in-place rename. Existing modules remain unbroken, and new modules can be verified incrementally.

## Scope

The plan covers:

- Module folders
- Model and validator classes
- Language dictionary files
- Installer files
- SQL schemas
- Admin templates
- Frontend templates
- Menu, permission, and relation references
- Data migration and validation

The plan does not cover:

- Adding new business rules
- Adding new schema fields
- Designing new UI flows
- Modifying `mod_content` or `mod_category` behavior

## Implementation Strategy

The work is organized in two major phases:

1. Derive `mod_news_category` and verify it is fully functional.
2. Derive `mod_news` and wire it to `mod_news_category`.

This sequence is essential because category selectors, nested category trees, join queries, and frontend content filters in `mod_news` depend on the category module.

## Primary File Surface

### Source Modules

`mod_category`:

- `mod/mod_category/index.php`
- `mod/mod_category/model/mod_category.model.php`
- `mod/mod_category/validator/add.validator.php`
- `mod/mod_category/validator/edit.validator.php`
- `mod/mod_category/lang/*.php`
- `mod/mod_category/mod_category.sql`
- `installer/mod_category/index.php`
- `installer/mod_category/mod_category.sql`
- `templates/admin/mod_category/*.twig`
- `templates/admin_old/mod_category/*.twig`

`mod_content`:

- `mod/mod_content/index.php`
- `mod/mod_content/model/mod_content.model.php`
- `mod/mod_content/model/contents.model.php`
- `mod/mod_content/model/datatable.model.php`
- `mod/mod_content/model/flexigrid.model.php`
- `mod/mod_content/validator/add.validator.php`
- `mod/mod_content/validator/edit.validator.php`
- `mod/mod_content/lang/*.php`
- `mod/mod_content/mod_content.sql`
- `installer/mod_content/index.php`
- `installer/mod_content/mod_content.sql`
- `installer/mod_content/contents.sql`
- `templates/admin/mod_content/*.twig`
- `templates/admin_old/mod_content/*.twig`
- `templates/admin_old/modern/mod_content/*.twig`
- `templates/default/index/mod_content/*.twig`
- `templates/artist/index/mod_content/*.twig`
- `templates/soul/index/mod_content/*.twig`

### Target Derived Modules

- `mod/mod_news/...`
- `mod/mod_news_category/...`
- `installer/mod_news/...`
- `installer/mod_news_category/...`
- `templates/admin/mod_news/...`
- `templates/admin/mod_news_category/...`
- `templates/admin_old/...` (if required)
- Frontend theme counterparts (if required)

## Phase 1: Deriving `mod_news_category`

This phase must be completed independently.

### 1. File Replication

Replicate the `mod_category` directory structure under `mod_news_category`:

- `mod/mod_category` -> `mod/mod_news_category`
- `installer/mod_category` -> `installer/mod_news_category`
- `templates/admin/mod_category` -> `templates/admin/mod_news_category`
- `templates/admin_old/mod_category` -> `templates/admin_old/mod_news_category` (if applicable)

### 2. Class and Loader References

Migrate the following identifiers to the new module:

- `mod_category` -> `mod_news_category`
- `class mod_category_mod` -> `class mod_news_category_mod`
- `class mod_category_model` -> `class mod_news_category_model`
- `Load::lang('mod_category')` -> `Load::lang('mod_news_category')`
- `Load::model('mod_category')` -> `Load::model('mod_news_category')`
- Permission checks: `mod_category` -> `mod_news_category`

### 3. SQL and Table Names

The new category module must use its own table:

- Duplicate the `mod_category` table structure as `mod_news_category`
- Update the installer SQL to create this table
- Set the `$_table` property in the model to `mod_news_category`

### 4. Language Keys

Set the root key in language dictionary files to `mod_news_category`.

Example:

- `$lang['mod_category']` -> `$lang['mod_news_category']`

Strings may retain generic category terminology initially; isolating the root module key is the primary goal.

### 5. Template Paths

Update admin template references to the new path:

- `admin/mod_category/add` -> `admin/mod_news_category/add`
- `admin/mod_category/edit` -> `admin/mod_news_category/edit`
- `admin/mod_category/list` -> `admin/mod_news_category/list`

### 6. Menu and Permission Records

Within the installer:

- Create the module entry named `mod_news_category`
- Register default permissions with the `mod_news_category` key
- Wire new routes into admin navigation if required

### 7. Verification

Verify the following at the end of this phase:

- Category listing
- Category creation
- Category editing
- Category deletion
- Category ordering and parent-child hierarchy
- Category image upload/delete (if applicable)
- Admin template rendering pipeline

## Phase 2: Deriving `mod_news`

This phase must be initiated only after `mod_news_category` is stable.

### 1. File Replication

Replicate the `mod_content` structure under `mod_news`:

- `mod/mod_content` -> `mod/mod_news`
- `installer/mod_content` -> `installer/mod_news`
- `templates/admin/mod_content` -> `templates/admin/mod_news`
- `templates/admin_old/mod_content` -> `templates/admin_old/mod_news` (if applicable)
- Replicate frontend theme templates under `mod_news`

### 2. Class and Loader References

Migrate the following identifiers to the new module:

- `mod_content` -> `mod_news`
- `class mod_content_mod` -> `class mod_news_mod`
- `class mod_content_model` -> `class mod_news_model`
- `Load::lang('mod_content')` -> `Load::lang('mod_news')`
- `Load::model('mod_content')` -> `Load::model('mod_news')`
- Permission checks: `mod_content` -> `mod_news`

### 3. Database Isolation

`mod_news` must use its own table family:

- Clone the `mod_content` table as `mod_news`
- Derive `installer/mod_news/mod_news.sql` from `installer/mod_content/mod_content.sql`
- Derive `installer/mod_news/contents.sql` from `installer/mod_content/contents.sql`
- Configure `$_table = "mod_news"` in associated models

### 4. Category Dependency Decoupling

This is the most critical step of Phase 2. `mod_news` must use `mod_news_category` instead of `mod_category`.

Key dependencies to migrate:

- `Load::model('mod_category')` -> `Load::model('mod_news_category')`
- Category select option dropdowns
- Nested category helper usage
- SQL joins: `mod_category` -> `mod_news_category`
- Category SEO and category name mappings
- Frontend helper category data sources

Note:

- The `category_id` column name may be preserved.
- However, it must reference IDs in `mod_news_category.id`.

### 5. Media, Attachment, and Gallery Relations

If `mod_news` uses ancillary modules like `mod_content`, update them with the new module identifier.

Areas to check:

- `mod_gallery_mod.mod_name`
- `mod_attachment_mod.mod_name`
- Tool modules checking `exists_mod_name('mod_news')`
- Permission checks
- Download and media access helpers

### 6. Template and Route Separation

Admin routes:

- `mod_news/add`
- `mod_news/edit`
- `mod_news/list`

Frontend routes:

- `mod_news/content`
- `mod_news/contents`
- `mod_news/arsiv`
- `mod_news/latest_content`

Initial views may mirror existing layouts; separating the data source and route names is the core requirement.

### 7. Language Keys

Set the root key in language dictionary files to `mod_news`.

Example:

- `$lang['mod_content']` -> `$lang['mod_news']`

Replacing "content" terminology with "news" can be refined later; consistent key naming takes precedence.

### 8. Menu and Permission Records

Register the new module as an independent entity in installer and admin menus:

- Module registration `mod_news`
- Permission key `mod_news`
- Admin links `mod_news/*`
- Dashboard widgets and quick links (if applicable)

## Phase 3: Cross-Reference Cleanup

After both modules are derived, audit the following surfaces:

- Admin navigation links in `mod_user`
- Common admin context data in `management`
- `installer/index.php` module loading order
- Shared helpers or service classes
- Frontend helpers
- SQL views and reporting scripts
- Manual string-based route generations

Specifically verify relation isolation:

- `mod_news` must not inadvertently render `mod_content` templates
- `mod_news` must not query `mod_category`
- `mod_news_category` must not check `mod_category` permissions
- Legacy modules must not query new module tables

## Phase 4: Data Strategy

Two strategies are available. Select one prior to implementation.

### Option A: Clean Slate

- `mod_news_category` starts with an empty table
- `mod_news` starts with an empty table
- News content and categories are authored manually

Advantages:

- Lowest data corruption risk

Disadvantages:

- Sample seed data required for admin verification

### Option B: Initial Data Migration

- Copy `mod_category` records into `mod_news_category`
- Copy `mod_content` records into `mod_news`
- Map `mod_news.category_id` values to new category IDs

Advantages:

- Module is pre-populated immediately

Disadvantages:

- Requires data mapping scripts
- Risk of mismatched category IDs

The recommended technical approach for this project is:

- Deploy and verify code first
- Run optional migration scripts subsequently

Therefore, Option A is the default plan.

## Phase 5: Verification Plan

### `mod_news_category`

- Admin list loads properly
- Create record persists to database
- Edit form updates record
- Delete and ordering operations succeed
- Nested category tree structure is preserved

### `mod_news`

- Admin list loads properly
- Create form populates `mod_news_category` options
- Save action stores correct `category_id`
- Edit view retrieves correct entity data
- Gallery and attachment integrations function under new module key
- Permission guards enforce `mod_news` permissions

### Frontend

- News list renders correctly
- Single news article page renders correctly
- Category filtering operates properly
- Related articles and archive queries target the correct table

## Execution Order

1. Scaffold `mod_news_category` directories and installer files.
2. Decouple `mod_news_category` class, language, SQL, and template references.
3. Verify `mod_news_category` admin flow.
4. Scaffold `mod_news` directories and installer files.
5. Decouple `mod_news` class, language, SQL, and template references.
6. Migrate all category dependencies in `mod_news` to `mod_news_category`.
7. Update media, permission, menu, and tool module relations.
8. Verify admin workflows.
9. Verify frontend workflows.
10. Execute data migration scripts if required.

## Estimated Risks

- Missed string-based references
- Template path remaining pointing to legacy module
- `mod_category` or `mod_content` table names retained in SQL join queries
- Missing entry in installer loading order
- Admin dashboard links failing to display new modules
- Frontend helpers reading from legacy category sources

## Decision Summary

This task is executed via derivation rather than in-place rename.

Methodology:

- Preserve `mod_category`, derive `mod_news_category`
- Preserve `mod_content`, derive `mod_news`
- Generate category module first
- Wire news content module to the category module
- Keep functionality identical without feature bloat
- Separate code deployment from data migration

This approach provides optimal balance across risk management, verifiability, rollback safety, and implementation effort.
