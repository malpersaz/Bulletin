<?php

/**
 * Social Story Card Generator (9:16 Vertical Instagram Story & High-Res OpenGraph)
 * Uses native Imagick to render 1080x1920 sRGB JPEG images with safe zones and localized typography.
 */
class SocialStoryCardGenerator
{
    public const STORY_WIDTH = 1080;
    public const STORY_HEIGHT = 1920;
    public const OG_WIDTH = 1200;
    public const OG_HEIGHT = 630;

    /**
     * Resolves absolute font path based on language.
     *
     * @param string $lang
     * @param bool $isBold
     * @return string
     */
    public static function getFontPath(string $lang = 'en', bool $isBold = true): string
    {
        $baseDir = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $fontDir = $baseDir . 'templates/base_static/fonts/social/';

        $rtlLanguages = ['ar', 'fa', 'ckb'];
        if (in_array(strtolower($lang), $rtlLanguages, true)) {
            $arabicFont = $fontDir . 'NotoNaskhArabic-Bold.ttf';
            if (is_readable($arabicFont)) {
                return $arabicFont;
            }
        }

        $fontFile = $isBold ? 'NotoSans-Bold.ttf' : 'NotoSans-Regular.ttf';
        $fullPath = $fontDir . $fontFile;

        if (is_readable($fullPath)) {
            return $fullPath;
        }

        // Fallback to system font if repository font is missing
        $systemBold = '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf';
        return is_readable($systemBold) ? $systemBold : 'Helvetica';
    }

    /**
     * Localized Call-To-Action (CTA) string for story bottom pill.
     *
     * @param string $lang
     * @return string
     */
    public static function getLocalizedCta(string $lang): string
    {
        $ctas = [
            'ku'  => 'Nûçeyê di lînka profîlê de bixwîne',
            'ckb' => 'هەواڵەکە لە بەستەری پڕۆفایلدا بخوێنەوە',
            'zza' => 'Xebera lînkê profîlî ra biwane',
            'tr'  => 'Haberin detayları profildeki linkte',
            'en'  => 'Read full article via link in bio',
            'ar'  => 'اقرأ الخبر كاملاً عبر الرابط في البايو',
            'fa'  => 'خبر کامل را در لینک بیو بخوانید',
            'de'  => 'Ganzen Artikel über den Link in der Bio lesen',
            'fr'  => "Lire l'article complet via le lien en bio",
            'ru'  => 'Читайте полную статью по ссылке в профиле',
            'sv'  => 'Läs hela artikeln via länken i bion',
        ];

        return $ctas[strtolower($lang)] ?? $ctas['en'];
    }

    /**
     * Resolves the source image file path.
     *
     * @param string|null $imageField
     * @return string|null
     */
    public static function resolveSourceImagePath(?string $imageField): ?string
    {
        if (empty($imageField)) {
            return null;
        }

        $baseDir = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $possiblePaths = [
            $baseDir . 'static/mod_news/' . ltrim($imageField, '/'),
            $baseDir . 'static/' . ltrim($imageField, '/'),
        ];

        foreach ($possiblePaths as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Word-wraps text according to maximum character width per line.
     *
     * @param string $text
     * @param int $maxCharsPerLine
     * @param int $maxLines
     * @return array
     */
    public static function wrapTextLines(string $text, int $maxCharsPerLine = 28, int $maxLines = 4): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return [];
        }

        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = ($currentLine === '') ? $word : $currentLine . ' ' . $word;
            if (mb_strlen($testLine, 'UTF-8') <= $maxCharsPerLine) {
                $currentLine = $testLine;
            } else {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
                if (count($lines) >= $maxLines - 1) {
                    break;
                }
            }
        }

        if ($currentLine !== '' && count($lines) < $maxLines) {
            $lines[] = $currentLine;
        }

        // Add ellipsis if text was truncated
        $joined = implode(' ', $lines);
        if (mb_strlen($joined, 'UTF-8') < mb_strlen($text, 'UTF-8')) {
            $lastIndex = count($lines) - 1;
            if ($lastIndex >= 0) {
                $lines[$lastIndex] = rtrim($lines[$lastIndex], '.,:;!? ') . '…';
            }
        }

