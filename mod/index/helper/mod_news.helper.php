<?php
class mod_news_helper extends indexBase
{
    use PublicHelperTrait;

    public function __construct()
    {
        parent::__construct();
    }

    private static array $imageFileCache = [];
    private static array $licenseUrlCache = [];

    private function _checkImageExists(string $path): bool
    {
        if (!isset(self::$imageFileCache[$path])) {
            self::$imageFileCache[$path] = (file_exists($path) && is_file($path));
        }
        return self::$imageFileCache[$path];
    }

    /**
     * Resolves canonical license and acquireLicensePage URLs for ImageObject schema metadata.
     */
    private function resolveImageLicenseUrls(): array
    {
        $lang = (string) ($this->site['lang'] ?? 'tr');
        $baseSiteUrl = rtrim((string) ($this->site['saddress'] ?? ''), '/') . '/';
        $cacheKey = $baseSiteUrl . '|' . $lang;
        if (isset(self::$licenseUrlCache[$cacheKey])) {
            return self::$licenseUrlCache[$cacheKey];
        }

        $legalUrl = $baseSiteUrl . 'legal';
        $licenseUrl = $legalUrl;

        if ($lang === 'tr') {
            $pageModel = Load::model('mod_page');
            $pageModel->flush();
            $pageModel->where_seo_and_status_and_root_and_lang('Kullanım_Koşulları', 'y', 'n', $lang);
            $pageModel->limit(1);
            $pageModel->gRo();
            if ($pageModel->getSize() !== 0) {
                $pageModel->sync();
                if (!empty($pageModel->seo)) {
                    $licenseUrl = $baseSiteUrl . 'page/' . $pageModel->seo;
                }
            }
        }

        self::$licenseUrlCache[$cacheKey] = [
            'license' => $licenseUrl,
            'acquire' => $legalUrl,
        ];

        return self::$licenseUrlCache[$cacheKey];
    }

    /**
     * Enriches news detail row with Google ImageObject license and attribution metadata.
     */
    private function enrichImageSchemaMetadata(array $newsData): array
    {
        $publisherName = trim((string) ($this->site['title'] ?? ''));
        if ($publisherName === '') {
            $publisherName = 'Amed Bulten';
        }
        $ownerName = trim((string) ($this->site['owner_name'] ?? ''));
        $defaultCopyrightOwner = ($ownerName !== '') ? $ownerName : $publisherName;

        $createdTimestamp = !empty($newsData['created_at']) ? strtotime((string) $newsData['created_at']) : false;
        $copyrightYear = ($createdTimestamp !== false && $createdTimestamp > 0)
            ? date('Y', $createdTimestamp)
            : date('Y');

        $rawCredit = trim((string) ($newsData['image_credit'] ?? ''));
        $creatorName = $publisherName;
        $copyrightOwner = $defaultCopyrightOwner;

        if ($rawCredit !== '') {
            $candidateName = $rawCredit;
            if (!preg_match('~^https?://~i', $candidateName) && preg_match('/^[^:]{2,25}:\s*(.+)$/u', $candidateName, $matches)) {
                $candidateName = trim((string) $matches[1]);
            }

            $isPlaceholder = (bool) preg_match('/^(?:tems[i\x{00EE}]l[i\x{00EE}]|illustrative|archive|ar\x{015F}[i\x{00EE}]v)$/iu', $candidateName);
            if ($candidateName !== '' && !$isPlaceholder) {
                $creatorName = $candidateName;
                $copyrightOwner = $candidateName;
            }
        }

        $licenseUrls = $this->resolveImageLicenseUrls();

        $newsData['image_schema_creator_name'] = $creatorName;
        $newsData['image_schema_credit_text'] = ($rawCredit !== '') ? $rawCredit : $publisherName;
        $newsData['image_schema_copyright_notice'] = '© ' . $copyrightYear . ' ' . $copyrightOwner;
        $newsData['image_schema_license_url'] = $licenseUrls['license'];
        $newsData['image_schema_acquire_license_url'] = $licenseUrls['acquire'];

        return $newsData;
    }

