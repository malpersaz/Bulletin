<?php

/**
 * Generates standardized llms.txt and llms-full.txt feeds for AI and LLM indexing
 * 
 * Standard: https://llmstxt.org
 * Amed Framework - Newspaper and Editorial Edition
 * @author Antigravity AI
 */

class LlmsService
{
    private $db;
    private $runtime;
    private $siteConfig;
    private $baseUrl = '';
    private $cacheDir = 'cache/llms/';
    private $cacheTtl = 3600; // 1 hour TTL fallback

    public function __construct()
    {
        $this->db = Load::lib('Database');
        $this->runtime = Load::lib('ModuleRuntime');
        $this->siteConfig = Registry::get('site_config');

        if (is_array($this->siteConfig) && !empty($this->siteConfig['address'])) {
            $this->baseUrl = rtrim((string) $this->siteConfig['address'], '/') . '/';
        } elseif (is_array($this->siteConfig) && !empty($this->siteConfig['saddress'])) {
            $this->baseUrl = rtrim((string) $this->siteConfig['saddress'], '/') . '/';
        } else {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->baseUrl = $scheme . '://' . $host . '/';
        }
    }

    /**
     * Render llms.txt or llms-full.txt content for the requested language
     *
     * @param string $lang Requested language code
     * @param bool $full Whether to render full content mode (llms-full.txt)
     * @return string Formatted Markdown string
     */
    public function render(string $lang = '', bool $full = false): string
    {
        $resolvedLang = $this->resolveLanguage($lang);
        $cacheFile = root_dir . $this->cacheDir . 'llms_' . $resolvedLang . ($full ? '_full' : '') . '.txt';

        if ($this->isCacheValid($cacheFile)) {
            $cachedContent = @file_get_contents($cacheFile);
            if ($cachedContent !== false && trim($cachedContent) !== '') {
                return $cachedContent;
            }
        }

        $content = $this->generateMarkdown($resolvedLang, $full);
        $this->writeCache($cacheFile, $content);

        return $content;
    }