        return $lines;
    }

    /**
     * Creates a rounded rectangle mask for an image.
     *
     * @param int $width
     * @param int $height
     * @param int $radius
     * @return \Imagick
     */
    private static function createRoundedMask(int $width, int $height, int $radius = 24): \Imagick
    {
        $mask = new \Imagick();
        $mask->newImage($width, $height, new \ImagickPixel('transparent'));
        $mask->setImageFormat('png');

        $draw = new \ImagickDraw();
        $draw->setFillColor(new \ImagickPixel('white'));
        $draw->roundRectangle(0, 0, $width - 1, $height - 1, $radius, $radius);
        $mask->drawImage($draw);
        $draw->destroy();

        return $mask;
    }

    /**
     * Generates a 1080x1920 Instagram Story Card JPEG.
     *
     * @param array $news
     * @param string $categoryName
     * @param string|null $brandName
     * @return string|null Absolute path to generated JPEG file
     */
    public static function generateStoryCard(array $news, string $categoryName = '', ?string $brandName = null): ?string
    {
        if (!extension_loaded('imagick')) {
            return null;
        }

        $baseDir = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $storyDir = $baseDir . 'static/mod_news/story/';
        if (!is_dir($storyDir)) {
            @mkdir($storyDir, 0775, true);
        }

        $newsId = (int) ($news['id'] ?? 0);
        $lang = (string) ($news['lang'] ?? 'tr');
        $title = (string) ($news['name'] ?? '');
        $deck = (string) ($news['summary'] ?? ($news['description'] ?? ''));
        $imageFile = $news['image'] ?? null;

        $hashKey = md5($newsId . '|' . $title . '|' . $imageFile . '|' . $lang);
        $fileName = 'story_' . $newsId . '_' . substr($hashKey, 0, 10) . '.jpg';
        $outputPath = $storyDir . $fileName;

        // Return cached story card if exists and newer than 1 hour
        if (file_exists($outputPath) && (time() - filemtime($outputPath) < 3600)) {
            return $outputPath;
        }

        $boldFont = self::getFontPath($lang, true);
        $regularFont = self::getFontPath($lang, false);
        $sourceImagePath = self::resolveSourceImagePath($imageFile);

        try {
            // 1. Create Base Canvas (1080x1920)
            $canvas = new \Imagick();
            $canvas->newImage(self::STORY_WIDTH, self::STORY_HEIGHT, new \ImagickPixel('#0a0c12'));
            $canvas->setImageFormat('jpeg');

            // 2. Background Layer (Aspect-filled, blurred source image)
            if ($sourceImagePath && file_exists($sourceImagePath)) {
                $bg = new \Imagick($sourceImagePath);
                $bg->setImageFormat('jpeg');
                $bg->cropThumbnailImage(self::STORY_WIDTH, self::STORY_HEIGHT);
                $bg->blurImage(40, 20);

                // Scrim overlay
                $scrim = new \Imagick();
                $scrim->newImage(self::STORY_WIDTH, self::STORY_HEIGHT, new \ImagickPixel('rgba(10, 12, 18, 0.78)'));
                $scrim->setImageFormat('png');
                $bg->compositeImage($scrim, \Imagick::COMPOSITE_OVER, 0, 0);
                $scrim->destroy();

                $canvas->compositeImage($bg, \Imagick::COMPOSITE_OVER, 0, 0);
                $bg->destroy();
            }

            // 3. Masthead (y: 260px)
            $resolvedBrand = !empty($brandName) ? $brandName : 'AMED BÜLTEN';
            $mastheadDraw = new \ImagickDraw();
            $mastheadDraw->setFont($boldFont);
            $mastheadDraw->setFontSize(32);
            $mastheadDraw->setFillColor(new \ImagickPixel('#FFFFFF'));
            $mastheadDraw->annotation(60, 290, mb_strtoupper($resolvedBrand, 'UTF-8'));

            // Localized category badge pill
            if (!empty($categoryName)) {
                $catText = mb_strtoupper($categoryName, 'UTF-8');
                $catDraw = new \ImagickDraw();
                $catDraw->setFont($boldFont);
                $catDraw->setFontSize(22);
                $metrics = $canvas->queryFontMetrics($catDraw, $catText);
                $catWidth = (int) ($metrics['textWidth'] + 36);
                $catHeight = 44;
                $catX = self::STORY_WIDTH - 60 - $catWidth;
                $catY = 258;

                $pillDraw = new \ImagickDraw();
                $pillDraw->setFillColor(new \ImagickPixel('#e63946'));
                $pillDraw->roundRectangle($catX, $catY, $catX + $catWidth, $catY + $catHeight, 12, 12);
                $canvas->drawImage($pillDraw);
                $pillDraw->destroy();

                $catTextDraw = new \ImagickDraw();
                $catTextDraw->setFont($boldFont);
                $catTextDraw->setFontSize(22);
                $catTextDraw->setFillColor(new \ImagickPixel('#FFFFFF'));
                $catTextDraw->setTextAlignment(\Imagick::ALIGN_CENTER);
                $catTextDraw->annotation($catX + ($catWidth / 2), $catY + 31, $catText);
                $canvas->drawImage($catTextDraw);
                $catTextDraw->destroy();
            }
            $canvas->drawImage($mastheadDraw);
            $mastheadDraw->destroy();

            // 4. Hero Image Box (y: 340px, 960x540 16:9, 24px rounded corners)
            $heroWidth = 960;
            $heroHeight = 540;
            $heroX = 60;
            $heroY = 340;

            if ($sourceImagePath && file_exists($sourceImagePath)) {
                $hero = new \Imagick($sourceImagePath);
                $hero->cropThumbnailImage($heroWidth, $heroHeight);

                $mask = self::createRoundedMask($heroWidth, $heroHeight, 24);
                $hero->compositeImage($mask, \Imagick::COMPOSITE_DSTIN, 0, 0);
                $mask->destroy();

                // Drop shadow plate underneath hero
                $shadow = new \ImagickDraw();
                $shadow->setFillColor(new \ImagickPixel('rgba(0, 0, 0, 0.45)'));
                $shadow->roundRectangle($heroX + 4, $heroY + 8, $heroX + $heroWidth + 4, $heroY + $heroHeight + 8, 24, 24);
                $canvas->drawImage($shadow);
                $shadow->destroy();

                $canvas->compositeImage($hero, \Imagick::COMPOSITE_OVER, $heroX, $heroY);
                $hero->destroy();
            } else {
                // Fallback graphic card placeholder
                $fallbackDraw = new \ImagickDraw();
                $fallbackDraw->setFillColor(new \ImagickPixel('#1e293b'));
                $fallbackDraw->roundRectangle($heroX, $heroY, $heroX + $heroWidth, $heroY + $heroHeight, 24, 24);
                $canvas->drawImage($fallbackDraw);
                $fallbackDraw->destroy();
            }

            // 5. Headline Text (y: 940px)
            $headlineDraw = new \ImagickDraw();
            $headlineDraw->setFont($boldFont);
            $headlineDraw->setFontSize(44);
            $headlineDraw->setFillColor(new \ImagickPixel('#FFFFFF'));
            $headlineDraw->setTextAntialias(true);

            $wrappedTitle = self::wrapTextLines($title, 26, 4);
            $currentTextY = 960;
            foreach ($wrappedTitle as $line) {
                $canvas->annotateImage($headlineDraw, 64, $currentTextY, 0, $line);
                $currentTextY += 62;
            }
            $headlineDraw->destroy();

            // 6. Deck / Summary Snippet (Optional 1-2 lines below headline)
            if (!empty($deck) && $currentTextY < 1320) {
                $deckDraw = new \ImagickDraw();
                $deckDraw->setFont($regularFont);
                $deckDraw->setFontSize(28);
                $deckDraw->setFillColor(new \ImagickPixel('#cbd5e1'));
                $deckLines = self::wrapTextLines($deck, 42, 2);
                $currentTextY += 16;
                foreach ($deckLines as $deckLine) {
                    $canvas->annotateImage($deckDraw, 64, $currentTextY, 0, $deckLine);
                    $currentTextY += 40;
                }
                $deckDraw->destroy();
            }

            // 7. Call-To-Action (CTA) Pill (y: 1460px)
            $ctaText = self::getLocalizedCta($lang);
            $ctaPillWidth = 840;
            $ctaPillHeight = 84;
            $ctaX = (int) ((self::STORY_WIDTH - $ctaPillWidth) / 2);
            $ctaY = 1460;

            $pillBackground = new \ImagickDraw();
            $pillBackground->setFillColor(new \ImagickPixel('#FFFFFF'));
            $pillBackground->roundRectangle($ctaX, $ctaY, $ctaX + $ctaPillWidth, $ctaY + $ctaPillHeight, 42, 42);
            $canvas->drawImage($pillBackground);
            $pillBackground->destroy();

            $pillText = new \ImagickDraw();
            $pillText->setFont($boldFont);
            $pillText->setFontSize(30);
            $pillText->setFillColor(new \ImagickPixel('#0f172a'));
            $pillText->setTextAlignment(\Imagick::ALIGN_CENTER);
            $pillText->annotation((int) (self::STORY_WIDTH / 2), $ctaY + 54, $ctaText);
            $canvas->drawImage($pillText);
            $pillText->destroy();

            // Domain Badge below Pill
            $siteConfig = (class_exists('Registry') && Registry::has('site_config')) ? Registry::get('site_config') : [];
            $host = !empty($siteConfig['address']) ? parse_url($siteConfig['address'], PHP_URL_HOST) : 'amedbulten.com';
            $domainBioText = rtrim((string) $host, '/') . '/bio';

            $domainDraw = new \ImagickDraw();
            $domainDraw->setFont($regularFont);
            $domainDraw->setFontSize(24);
            $domainDraw->setFillColor(new \ImagickPixel('#94a3b8'));
            $domainDraw->setTextAlignment(\Imagick::ALIGN_CENTER);
            $domainDraw->annotation((int) (self::STORY_WIDTH / 2), $ctaY + 130, $domainBioText);
            $canvas->drawImage($domainDraw);
            $domainDraw->destroy();

            // 8. Output as sRGB JPEG
            $canvas->setImageFormat('jpeg');
            $canvas->setImageCompressionQuality(85);
            $canvas->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $canvas->writeImage($outputPath);
            $canvas->destroy();

            return $outputPath;
        } catch (\Throwable $e) {
            error_log('SocialStoryCardGenerator Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generates a 1200x630 companion JPEG for OpenGraph and X large card preview.
     *
     * @param string|null $imageField
     * @return string|null Absolute path to generated JPEG
     */
    public static function generateOpenGraphImage(?string $imageField): ?string
    {
        if (empty($imageField)) {
            return null;
        }

        if (class_exists('ImageService')) {
            $ogMeta = ImageService::ensureOpenGraphJpeg('mod_news', $imageField);
            if (is_array($ogMeta) && !empty($ogMeta['path']) && is_file($ogMeta['path'])) {
                return (string) $ogMeta['path'];
            }
        }

        if (!extension_loaded('imagick')) {
            return null;
        }

        $sourcePath = self::resolveSourceImagePath($imageField);
        if (!$sourcePath || !file_exists($sourcePath)) {
            return null;
        }

        $baseDir = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $ogDir = $baseDir . 'static/mod_news/og/';
        if (!is_dir($ogDir)) {
            @mkdir($ogDir, 0775, true);
        }

        $baseName = pathinfo($imageField, PATHINFO_FILENAME);
        $outputPath = $ogDir . $baseName . '.jpg';

        if (file_exists($outputPath) && (time() - filemtime($outputPath) < 86400 * 7)) {
            return $outputPath;
        }

        try {
            $img = new \Imagick($sourcePath);
            $img->cropThumbnailImage(self::OG_WIDTH, self::OG_HEIGHT);
            $img->setImageFormat('jpeg');
            $img->setImageCompressionQuality(86);
            $img->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $img->writeImage($outputPath);
            $img->destroy();

            return $outputPath;
        } catch (\Throwable $e) {
            error_log('SocialStoryCardGenerator::generateOpenGraphImage Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prunes story card files older than specified hours to prevent disk bloat.
     *
     * @param int $hours
     * @return int Count of deleted files
     */
    public static function cleanupOldStoryCards(int $hours = 72): int
    {
        $baseDir = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $storyDir = $baseDir . 'static/mod_news/story/';
        if (!is_dir($storyDir)) {
            return 0;
        }

        $deleted = 0;
        $threshold = time() - ($hours * 3600);

        $files = glob($storyDir . 'story_*.jpg');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $threshold) {
                    if (@unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }
}