    private function _cleanText(?string $value): string
    {
        return html_entity_decode(
            html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }

    private function _cleanNewsRow(array $row): array
    {
        foreach (['name', 'title', 'summary', 'description', 'keywords', 'category_name', 'image_alt', 'image_credit'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->_cleanText($row[$field]);
            }
        }
        if (!empty($row['image'])) {
            $imageFile = root_dir . 'static' . DS . 'mod_news' . DS . $row['image'];
            if (!$this->_checkImageExists($imageFile)) {
                $row['image'] = '';
                $row['image_16x9'] = '';
                $row['image_4x3'] = '';
                $row['image_1x1'] = '';
            } else {
                $baseImgUrl = (string) ($this->site['iaddress'] ?? '');
                $baseResizerUrl = (string) ($this->site['address'] ?? '');

                // 16:9 (Main image: 1280x720)
                $row['image_16x9'] = $baseImgUrl . 'mod_news/' . $row['image'];

                // 4:3 (1200x900)
                $file4x3 = root_dir . 'static' . DS . 'mod_news' . DS . '4x3' . DS . $row['image'];
                if ($this->_checkImageExists($file4x3)) {
                    $row['image_4x3'] = $baseImgUrl . 'mod_news/4x3/' . $row['image'];
                } else {
                    $row['image_4x3'] = $baseResizerUrl . 'r/w-1200/h-900/c-1/q-80/f/mod_news/' . $row['image'];
                }

                // 1:1 (1200x1200)
                $file1x1 = root_dir . 'static' . DS . 'mod_news' . DS . '1x1' . DS . $row['image'];
                if ($this->_checkImageExists($file1x1)) {
                    $row['image_1x1'] = $baseImgUrl . 'mod_news/1x1/' . $row['image'];
                } else {
                    $row['image_1x1'] = $baseResizerUrl . 'r/w-1200/h-1200/c-1/q-80/f/mod_news/' . $row['image'];
                }
            }
        } else {
            $row['image_16x9'] = '';
            $row['image_4x3'] = '';
            $row['image_1x1'] = '';
        }
        $row['has_video'] = (!empty($row['video']) && $row['video'] !== 'a:0:{}');
        $row['has_gallery'] = (isset($row['gallery_id']) && (int) $row['gallery_id'] !== 0);
        $row['has_file'] = (isset($row['file_id']) && (int) $row['file_id'] !== 0);
        $row['has_deck'] = (isset($row['deck']) && $row['deck'] === 'y');
        $row = $this->enrichTimestampFields($row);
        return $row;
    }

    private function _cleanNewsRows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $rows[$index] = $this->_cleanNewsRow($row);
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
        $byId = $this->getCategoryHierarchyMap('mod_news_category', $lang);
        if (!isset($byId[$categoryId])) {
            $byId = $this->getCategoryHierarchyMap('mod_news_category', '');
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

    public function _categories()
    {
        if (!$this->isFeatureReady('news.front')) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        if (empty($allowedCatIds)) {
            $this->theme->ctx_set('categories', []);
            return;
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $mod_news_category_nested = Load::model('mod_news_category');
        $mod_news_category_nested->flush();
        $mod_news_category_nested->where_lang_and_root($lang, 'n');
        $mod_news_category_nested->addWhere("id IN ({$allowedCatList})");
        $mod_news_category_nested->order('order', 'ASC')->gR();

        $categories = $mod_news_category_nested->toArray();

        $countsByCategoryId = [];
        $db = Load::lib('Database');
        $safeLang = addslashes($lang);
        $aclCondition = $this->getAllowedAclCondition('');
        $publishCondition = $this->getPublishedDateCondition('');
        $db->fetch_array("SELECT `category_id`, COUNT(*) FROM `mod_news` WHERE `status` = 'y' AND `lang` = '{$safeLang}' AND {$aclCondition} AND {$publishCondition} AND `category_id` IN ({$allowedCatList}) GROUP BY `category_id`");
        $countRows = $db->toArray();
        if (is_array($countRows)) {
            foreach ($countRows as $cRow) {
                if (isset($cRow['category_id'])) {
                    $countsByCategoryId[(int) $cRow['category_id']] = (int) end($cRow);
                }
            }
        }

        if (is_array($categories)) {
            foreach ($categories as &$categoryItem) {
                $categoryItem['name'] = $this->_cleanText($categoryItem['name'] ?? '');
                $catId = isset($categoryItem['id']) ? (int) $categoryItem['id'] : 0;
                $categoryItem['news_count'] = $countsByCategoryId[$catId] ?? 0;
            }
            unset($categoryItem);
        }

        $this->theme->ctx_set('categories', $categories);
    }

    public function news($seo)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        $this->_categories();
        $seo = trim(html_entity_decode(rawurldecode((string) $seo), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $news = Load::model('mod_news.newses');
        $news->flush();
        $news->where_seo_and_status_and_lang($seo, 'y', $this->site['lang']);
        $news->addWhere($this->getPublishedDateCondition(''));
        $news->limit(1);
        $news->gRo();
        if ($news->getSize() !== 0) {
            $news->sync();

            // Strict canonical slug comparison to prevent non-canonical collation / casing URLs
            if (PHP_SAPI !== 'cli' && !headers_sent() && (string)$news->seo !== $seo) {
                $canonicalUrl = Helper::sitemap_url(rtrim((string) $this->site['saddress'], '/') . '/news/' . $news->seo);
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $canonicalUrl);
                exit;
            }

            if ($this->_permission_check((int) $news->acl_id)) {
                return;
            }
            if ($this->_checkCategoryHierarchyPermission((int) $news->category_id)) {
                return;
            }
            $this->_update_view_count('mod_news', $news->id);

            if (isset($news->video) && $news->video != '') {
                $this->_mod_video($news->video);
            }
            if (isset($news->gallery_id) && $news->gallery_id != 0) {
                $this->_exact_gallery($news->gallery_id);
            } else {
                $this->_fetch_gallery_images('mod_news', $news->id);
            }
            if (isset($news->file_id) && $news->file_id != 0) {
                $this->_exact_attachment($news->file_id);
            } else {
                $this->_mod_attachment('mod_news', $news->id);
            }

            $this->_mod_faq('mod_news', $news->id);

            $news_data = $this->_cleanNewsRow($news->toArray());
            $news_data = $this->enrichRowWithSocialPosts('mod_news', (int) $news->id, $news_data);

            // Metrics & E-E-A-T Enrichment
            $cleanBodyText = trim(strip_tags((string) ($news_data['content'] ?? '')));
            $cleanBodyText = preg_replace('/\s+/u', ' ', $cleanBodyText);
            $news_data['char_count'] = mb_strlen($cleanBodyText, 'UTF-8');
            $news_data['word_count'] = ($cleanBodyText !== '') ? count(preg_split('/\s+/u', $cleanBodyText, -1, PREG_SPLIT_NO_EMPTY)) : 0;

            // Author metadata resolution
            $username = trim((string) ($news_data['username'] ?? ''));
            $uName = trim((string) ($news_data['u_name'] ?? ''));
            $news_data['author_is_org'] = ($username === '' || $username === 'habermerkezi' || $uName === '');

            $allowedJobTitles = ['Gazeteci', 'Yazar', 'Editör', 'Editor', 'Muhabir', 'Köşe Yazarı'];
            $userGroup = (string) ($news_data['group_name'] ?? '');
            $news_data['author_job_title'] = in_array($userGroup, $allowedJobTitles, true) ? $userGroup : '';

            $userImg = trim((string) ($news_data['user_image'] ?? ''));
            if ($userImg !== '') {
                $avatarFile = root_dir . 'static' . DS . 'mod_user' . DS . 'thumb' . DS . $userImg;
                if ($this->_checkImageExists($avatarFile)) {
                    $news_data['author_avatar_url'] = (string) ($this->site['iaddress'] ?? '') . 'mod_user/thumb/' . $userImg;
                } else {
                    $news_data['author_avatar_url'] = '';
                }
            } else {
                $news_data['author_avatar_url'] = '';
            }

            $news_data['author_same_as'] = [];
            if (!$news_data['author_is_org'] && !empty($news_data['user_id'])) {
                try {
                    $userId = (int) $news_data['user_id'];
                    $social = Load::model('mod_user.mod_user_social');
                    $social->flush();
                    $social->where_user_id_and_is_active($userId, 'y')->order('display_order', 'ASC')->gR();
                    if ($social->getSize() > 0) {
                        foreach ($social->toArray() as $sRow) {
                            $sLink = trim((string) ($sRow['url'] ?? ''));
                            if ($sLink !== '' && preg_match('~^https?://~i', $sLink)) {
                                $news_data['author_same_as'][] = $sLink;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Database::query() exits on error (lib/Database/Database.php:130); this catch
                    // only intercepts non-DB throwables. The query must match mod_user_social's schema.
                }
            }

            $news_data = $this->enrichImageSchemaMetadata($news_data);

            $this->theme->ctx_set('d', $news_data);
            $this->theme->ctx_set('detail', $news_data);

            $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', (string) $this->site['lang']);
            $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
            $aclCondition = $this->getAllowedAclCondition('');

            $news_24h_model = Load::model('mod_news');
            $news_24h_model->flush();
            $news_24h_model->select('id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,deck')
                ->where_lang_and_status($this->site['lang'], 'y');
            if (!empty($news->id)) {
                $news_24h_model->addWhere("id != '" . (int) $news->id . "'");
            }
            $news_24h_model->addWhere($aclCondition);
            $news_24h_model->addWhere($this->getPublishedDateCondition(''));
            $news_24h_model->addWhere("category_id IN ({$allowedCatList})");
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $news_24h_model->addWhere("created_at >= '" . $yesterday . "'");
            $news_24h_model->limit(8)->order('created_at', 'DESC');
            $news_24h_model->gR();
            $news_24h = [];
            if ($news_24h_model->getSize() !== 0) {
                $news_24h = $this->_cleanNewsRows($news_24h_model->toArray());
            }

            $news_24h_count_model = Load::model('mod_news');
            $news_24h_count_model->flush();
            $news_24h_count_model->select('id')
                ->where_lang_and_status($this->site['lang'], 'y');
            if (!empty($news->id)) {
                $news_24h_count_model->addWhere("id != '" . (int) $news->id . "'");
            }
            $news_24h_count_model->addWhere($aclCondition);
            $news_24h_count_model->addWhere($this->getPublishedDateCondition(''));
            $news_24h_count_model->addWhere("category_id IN ({$allowedCatList})");
            $news_24h_count_model->addWhere("created_at >= '" . $yesterday . "'");
            $news_24h_count_model->getResult(true, true);
            $news_24h_count = (int) $news_24h_count_model->getSize();

            $isBackfill = false;
            if (count($news_24h) < 5) {
                $isBackfill = true;
                $exclude24hIds = [(int) $news->id];
                foreach ($news_24h as $n24) {
                    if (!empty($n24['id'])) {
                        $exclude24hIds[] = (int) $n24['id'];
                    }
                }
                $exclude24hList = implode(',', $exclude24hIds);
                $neededBackfill = 8 - count($news_24h);

                $backfill_model = Load::model('mod_news');
                $backfill_model->flush();
                $backfill_model->select('id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,deck')
                    ->where_lang_and_status($this->site['lang'], 'y');
                $backfill_model->addWhere("id NOT IN ({$exclude24hList})");
                $backfill_model->addWhere($aclCondition);
                $backfill_model->addWhere($this->getPublishedDateCondition(''));
                $backfill_model->addWhere("category_id IN ({$allowedCatList})");
                $backfill_model->limit($neededBackfill)->order('created_at', 'DESC');
                $backfill_model->gR();
                if ($backfill_model->getSize() !== 0) {
                    $backfillRows = $this->_cleanNewsRows($backfill_model->toArray());
                    $news_24h = array_merge($news_24h, $backfillRows);
                }
                $news_24h_count = count($news_24h);
            }

            $this->theme->ctx_set('news_24h', $news_24h);
            $this->theme->ctx_set('news_24h_count', $news_24h_count);
            $this->theme->ctx_set('news_24h_is_backfill', $isBackfill);

            // Related News Grid (newses)
            $relatedNews = [];
            $currentCatId = (int) ($news->category_id ?? 0);
            $catExcludeIds = [(int) $news->id];

            if ($currentCatId > 0) {
                $related_model = Load::model('mod_news');
                $related_model->flush();
                $related_model->select('id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,deck')
                    ->where_lang_and_status($this->site['lang'], 'y');
                $related_model->addWhere("id != '" . (int) $news->id . "'");
                $related_model->addWhere("category_id = {$currentCatId}");
                $related_model->addWhere($aclCondition);
                $related_model->addWhere($this->getPublishedDateCondition(''));
                $related_model->limit(6)->order('created_at', 'DESC');
                $related_model->gR();
                if ($related_model->getSize() !== 0) {
                    $relatedNews = $this->_cleanNewsRows($related_model->toArray());
                    foreach ($relatedNews as $rn) {
                        if (!empty($rn['id'])) {
                            $catExcludeIds[] = (int) $rn['id'];
                        }
                    }
                }
            }

            if (count($relatedNews) < 6) {
                $neededRelated = 6 - count($relatedNews);
                $excludeRelatedList = implode(',', $catExcludeIds);
                $fallback_model = Load::model('mod_news');
                $fallback_model->flush();
                $fallback_model->select('id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,deck')
                    ->where_lang_and_status($this->site['lang'], 'y');
                $fallback_model->addWhere("id NOT IN ({$excludeRelatedList})");
                $fallback_model->addWhere($aclCondition);
                $fallback_model->addWhere($this->getPublishedDateCondition(''));
                $fallback_model->addWhere("category_id IN ({$allowedCatList})");
                $fallback_model->limit($neededRelated)->order('created_at', 'DESC');
                $fallback_model->gR();
                if ($fallback_model->getSize() !== 0) {
                    $fallbackRows = $this->_cleanNewsRows($fallback_model->toArray());
                    $relatedNews = array_merge($relatedNews, $fallbackRows);
                }
            }

            $this->theme->ctx_set('newses', $relatedNews);
        } else {
            // Inbound legacy ASCII fallback: match requested key against published news
            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                $requestedKey = Helper::ascii_slug_key($seo);
                if ($requestedKey !== '') {
                    $db = Load::lib('Database');
                    $safeLang = addslashes((string)$this->site['lang']);
                    $publishCondition = $this->getPublishedDateCondition('');
                    $publishSql = !empty($publishCondition) ? " AND {$publishCondition}" : "";
                    $db->fetch_array("SELECT id, seo FROM `mod_news` WHERE `lang` = '{$safeLang}' AND `status` = 'y'{$publishSql}");
                    $candidateRows = $db->toArray();
                    $matchedSeo = null;
                    $matchCount = 0;
                    if (is_array($candidateRows)) {
                        foreach ($candidateRows as $candidateRow) {
                            if (Helper::ascii_slug_key((string)$candidateRow['seo']) === $requestedKey) {
                                $matchedSeo = (string)$candidateRow['seo'];
                                $matchCount++;
                            }
                        }
                    }

                    if ($matchCount === 1 && $matchedSeo !== null) {
                        $canonicalUrl = Helper::sitemap_url(rtrim((string) $this->site['saddress'], '/') . '/news/' . $matchedSeo);
                        header("HTTP/1.1 301 Moved Permanently");
                        header("Location: " . $canonicalUrl);
                        exit;
                    }
                }
            }

            return $this->_404();
        }
        $this->theme->tpl('index/mod_news/news');
        $this->_index();
        unset($news);
    }

    public function newses($category = null, $page = 1)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        if ($category !== null && is_numeric($category)) {
            $page = intval($category);
            $category = null;
        }

        if ($page !== null && $page !== '' && !is_numeric($page)) {
            return $this->_404();
        }

        $this->_categories();

        $page_limit = 80;
        $pager_limit = 4;

        $page = max(1, intval($page ?: 1));
        $offset = ($page - 1) * $page_limit;

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');
        $publishCondition = $this->getPublishedDateCondition('');

        $news = Load::model('mod_news.newses');
        $news->flush();
        $category_model = Load::model('mod_news_category');
        $category_model->flush();

        $allNewsLabel = $this->lang['index']['mod_news']['all_news'];

        if ($category === null || $category === '' || $category === 'all') {
            $news->where_status_and_lang('y', $lang);
            $news->addWhere($aclCondition);
            $news->addWhere($publishCondition);
            $news->addWhere("category_id IN ({$allowedCatList})");
            $news->all();
            $total_item = $news->getSize();

            $news->flush();
            $news->where_status_and_lang('y', $lang);
            $news->addWhere($aclCondition);
            $news->addWhere($publishCondition);
            $news->addWhere("category_id IN ({$allowedCatList})");
            $news->limit($page_limit, $offset)->order('created_at', 'DESC')->gR();

            if ($news->getSize() != 0) {
                $news_items = $this->_cleanNewsRows($news->toArray());
                $this->theme->ctx_set('newses', $news_items);
            }
            $category_seo = 'all';
            $categoryname = $allNewsLabel;
        } else {
            $category_clean = trim(html_entity_decode(rawurldecode((string)$category), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $category_model->where_seo_and_lang($category_clean, $lang)->limit(1)->gRo();
            if ($category_model->getSize() === 0) {
                // Inbound legacy ASCII category matching
                $requestedCategoryKey = Helper::ascii_slug_key($category_clean);
                if ($requestedCategoryKey !== '') {
                    $db = Load::lib('Database');
                    $safeLang = addslashes($lang);
                    $db->fetch_array("SELECT id, seo FROM `mod_news_category` WHERE `lang` = '{$safeLang}' AND `root` = 'n'");
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
                        $targetUrl = rtrim((string)$this->site['saddress'], '/') . '/newses/' . $matchedCategorySeo;
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
            $category_model->sync();

            // Strict canonical category slug comparison to prevent non-canonical collation / casing URLs
            if (PHP_SAPI !== 'cli' && !headers_sent() && (string)$category_model->seo !== $category_clean) {
                $targetUrl = rtrim((string)$this->site['saddress'], '/') . '/newses/' . $category_model->seo;
                if ($page > 1) {
                    $targetUrl .= '/' . $page;
                }
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . Helper::sitemap_url($targetUrl));
                exit;
            }

            $categoryId = (int) $category_model->id;
            $categoryRecord = $category_model->toArray();
            if (isset($categoryRecord['name'])) {
                $categoryRecord['name'] = $this->_cleanText($categoryRecord['name']);
            }
            $categoryname = $this->_cleanText($category_model->name);
            $category_seo = $category_model->seo;

            if ($this->_checkCategoryHierarchyPermission($categoryId)) {
                return;
            }

            $descendantIds = $this->getCategoryDescendantIds($categoryId, $lang, 'mod_news_category');
            $targetCategoryIds = array_values(array_intersect($descendantIds, $allowedCatIds));
            $idList = !empty($targetCategoryIds) ? implode(',', array_map('intval', $targetCategoryIds)) : '0';

            $news->where_status_and_lang('y', $lang);
            $news->addWhere($aclCondition);
            $news->addWhere($publishCondition);
            $news->addWhere("category_id IN ({$idList})");
            $news->order('created_at', 'DESC')->gR();
            $total_item = $news->getSize();
            $news->getLimit($page_limit, $offset);
            if ($news->getSize() !== 0) {
                $news_items = $this->_cleanNewsRows($news->toArray());
                $this->theme->ctx_set('newses', $news_items);
            }

            $this->theme->ctx_set('category_record', $categoryRecord);
        }

        $p = Load::lib('Pager');
        $p->total_item = $total_item;
        $p->page_limit = $page_limit;
        $p->pager_limit = $pager_limit;
        $p->page = $page;

        if ($p->check() === false) {
            return $this->_404();
        }

        if ($p->Result() !== false) {
            $this->theme->ctx_set('pagination', $p->Result());
        }

        $categorySlug = ($category === null || $category === '') ? 'all' : $category;
        if ($page > 1) {
            $d['link'] = $this->site['saddress'] . 'newses/' . $categorySlug . '/' . $page;
        } else {
            $d['link'] = $this->site['saddress'] . 'newses/' . $categorySlug;
        }

        $archivePageTemplate = $this->lang['index']['mod_news']['archive_page_title'];
        $singlePageTemplate = $this->lang['index']['mod_news']['title_format'];

        if ($page <= 1) {
            $d['title'] = trim(sprintf($singlePageTemplate, $categoryname));
        } else {
            $d['title'] = trim(sprintf($archivePageTemplate, $categoryname, $page));
        }

        $d['description'] = $allNewsLabel;
        $d['keywords'] = $this->lang['index']['mod_news']['keywords'];

        $this->theme->ctx_set('d', $d);
        $this->theme->ctx_set('categoryname', $categoryname);
        $this->theme->ctx_set('category_seo', $category_seo);
        $this->theme->ctx_set('page', $page);
        $this->theme->tpl('index/mod_news/newses');

        $this->_index();
    }

    public function _latest_news($limit, $select, array $excludeCategories = [], bool $onlyToday = false)
    {
        if (!$this->isFeatureReady('news.latest')) {
            return [];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $excludedCategoryIds = [];
        if (!empty($excludeCategories) && !empty($allowedCatIds)) {
            $catAliases = [
                'spor' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'werzîş' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'werzis' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'sports' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'sport' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'dünya' => ['dünya', 'dunya', 'cîhan', 'cihan', 'world', 'welt', 'monde', 'världen', 'العالم', 'جیهان'],
                'dunya' => ['dünya', 'dunya', 'cîhan', 'cihan', 'world', 'welt', 'monde', 'världen', 'العالم', 'جیهان'],
                'cîhan' => ['dünya', 'dunya', 'cîhan', 'cihan', 'world', 'welt', 'monde', 'världen', 'العالم', 'جیهان'],
                'cihan' => ['dünya', 'dunya', 'cîhan', 'cihan', 'world', 'welt', 'monde', 'världen', 'العالم', 'جیهان'],
                'world' => ['dünya', 'dunya', 'cîhan', 'cihan', 'world', 'welt', 'monde', 'världen', 'العالم', 'جیهان'],
                'ortadoğu' => ['ortadoğu', 'ortadogu', 'rojhilata_navîn', 'rojhilata navîn', 'middle_east', 'middle east', 'naher_osten', 'naher osten', 'moyen-orient', 'mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'],
                'ortadogu' => ['ortadoğu', 'ortadogu', 'rojhilata_navîn', 'rojhilata navîn', 'middle_east', 'middle east', 'naher_osten', 'naher osten', 'moyen-orient', 'mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'],
                'rojhilata_navîn' => ['ortadoğu', 'ortadogu', 'rojhilata_navîn', 'rojhilata navîn', 'middle_east', 'middle east', 'naher_osten', 'naher osten', 'moyen-orient', 'mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'],
                'rojhilata navîn' => ['ortadoğu', 'ortadogu', 'rojhilata_navîn', 'rojhilata navîn', 'middle_east', 'middle east', 'naher_osten', 'naher osten', 'moyen-orient', 'mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'],
                'middle east' => ['ortadoğu', 'ortadogu', 'rojhilata_navîn', 'rojhilata navîn', 'middle_east', 'middle east', 'naher_osten', 'naher osten', 'moyen-orient', 'mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'],
                'middle_east' => ['ortadoğu', 'ortadogu', 'rojhilata_navîn', 'rojhilata navîn', 'middle_east', 'middle east', 'naher_osten', 'naher osten', 'moyen-orient', 'mellanöstern', 'الشرق_الأوسط', 'ڕۆژهەڵاتی_ناوەڕاست'],
                'ekonomi' => ['ekonomi', 'aborî', 'abori', 'economy', 'wirtschaft', 'économie', 'economie', 'экономика', 'اقتصاد', 'ئابووری', 'ekonomiye'],
                'aborî' => ['ekonomi', 'aborî', 'abori', 'economy', 'wirtschaft', 'économie', 'economie', 'экономика', 'اقتصاد', 'ئابووری', 'ekonomiye'],
                'abori' => ['ekonomi', 'aborî', 'abori', 'economy', 'wirtschaft', 'économie', 'economie', 'экономика', 'اقتصاد', 'ئابووری', 'ekonomiye'],
                'economy' => ['ekonomi', 'aborî', 'abori', 'economy', 'wirtschaft', 'économie', 'economie', 'экономика', 'اقتصاد', 'ئابووری', 'ekonomiye'],
            ];

            $candidateLookup = [];
            if (!empty($excludeCategories)) {
                foreach ($excludeCategories as $categoryCandidate) {
                    $cLower = mb_strtolower(trim((string) $categoryCandidate));
                    if ($cLower === '') {
                        continue;
                    }
                    $candidateLookup[] = $cLower;
                    $cNormalized = str_replace(['_', '-'], ' ', $cLower);
                    $candidateLookup[] = $cNormalized;
                    $candidateLookup[] = str_replace(' ', '_', $cNormalized);
                    $candidateLookup[] = str_replace(' ', '-', $cNormalized);
                    if (isset($catAliases[$cLower])) {
                        foreach ($catAliases[$cLower] as $alias) {
                            $aLower = mb_strtolower(trim((string) $alias));
                            $candidateLookup[] = $aLower;
                            $aNorm = str_replace(['_', '-'], ' ', $aLower);
                            $candidateLookup[] = $aNorm;
                            $candidateLookup[] = str_replace(' ', '_', $aNorm);
                            $candidateLookup[] = str_replace(' ', '-', $aNorm);
                        }
                    }
                }
                $candidateLookup = array_values(array_unique($candidateLookup));
            }

            $catModel = Load::model('mod_news_category');
            $catModel->flush();
            $catModel->where_lang($lang);
            $catModel->addWhere("id IN ({$allowedCatList})");
            $catModel->gR();

            if ($catModel->getSize() > 0) {
                $categories = $catModel->toArray();
                foreach ($categories as $cat) {
                    $cName = mb_strtolower(trim((string) ($cat['name'] ?? '')));
                    $cSeo = mb_strtolower(trim((string) ($cat['seo'] ?? '')));
                    $cNameNorm = str_replace(['_', '-'], ' ', $cName);
                    $cSeoNorm = str_replace(['_', '-'], ' ', $cSeo);

                    if (
                        in_array($cName, $candidateLookup, true) ||
                        in_array($cSeo, $candidateLookup, true) ||
                        in_array($cNameNorm, $candidateLookup, true) ||
                        in_array($cSeoNorm, $candidateLookup, true)
                    ) {
                        $excludedCategoryIds[] = (int) $cat['id'];
                    }
                }
            }

            if (!empty($excludedCategoryIds)) {
                $targetExcludedIds = $excludedCategoryIds;
                foreach ($excludedCategoryIds as $exCatId) {
                    $descendants = $this->getCategoryDescendantIds($exCatId, $lang, 'mod_news_category');
                    if (!empty($descendants)) {
                        $targetExcludedIds = array_merge($targetExcludedIds, $descendants);
                    }
                }
                $excludedCategoryIds = array_values(array_unique(array_map('intval', $targetExcludedIds)));
            }
        }

        $m = Load::model('mod_news.newses');
        $m->flush();
        $m->select($select)->where_lang_and_status($lang, 'y');
        $m->addWhere("slider='n'");
        $m->addWhere("show_on_homepage='y'");
        $m->addWhere($aclCondition);
        $m->addWhere($this->getPublishedDateCondition(''));
        if ($onlyToday) {
            $todayStart = date('Y-m-d 00:00:00');
            $m->addWhere("created_at >= '{$todayStart}'");
        }
        $m->addWhere("category_id IN ({$allowedCatList})");
        if (!empty($excludedCategoryIds)) {
            $excludedCatList = implode(',', $excludedCategoryIds);
            $m->addWhere("category_id NOT IN ({$excludedCatList})");
        }
        $limitVal = (int) $limit;
        if ($limitVal > 0) {
            $m->limit($limitVal);
        }
        $m->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $this->_cleanNewsRows($m->toArray());
        }

        return [];
    }

    public function _featured_news(int $limit = 6, string $select = 'id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,category_id,category_name,category_seo', array $excludeCategories = ['spor']): array
    {
        if (!$this->isFeatureReady('news.latest')) {
            return [];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $excludedCategoryIds = [];
        if (!empty($excludeCategories) && !empty($allowedCatIds)) {
            $catAliases = [
                'spor' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'werzîş' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'werzis' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'sports' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
                'sport' => ['spor', 'werzîş', 'werzis', 'sports', 'sport', 'رياضة', 'وەرزش'],
            ];

            $candidateLookup = [];
            foreach ($excludeCategories as $categoryCandidate) {
                $cLower = mb_strtolower(trim((string) $categoryCandidate));
                if ($cLower === '') {
                    continue;
                }
                $candidateLookup[] = $cLower;
                $cNormalized = str_replace(['_', '-'], ' ', $cLower);
                $candidateLookup[] = $cNormalized;
                $candidateLookup[] = str_replace(' ', '_', $cNormalized);
                $candidateLookup[] = str_replace(' ', '-', $cNormalized);
                if (isset($catAliases[$cLower])) {
                    foreach ($catAliases[$cLower] as $alias) {
                        $aLower = mb_strtolower(trim((string) $alias));
                        $candidateLookup[] = $aLower;
                        $aNorm = str_replace(['_', '-'], ' ', $aLower);
                        $candidateLookup[] = $aNorm;
                        $candidateLookup[] = str_replace(' ', '_', $aNorm);
                        $candidateLookup[] = str_replace(' ', '-', $aNorm);
                    }
                }
            }
            $candidateLookup = array_values(array_unique($candidateLookup));

            $catModel = Load::model('mod_news_category');
            $catModel->flush();
            $catModel->where_lang($lang);
            $catModel->addWhere("id IN ({$allowedCatList})");
            $catModel->gR();

            if ($catModel->getSize() > 0) {
                $categories = $catModel->toArray();
                foreach ($categories as $cat) {
                    $cName = mb_strtolower(trim((string) ($cat['name'] ?? '')));
                    $cSeo = mb_strtolower(trim((string) ($cat['seo'] ?? '')));
                    $cNameNorm = str_replace(['_', '-'], ' ', $cName);
                    $cSeoNorm = str_replace(['_', '-'], ' ', $cSeo);

                    if (
                        in_array($cName, $candidateLookup, true) ||
                        in_array($cSeo, $candidateLookup, true) ||
                        in_array($cNameNorm, $candidateLookup, true) ||
                        in_array($cSeoNorm, $candidateLookup, true)
                    ) {
                        $excludedCategoryIds[] = (int) $cat['id'];
                    }
                }
            }

            if (!empty($excludedCategoryIds)) {
                $targetExcludedIds = $excludedCategoryIds;
                foreach ($excludedCategoryIds as $exCatId) {
                    $descendants = $this->getCategoryDescendantIds($exCatId, $lang, 'mod_news_category');
                    if (!empty($descendants)) {
                        $targetExcludedIds = array_merge($targetExcludedIds, $descendants);
                    }
                }
                $excludedCategoryIds = array_values(array_unique(array_map('intval', $targetExcludedIds)));
            }
        }

        $m = Load::model('mod_news.newses');
        $m->flush();
        $m->select($select)->where_lang_and_status($lang, 'y');
        $m->addWhere("slider='y'");
        $m->addWhere("show_on_homepage='y'");
        $m->addWhere($aclCondition);
        $m->addWhere($this->getPublishedDateCondition(''));
        $m->addWhere("category_id IN ({$allowedCatList})");
        if (!empty($excludedCategoryIds)) {
            $excludedCatList = implode(',', $excludedCategoryIds);
            $m->addWhere("category_id NOT IN ({$excludedCatList})");
        }
        $limitVal = (int) $limit;
        if ($limitVal > 0) {
            $m->limit($limitVal);
        }
        $m->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $this->_cleanNewsRows($m->toArray());
        }

        return [];
    }

    public function _category_news(array $categoryCandidates, int $limit = 3, string $select = 'id,image,name,seo,summary,created_at,video,gallery_id,file_id,deck,category_id,category_name,category_seo', array $excludeIds = [], bool $excludeSlider = true): array
    {
        if (!$this->isFeatureReady('news.latest')) {
            return ['items' => [], 'category_name' => '', 'category_seo' => '', 'matched_categories' => []];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        if (empty($allowedCatIds)) {
            return ['items' => [], 'category_name' => '', 'category_seo' => '', 'matched_categories' => []];
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $catModel = Load::model('mod_news_category');
        $catModel->flush();
        $catModel->where_lang($lang);
        $catModel->addWhere("id IN ({$allowedCatList})");
        $catModel->gR();

        $matchedCategoryIds = [];
        $matchedCategories = [];
        $matchedCategoryName = '';
        $matchedCategorySeo = '';

        if ($catModel->getSize() > 0) {
            $categories = $catModel->toArray();
            $candidateLookup = array_map(function ($c) {
                return mb_strtolower(trim((string) $c));
            }, $categoryCandidates);

            foreach ($categories as $cat) {
                $cName = mb_strtolower(trim((string) ($cat['name'] ?? '')));
                $cSeo = mb_strtolower(trim((string) ($cat['seo'] ?? '')));
                if (in_array($cName, $candidateLookup, true) || in_array($cSeo, $candidateLookup, true)) {
                    $catId = (int) $cat['id'];
                    $catNameClean = $this->_cleanText((string) ($cat['name'] ?? ''));
                    $catSeoClean = (string) ($cat['seo'] ?? '');
                    $matchedCategoryIds[] = $catId;
                    $matchedCategories[] = [
                        'id' => $catId,
                        'name' => $catNameClean,
                        'seo' => $catSeoClean,
                    ];
                    if ($matchedCategoryName === '') {
                        $matchedCategoryName = $catNameClean;
                        $matchedCategorySeo = $catSeoClean;
                    }
                }
            }
        }

        if (empty($matchedCategoryIds)) {
            return ['items' => [], 'category_name' => '', 'category_seo' => '', 'matched_categories' => []];
        }

        $targetCategoryIds = $matchedCategoryIds;
        foreach ($matchedCategoryIds as $matchedCatId) {
            $descendants = $this->getCategoryDescendantIds($matchedCatId, $lang, 'mod_news_category');
            if (!empty($descendants)) {
                $targetCategoryIds = array_merge($targetCategoryIds, $descendants);
            }
        }
        $targetCategoryIds = array_values(array_unique($targetCategoryIds));
        $targetCategoryIds = array_intersect($targetCategoryIds, $allowedCatIds);
        if (empty($targetCategoryIds)) {
            return [
                'items' => [],
                'category_name' => $matchedCategoryName,
                'category_seo' => $matchedCategorySeo,
                'matched_categories' => $matchedCategories,
            ];
        }

        $targetCatList = implode(',', array_map('intval', $targetCategoryIds));
        $aclCondition = $this->getAllowedAclCondition('');
        $publishCondition = $this->getPublishedDateCondition('');

        $m = Load::model('mod_news.newses');
        $m->flush();
        $m->select($select)->where_lang_and_status($lang, 'y');
        if ($excludeSlider) {
            $m->addWhere("slider='n'");
        }
        $m->addWhere("show_on_homepage='y'");
        $cleanExcludeIds = array_values(array_unique(array_filter(array_map('intval', $excludeIds), function ($id) {
            return $id > 0;
        })));
        if (!empty($cleanExcludeIds)) {
            $m->addWhere("id NOT IN (" . implode(',', $cleanExcludeIds) . ")");
        }
        $m->addWhere($aclCondition);
        $m->addWhere($publishCondition);
        $m->addWhere("category_id IN ({$targetCatList})");
        $m->limit($limit)->order('created_at', 'DESC');
        $m->gR();

        $items = [];
        if ($m->getSize() !== 0) {
            $rawRows = $this->_cleanNewsRows($m->toArray());
            foreach ($rawRows as $row) {
                $summaryPlain = trim(strip_tags((string) ($row['summary'] ?? '')));
                $row['summary_short'] = $summaryPlain;
                $items[] = $row;
            }
        }

        if (count($items) < $limit && $excludeSlider) {
            $fetchedIds = array_map(function ($it) {
                return (int) ($it['id'] ?? 0);
            }, $items);
            $allExcludes = array_values(array_unique(array_filter(array_merge($cleanExcludeIds, $fetchedIds), function ($id) {
                return $id > 0;
            })));

            $remainingLimit = $limit - count($items);
            $m->flush();
            $m->select($select)->where_lang_and_status($lang, 'y');
            if (!empty($allExcludes)) {
                $m->addWhere("id NOT IN (" . implode(',', $allExcludes) . ")");
            }
            $m->addWhere($aclCondition);
            $m->addWhere($publishCondition);
            $m->addWhere("category_id IN ({$targetCatList})");
            $m->limit($remainingLimit)->order('created_at', 'DESC');
            $m->gR();

            if ($m->getSize() !== 0) {
                $rawRows = $this->_cleanNewsRows($m->toArray());
                foreach ($rawRows as $row) {
                    $summaryPlain = trim(strip_tags((string) ($row['summary'] ?? '')));
                    $row['summary_short'] = $summaryPlain;
                    $items[] = $row;
                }
            }
        }

        return [
            'items' => $items,
            'category_name' => $matchedCategoryName,
            'category_seo' => $matchedCategorySeo,
            'matched_categories' => $matchedCategories,
        ];
    }

    public function _homepage_categories($limit = 4, $select = 'image,name,seo,summary,created_at,deck')
    {
        if (!$this->isFeatureReady('news.homepage_categories')) {
            return [];
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        if (empty($allowedCatIds)) {
            return [];
        }

        $allowedCatList = implode(',', array_map('intval', $allowedCatIds));
        $aclCondition = $this->getAllowedAclCondition('');
        $publishCondition = $this->getPublishedDateCondition('');

        $cat_model = Load::model('mod_news_category');
        $cat_model->flush();
        $cat_model->where_lang_and_show_on_homepage_and_root($lang, 'y', 'n');
        $cat_model->addWhere("id IN ({$allowedCatList})");
        $cat_model->order('order', 'ASC')->gR();

        if ($cat_model->getSize() === 0) {
            return [];
        }

        $categories_raw = $cat_model->toArray();
        $news_model = Load::model('mod_news.newses');

        $categories = [];
        $seen_ids = [];

        foreach ($categories_raw as $categoryItem) {
            if (in_array($categoryItem['id'], $seen_ids)) {
                continue;
            }
            if (empty($categoryItem['seo'])) {
                continue;
            }
            $seen_ids[] = $categoryItem['id'];

            $news_model->flush();
            $news_model->where_category_seo_and_status_and_lang($categoryItem['seo'], 'y', $lang);
            $news_model->addWhere("slider='n'");
            $news_model->addWhere("show_on_homepage='y'");
            $news_model->addWhere($aclCondition);
            $news_model->addWhere($publishCondition);
            $news_model->select($select)->order('created_at', 'DESC')->limit($limit);
            $news_model->gR();

            $items = $this->_cleanNewsRows($news_model->toArray());
            $categoryItem['name'] = $this->_cleanText($categoryItem['name'] ?? '');
            $categoryItem['news'] = $items;
            $categories[] = $categoryItem;
        }

        return $categories;
    }

    public function archive()
    {
        if (!$this->guardFeatureOr404('news.archive')) {
            return;
        }

        $lang = (string) $this->site['lang'];
        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('');

        $news = Load::model('mod_news');
        $news->flush();
        $publishCondition = $this->getPublishedDateCondition('');
        $sql = "SELECT MAX(created_at), MIN(created_at) FROM `mod_news` WHERE {$publishCondition}";
        $news->fetch_array($sql);
        $row = $news->getResultOne();
        $row_data = is_object($row) ? $row->toArray() : (array) $row;
        $values = array_values($row_data);
        $max_date = $values[0] ?? null;
        $min_date = $values[1] ?? null;
        $min_year = $min_date ? (int) date('Y', strtotime($min_date)) : 2020;
        $max_year = $max_date ? (int) date('Y', strtotime($max_date)) : (int) date('Y');
        $archiveYears = [
            'min_year' => $min_year,
            'max_year' => $max_year
        ];
        $this->theme->ctx_set('archive_years', $archiveYears);

        $category = Load::model('mod_news_category');
        $category->flush();
        $category->where_lang_and_root($lang, 'n');
        $category->addWhere("id IN ({$allowedCatList})");
        $category->order('parent_id, order', 'DESC');
        $category->gR();
        if ($category->getSize() !== 0) {
            $archiveCategories = $category->toArray();
            if (is_array($archiveCategories)) {
                foreach ($archiveCategories as &$catRow) {
                    $catRow['name'] = $this->_cleanText($catRow['name'] ?? '');
                }
                unset($catRow);
            }
            $this->theme->ctx_set('archive_categories', $archiveCategories);
        }

        $archiveTitle = $this->lang['index']['mod_news']['archive_title'];
        $d['name'] = $archiveTitle;
        $d['title'] = $archiveTitle;
        $d['keywords'] = $archiveTitle;
        $d['description'] = $archiveTitle;
        $this->theme->ctx_set('d', $d);
        $this->theme->tpl('index/mod_news/archive');

        if (Req::is_Post('category_id') && Req::is_Post('year') && Req::is_Post('month')) {
            $rawYear = Req::Post_Safe('year');
            $rawMonth = Req::Post_Safe('month');
            $rawCategoryId = Req::Post_Safe('category_id');

            $year = ($rawYear === '*' || $rawYear === '') ? null : intval($rawYear);
            $month = ($rawMonth === '*' || $rawMonth === '') ? null : intval($rawMonth);
            $categoryId = ($rawCategoryId === '*' || $rawCategoryId === '') ? '*' : intval($rawCategoryId);

            $safeLang = addslashes($lang);
            $publishCondition = $this->getPublishedDateCondition('');
            if ($categoryId === '*') {
                $categoryWhere = " AND category_id IN ({$allowedCatList})";
            } elseif (in_array((int) $categoryId, $allowedCatIds, true)) {
                $categoryWhere = " AND category_id='" . intval($categoryId) . "'";
            } else {
                $categoryWhere = " AND category_id=0";
            }

            $this->theme->ctx_set('category_id', $categoryId);

            $news->flush();
            $news->select('seo,name,created_at,summary');
            if ($year !== null && $month !== null) {
                $news->where(
                    "YEAR(created_at)='" . $year . "' AND MONTH(created_at)='" . $month . "' AND lang='" . $safeLang . "' AND status = 'y' AND " . $aclCondition . " AND " . $publishCondition . $categoryWhere
                );
            } elseif ($year !== null) {
                $news->where(
                    "YEAR(created_at)='" . $year . "' AND lang='" . $safeLang . "' AND status = 'y' AND " . $aclCondition . " AND " . $publishCondition . $categoryWhere
                );
            } elseif ($month !== null) {
                $news->where(
                    "MONTH(created_at)='" . $month . "' AND lang='" . $safeLang . "' AND status = 'y' AND " . $aclCondition . " AND " . $publishCondition . $categoryWhere
                );
            } else {
                $news->where("status = 'y' AND lang='" . $safeLang . "' AND " . $aclCondition . " AND " . $publishCondition . $categoryWhere);
            }

            $news->gR();
            if ($news->getSize() !== 0) {
                $this->theme->ctx_set('newses', $this->_cleanNewsRows($news->toArray()));
            } else {
                $this->theme->ctx_set('not_found', true);
            }

            if ($year !== null) {
                $this->theme->ctx_set('year', $year);
            }
            if ($month !== null) {
                $this->theme->ctx_set('month', $month);
            }
        }
        $this->theme->tpl('index/mod_news/archive');
        $this->_index();
    }

    public function bio($lang = null)
    {
        if (!$this->guardFeatureOr404('news.front')) {
            return;
        }

        $activeLang = !empty($lang) ? (string) $lang : (string) ($this->site['lang'] ?? 'tr');
        $safeLang = addslashes($activeLang);

        $allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $activeLang);
        $allowedCatList = !empty($allowedCatIds) ? implode(',', array_map('intval', $allowedCatIds)) : '0';
        $aclCondition = $this->getAllowedAclCondition('n');
        $publishCondition = $this->getPublishedDateCondition('n');

        $db = new Database();
        $sql = "SELECT DISTINCT n.id, n.name, n.seo, n.image, n.summary, n.created_at, n.lang, 
                       c.name as category_name, c.seo as category_seo,
                       (SELECT MAX(j.published_at) FROM mod_news_social_jobs j WHERE j.news_id = n.id AND j.platform = 'instagram_story' AND j.status = 'published') as story_published_at
                FROM mod_news n
                LEFT JOIN mod_news_category c ON n.category_id = c.id
                WHERE n.status = 'y' 
                  AND n.lang = '{$safeLang}' 
                  AND n.category_id IN ({$allowedCatList})
                  AND {$aclCondition}
                  AND {$publishCondition}
                ORDER BY (story_published_at IS NOT NULL) DESC, story_published_at DESC, n.created_at DESC
                LIMIT 24";

        $db->fetch_array($sql);
        $items = ($db->getSize() > 0) ? $this->_cleanNewsRows($db->toArray()) : [];

        $d = [
            'title' => ($this->lang['index']['mod_news']['bio_title'] ?? 'Instagram Bio Link') . ' | ' . ($this->site['title'] ?? 'Amed Bülten'),
            'description' => ($this->lang['index']['mod_news']['bio_description'] ?? 'Read latest featured news via our Instagram bio link.'),
            'link' => rtrim($this->site['saddress'] ?? $this->site['address'], '/') . '/bio',
            'keywords' => $this->lang['index']['mod_news']['keywords'] ?? 'news, bio, amed bulten',
        ];

        $this->theme->ctx_set('d', $d);
        $this->theme->ctx_set('bio_items', $items);
        $this->theme->ctx_set('active_lang', $activeLang);
        $this->theme->tpl('index/mod_news/bio');
        $this->_index();
    }
}
