<?php
/**
 * Amed Framework
 * MCP Prompt Manager - Handles MCP prompts/list and prompts/get
 * lib/McpPromptManager.class.php
 */

require_once(lib_dir . 'McpToolException.php');

class McpPromptManager
{
    private const PROMPTS = [
        'kurdish_editorial_assistant' => [
            'name' => 'kurdish_editorial_assistant',
            'description' => 'Standard Kurmanji Kurdish editorial review, grammar refinement, and vocabulary normalization.',
            'arguments' => [
                [
                    'name' => 'text',
                    'description' => 'The Kurdish text content to review and edit.',
                    'required' => true,
                ],
                [
                    'name' => 'tone',
                    'description' => 'Target editorial tone (e.g. formal, journalistic, literary, conversational).',
                    'required' => false,
                ],
            ],
        ],
        'seo_meta_optimizer' => [
            'name' => 'seo_meta_optimizer',
            'description' => 'Generates SEO title, Wikipedia-style URL slug, keywords, and synchronized summary/description (90-160 chars; legacy deck range between 90 and 120 characters, optimal SERP snippet 120-155 chars).',
            'arguments' => [
                [
                    'name' => 'title',
                    'description' => 'Content article title.',
                    'required' => true,
                ],
                [
                    'name' => 'content',
                    'description' => 'Full or partial body text.',
                    'required' => true,
                ],
                [
                    'name' => 'lang',
                    'description' => 'Language code (e.g. ku, en, tr). Defaults to ku.',
                    'required' => false,
                ],
            ],
        ],
        'content_translation_sync' => [
            'name' => 'content_translation_sync',
            'description' => 'Translates content between active site languages while preserving HTML tags, layout, and links.',
            'arguments' => [
                [
                    'name' => 'source_content',
                    'description' => 'Original HTML or text body.',
                    'required' => true,
                ],
                [
                    'name' => 'source_lang',
                    'description' => 'Source language code.',
                    'required' => true,
                ],
                [
                    'name' => 'target_lang',
                    'description' => 'Target language code.',
                    'required' => true,
                ],
            ],
        ],
    ];

    public function listPrompts(): array
    {
        return ['prompts' => array_values(self::PROMPTS)];
    }

    public function getPrompt(array $params): array
    {
        $name = trim((string) ($params['name'] ?? ''));
        if ($name === '') {
            throw new McpToolException("Parameter 'name' is required.");
        }

        if (!isset(self::PROMPTS[$name])) {
            throw new McpToolException("Prompt not found: {$name}");
        }

        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments)) {
            $arguments = [];
        }

        $def = self::PROMPTS[$name];

        foreach ($def['arguments'] as $arg) {
            if (!empty($arg['required']) && (!isset($arguments[$arg['name']]) || trim((string) $arguments[$arg['name']]) === '')) {
                throw new McpToolException("Missing required argument '{$arg['name']}' for prompt '{$name}'.");
            }
        }

        $messages = match ($name) {
            'kurdish_editorial_assistant' => $this->buildKurdishEditorialPrompt($arguments),
            'seo_meta_optimizer' => $this->buildSeoOptimizerPrompt($arguments),
            'content_translation_sync' => $this->buildTranslationSyncPrompt($arguments),
            default => throw new McpToolException("Prompt generator not implemented for: {$name}"),
        };

        return [
            'description' => $def['description'],
            'messages' => $messages,
        ];
    }

    private function buildKurdishEditorialPrompt(array $args): array
    {
        $text = (string) ($args['text'] ?? '');
        $tone = (string) ($args['tone'] ?? 'journalistic');

        $userPrompt = "Please review, proofread, and polish the following Kurdish (Kurmanji) text.\n"
            . "Target tone: {$tone}.\n"
            . "Mandatory linguistic guidelines:\n"
            . "- Use the Latin alphabet and correct Kurmanji grammar.\n"
            . "- Use 'nûjenkirin' instead of update/nûve.\n"
            . "- Use 'tikandin' instead of click/kirtandin.\n"
            . "- Use 'lêke' for adding items, questions, or articles.\n"
            . "- Maintain natural sentence structure.\n\n"
            . "Text to edit:\n{$text}";

        return [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => $userPrompt,
                ],
            ],
        ];
    }

    private function buildSeoOptimizerPrompt(array $args): array
    {
        $title = (string) ($args['title'] ?? '');
        $content = (string) ($args['content'] ?? '');
        $lang = (string) ($args['lang'] ?? 'ku');

        $userPrompt = "Generate optimized SEO metadata for the following article in language '{$lang}'.\n"
            . "Title: {$title}\n\n"
            . "Body content:\n{$content}\n\n"
            . "Requirements:\n"
            . "1. Deck Summary & Meta Description: MUST be between 90 and 160 characters long (legacy deck baseline between 90 and 120 characters; optimal SERP snippet sweet spot is 120-155 characters). Synchronize both `summary` and `description`.\n"
            . "2. SEO Title: Catchy, entity-rich, and 45-60 characters.\n"
            . "3. Meta Keywords: 5 to 8 comma-separated relevant keywords.\n"
            . "4. Slug (`seo`): Strictly Wikipedia-style (`Helper::legacy_seo()`) preserving original alphabet casing and replacing spaces with underscores (`_`), NEVER forced lowercase hyphenated slugs.\n"
            . "5. Body HTML Structure: Follow Four-Zone Semantic HTML (`<h2>`, `<h3>`, `<p>`, `<ul>`, `<table>`, `<blockquote>`) without `<h1>`, inline `style=\"...\"`, or `<script>` tags.";

        return [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => $userPrompt,
                ],
            ],
        ];
    }

    private function buildTranslationSyncPrompt(array $args): array
    {
        $sourceContent = (string) ($args['source_content'] ?? '');
        $sourceLang = (string) ($args['source_lang'] ?? '');
        $targetLang = (string) ($args['target_lang'] ?? '');

        $userPrompt = "Translate the following content from '{$sourceLang}' to '{$targetLang}'.\n"
            . "Important rules:\n"
            . "- Preserve all HTML tags, layout formatting, links, and element attributes exactly.\n"
            . "- Only translate the text content within the tags.\n"
            . "- Ensure fluent and accurate phrasing in '{$targetLang}'.\n\n"
            . "Content to translate:\n{$sourceContent}";

        return [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => $userPrompt,
                ],
            ],
        ];
    }
}
