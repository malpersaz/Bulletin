# BRIEFING — 2026-11-11T11:40:00Z

## Mission
Investigate Defect 2 (McpCrudToolsBase::makeSeo dirty slug / forward slash bug) and Defect 3 (Leaf Category Check in updateNews) to formulate exact fix strategies.

## 🔒 My Identity
- Archetype: explorer
- Roles: investigation, synthesis
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_2/
- Original parent: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Milestone: Milestone 1, Iteration 2

## 🔒 Key Constraints
- Read-only investigation — do NOT implement
- Analyze Defect 2 (`McpCrudToolsBase::makeSeo` dirty slug / forward slash bug) and Defect 3 (Leaf Category Check in `updateNews()`)
- Formulate exact fix strategies with evidence chain and verification methods

## Current Parent
- Conversation ID: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Updated: not yet

## Investigation State
- **Explored paths**:
  - `lib/McpCrudToolsBase.class.php:742–777` (`makeSeo`)
  - `lib/Helper.class.php:180–191` (`legacy_seo`)
  - `templates/admin/assets/js/script.js:70–78` (`makeLegacySeoValue`)
  - `lib/Amed.class.php:636–650` (`parseRoute`)
  - `lib/McpNewsTools.class.php:978–984` (`updateNews` category validation)
  - `lib/McpNewsTools.class.php:776–799` (`assertSelectableNewsCategory`)
  - `tasks/challenger_stress_test.php:570–625` (Dimension 4 tests)
- **Key findings**:
  - Defect 2: `McpCrudToolsBase::makeSeo()` preserves forward slashes `/` and symbols because its regex only strips quotes and specific punctuation (`[\x{0027}...:;,!?()\[\]{}]`). When slashes enter slugs, `Amed.class.php:639` splits the route on `/`, breaking router dispatch and causing 404s.
  - Critical Unicode Diacritic Caveat in Defect 2: Normalization to NFC (`Normalizer::FORM_C`) must occur BEFORE replacing non-alphanumeric characters with spaces (`[^\p{L}\p{N}\s_-]`). If non-alphanumeric replacement runs first, decomposed combining marks (`\u{0307}` in `I\u{0307}stanbul` and `\u{0308}` in `Ku\u{0308}ltu\u{0308}r`) are treated as non-letters (`\p{Mn}`), turned into spaces, and collapsed into underscores (`I_stanbul`, `Ku_ltu_r`), breaking diacritic composition!
  - Defect 3: `updateNews()` checks `if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang)`. If an update payload explicitly submits `category_id` equal to the model's existing `category_id` (e.g. legacy root category 17), validation is bypassed completely. Changing condition to `if (isset($args['category_id']) || $lang !== (string) $model->lang)` validates whenever `category_id` is present in the payload or language changes.
- **Unexplored areas**: None for Defect 2 and Defect 3; complete evidence chains and runtime proofs established.

## Key Decisions Made
- Established order of operations for `makeSeo()`: 1. Unicode NFC normalization & U+0307 strip; 2. Quote/punctuation removal; 3. Non-alphanumeric `[^\p{L}\p{N}\s_-]` to space; 4. Kurdish typo substitution; 5. Whitespace/dash to underscore; 6. Underscore collapse & trim; 7. 255-char clamp.
- Formulated exact single-line condition fix for `updateNews()` leaf category guard: `if (isset($args['category_id']) || $lang !== (string) $model->lang)`.

## Artifact Index
- DISPATCH.md — incoming dispatch instructions
- BRIEFING.md — persistent working memory
- progress.md — liveness heartbeat
- handoff.md — 5-component handoff report