    /**
     * Generate fresh Markdown content conforming to the LLMS text specification
     */
    private function generateMarkdown(string $lang, bool $full): string
    {
        $settings = $this->getSettings($lang);
        $siteTitle = !empty($settings['site_name']) ? $settings['site_name'] : (!empty($settings['title']) ? $settings['title'] : 'AKM Bulletin CMS');
        $siteDesc = !empty($settings['description']) ? trim($settings['description']) : 'Digital broadsheet journalism, independent reporting, and cultural publishing platform.';

        $md = [];

        // 1. Mandatory H1 Title
        $md[] = "# " . $siteTitle;
        $md[] = "";

        // 2. Recommended Blockquote Summary
        $md[] = "> " . $this->cleanSingleLine($siteDesc);
        $md[] = "";

        // 3. Contextual Introductory Briefing
        $md[] = "Welcome to the official AI index for " . $siteTitle . ". This index provides machine-readable discovery feeds, structured news dispatches, publications, cultural essays, editorial masthead credentials, and legal declarations for AI systems and research agents.";
        $md[] = "";

        // 4. Latest News & Dispatches (mod_news)
        $news = [];
        if ($this->runtime->isModuleReady('mod_news')) {
            $news = $this->getActiveNews($lang, 25);
            if (!empty($news)) {
                $md[] = "## Latest News & Dispatches";
                foreach ($news as $n) {
                    $newsTitle = !empty($n['name']) ? $n['name'] : (!empty($n['title']) ? $n['title'] : 'News Dispatch');
                    $newsSlug = !empty($n['seo']) ? $n['seo'] : Helper::legacy_seo($newsTitle);
                    $newsUrl = $this->buildUrl($lang, 'news/' . $newsSlug);
                    $newsDesc = $this->resolveItemSummary($n, 'Latest news reporting and analysis.');
                    $md[] = "- [" . $newsTitle . "](" . $newsUrl . "): " . $newsDesc;
                }
                $md[] = "";
            }
        }

        // 5. Publications & Literary Works (mod_publication)
        $pubs = [];
        if ($this->runtime->isModuleReady('mod_publication')) {
            $pubs = $this->getActivePublications($lang, 15);
            if (!empty($pubs)) {
                $md[] = "## Publications & Literary Works";
                foreach ($pubs as $p) {
                    $pubTitle = !empty($p['name']) ? $p['name'] : (!empty($p['title']) ? $p['title'] : 'Publication');
                    $pubSlug = !empty($p['seo']) ? $p['seo'] : Helper::legacy_seo($pubTitle);
                    $pubUrl = $this->buildUrl($lang, 'publication/' . $pubSlug);
                    $pubDesc = $this->resolveItemSummary($p, 'Long-form editorial work and cultural literature.');
                    $md[] = "- [" . $pubTitle . "](" . $pubUrl . "): " . $pubDesc;
                }
                $md[] = "";
            }
        }


        // 7. Pages & Institutional Information (mod_page)
        $pages = [];
        if ($this->runtime->isModuleReady('mod_page')) {
            $pages = $this->getActivePages($lang);
            if (!empty($pages)) {
                $md[] = "## Pages & Institutional Policies";
                foreach ($pages as $pg) {
                    $pageTitle = !empty($pg['name']) ? $pg['name'] : (!empty($pg['title']) ? $pg['title'] : 'Page');
                    $pageSlug = !empty($pg['seo']) ? $pg['seo'] : Helper::legacy_seo($pageTitle);
                    $pageUrl = $this->buildUrl($lang, 'page/' . $pageSlug);
                    $pageDesc = $this->resolveItemSummary($pg, 'Official institutional documentation and background.');
                    $md[] = "- [" . $pageTitle . "](" . $pageUrl . "): " . $pageDesc;
                }
                $md[] = "";
            }
        }

        // 8. Announcements & Press Releases (mod_announcements)
        $announcements = [];
        if ($this->runtime->isModuleReady('mod_announcements')) {
            $announcements = $this->getActiveAnnouncements($lang, 10);
            if (!empty($announcements)) {
                $md[] = "## Announcements & Press Releases";
                foreach ($announcements as $a) {
                    $annTitle = !empty($a['name']) ? $a['name'] : (!empty($a['title']) ? $a['title'] : 'Announcement');
                    $annSlug = !empty($a['seo']) ? $a['seo'] : Helper::legacy_seo($annTitle);
                    $annUrl = $this->buildUrl($lang, 'announcement/' . $a['id'] . '-' . $annSlug);
                    $annDesc = $this->resolveItemSummary($a, 'Official press release and public notice.');
                    $md[] = "- [" . $annTitle . "](" . $annUrl . "): " . $annDesc;
                }
                $md[] = "";
            }
        }

        // 9. Video & Multimedia Broadcasts (mod_video_embed)
        if ($this->runtime->isModuleReady('mod_video_embed')) {
            $videos = $this->getActiveVideos($lang, 10);
            if (!empty($videos)) {
                $md[] = "## Video & Multimedia Broadcasts";
                foreach ($videos as $v) {
                    $videoTitle = !empty($v['name']) ? $v['name'] : (!empty($v['title']) ? $v['title'] : 'Video Broadcast');
                    $videoSlug = !empty($v['seo']) ? $v['seo'] : Helper::legacy_seo($videoTitle);
                    $videoUrl = $this->buildUrl($lang, 'video/' . $v['id'] . '-' . $videoSlug);
                    $videoDesc = !empty($v['summary']) ? $this->cleanSingleLine($v['summary']) : 'Broadcast stream and multimedia coverage.';
                    $md[] = "- [" . $videoTitle . "](" . $videoUrl . "): " . $videoDesc;
                }
                $md[] = "";
            }
        }

        // 10. Editorial Masthead & Publishing Credentials
        $md[] = "## Editorial Masthead & Publishing Credentials";
        if (!empty($settings['managing_director'])) {
            $md[] = "- **Managing Director:** " . $settings['managing_director'];
        }
        if (!empty($settings['editor_in_chief'])) {
            $md[] = "- **Editor-in-Chief:** " . $settings['editor_in_chief'];
        }
        if (!empty($settings['press_code_license'])) {
            $md[] = "- **Press Code / License:** " . $settings['press_code_license'];
        }
        if (!empty($settings['dpo_email'])) {
            $md[] = "- **DPO / Compliance Email:** " . $settings['dpo_email'];
        }
        $contactEmail = !empty($settings['registered_email']) ? $settings['registered_email'] : (!empty($settings['mail']) ? $settings['mail'] : (!empty($settings['email']) ? $settings['email'] : ''));
        if (!empty($contactEmail)) {
            $md[] = "- **Verified Editorial Contact:** " . $contactEmail;
        }
        $legalEntity = !empty($settings['legal_company_name']) ? $settings['legal_company_name'] : (!empty($settings['owner_name']) ? $settings['owner_name'] : '');
        if (!empty($legalEntity)) {
            $md[] = "- **Legal Publishing Entity:** " . $legalEntity;
        }
        $taxId = !empty($settings['tax_number']) ? $settings['tax_number'] : (!empty($settings['vat_number']) ? $settings['vat_number'] : (!empty($settings['company_registration_number']) ? $settings['company_registration_number'] : ''));
        if (!empty($taxId)) {
            $md[] = "- **Tax / Registration ID:** " . $taxId;
        }
        $address = !empty($settings['corporate_address']) ? $settings['corporate_address'] : (!empty($settings['office_address']) ? $settings['office_address'] : (!empty($settings['contact_address']) ? $settings['contact_address'] : ''));
        if (!empty($address)) {
            $md[] = "- **Corporate Address:** " . $this->cleanSingleLine($address);
        }
        if (!empty($settings['compliance_jurisdiction'])) {
            $md[] = "- **Regulatory Jurisdiction:** " . $settings['compliance_jurisdiction'];
        }
        $md[] = "";

        // 11. Optional Section (Feeds, Sitemaps & Compliance)
        $md[] = "## Optional";
        $md[] = "- [Master XML Sitemap](" . $this->baseUrl . "sitemap.xml): Machine-readable XML index of all articles, publications, and pages.";
        $md[] = "- [News XML Sitemap](" . $this->buildUrl($lang, 'newsSitemap.xml') . "): Dedicated Google News compatible XML feed.";
        $md[] = "- [Full Aggregated Context](" . $this->buildUrl($lang, 'llms-full.txt') . "): Complete plain-text news and publication archive for direct LLM context-window loading and RAG pipelines.";
        $md[] = "- [Legal & Ethics Policy](" . $this->buildUrl($lang, 'legal') . "): Press code compliance, terms of service, and editorial governance.";
        $md[] = "- [Cookie Policy](" . $this->buildUrl($lang, 'cookie-policy') . "): Privacy parameters and cookie declaration.";
        $md[] = "";

        // Append Full Text in Full Mode
        if ($full) {
            $md[] = "---";
            $md[] = "";
            $md[] = "# Full Document Contents";
            $md[] = "";
            $md[] = "This section contains the aggregated plain text content of published news reports, publications, articles, and documentation for direct LLM ingestion and RAG embedding.";
            $md[] = "";

            if (!empty($news)) {
                foreach ($news as $n) {
                    if (!empty($n['content'])) {
                        $nTitle = !empty($n['name']) ? $n['name'] : (!empty($n['title']) ? $n['title'] : 'News');
                        $md[] = "### News: " . $nTitle;
                        $md[] = $this->htmlToCleanMarkdown($n['content']);
                        $md[] = "";
                    }
                }
            }

            if (!empty($pubs)) {
                foreach ($pubs as $p) {
                    if (!empty($p['content'])) {
                        $pTitle = !empty($p['name']) ? $p['name'] : (!empty($p['title']) ? $p['title'] : 'Publication');
                        $md[] = "### Publication: " . $pTitle;
                        $md[] = $this->htmlToCleanMarkdown($p['content']);
                        $md[] = "";
                    }
                }
            }

            if (!empty($articles)) {
                foreach ($articles as $c) {
                    if (!empty($c['content'])) {
                        $cTitle = !empty($c['name']) ? $c['name'] : (!empty($c['title']) ? $c['title'] : 'Article');
                        $md[] = "### Article: " . $cTitle;
                        $md[] = $this->htmlToCleanMarkdown($c['content']);
                        $md[] = "";
                    }
                }
            }

            if (!empty($pages)) {
                foreach ($pages as $pg) {
                    if (!empty($pg['content'])) {
                        $pgTitle = !empty($pg['name']) ? $pg['name'] : (!empty($pg['title']) ? $pg['title'] : 'Page');
                        $md[] = "### Page: " . $pgTitle;
                        $md[] = $this->htmlToCleanMarkdown($pg['content']);
                        $md[] = "";
                    }
                }
            }

            if (!empty($announcements)) {
                foreach ($announcements as $a) {
                    if (!empty($a['content'])) {
                        $aTitle = !empty($a['name']) ? $a['name'] : (!empty($a['title']) ? $a['title'] : 'Announcement');
                        $md[] = "### Announcement: " . $aTitle;
                        $md[] = $this->htmlToCleanMarkdown($a['content']);
                        $md[] = "";
                    }
                }
            }
        }

        return implode("\n", $md);
    }

