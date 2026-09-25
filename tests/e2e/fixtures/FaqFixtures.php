<?php

namespace E2E\Fixtures;

class FaqFixtures
{
    public static function getFaqs(): array
    {
        return [
            [
                'id' => 1,
                'mod_name' => 'mod_content',
                'vid' => 1,
                'question' => 'Where can I read the full peace agreement text?',
                'answer' => 'The full official text is published under the official archive portal.',
                'sort_order' => 1,
                'active' => 1,
            ],
            [
                'id' => 2,
                'mod_name' => 'mod_content',
                'vid' => 1,
                'question' => 'Will there be international observers during implementation?',
                'answer' => 'Yes, representatives from EU and UN human rights bodies will monitor progress.',
                'sort_order' => 2,
                'active' => 1,
            ],
            [
                'id' => 3,
                'mod_name' => 'mod_newsletter',
                'vid' => 0,
                'question' => 'How often is the daily news digest delivered?',
                'answer' => 'The morning edition is sent daily at 07:00 UTC.',
                'sort_order' => 1,
                'active' => 1,
            ],
        ];
    }

    public static function getSampleJsonLdSchema(array $faqs): array
    {
        $entities = [];
        foreach ($faqs as $faq) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq['answer']),
                ]
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }
}
