<?php
/**
 * Amed Framework - Event Public Helper
 * mod/index/helper/mod_event.helper.php
 * Strict Code Separation: Zero Inline CSS, Zero Inline JS
 * Pure English Identifiers, Zero Single-Line SQL Aliases
 */

class mod_event_helper extends indexBase
{
    use PublicHelperTrait;

    public function __construct()
    {
        parent::__construct();
    }

    private function _cleanText(?string $value): string
    {
        return html_entity_decode(
            html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }

    private function _cleanEventRow(array $row): array
    {
        foreach (['name', 'title', 'summary', 'description', 'keywords', 'location', 'subject', 'participants', 'guests', 'category_name'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->_cleanText($row[$field]);
            }
        }

        if (!empty($row['image'])) {
            $imageFile = root_dir . 'static' . DS . 'mod_event' . DS . $row['image'];
            if (!file_exists($imageFile) || !is_file($imageFile)) {
                $row['image'] = '';
            }
        }

        $row['has_video'] = (!empty($row['video']) && $row['video'] !== 'a:0:{}');
        $row['has_gallery'] = (isset($row['gallery_id']) && (int) $row['gallery_id'] !== 0);
        $row['has_file'] = (isset($row['file_id']) && (int) $row['file_id'] !== 0);
        $row['has_deck'] = (isset($row['deck']) && $row['deck'] === 'y');
        $row['has_slider'] = (isset($row['slider']) && $row['slider'] === 'y');
        $row['is_official_holiday'] = (isset($row['is_official_holiday']) && $row['is_official_holiday'] === 'y') ? 'y' : 'n';
        $row['is_holiday_flag'] = ($row['is_official_holiday'] === 'y');
        $row['holiday_duration'] = isset($row['holiday_duration']) ? round((float) $row['holiday_duration'], 1) : 0.0;
        $row['is_half_day_holiday'] = ($row['is_holiday_flag'] && abs($row['holiday_duration'] - 0.5) < 0.05);
        $row['is_recurring'] = (isset($row['is_recurring']) && $row['is_recurring'] === 'y') ? 'y' : 'n';

        if (isset($row['start_date']) && !empty($row['start_date'])) {
            $startTs = strtotime($row['start_date']);
            $endTs = !empty($row['end_date']) ? strtotime($row['end_date']) : strtotime(date('Y-m-d 23:59:59', $startTs));
            if ($endTs < $startTs) {
                $endTs = $startTs;
            }
            $now = time();

            // Duration in days (e.g. multi-day holidays)
            $row['duration_days'] = max(1, (int) floor((strtotime(date('Y-m-d', $endTs)) - strtotime(date('Y-m-d', $startTs))) / 86400) + 1);

            $row['start_day'] = date('d', $startTs);
            $row['start_month'] = date('M', $startTs);
            $row['start_time'] = date('H:i', $startTs);
            $row['start_date_only'] = date('d.m.Y', $startTs);
            $row['start_date_formatted'] = date('d.m.Y H:i', $startTs);

            // Localized Month Map
            $langCode = strtolower((string) ($this->site['lang'] ?? 'tr'));
            $monthNum = date('m', $startTs);
            $monthsMap = [
                'tr' => ['01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis', '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu', '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'],
                'ku' => ['01' => 'Rêb', '02' => 'Reş', '03' => 'Ada', '04' => 'Avb', '05' => 'Gul', '06' => 'Pûş', '07' => 'Tîr', '08' => 'Teb', '09' => 'Îlû', '10' => 'Cot', '11' => 'Miş', '12' => 'Ber'],
                'en' => ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'],
                'de' => ['01' => 'Jan', '02' => 'Feb', '03' => 'Mär', '04' => 'Apr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Dez'],
                'fr' => ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Aoû', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'],
                'zza' => ['01' => 'Çel', '02' => 'Guc', '03' => 'Ada', '04' => 'Nîs', '05' => 'Gul', '06' => 'Hez', '07' => 'Tîr', '08' => 'Pal', '09' => 'Kek', '10' => 'Tşr', '11' => 'Kan', '12' => 'Çil'],
                'ar' => ['01' => 'يناير', '02' => 'فبراير', '03' => 'مارس', '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو', '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر', '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'],
                'ckb' => ['01' => 'کانوونی دووەم', '02' => 'شوبات', '03' => 'ئازار', '04' => 'نیسان', '05' => 'ئایار', '06' => 'حوزەیران', '07' => 'تەممووز', '08' => 'ئاب', '09' => 'ئەیلوول', '10' => 'تشرینی یەکەم', '11' => 'تشرینی دووەم', '12' => 'کانوونی یەکەم'],
                'ru' => ['01' => 'Янв', '02' => 'Фев', '03' => 'Мар', '04' => 'Апр', '05' => 'Май', '06' => 'Июн', '07' => 'Июл', '08' => 'Авг', '09' => 'Сен', '10' => 'Окт', '11' => 'Ноя', '12' => 'Дек'],
                'sv' => ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Maj', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Dec'],
            ];
            $row['start_month_localized'] = $monthsMap[$langCode][$monthNum] ?? date('M', $startTs);

            // Relative date calculations (active throughout the entire day / multi-day span)
            $todayStartTs = strtotime(date('Y-m-d 00:00:00', $now));
            $todayEndTs = strtotime(date('Y-m-d 23:59:59', $now));
            $startDateStr = date('Y-m-d', $startTs);
            $tomorrowStr = date('Y-m-d', strtotime('+1 day', $now));

            $row['is_today'] = ($startTs <= $todayEndTs && $endTs >= $todayStartTs);
            $row['is_tomorrow'] = (!$row['is_today'] && $startDateStr === $tomorrowStr);
            $row['is_upcoming'] = ($endTs >= $now || $row['is_today']);

            $endOfWeekTs = strtotime('sunday this week 23:59:59', $now);
            $row['is_this_week'] = ($row['is_today'] || ($startTs >= $now && $startTs <= $endOfWeekTs));

            $diffDays = (int) floor((strtotime(date('Y-m-d 00:00:00', $startTs)) - $todayStartTs) / 86400);
            $row['days_until'] = max(0, $diffDays);

            // Calendar and Schema ISO-8601 integration fields
            $row['iso_start'] = date('Ymd\THis', $startTs);
            $row['schema_start_date'] = date('c', $startTs);
            if (!empty($row['end_date'])) {
                $rawEndTs = strtotime($row['end_date']);
                $row['iso_end'] = date('Ymd\THis', $rawEndTs);
                $row['schema_end_date'] = date('c', $rawEndTs);
                $row['end_date_only'] = date('d.m.Y', $rawEndTs);
                $row['end_date_formatted'] = date('d.m.Y H:i', $rawEndTs);
            } else {
                $row['iso_end'] = date('Ymd\THis', $startTs + 7200);
                $row['schema_end_date'] = date('c', $endTs);
                $row['end_date_only'] = '';
                $row['end_date_formatted'] = '';
            }

            // Google Calendar URL
            $calTitle = rawurlencode($row['name'] ?? '');
            $calDates = $row['iso_start'] . '/' . $row['iso_end'];
            $calDetails = rawurlencode(strip_tags((string) ($row['summary'] ?? ($row['name'] ?? ''))));
            $calLocation = rawurlencode((string) ($row['location'] ?? ''));
            $row['google_calendar_url'] = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$calTitle}&dates={$calDates}&details={$calDetails}&location={$calLocation}";