    /**
     * Invalidate and clear cached llms files
     */
    public function invalidateCache(): bool
    {
        $dir = root_dir . $this->cacheDir;
        if (!is_dir($dir)) {
            return true;
        }

        $files = glob($dir . 'llms_*.txt');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        return true;
    }

    private function resolveLanguage(string $lang): string
    {
        $defaultLang = Registry::has('default_lang') ? (string) Registry::get('default_lang') : 'ku';
        if (empty($lang)) {
            $lang = Registry::has('lang') ? (string) Registry::get('lang') : $defaultLang;
        }

        $activeLangs = Registry::get('active_langs');
        if (!is_array($activeLangs) || empty($activeLangs)) {
            $bootstrap = Registry::get('i18n_bootstrap');
            if (is_array($bootstrap) && !empty($bootstrap['active_langs'])) {
                $activeLangs = $bootstrap['active_langs'];
            }
        }

        if (is_array($activeLangs) && !empty($activeLangs)) {
            if (in_array($lang, $activeLangs, true)) {
                return $lang;
            }
        }

        return !empty($lang) ? preg_replace('/[^a-z0-9_-]/i', '', $lang) : $defaultLang;
    }

    private function buildUrl(string $lang, string $path): string
    {
        $defaultLang = Registry::has('default_lang') ? (string) Registry::get('default_lang') : 'ku';
        $trimmedPath = ltrim($path, '/');

        if ($lang === $defaultLang) {
            return $this->baseUrl . $trimmedPath;
        }

        return $this->baseUrl . $lang . '/' . $trimmedPath;
    }

