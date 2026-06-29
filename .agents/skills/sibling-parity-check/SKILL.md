---
name: sibling-parity-check
description: Sibling Repositories Parity Check - Dynamic parity check and synchronization control between Amed Framework sibling repositories (Business, Property, Association, Artist).
---

# Sibling Repositories Parity Check (Cross-Repository Parity)

## Purpose

This skill defines the rules and steps to audit and verify parity (synchronization) between the current workspace (`Rojname`) and its sibling repositories under `~/Desktop/projects/htdocs/AF/` (specifically `Business`, `Property`, `Association`, and `Artist`).

## Operating Rule

* **No Coding Standards Checking:** Under no circumstances should this skill evaluate if a module controller extends `BaseModuleController` or follows other internal coding standards. It purely checks if the common files are synchronized (identical) across repositories.
* **Execution:** Heta bikarhêner fermana rasterast a qontrolkirina senkronîzasyona hindurîn (mînak a vê repoyê) an jî senkronîzasyona repoyên cuda (Business, Artist, Association, Property, Rojname) nede, tu bi serê xwe vê pêvajoyê mede destpêkirin û ji bikarhêner jî daxwaza destûra wê meke!


## Step-by-Step Sequence

1. **Scan Siblings:** Scan the active sibling directories under `~/Desktop/projects/htdocs/AF/` (`Business`, `Property`, `Association`, `Artist`).
2. **Resolve Baseline:** For each shared core file or common module, resolve which repository contains the most advanced state (based on git history or complete feature set).
3. **Run Parity Check:** Compare the current workspace against the dynamically resolved baseline.
4. **Identify Differences:** List all files or folders that are unsynchronized (DIFF) or missing (MISSING).
5. **Ask User:** Output the list of unsynchronized files to the user and ask explicitly in Kurdish:
   *"Ev aliyên min ne senkronîze ne, ez senkronîze bikim, an na?"*
6. **Wait for Approval:** Wait for explicit user confirmation before attempting to copy or sync any files.

## Synchronization & Parity Criteria

### 1. Shared Core Directories (Must be kept in 100% parity)
- `lib/` (Core framework classes and database adapters)
- `config/` (Core configurations, excluding local db/site configs)
- `installer/` (Core installation engine and templates)
- `vendor/` (Dependency versions)

### 2. Common Modules (Must be kept in 100% parity)
- `mod_gallery`, `mod_gallery_mod`, `mod_redirect`, `mod_seo`, `mod_user`, `mod_user_group`, `mod_user_pm`, `mod_menu`, `mod_page`, `mod_content`, `mod_announcements`, `mod_attachment`, `mod_attachment_mod`, `mod_document`, `mod_settings`, `mod_slider`, `mod_hero_slider`, `mod_survey`, `mod_video`, `mod_video_embed`, `mod_video_embed_category`, `mod_imagecategories`, `mod_headline`, `mod_popup`, `mod_acl`

### 3. Excluded Specialized Modules (Do NOT sync or compare)
- `mod_news`, `mod_news_category`, `mod_publication`, `mod_publication_category` (Exclusive to **Rojname**)
- `mod_product`, `mod_product_category` (Exclusive to **Business**)
- `mod_service`, `mod_service_category`, `mod_reference`, `mod_reference_category`, `mod_client` (Exclusive to Commercial: **Business/Property**)
- `mod_property`, `mod_property_category`, `mod_property_subtype`, `mod_property_type` (Exclusive to **Property**)
