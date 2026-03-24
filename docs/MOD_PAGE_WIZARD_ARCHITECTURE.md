# Mod Page Wizard Architecture

This document defines the intended wizard structure for `mod_page/add`, using the `mod_content` add flow as the reference interaction model.

## Goal

Turn page creation into a structured admin wizard instead of a long flat form.

The target pattern is:

1. A thin route wrapper page.
2. A dedicated wizard partial.
3. A dedicated wizard JS state/step controller.
4. Standard POST submission to the existing `mod_page/add` backend.

## Reference Pattern

The current product reference for this interaction is:

- [`templates/admin/mod_content/add.twig`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/mod_content/add.twig)
- [`templates/admin/partials/wizard_content.twig`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/partials/wizard_content.twig)
- [`templates/admin/assets/js/script.js`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/assets/js/script.js)

`mod_page` should follow the same architectural idea, not necessarily the exact same steps.

## Route Structure

### Thin route wrapper

`templates/admin/mod_page/add.twig`

Responsibilities:

- load the wizard asset files
- auto-open the wizard when the page loads
- provide config values for the wizard JS
- include the wizard partial through the layout `wizards` block

### Wizard partial

`templates/admin/partials/wizard_page.twig`

Responsibilities:

- render the actual multi-step form
- keep form names aligned with `mod_page/add` POST expectations
- present structured steps instead of a flat form

### Wizard controller JS

`templates/admin/assets/js/mod_page_wizard.js`

Responsibilities:

- manage current step
- manage step transitions
- perform lightweight client validation
- update summaries/counters
- initialize editor and image preview
- preserve temporary state when appropriate
- submit the form

## Wizard Step Model

The first usable version uses 8 steps.

### Step 1: Language

Purpose:

- choose page language first
- establish the context for page hierarchy and relation choices

Fields:

- `lang`

### Step 2: Structure

Purpose:

- define where the page belongs structurally

Fields:

- `parent_id`
- `category_id`
- `menu`

### Step 3: Title

Purpose:

- define the main page name

Fields:

- `name`

### Step 4: Summary

Purpose:

- define the intro/deck text

Fields:

- `summary`

### Step 5: Content

Purpose:

- write the page body

Fields:

- `content`

### Step 6: Display

Purpose:

- define publish and structural flags

Fields:

- `status`
- `root`
- `deck`

### Step 7: Relations

Purpose:

- connect optional related entities and media

Fields:

- `gallery_id`
- `file_id`
- `acl_id`
- `image`

### Step 8: SEO & Finish

Purpose:

- review slug/meta fields
- confirm high-level page summary

Fields:

- `seo`
- `title`
- `keywords`
- `description`

## Backend Compatibility Contract

The wizard must submit the same POST field names already expected by:

- [`mod/mod_page/index.php`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/mod/mod_page/index.php)
- [`mod/mod_page/validator/add.validator.php`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/mod/mod_page/validator/add.validator.php)

That means the wizard is a UI-layer refactor, not a backend contract rewrite.

## State Model

The wizard controller should own a small client-side state model:

- current step
- selected language
- title
- summary
- SEO fields
- image preview state

The first skeleton supports local persistence for the core text fields and current step.

## Validation Strategy

### Client-side

Keep it lightweight:

- `name` required before moving past Title
- `content` required before final steps

### Server-side

Authoritative validation remains in:

- [`mod/mod_page/validator/add.validator.php`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/mod/mod_page/validator/add.validator.php)

## Future Expansion

The wizard skeleton intentionally leaves room for later work:

1. language-aware live reloading of hierarchy options inside the wizard
2. richer relation pickers
3. AJAX submission and success transitions
4. step-specific validation messages
5. parity with future `mod_page/edit` wizardization

## Implemented Skeleton Files

- [`templates/admin/mod_page/add.twig`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/mod_page/add.twig)
- [`templates/admin/partials/wizard_page.twig`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/partials/wizard_page.twig)
- [`templates/admin/assets/js/mod_page_wizard.js`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/assets/js/mod_page_wizard.js)
- [`templates/admin/assets/css/mod_page_wizard.css`](/home/malpersaz/Desktop/projects/htdocs/AKMbusiness/templates/admin/assets/css/mod_page_wizard.css)