    private function getSettings(string $lang): array
    {
        $settings = [];

        try {
            $model = Load::model('mod_settings');
            $model->where_lang($lang);
            $model->limit(1);
            $model->gR();
            if ($model->getSize() > 0) {
                $settings = $model->toArray()[0];
            } else {
                // Fallback to any language row
                $model = Load::model('mod_settings');
                $model->limit(1);
                $model->gR();
                if ($model->getSize() > 0) {
                    $settings = $model->toArray()[0];
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        if (is_array($this->siteConfig) && !empty($this->siteConfig)) {
            $settings = array_merge($this->siteConfig, $settings);
        }

        return $settings;
    }

    private function getActiveNews(string $lang, int $limit = 25): array
    {
        try {
            $model = Load::model('mod_news');
            $model->where_status_and_lang('y', $lang);
            $model->order('created_at', 'DESC');
            $model->limit($limit);
            $model->gR();
            if ($model->getSize() > 0) {
                return $model->toArray();
            }
        } catch (\Throwable $e) {
        }
        return [];
    }

    private function getActivePublications(string $lang, int $limit = 15): array
    {
        try {
            $model = Load::model('mod_publication');
            $model->where_status_and_lang('y', $lang);
            $model->order('created_at', 'DESC');
            $model->limit($limit);
            $model->gR();
            if ($model->getSize() > 0) {
                return $model->toArray();
            }
        } catch (\Throwable $e) {
        }
        return [];
    }


    private function getActivePages(string $lang): array
    {
        try {
            $model = Load::model('mod_page');
            $model->where_status_and_lang('y', $lang);
            $model->order('order', 'ASC');
            $model->gR();
            if ($model->getSize() > 0) {
                return $model->toArray();
            }
        } catch (\Throwable $e) {
        }
        return [];
    }

    private function getActiveAnnouncements(string $lang, int $limit = 10): array
    {
        try {
            $model = Load::model('mod_announcements');
            $model->where_status_and_lang('y', $lang);
            $model->order('created_at', 'DESC');
            $model->limit($limit);
            $model->gR();
            if ($model->getSize() > 0) {
                return $model->toArray();
            }
        } catch (\Throwable $e) {
        }
        return [];
    }

    private function getActiveVideos(string $lang, int $limit = 10): array
    {
        try {
            $model = Load::model('mod_video_embed');
            $model->where_status_and_lang('y', $lang);
            $model->order('created_at', 'DESC');
            $model->limit($limit);
            $model->gR();
            if ($model->getSize() > 0) {
                return $model->toArray();
            }
        } catch (\Throwable $e) {
        }
        return [];
    }

    private function isCacheValid(string $filePath): bool
    {
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return false;
        }

        return (time() - filemtime($filePath)) < $this->cacheTtl;
    }

    private function writeCache(string $filePath, string $content): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        @file_put_contents($filePath, $content, LOCK_EX);
    }

    private function resolveItemSummary(array $item, string $defaultFallback = ''): string
    {
        $deck = !empty($item['deck']) ? trim((string) $item['deck']) : '';
        if ($deck !== '' && !in_array(strtolower($deck), ['y', 'n'], true) && mb_strlen($deck) > 2) {
            return $this->cleanSingleLine($deck);
        }

        $summary = !empty($item['summary']) ? trim((string) $item['summary']) : '';
        if ($summary !== '' && !in_array(strtolower($summary), ['y', 'n'], true) && mb_strlen($summary) > 2) {
            $cleaned = $this->cleanSingleLine($summary);
            if ($cleaned !== '') {
                return mb_substr($cleaned, 0, 240);
            }
        }

        $description = !empty($item['description']) ? trim((string) $item['description']) : '';
        if ($description !== '' && mb_strlen($description) > 2) {
            return $this->cleanSingleLine($description);
        }

        return $defaultFallback;
    }

    private function cleanSingleLine(string $str): string
    {
        $clean = strip_tags($str);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }

    private function htmlToCleanMarkdown(string $html): string
    {
        $text = $html;
        $text = preg_replace('/<h1[^>]*>(.*?)<\/h1>/si', "# $1\n\n", $text);
        $text = preg_replace('/<h2[^>]*>(.*?)<\/h2>/si', "## $1\n\n", $text);
        $text = preg_replace('/<h3[^>]*>(.*?)<\/h3>/si', "### $1\n\n", $text);
        $text = preg_replace('/<h[4-6][^>]*>(.*?)<\/h[4-6]>/si', "#### $1\n\n", $text);
        $text = preg_replace('/<p[^>]*>(.*?)<\/p>/si', "$1\n\n", $text);
        $text = preg_replace('/<li[^>]*>(.*?)<\/li>/si', "- $1\n", $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}
