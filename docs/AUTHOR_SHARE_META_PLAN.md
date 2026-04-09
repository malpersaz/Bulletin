# Author Share Meta Plan

## Purpose

Document the verified current behavior of the frontend author surfaces and define a safe implementation plan for share-preview metadata on:

- `templates/default/index/mod_publication/author.twig`
- `templates/default/index/mod_publication/authors.twig`

This document is based on inspected code paths only. It does not rely on assumptions about runtime data outside the repository.

## Scope

This plan covers:

- Open Graph metadata for author detail and author listing pages
- Twitter card metadata for the same pages
- the concrete image sources already available in the current data contract
- fallback ordering for share images
- validation requirements for multilingual frontend behavior

This plan does not cover:

- redesigning the visible page layout
- changing user avatar upload behavior
- changing publication image storage
- changing WhatsApp, Facebook, X, or other crawler behavior outside metadata control

## Verified Current State

### 1. Global layout defines a logo-based metadata fallback

The shared base layout publishes a site-level metadata image:

- `og:image` is `{{ site.radres }}mod_settings/logo.png`
- `twitter:image` is `{{ site.radres }}mod_settings/logo.png`

Source:

- [templates/default/layouts/base.twig](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/default/layouts/base.twig)

This means any page that does not override `meta_tags` inherits the site logo as its share image.

### 2. `author.twig` does not define page-specific share metadata

`author.twig` overrides:

- `canonical`
- `title`
- `css_custom`
- `content`

It does not override `meta_tags`.

Source:

- [templates/default/index/mod_publication/author.twig](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/default/index/mod_publication/author.twig)

Result:

- the author detail page inherits the global logo-based `og:image` and `twitter:image`

### 3. `authors.twig` does not define page-specific share metadata

`authors.twig` overrides:

- `canonical`
- `title`
- `css_custom`
- `content`

It does not override `meta_tags`.

Source:

- [templates/default/index/mod_publication/authors.twig](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/default/index/mod_publication/authors.twig)

Result:

- the author listing page also inherits the global logo-based `og:image` and `twitter:image`

### 4. `author.twig` already has access to a profile image candidate in its page data

The author detail template renders the profile image with:

- `author.user_image`

Template source:

