# BRIEFING — 2026-11-11T11:00:00Z

## Mission
Investigate Features 5 through 11 for Milestone 1 (McpNewsTools and McpFaqTools enhancements) and produce a comprehensive technical handoff report.

## 🔒 My Identity
- Archetype: explorer
- Roles: read-only investigation, code analysis, synthesis, handoff report authoring
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_2/
- Original parent: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Milestone: Milestone 1

## 🔒 Key Constraints
- Read-only investigation — do NOT implement
- Strictly write only within state folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_2/
- Zero cross-repo modification
- Language protocol: English for technical docs/plans/handoffs, Kurmanji for user interactions

## Current Parent
- Conversation ID: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Updated: 2026-11-11T10:49:45Z

## Investigation State
- **Explored paths**:
  - `lib/McpNewsTools.class.php` (lines 30-150, 670-745, 860-920, 1030-1065, 1180-1215, 1280-1340)
  - `lib/McpFaqTools.class.php` (lines 1-530)
  - `lib/McpCrudToolsBase.class.php` (lines 263-270, 687-722, 780-822)
  - `lib/McpGalleryTools.class.php` (lines 247-280, 636-661, 934-940)
  - `lib/McpMenuTools.class.php` (lines 330-380, 903-934)
  - `lib/Traits/ModuleValidatorTrait.php` (lines 70-95)
  - `mod/mod_news_category/mod_news_category.sql`
  - `mod/mod_news_category/model/mod_news_category.model.php`
  - `mod/mod_news/model/newses.model.php`
  - `mod/mod_faq_mod/model/mod_faq_mod.model.php`
  - `tasks/mcp_smoke.php` (lines 212-225, 365-465, 490-580)
- **Key findings**:
  - Feature 5: `McpNewsTools` calls `assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category')` at lines 710 and 871, which does not check `$cat->root === 'y'`. An `assertSelectableNewsCategory()` helper solves this with actionable child category hints.
  - Feature 6: `createNews` (lines 725-728) and `updateNews` (lines 894-898) bypass `makeSeo()` when an explicit `seo` is passed. Piping explicit `seo` through `self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)` normalizes punctuation, spaces, Kurdish characters, and strips `\u{0307}`.
  - Feature 7: `getNews` (lines 670-686) only returns `mod_news` flat columns. Enriching with `username` and `author_fullname` from `mod_user` and `category_name` and `category_seo` from `mod_news_category`, passing `$row['status']` to `getRecordAuditMeta`, and setting `$row['item']` after enrichment delivers a clean, dual-compatible envelope.
  - Feature 8: `check_duplicate_news` can be added to `McpNewsTools::definitions()` and implemented via 48-hour Jaccard token overlap (>= 0.55) and exact SEO collision checks against `mod_news`.
  - Feature 9: `batch_create_faqs` can be added to `McpFaqTools::definitions()` and implemented with pre-validation (structure, language, intra-batch and DB uniqueness, entity ceiling, draft replacement) and atomic transaction rollback.
  - Feature 10: `list_faq_modules` can be added to `McpFaqTools` querying `mod_faq_mod` (id, name, mod_name, mod_key, mod_value) following `McpGalleryTools::listGalleryModules`.
  - Feature 11: `list_faqs` can support `include_answer: bool` (default false) to append `, answer` to the select query.
- **Unexplored areas**: None for Features 5-11.

## Key Decisions Made
- Reusable `assertSelectableNewsCategory()` method in `McpNewsTools` handles both create and update validation.
- `checkDuplicateNews()` is exposed as a dedicated tool and also leveraged by `createNews` for duplicate warnings.
- `batchCreateFaqs()` performs full in-memory pre-validation before opening a database transaction to prevent partial commits.
- `listFaqs()` preserves `include_answer: false` by default for performance and backward compatibility.

## Artifact Index
- DISPATCH.md — incoming dispatch log
- BRIEFING.md — persistent working memory
- progress.md — liveness and progress log
- handoff.md — final 5-component handoff report
