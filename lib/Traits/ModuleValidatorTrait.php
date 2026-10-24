<?php

/**
 * ModuleValidatorTrait - Shared validator helper trait
 * 
 * Enforces:
 * - Rule 10 SEF slug normalization via Helper::legacy_seo and uniqueness per language
 * - Relational checks on category_id (must exist, must match item language, cannot be a language root)
 * - Active language verification against `lang` table (status = 'y')
 * - Pure localized error messages without inline/hardcoded strings
 */
trait ModuleValidatorTrait
{
    /**
     * Normalize SEO slug preserving uppercase and Unicode characters (Rule 10).
     *
     * @param string|null $seo
     * @return string
     */
    public function normalizeSeo(?string $seo): string
    {
        $raw = html_entity_decode(trim((string) $seo), ENT_QUOTES, 'UTF-8');
        return Helper::legacy_seo($raw);
    }

    /**
     * Validate that the requested language code is active in the system.
     *
     * @param string $lang
     * @param string|null $moduleName
     * @return bool True if valid, false if invalid (error recorded)
     */
    public function validateLanguage(string $lang, ?string $moduleName = null): bool
    {
        $mod = $moduleName ?? ($this->module_name ?? null);
        if (empty($mod)) {
            throw new \InvalidArgumentException('Module name must be specified for validation.');
        }
        $langModel = Load::model('lang');
        $langModel->where_seo_and_status($lang, 'y')->limit(1)->gRo();

        if ($langModel->getSize() === 0) {
            $msg = $this->lang[$mod]['validator']['invalid_lang'] 
                ?? ($this->lang['validator']['invalid_lang'] ?? ($this->lang['management']['error'] ?? ''));
            $this->appendError($msg);
            return false;
        }

        return true;
    }

    /**
     * Validate category relation: category exists, is not a root, and matches record language.
     *
     * @param int $categoryId
     * @param string $requestedLang
     * @param string $categoryModelName e.g. 'mod_news_category'
     * @param string|null $moduleName e.g. 'mod_news'
     * @return bool True if valid, false otherwise (error recorded)
     */
    public function validateCategoryRelation(int $categoryId, string $requestedLang, string $categoryModelName, ?string $moduleName = null): bool
    {
        $mod = $moduleName ?? ($this->module_name ?? null);
        if (empty($mod)) {
            throw new \InvalidArgumentException('Module name must be specified for validation.');
        }
        $catModel = Load::model($categoryModelName);
        $catModel->where_id($categoryId)->limit(1)->gRo();

        if ($catModel->getSize() === 0) {
            $msg = $this->lang[$mod]['validator']['category_not_found'] 
                ?? ($this->lang['validator']['category_not_found'] ?? ($this->lang['management']['error'] ?? ''));
            $this->appendError($msg);
            return false;
        }

        $catModel->sync();

        if (isset($catModel->root) && $catModel->root === 'y') {
            $msg = $this->lang[$mod]['validator']['category_root_forbidden'] 
                ?? ($this->lang['validator']['category_root_forbidden'] ?? ($this->lang['management']['error'] ?? ''));
            $this->appendError($msg);
            return false;
        }

        if (isset($catModel->lang) && $catModel->lang !== $requestedLang) {
            $msg = $this->lang[$mod]['validator']['category_lang_mismatch'] 
                ?? ($this->lang['validator']['category_lang_mismatch'] ?? ($this->lang['management']['error'] ?? ''));
            $this->appendError($msg);
            return false;
        }

        return true;
    }

    /**
     * Validate SEO uniqueness for a model within a language.
     *
     * @param string $modelName Model name to check against
     * @param string $seo Raw or normalized SEO string
     * @param string $lang Language code
     * @param int|null $excludeId Optional ID to exclude (for edits)
     * @param string|null $moduleName
     * @param string $fieldColumn
     * @return bool True if unique, false otherwise (error recorded)
     */
    public function validateSeoUniqueness(string $modelName, string $seo, string $lang, ?int $excludeId = null, ?string $moduleName = null, string $fieldColumn = 'seo'): bool
    {
        $mod = $moduleName ?? ($this->module_name ?? null);
        if (empty($mod)) {
            throw new \InvalidArgumentException('Module name must be specified for validation.');
        }
        $normalizedSeo = $this->normalizeSeo($seo);
        $model = Load::model($modelName);

        if ($excludeId !== null && $excludeId > 0) {
            $model->where_seo_and_lang_and_not_id($normalizedSeo, $lang, $excludeId);
        } else {
            $model->where_seo_and_lang($normalizedSeo, $lang);
        }

        if ($model->limit(1)->gRo()->getSize() > 0) {
            $columnLabel = $this->lang[$mod]['admin']['columns'][$fieldColumn] ?? ($this->lang['management'][$fieldColumn] ?? $fieldColumn);
            $template = $this->lang[$mod]['validator']['unique'] 
                ?? ($this->lang['validator']['unique'] ?? ($this->lang['management']['error'] ?? ''));
            $msg = sprintf($template, $columnLabel, $columnLabel);
            $this->appendError($msg);
            return false;
        }

        return true;
    }

    /**
     * Append an error message to the validator's error list.
     *
     * @param string $message
     */
    protected function appendError(string $message): void
    {
        if (!isset($this->errors) || !is_array($this->errors)) {
            $this->errors = [];
        }
        if (!in_array($message, $this->errors, true)) {
            $this->errors[] = $message;
        }
    }
}