- [templates/default/index/mod_publication/author.twig:47](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/default/index/mod_publication/author.twig#L47)

The backend data source for this field is verified in the `publications` view model:

- `mod_user.image AS user_image`

Model source:

- [mod/mod_publication/model/publications.model.php:70](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/mod_publication/model/publications.model.php#L70)

The author page query uses `SELECT p.* FROM publications p`, so `user_image` is part of the fetched record.

Query source:

- [mod/index/helper/mod_publication.helper.php:371](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/index/helper/mod_publication.helper.php#L371)

### 5. `authors.twig` already has access to two image candidates per author card

The author listing template renders:

- profile image from `author.user_image` using `mod_user/thumb/`
- latest publication image from `author.image` using `mod_publication/`

Template source:

- [templates/default/index/mod_publication/authors.twig:45](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/default/index/mod_publication/authors.twig#L45)
- [templates/default/index/mod_publication/authors.twig:60](/home/malpersaz/Desktop/projects/htdocs/Rojname/templates/default/index/mod_publication/authors.twig#L60)

The backend query also uses `SELECT latest_publications.* FROM publications AS latest_publications`, so both fields are already present in the page data contract.

Query source:

- [mod/index/helper/mod_publication.helper.php:304](/home/malpersaz/Desktop/projects/htdocs/Rojname/mod/index/helper/mod_publication.helper.php#L304)

## Root Cause

The current share-preview behavior is not caused by missing author image fields in the inspected code path.

The verified root cause is:

- `author.twig` and `authors.twig` do not publish page-specific metadata images
- both pages therefore inherit the global logo image from `layouts/base.twig`

For the inspected author detail flow, the required profile-image field already exists in the fetched data contract.

## Image Source Inventory

### Author detail page

Verified available image sources:

1. `author.user_image` via `mod_user.image`
2. global logo fallback from `layouts/base.twig`

Current rendered path:

- visible page image uses `{{ site.radres }}mod_user/{{ author.user_image }}`

### Author listing page

Verified available image sources:

1. `author.user_image` via `mod_user.image`
2. `author.image` via the latest publication record
3. global logo fallback from `layouts/base.twig`

Current rendered paths:

- visible avatar uses `{{ site.radres }}mod_user/thumb/{{ author.user_image }}`
- visible featured image uses `{{ site.radres }}mod_publication/{{ author.image }}`

## Plan

## Phase 1: Add Explicit Share Metadata Blocks

### `author.twig`

Add a dedicated `meta_tags` block that overrides the base layout metadata and publishes:

- `og:type`
- `og:url`
- `og:title`
- `og:description`
- `og:image`
- `og:site_name`
- `og:locale`
- `twitter:card`
- `twitter:url`
- `twitter:title`
- `twitter:description`
- `twitter:image`

Use page-specific values instead of site-level defaults.

### `authors.twig`

Add a dedicated `meta_tags` block with page-specific metadata for the author listing page.

## Phase 2: Define Share Image Selection Rules

### `author.twig`

Use this exact selection order:

1. `author.user_image` from `mod_user`
2. global site logo fallback

The image URL should be absolute and should match the public asset path used by the page.

### `authors.twig`

Use this exact selection order:

1. first available `author.user_image` from the current author collection
2. first available `author.image` from the latest publication collection
3. global site logo fallback

This page is a listing page, not a person profile page, so the image choice should represent the listing surface consistently rather than pretending the page belongs to a single author.

## Phase 3: Normalize Metadata Content

### `author.twig`

Title:

- use the existing author page title contract

Description:

- prefer author biography text if present after safe plain-text extraction
- otherwise fall back to the existing page-level description contract already set in helper state

URL:

- use the canonical author URL, including pagination when applicable

### `authors.twig`

Title:

- use the existing authors page title contract

Description:

- use the existing localized listing description contract or an explicit listing summary

URL:

- use the canonical authors URL, including pagination when applicable

## Phase 4: Keep Fallbacks Honest

Do not introduce fake metadata fallbacks that imply a profile image exists when the inspected page data does not contain one.

Required behavior:

- use real page data when present
- fall back to the site logo only when page-specific image data is absent
- do not emit inline hard-coded placeholder text for visible UI
- do not keep the base layout metadata and page metadata alive together in conflicting form

## Phase 5: Validation

Validation must include:

1. Inspect generated HTML for `author/<username>` and `authors`
2. Confirm only one effective `og:image` and one effective `twitter:image` are emitted per page
3. Confirm metadata URLs are absolute
4. Confirm author detail pages use `author.user_image` when the record contains it
5. Confirm author listing pages use the defined listing-page image priority
6. Confirm fallback to the logo occurs only when no page-specific source is available
7. Confirm multilingual routes still emit the correct canonical and metadata URLs

## Implementation Notes

- `author.twig` currently uses `mod_user/{{ author.user_image }}` for the visible profile image, while `authors.twig` uses `mod_user/thumb/{{ author.user_image }}` for list avatars.
- The share-image decision should be intentional per page; it should not blindly mirror the visible image path without confirming that the chosen path is suitable for external crawlers.
- Since the base layout already defines metadata defaults, the page templates must fully override `meta_tags` rather than trying to append partial overrides.

## Acceptance Criteria

- Sharing an author detail URL no longer defaults to the site logo when `author.user_image` is available in the inspected page data
- Sharing the authors listing URL no longer defaults to the site logo when a higher-priority page-specific image source exists in the inspected listing data
- When no page-specific image source exists, both pages fall back cleanly to the site logo
- No duplicate or conflicting Open Graph image tags remain in the final page output