            // Google Maps URL
            $locationQuery = !empty($row['location']) ? rawurlencode($row['location']) : '';
            $row['map_search_url'] = !empty($locationQuery) ? "https://www.google.com/maps/search/?api=1&query={$locationQuery}" : '';
        } else {
            $row['duration_days'] = 1;
            $row['is_upcoming'] = false;
            $row['is_today'] = false;
            $row['is_tomorrow'] = false;
            $row['is_this_week'] = false;
            $row['days_until'] = 0;
            $row['iso_start'] = '';
            $row['iso_end'] = '';
            $row['schema_start_date'] = '';
            $row['schema_end_date'] = '';
            $row['google_calendar_url'] = '';
            $row['map_search_url'] = '';
            $row['start_date_only'] = '';
            $row['end_date_only'] = '';
            $row['end_date_formatted'] = '';
            $row['start_month_localized'] = '';
        }

        if (!empty($row['category_id']) && (empty($row['category_name']) || empty($row['category_seo']))) {
            $catMap = $this->getCategoryHierarchyMap('mod_event_category', '');
            $cId = (int) $row['category_id'];
            if (isset($catMap[$cId])) {
                $row['category_name'] = $this->_cleanText($catMap[$cId]['name'] ?? '');
                $row['category_seo'] = $catMap[$cId]['seo'] ?? '';
            }
        }

        if (isset($row['created_at'])) {
            $row['published_ago'] = $this->formatRelativeTime($row['created_at']);
        }

        return $row;
    }

    private function formatRelativeTime($datetime): string
    {
        return Helper::time_ago($datetime, $this->lang['index'] ?? null);
    }

    private function _cleanEventRows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $rows[$index] = $this->_cleanEventRow($row);
            }
        }
        return $rows;
    }

    private function _checkCategoryHierarchyPermission(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            return false;
        }

        $lang = (string) ($this->site['lang'] ?? '');
        $byId = $this->getCategoryHierarchyMap('mod_event_category', $lang);
        if (!isset($byId[$categoryId])) {
            $byId = $this->getCategoryHierarchyMap('mod_event_category', '');
        }

        $currentId = $categoryId;
        $visited = [];
        while ($currentId > 0 && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            if (!isset($byId[$currentId])) {
                break;
            }
            $categoryRow = $byId[$currentId];
            if ($this->_permission_check((int) $categoryRow['acl_id'])) {
                return true;
            }
            $currentId = (int) $categoryRow['parent_id'];
        }

        return false;
    }

    /**
     * Resolves dedicated template key from category SEO slug or name.
     */
    private function _resolveCategoryTemplateKey(array $cat): string
    {
        $rawSeo = rawurldecode((string) ($cat['seo'] ?? ''));
        $seoNorm = mb_strtolower(trim(str_replace(['-', ' '], '_', $rawSeo)), 'UTF-8');
        $nameNorm = mb_strtolower(trim((string) ($cat['name'] ?? '')), 'UTF-8');

        if (strpos($seoNorm, 'kultur_sanat') !== false || strpos($seoNorm, 'kültür_sanat') !== false || strpos($nameNorm, 'kültür') !== false || strpos($nameNorm, 'çand') !== false) {
            return 'culture_art';
        }
        if (strpos($seoNorm, 'edebiyat_soylesi') !== false || strpos($seoNorm, 'edebiyat_söyleşi') !== false || strpos($nameNorm, 'edebiyat') !== false) {
            return 'literature_talk';
        }
        if (strpos($seoNorm, 'sinema_belgesel') !== false || strpos($nameNorm, 'sinema') !== false || strpos($nameNorm, 'sînema') !== false) {
            return 'cinema_documentary';
        }
        if (strpos($seoNorm, 'atolye_akademi') !== false || strpos($seoNorm, 'atölye_akademi') !== false || strpos($nameNorm, 'atölye') !== false || strpos($nameNorm, 'atolye') !== false) {
            return 'workshop_academy';
        }
        if (strpos($seoNorm, 'sinavlar') !== false || strpos($seoNorm, 'sınavlar') !== false || strpos($nameNorm, 'sınav') !== false || strpos($nameNorm, 'azmûn') !== false || strpos($nameNorm, 'ezmûn') !== false) {
            return 'exams';
        }
        if (strpos($seoNorm, 'milli_bayram') !== false || strpos($nameNorm, 'milli bayram') !== false || strpos($nameNorm, 'millî bayram') !== false) {
            return 'national_holidays';
        }
        if (strpos($seoNorm, 'dini_gun') !== false || strpos($seoNorm, 'dini_gün') !== false || strpos($nameNorm, 'dini gün') !== false) {
            return 'religious_holidays';
        }
        if (strpos($seoNorm, 'genel_tatil') !== false || strpos($nameNorm, 'genel tatil') !== false) {
            return 'public_holidays';
        }
        if (strpos($seoNorm, 'anma_gun') !== false || strpos($seoNorm, 'anma_gün') !== false || strpos($nameNorm, 'anma gün') !== false) {
            return 'memorial_days';
        }
        if (strpos($seoNorm, 'mesleki_gun') !== false || strpos($seoNorm, 'mesleki_gün') !== false || strpos($nameNorm, 'mesleki gün') !== false) {
            return 'professional_days';
        }
        if (strpos($seoNorm, 'onemli_gun') !== false || strpos($seoNorm, 'önemli_gün') !== false || strpos($nameNorm, 'önemli gün') !== false) {
            return 'special_observances';
        }
        if (strpos($seoNorm, 'resmi_tatil') !== false || strpos($nameNorm, 'resmi tatil') !== false || strpos($nameNorm, 'bêhnvedan') !== false) {
            return 'official_holidays';
        }
        if (strpos($seoNorm, 'bayramlar') !== false || strpos($nameNorm, 'bayram') !== false || strpos($nameNorm, 'cejn') !== false) {
            return 'holidays';
        }

        return 'default';
    }

    /**
     * Loads top-level editorial categories and full descendant hierarchy for active language.
     * Respects root containers (root = 'y') such as ##TR so categories inside container rows are preserved.
     */
    private function _getTopLevelCategoriesData(string $lang, array $allowedCatIds): array
    {
        $db = Load::lib('Database');
        $safeLang = addslashes($lang);

        // Fetch container IDs (root = 'y' or name starting with '##')
        $db->fetch_array("SELECT `id` FROM `mod_event_category` WHERE `lang` = '{$safeLang}' AND (`root` = 'y' OR `name` LIKE '##%')");
        $containerRows = $db->toArray();
        $containerIds = [];
        if (is_array($containerRows)) {
            foreach ($containerRows as $c) {
                $containerIds[(int) $c['id']] = true;
            }
        }

        // Fetch all active non-container categories
        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $db->fetch_array("SELECT `id`, `name`, `seo`, `lang`, `parent_id`, `root`, `order`, `title`, `description`, `image`, `acl_id`
                          FROM `mod_event_category`
                          WHERE `lang` = '{$safeLang}' AND `root` = 'n' AND `id` IN ({$allowedCatList})
                          ORDER BY `order` ASC, `id` ASC");
        $allRows = $db->toArray();
        if (!is_array($allRows) || empty($allRows)) {
            return ['top' => [], 'sub_by_parent' => [], 'all_by_id' => []];
        }

        $allById = [];
        foreach ($allRows as $r) {
            $allById[(int) $r['id']] = $r;
        }

        $topLevel = [];
        $subByParent = [];

        foreach ($allRows as $r) {
            $catId = (int) $r['id'];
            $parentId = (int) ($r['parent_id'] ?? 0);

            // A category is top-level if parent_id is 0 OR parent is a root container (##TR) OR parent is missing
            $isTop = ($parentId === 0 || isset($containerIds[$parentId]) || !isset($allById[$parentId]));

            if ($isTop) {
                $topLevel[$catId] = $r;
            } else {
                $subByParent[$parentId][] = $r;
            }
        }

        return [
            'top' => array_values($topLevel),
            'sub_by_parent' => $subByParent,
            'all_by_id' => $allById,
        ];
    }

    public function _categories()
    {
        if (!$this->isFeatureReady('event.front')) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_event_category', $lang);
        if (empty($allowedCatIds)) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $catData = $this->_getTopLevelCategoriesData($lang, $allowedCatIds);
        $topCategories = $catData['top'];
        if (empty($topCategories)) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $db = Load::lib('Database');
        $safeLang = addslashes($lang);
        $aclCondition = $this->getAllowedAclCondition('');

        $countsByCategoryId = [];
        $db->fetch_array("SELECT `category_id`, COUNT(*)
                          FROM `mod_event`
                          WHERE `status` = 'y' AND `lang` = '{$safeLang}' AND {$aclCondition} AND `category_id` IN ({$allowedCatList})
                          GROUP BY `category_id`");
        $countRows = $db->toArray();
        if (is_array($countRows)) {
            foreach ($countRows as $cRow) {
                if (isset($cRow['category_id'])) {
                    $countsByCategoryId[(int) $cRow['category_id']] = (int) end($cRow);
                }
            }
        }

        foreach ($topCategories as &$cat) {
            $catId = (int) $cat['id'];
            $cat['name'] = $this->_cleanText($cat['name'] ?? '');

            // Roll up count from self and all descendant subcategories
            $descendantIds = $this->getCategoryDescendantIds($catId, $lang, 'mod_event_category');
            $descendantIds = array_intersect($descendantIds, $allowedCatIds);

            $totalCount = 0;
            foreach ($descendantIds as $dId) {
                $totalCount += ($countsByCategoryId[(int) $dId] ?? 0);
            }
            $cat['event_count'] = $totalCount;
            $cat['template_key'] = $this->_resolveCategoryTemplateKey($cat);
        }
        unset($cat);

        $this->theme->ctx_set('categories', $topCategories);
    }

    public function events($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('event.front')) {
            return;
        }

        // Shift numeric category to page parameter (e.g. /events/2)
        if ($category !== null && is_numeric($category)) {
            $page = max(1, (int) $category);
            $category = null;
        }

        $this->_categories();
        $page = max(1, intval($page));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_event_category', $lang);
        if (empty($allowedCatIds)) {
            $this->theme->ctx_set('events', []);
            $this->theme->ctx_set('category_sections', []);
            $this->theme->ctx_set('total_events', 0);
            $this->theme->ctx_set('page', 1);
            $this->theme->ctx_set('page_count', 0);
            $this->theme->tpl('index/mod_event/events');
            return;
        }

        $catData = $this->_getTopLevelCategoriesData($lang, $allowedCatIds);
        $topCategories = $catData['top'];
        $subByParent = $catData['sub_by_parent'];
        $allById = $catData['all_by_id'];

        $safeLang = addslashes($lang);
        $aclCondition = $this->getAllowedAclCondition('');
        $now = date('Y-m-d H:i:s');
        $db = Load::lib('Database');

        // =====================================================================
        // 1. HUB MODE (/events): Render Dedicated Twig Section per Category
        // =====================================================================
        if ($category === null || $category === '') {
            $allowedCatList = implode(',', array_map('intval', $allowedCatIds));

            // Batch fetch all published events for active language
            $db->fetch_array("SELECT `id`, `name`, `seo`, `lang`, `image`, `subject`, `start_date`, `end_date`, `location`, `summary`, `deck`, `category_id`, `participants`, `guests`, `is_official_holiday`, `holiday_duration`, `is_recurring`
                              FROM `mod_event`
                              WHERE `status` = 'y' AND `lang` = '{$safeLang}' AND {$aclCondition} AND `category_id` IN ({$allowedCatList})
                              ORDER BY `start_date` ASC, `id` ASC");
            $allEventRows = $db->toArray();
            $allEventsClean = is_array($allEventRows) ? $this->_cleanEventRows($allEventRows) : [];

            // Map each category to its owning top-level category ID
            $catToTopMap = [];
            foreach ($topCategories as $tCat) {
                $topId = (int) $tCat['id'];
                $descendants = $this->getCategoryDescendantIds($topId, $lang, 'mod_event_category');
                foreach ($descendants as $descId) {
                    $catToTopMap[(int) $descId] = $topId;
                }
            }

            // Group events by owning top-level category
            $eventsByTopCat = [];
            foreach ($allEventsClean as $ev) {
                $cId = (int) ($ev['category_id'] ?? 0);
                $ownerTopId = $catToTopMap[$cId] ?? $cId;
                $eventsByTopCat[$ownerTopId][] = $ev;
            }

            // Build complete category sections array (EVERY category is included)
            $categorySections = [];
            $totalCountAll = 0;

            foreach ($topCategories as $tCat) {
                $topId = (int) $tCat['id'];
                $tEvents = $eventsByTopCat[$topId] ?? [];

                // Sort: Upcoming events first (ASC), followed by past events (DESC)
                $upcoming = [];
                $past = [];
                $todayFloor = date('Y-m-d 00:00:00');
                foreach ($tEvents as $ev) {
                    $rawEndDate = !empty($ev['end_date']) ? $ev['end_date'] : $ev['start_date'];
                    if ($rawEndDate >= $todayFloor) {
                        $upcoming[] = $ev;
                    } else {
                        $past[] = $ev;
                    }
                }

                // Reverse past events so most recent past appears first
                usort($past, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));
                $mergedEvents = array_merge($upcoming, $past);

                $templateKey = $this->_resolveCategoryTemplateKey($tCat);
                $templatePath = 'index/mod_event/sections/section_' . $templateKey . '.twig';
                $isCivic = in_array($templateKey, [
                    'exams',
                    'official_holidays',
                    'holidays',
                    'public_holidays',
                    'national_holidays',
                    'religious_holidays',
                    'memorial_days',
                    'professional_days',
                    'special_observances',
                ], true);

                $section = [
                    'id' => $topId,
                    'name' => $this->_cleanText($tCat['name'] ?? ''),
                    'seo' => $tCat['seo'],
                    'description' => $this->_cleanText($tCat['description'] ?? ''),
                    'template_key' => $templateKey,
                    'template_path' => $templatePath,
                    'is_civic' => $isCivic,
                    'section_col_class' => $isCivic ? 'col-12 col-lg-4' : 'col-12 col-lg-6',
                    'event_count' => count($mergedEvents),
                    'events' => array_slice($mergedEvents, 0, 6), // 6 featured events per category section
                    'subcategories' => $subByParent[$topId] ?? [],
                ];

                $totalCountAll += count($mergedEvents);
                $categorySections[] = $section;
            }

            $this->theme->ctx_set('category_sections', $categorySections);
            $this->theme->ctx_set('events', $allEventsClean);
            $this->theme->ctx_set('total_events', $totalCountAll);
            $this->theme->ctx_set('page', 1);
            $this->theme->ctx_set('page_count', 1);
            $this->theme->ctx_set('active_category', null);
            $this->theme->ctx_set('active_section', null);
            $this->theme->tpl('index/mod_event/events');
            return;
        }

        // =====================================================================
        // 2. FILTERED CATEGORY MODE (/events/{seo}): Single Category Listing
        // =====================================================================
        $decodedSeo = trim(rawurldecode(html_entity_decode((string) $category, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        $catModel = Load::model('mod_event_category');
        $catModel->flush();
        $catModel->where_seo_and_lang_and_root($decodedSeo, $lang, 'n')->limit(1)->gRo();
        if ($catModel->getSize() === 0 && is_numeric($decodedSeo)) {
            $catModel->flush();
            $catModel->where_id_and_lang_and_root((int) $decodedSeo, $lang, 'n')->limit(1)->gRo();
        }

        if ($catModel->getSize() === 0) {
            // Inbound legacy ASCII category matching
            $requestedCategoryKey = Helper::ascii_slug_key($decodedSeo);
            $legacyCategoryAliasMap = [
                'resmi_tatil' => 'Genel_Tatiller',
                'bayramlar'   => 'Milli_Bayramlar',
            ];
            if (isset($legacyCategoryAliasMap[$requestedCategoryKey])) {
                $targetUrl = rtrim((string)$this->site['saddress'], '/') . '/events/' . $legacyCategoryAliasMap[$requestedCategoryKey];
                if ($page > 1) {
                    $targetUrl .= '/' . $page;
                }
                if (PHP_SAPI !== 'cli' && !headers_sent()) {
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . Helper::sitemap_url($targetUrl));
                    exit;
                }
            }
            if ($requestedCategoryKey !== '') {
                $db = Load::lib('Database');
                $safeLang = addslashes($lang);
                $db->fetch_array("SELECT id, seo FROM `mod_event_category` WHERE `lang` = '{$safeLang}' AND `root` = 'n'");
                $candidateCategories = $db->toArray();
                $matchedCategorySeo = null;
                $matchedCategoryCount = 0;
                if (is_array($candidateCategories)) {
                    foreach ($candidateCategories as $candidateCategory) {
                        if (Helper::ascii_slug_key((string)$candidateCategory['seo']) === $requestedCategoryKey) {
                            $matchedCategorySeo = (string)$candidateCategory['seo'];
                            $matchedCategoryCount++;
                        }
                    }
                }
                if ($matchedCategoryCount === 1 && $matchedCategorySeo !== null) {
                    $targetUrl = rtrim((string)$this->site['saddress'], '/') . '/events/' . $matchedCategorySeo;
                    if ($page > 1) {
                        $targetUrl .= '/' . $page;
                    }
                    if (PHP_SAPI !== 'cli' && !headers_sent()) {
                        header("HTTP/1.1 301 Moved Permanently");
                        header("Location: " . Helper::sitemap_url($targetUrl));
                        exit;
                    }
                }
            }

            return $this->_404();
        }

        $activeCategory = $catModel->toArray();
        $catId = (int) $activeCategory['id'];
        if (!in_array($catId, $allowedCatIds, true)) {
            return $this->_404();
        }

        // Strict canonical category slug comparison
        if (PHP_SAPI !== 'cli' && !headers_sent() && (string)$activeCategory['seo'] !== $decodedSeo) {
            $targetUrl = rtrim((string)$this->site['saddress'], '/') . '/events/' . $activeCategory['seo'];
            if ($page > 1) {
                $targetUrl .= '/' . $page;
            }
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . Helper::sitemap_url($targetUrl));
            exit;
        }

        $descendantIds = $this->getCategoryDescendantIds($catId, $lang, 'mod_event_category');
        $descendantIds = array_intersect($descendantIds, $allowedCatIds);
        if (empty($descendantIds)) {
            $descendantIds = [$catId];
        }
        $descendantList = implode(',', array_map('intval', $descendantIds));

        // Direct subcategories of this category for subcategory filter bar
        $subcategories = [];
        if (isset($subByParent[$catId])) {
            $subcategories = $subByParent[$catId];
        }

        $eventModel = Load::model('mod_event');
        $eventModel->flush();
        $eventModel->where_lang_and_status($lang, 'y');
        $eventModel->addWhere($aclCondition);
        $eventModel->addWhere("category_id IN ({$descendantList})");

        $totalEvents = $eventModel->getCount();
        $pageCount = (int) ceil($totalEvents / $limit);

        $eventModel->order('start_date', 'DESC');
        $eventModel->limit($limit, $offset);
        $eventModel->gR();

        $events = [];
        if ($eventModel->getSize() > 0) {
            $events = $this->_cleanEventRows($eventModel->toArray());
        }

        $templateKey = $this->_resolveCategoryTemplateKey($activeCategory);
        $templatePath = 'index/mod_event/sections/section_' . $templateKey . '.twig';

        $activeSection = [
            'id' => $catId,
            'name' => $this->_cleanText($activeCategory['name'] ?? ''),
            'seo' => $activeCategory['seo'],
            'description' => $this->_cleanText($activeCategory['description'] ?? ''),
            'template_key' => $templateKey,
            'template_path' => $templatePath,
            'event_count' => $totalEvents,
            'events' => $events,
            'subcategories' => $subcategories,
        ];

        $this->theme->ctx_set('events', $events);
        $this->theme->ctx_set('total_events', $totalEvents);
        $this->theme->ctx_set('page', $page);
        $this->theme->ctx_set('page_count', $pageCount);
        $this->theme->ctx_set('active_category', $activeCategory);
        $this->theme->ctx_set('active_section', $activeSection);
        $this->theme->ctx_set('subcategories', $subcategories);
        $this->theme->ctx_set('category_sections', []);
        $this->theme->tpl('index/mod_event/events');
    }

    public function event($seo)
    {
        if (!$this->guardFeatureOr404('event.front')) {
            return;
        }

        $this->_categories();
        $lang = (string) $this->site['lang'];
        $cleanSeo = trim(html_entity_decode(rawurldecode((string)$seo), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $eventModel = $this->findItemBySeoOrIdStrict('mod_event.events', $cleanSeo, $lang, 'y');
        if ($eventModel === null || $eventModel->getSize() === 0) {
            // Inbound legacy ASCII fallback
            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                $requestedKey = Helper::ascii_slug_key($cleanSeo);
                if ($requestedKey !== '') {
                    $db = Load::lib('Database');
                    $safeLang = addslashes($lang);
                    $db->fetch_array("SELECT id, seo FROM `mod_event` WHERE `lang` = '{$safeLang}' AND `status` = 'y'");
                    $candidateRows = $db->toArray();
                    $matchedSeo = null;
                    $matchedCount = 0;
                    if (is_array($candidateRows)) {
                        foreach ($candidateRows as $candidateRow) {
                            if (Helper::ascii_slug_key((string)$candidateRow['seo']) === $requestedKey) {
                                $matchedSeo = (string)$candidateRow['seo'];
                                $matchedCount++;
                            }
                        }
                    }
                    if ($matchedCount === 1 && $matchedSeo !== null) {
                        $canonicalUrl = rtrim((string)$this->site['saddress'], '/') . '/event/' . $matchedSeo;
                        header("HTTP/1.1 301 Moved Permanently");
                        header("Location: " . Helper::sitemap_url($canonicalUrl));
                        exit;
                    }
                }
            }

            return $this->_404();
        }

        $eventModel->sync();

        // Strict canonical slug comparison
        if (PHP_SAPI !== 'cli' && !headers_sent() && (string)$eventModel->seo !== $cleanSeo) {
            $canonicalUrl = rtrim((string)$this->site['saddress'], '/') . '/event/' . $eventModel->seo;
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . Helper::sitemap_url($canonicalUrl));
            exit;
        }

        if ($this->_permission_check((int) $eventModel->acl_id)) {
            return;
        }
        if ($this->_checkCategoryHierarchyPermission((int) $eventModel->category_id)) {
            return;
        }

        $this->_update_view_count('mod_event', $eventModel->id);

        if (isset($eventModel->video) && $eventModel->video != '') {
            $this->_mod_video($eventModel->video);
        }
        if (isset($eventModel->gallery_id) && $eventModel->gallery_id != 0) {
            $this->_exact_gallery($eventModel->gallery_id);
        } else {
            $this->_fetch_gallery_images('mod_event', $eventModel->id);
        }
        if (isset($eventModel->file_id) && $eventModel->file_id != 0) {
            $this->_exact_attachment($eventModel->file_id);
        } else {
            $this->_mod_attachment('mod_event', $eventModel->id);
        }

        $this->_mod_faq('mod_event', $eventModel->id);

        $eventData = $this->_cleanEventRow($eventModel->toArray());
        $this->theme->ctx_set('d', $eventData);
        $this->theme->ctx_set('detail', $eventData);

        // Related upcoming events
        $relatedModel = Load::model('mod_event');
        $relatedModel->flush();
        $relatedModel->where_lang_and_status($lang, 'y');
        $relatedModel->addWhere("id != '" . (int) $eventModel->id . "'");
        $aclCondition = $this->getAllowedAclCondition('');
        $relatedModel->addWhere($aclCondition);
        if ($eventModel->category_id > 0) {
            $relatedModel->addWhere("category_id = '" . (int) $eventModel->category_id . "'");
        }
        $relatedModel->order('start_date', 'ASC')->limit(4)->gR();
        $related = [];
        if ($relatedModel->getSize() > 0) {
            $related = $this->_cleanEventRows($relatedModel->toArray());
        }
        $this->theme->ctx_set('related_events', $related);

        // Context Bridge: Related News from mod_news matching subject or keyword token
        $relatedNews = [];
        if ($this->isFeatureReady('news.front')) {
            $newsDb = Load::lib('Database');
            $searchToken = !empty($eventModel->subject) ? addslashes($eventModel->subject) : '';
            if ($searchToken !== '') {
                $newsAclCondition = $this->getAllowedAclCondition('', 'acl_id');
                $newsSafeLang = addslashes($lang);
                $newsQuery = "SELECT `id`, `name`, `seo`, `image`, `summary`, `start_date`, `view_count`, `lang`
                              FROM `mod_news`
                              WHERE `status` = 'y' AND `lang` = '{$newsSafeLang}' AND {$newsAclCondition}
                                AND (`name` LIKE '%{$searchToken}%' OR `summary` LIKE '%{$searchToken}%' OR `subject` LIKE '%{$searchToken}%')
                              ORDER BY `start_date` DESC
                              LIMIT 4";
                $newsDb->fetch_array($newsQuery);
                $nRows = $newsDb->toArray();
                if (is_array($nRows) && !empty($nRows)) {
                    $relatedNews = $nRows;
                }
            }
        }
        $this->theme->ctx_set('related_news', $relatedNews);

        $this->theme->tpl('index/mod_event/event');
    }

    public function calendar()
    {
        if (!$this->guardFeatureOr404('event.calendar')) {
            return;
        }

        $this->_categories();
        $now = time();
        $currentYear = (int) date('Y', $now);
        $currentMonth = (int) date('m', $now);

        $this->theme->ctx_set('current_year', $currentYear);
        $this->theme->ctx_set('current_month', $currentMonth);
        $this->theme->tpl('index/mod_event/calendar');
    }

    public function calendar_json()
    {
        if (!$this->guardFeatureOr404('event.calendar')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            return;
        }

        $year = intval(Req::is_Post('year') ? Req::Post_Safe('year') : (isset($_GET['year']) ? $_GET['year'] : date('Y')));
        $month = intval(Req::is_Post('month') ? Req::Post_Safe('month') : (isset($_GET['month']) ? $_GET['month'] : date('m')));
        $catFilter = trim(rawurldecode((string) (Req::Post_Safe('category') ?: ($_GET['category'] ?? ''))));

        if ($year < 2000 || $year > 2100) $year = (int) date('Y');
        if ($month < 1 || $month > 12) $month = (int) date('m');

        // Dual-DB safe ISO date boundaries (zero YEAR() or MONTH() calls)
        $monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $daysInMonth = (int) date('t', strtotime($monthStart));
        $monthEnd = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $daysInMonth);

        $lang = (string) $this->site['lang'];
        $safeLang = addslashes($lang);
        $aclCondition = $this->getAllowedAclCondition('');

        $eventModel = Load::model('mod_event');
        $eventModel->flush();
        $eventModel->where_lang_and_status($safeLang, 'y');

        // Multi-day overlap check: starts before or in month, and ends in or after month start
        $eventModel->addWhere("start_date <= '{$monthEnd}' AND COALESCE(end_date, start_date) >= '{$monthStart}'");
        $eventModel->addWhere($aclCondition);

        // Optional category filtering with hierarchy expansion
        if ($catFilter !== '') {
            $catLookup = Load::model('mod_event_category');
            $catLookup->flush();
            $catLookup->where_seo_and_lang_and_root($catFilter, $lang, 'n')->limit(1)->gRo();
            if ($catLookup->getSize() === 0 && is_numeric($catFilter)) {
                $catLookup->flush();
                $catLookup->where_id_and_lang_and_root((int) $catFilter, $lang, 'n')->limit(1)->gRo();
            }
            if ($catLookup->getSize() > 0) {
                $catLookup->sync();
                $filterCatId = (int) $catLookup->id;
                $filterCatIds = $this->getCategoryDescendantIds($filterCatId, $lang, 'mod_event_category');
                $allowedCatIds = $this->getAllowedCategoryIds('mod_event_category', $lang);
                $filterCatIds = array_intersect($filterCatIds, $allowedCatIds);
                if (!empty($filterCatIds)) {
                    $catInSql = implode(',', array_map('intval', $filterCatIds));
                    $eventModel->addWhere("category_id IN ({$catInSql})");
                }
            }
        }

        $eventModel->order('start_date', 'ASC')->gR();

        $results = [];
        if ($eventModel->getSize() > 0) {
            foreach ($eventModel->toArray() as $row) {
                $cleaned = $this->_cleanEventRow($row);

                // Compute span_days across this requested month
                $eventStartTs = strtotime($cleaned['start_date']);
                $rawEnd = !empty($cleaned['end_date']) ? $cleaned['end_date'] : $cleaned['start_date'];
                $eventEndTs = strtotime($rawEnd);

                $spanStartTs = max(strtotime($monthStart), strtotime(date('Y-m-d 00:00:00', $eventStartTs)));
                $spanEndTs = min(strtotime($monthEnd), strtotime(date('Y-m-d 23:59:59', $eventEndTs)));

                $spanDays = [];
                for ($curTs = $spanStartTs; $curTs <= $spanEndTs; $curTs += 86400) {
                    $spanDays[] = (int) date('j', $curTs);
                }
                if (empty($spanDays)) {
                    $spanDays = [(int) $cleaned['start_day']];
                }
                $spanDays = array_values(array_unique($spanDays));

                $results[] = [
                    'id' => (int) $cleaned['id'],
                    'name' => $cleaned['name'],
                    'seo' => $cleaned['seo'],
                    'url' => $this->site['saddress'] . 'event/' . $cleaned['seo'],
                    'subject' => $cleaned['subject'] ?? '',
                    'start_date' => $cleaned['start_date'],
                    'end_date' => $cleaned['end_date'] ?? '',
                    'start_day' => (int) $cleaned['start_day'],
                    'start_time' => $cleaned['start_time'],
                    'span_days' => $spanDays,
                    'duration_days' => (int) ($cleaned['duration_days'] ?? 1),
                    'category_id' => (int) ($cleaned['category_id'] ?? 0),
                    'days_until' => (int) ($cleaned['days_until'] ?? 0),
                    'is_today' => !empty($cleaned['is_today']),
                    'is_tomorrow' => !empty($cleaned['is_tomorrow']),
                    'is_upcoming' => !empty($cleaned['is_upcoming']),
                    'is_official_holiday' => $cleaned['is_official_holiday'] ?? 'n',
                    'holiday_duration' => (float) ($cleaned['holiday_duration'] ?? 0.0),
                    'is_recurring' => $cleaned['is_recurring'] ?? 'n',
                    'location' => $cleaned['location'] ?? '',
                    'image' => !empty($cleaned['image']) ? $this->site['iaddress'] . 'mod_event/thumb/' . $cleaned['image'] : '',
                    'summary' => $cleaned['summary'] ?? ''
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'year' => $year,
            'month' => $month,
            'events' => $results
        ]);
    }

    public function _event_agenda($limit = 10): array
    {
        if (!$this->isFeatureReady('event.front')) {
            $this->theme->ctx_set('event_agenda', []);
            return [];
        }

        $limit = max(1, intval($limit));
        $lang = (string) $this->site['lang'];
        $safeLang = addslashes($lang);
        $todayStart = date('Y-m-d 00:00:00');
        $aclCondition = $this->getAllowedAclCondition('');

        $allowedCatIds = $this->getAllowedCategoryIds('mod_event_category', $lang);
        $catFilter = '';
        if (!empty($allowedCatIds)) {
            $catList = implode(',', array_map('intval', $allowedCatIds));
            $catFilter = "AND `category_id` IN ({$catList})";
        }

        $db = Load::lib('Database');

        // 1. Fetch active-today or upcoming events ordered ascending
        $queryUpcoming = "SELECT `id`, `name`, `seo`, `lang`, `image`, `subject`, `start_date`, `end_date`, `location`, `summary`, `deck`, `category_id`, `is_official_holiday`, `holiday_duration`, `is_recurring`
                          FROM `mod_event`
                          WHERE `status` = 'y'
                            AND `lang` = '{$safeLang}'
                            AND COALESCE(`end_date`, `start_date`) >= '{$todayStart}'
                            AND {$aclCondition}
                            {$catFilter}
                          ORDER BY `start_date` ASC
                          LIMIT {$limit}";

        $db->fetch_array($queryUpcoming);
        $upcomingRows = $db->toArray();
        $agenda = is_array($upcomingRows) ? $upcomingRows : [];

        // 2. If fewer than limit, backfill with most recent past events excluding already-fetched IDs
        $remaining = $limit - count($agenda);
        if ($remaining > 0) {
            $excludeSql = '';
            if (!empty($agenda)) {
                $excludeIds = implode(',', array_map('intval', array_column($agenda, 'id')));
                $excludeSql = "AND `id` NOT IN ({$excludeIds})";
            }
            $queryPast = "SELECT `id`, `name`, `seo`, `lang`, `image`, `subject`, `start_date`, `end_date`, `location`, `summary`, `deck`, `category_id`, `is_official_holiday`, `holiday_duration`, `is_recurring`
                          FROM `mod_event`
                          WHERE `status` = 'y'
                            AND `lang` = '{$safeLang}'
                            AND COALESCE(`end_date`, `start_date`) < '{$todayStart}'
                            {$excludeSql}
                            AND {$aclCondition}
                            {$catFilter}
                          ORDER BY `start_date` DESC
                          LIMIT {$remaining}";

            $db->fetch_array($queryPast);
            $pastRows = $db->toArray();
            if (is_array($pastRows) && !empty($pastRows)) {
                $agenda = array_merge($agenda, $pastRows);
            }
        }

        // Attach category names if category_id is set
        $catIds = array_unique(array_filter(array_column($agenda, 'category_id')));
        if (!empty($catIds)) {
            $catIdList = implode(',', array_map('intval', $catIds));
            $db->fetch_array("SELECT `id`, `name`, `seo` FROM `mod_event_category` WHERE `id` IN ({$catIdList})");
            $catRows = $db->toArray();
            if (is_array($catRows) && !empty($catRows)) {
                $catMap = [];
                foreach ($catRows as $cRow) {
                    $catMap[(int) $cRow['id']] = $cRow;
                }
                foreach ($agenda as &$eventRow) {
                    $categoryId = (int) ($eventRow['category_id'] ?? 0);
                    if (isset($catMap[$categoryId])) {
                        if (empty($eventRow['category_name'])) {
                            $eventRow['category_name'] = $catMap[$categoryId]['name'];
                        }
                        if (empty($eventRow['category_seo'])) {
                            $eventRow['category_seo'] = $catMap[$categoryId]['seo'];
                        }
                    }
                }
                unset($eventRow);
            }
        }

        $cleanedAgenda = $this->_cleanEventRows($agenda);
        $this->theme->ctx_set('event_agenda', $cleanedAgenda);

        return $cleanedAgenda;
    }
}
