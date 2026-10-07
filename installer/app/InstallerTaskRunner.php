<?php

class InstallerTaskRunner
{
    public function run(installer $installer, array $selectedModules, InstallerModuleCatalog $catalog, $sampleContent, array $previousResult = array())
    {
        $resumeModules = $this->extractResumeModules($previousResult, $selectedModules);
        $result = array(
            'success' => true,
            'installed_at' => NOW,
            'lifecycle_state' => 'completed',
            'sample_content' => $sampleContent === 'yes' ? 'requested' : 'skipped',
            'sample_content_note' => '',
            'sample_content_results' => array(),
            'failed_module' => null,
            'warnings' => array(),
            'logs' => array(),
            'modules' => array(),
            'completed_modules' => $resumeModules,
            'skipped_modules' => array(),
            'resumed_modules' => $resumeModules,
        );

        $selectedMap = array_fill_keys($selectedModules, true);
        $resumeMap = array_fill_keys($resumeModules, true);
        $modUserInstance = null;

        foreach ($catalog->getInstallOrder() as $module) {
            if (!isset($selectedMap[$module])) {
                $result['modules'][$module] = $this->buildModuleState($module, 'skipped', 'module_not_selected');
                $result['skipped_modules'][] = $module;
                continue;
            }

            if (isset($resumeMap[$module])) {
                $result['modules'][$module] = $this->buildModuleState($module, 'completed', 'module_resumed_completed');
                continue;
            }

            if ($module === 'mod_user') {
                $moduleResult = $this->runModuleInstall($installer, $module);
                $modUserInstance = isset($moduleResult['instance']) ? $moduleResult['instance'] : null;
                unset($moduleResult['instance']);
                $result['modules'][$module] = $moduleResult;
                $result['logs'] = array_merge($result['logs'], $moduleResult['logs']);

                if ($moduleResult['success'] !== true) {
                    return $this->failResult($result, $module);
                }

                $result['completed_modules'][] = $module;
                continue;
            }

            $moduleResult = $this->runModuleInstall($installer, $module);
            unset($moduleResult['instance']);
            $result['modules'][$module] = $moduleResult;
            $result['logs'] = array_merge($result['logs'], $moduleResult['logs']);

            if ($moduleResult['success'] !== true) {
                return $this->failResult($result, $module);
            }

            $result['completed_modules'][] = $module;

            if ($module === 'mod_user_group' && $modUserInstance !== null) {
                $finishResult = $this->runUserFinish($installer, $modUserInstance);
                $result['modules']['mod_user']['logs'] = array_merge($result['modules']['mod_user']['logs'], $finishResult['logs']);
                $result['logs'] = array_merge($result['logs'], $finishResult['logs']);

                if ($finishResult['success'] !== true) {
                    $result['modules']['mod_user']['state'] = 'failed';
                    $result['modules']['mod_user']['success'] = false;
                    $result['modules']['mod_user']['message'] = $this->buildCopyRef('user_finalize_failed');
                    return $this->failResult($result, 'mod_user');
                }

                $result['modules']['mod_user']['message'] = $this->buildCopyRef('user_group_sync_complete');
            }
        }

        $precomputedSampleResults = $this->enforceProjectSampleExclusions($selectedModules, $sampleContent);
        $precomputedSampleResults = array_merge(
            $precomputedSampleResults,
            $this->enforceExplicitSampleChoice($selectedModules, $sampleContent)
        );
        $sampleSummary = $this->buildSampleContentSummary($selectedModules, $catalog, $sampleContent, $precomputedSampleResults);
        $result['sample_content_note'] = $sampleSummary['note'];
        $result['sample_content_results'] = $sampleSummary['results'];

        if (!empty($sampleSummary['warnings'])) {
            $result['warnings'] = array_merge($result['warnings'], $sampleSummary['warnings']);
            $result['lifecycle_state'] = 'completed_with_warnings';
        }

        if (!empty($resumeModules) && $result['lifecycle_state'] === 'completed') {
            $result['lifecycle_state'] = 'resumed_completed';
        }

        if (!empty($resumeModules) && $result['lifecycle_state'] === 'completed_with_warnings') {
            $result['lifecycle_state'] = 'resumed_completed_with_warnings';
        }

        return $result;
    }

    private function runModuleInstall(installer $installer, $module)
    {
        $logs = array();
        $success = false;
        $message = $this->buildCopyRef('module_installed');
        $instance = null;

        try {
            ob_start();
            $instance = $installer->load_mod($module);
            $success = $instance->install() === true;
            $logs = $this->normalizeOutput(ob_get_clean());
        } catch (Throwable $exception) {
            if (ob_get_level() > 0) {
                $logs = $this->normalizeOutput(ob_get_clean());
            }
            $logs[] = $exception->getMessage();
            $message = $this->buildCopyRef('module_execute_failed');
            $success = false;
        }

        if ($success !== true && empty($logs)) {
            $logs[] = $this->buildCopyRef('module_failure_log', array($module));
            $message = $this->buildCopyRef('module_reported_failure');
        }

        return array(
            'success' => $success === true,
            'state' => $success === true ? 'completed' : 'failed',
            'message' => $message,
            'logs' => $logs,
            'instance' => $instance,
        );
    }

    private function runUserFinish(installer $installer, $modUserInstance)
    {
        $logs = array();
        $success = false;

        try {
            ob_start();
            $success = $modUserInstance->install_finish() === true;
            $logs = $this->normalizeOutput(ob_get_clean());

            if ($success === true) {
                $session = Load::lib('Session');
                foreach (['id', 'username', 'group_id', 'permissions', 'sid', 'session_token', 'okunmamis'] as $authKey) {
                    if ($session->has($authKey)) {
                        $session->remove($authKey);
                    }
                }
            }
        } catch (Throwable $exception) {
            if (ob_get_level() > 0) {
                $logs = $this->normalizeOutput(ob_get_clean());
            }
            $logs[] = $exception->getMessage();
            $success = false;
        }

        if ($success !== true && empty($logs)) {
            $logs[] = $this->buildCopyRef('user_finalize_log_failure');
        }

        return array(
            'success' => $success === true,
            'logs' => $logs,
        );
    }

    private function failResult(array $result, $module)
    {
        $failure = $this->classifyFailure($module);
        $result['success'] = false;
        $result['failed_module'] = $module;
        $result['failure_recoverable'] = $failure['recoverable'];
        $result['failure_reason'] = $failure['reason_key'];
        $result['lifecycle_state'] = $failure['recoverable'] ? 'recoverable_failure' : 'failed';

        return $result;
    }

    private function buildModuleState($module, $state, $messageKey)
    {
        return array(
            'success' => $state === 'completed',
            'state' => $state,
            'message' => $this->buildCopyRef($messageKey),
            'logs' => array(),
        );
    }

    private function normalizeOutput($rawOutput)
    {
        $normalizedOutput = preg_replace('/<br\s*\/?>/i', PHP_EOL, (string) $rawOutput);
        $normalizedOutput = trim(strip_tags($normalizedOutput));

        if ($normalizedOutput === '') {
            return array();
        }

        return preg_split('/\r\n|\r|\n/', $normalizedOutput);
    }

    private function buildSampleContentSummary(array $selectedModules, InstallerModuleCatalog $catalog, $sampleContent, array $precomputedResults = array())
    {
        $results = $precomputedResults;
        $warnings = array();

        foreach ($selectedModules as $module) {
            $meta = $catalog->getModule($module);
            if (!is_array($meta) || empty($meta['supports_sample_content'])) {
                continue;
            }

            if (isset($results[$module])) {
                continue;
            }

            $mode = isset($meta['sample_content_mode']) ? $meta['sample_content_mode'] : 'none';
            $moduleResult = $this->buildSampleModuleResult($module, $mode, $sampleContent);
            $results[$module] = $moduleResult;

            if ($moduleResult['state'] === 'warning') {
                $warnings[] = $moduleResult['message'];
            }
        }

        if (empty($results)) {
            return array(
                'note' => $this->buildCopyRef('sample_note_no_capable_modules'),
                'results' => array(),
                'warnings' => array(),
            );
        }

        if ($sampleContent !== 'yes') {
            return array(
                'note' => $this->buildCopyRef('sample_note_disabled_selected'),
                'results' => $results,
                'warnings' => $warnings,
            );
        }

        return array(
            'note' => $this->buildCopyRef('sample_note_evaluated_selected'),
            'results' => $results,
            'warnings' => $warnings,
        );
    }

    private function buildSampleModuleResult($module, $mode, $sampleContent)
    {
        if ($sampleContent !== 'yes') {
            return array(
                'state' => 'skipped',
                'message' => $this->buildCopyRef('sample_module_disabled'),
            );
        }

        if ($mode === 'legacy_inline') {
            return array(
                'state' => 'warning',
                'message' => $this->buildCopyRef('sample_module_legacy_inline'),
            );
        }

        if ($mode === 'not_available') {
            return array(
                'state' => 'skipped',
                'message' => $this->buildCopyRef('sample_module_not_available'),
            );
        }

        if ($mode === 'module_bootstrap_optional') {
            return array(
                'state' => 'completed',
                'message' => $this->buildCopyRef('sample_module_enabled'),
            );
        }

        if ($mode === 'project_rule_excluded') {
            return array(
                'state' => 'skipped',
                'message' => $this->buildCopyRef('sample_module_project_rule_excluded'),
            );
        }

        return array(
            'state' => 'skipped',
            'message' => $this->buildCopyRef('sample_module_not_exposed'),
        );
    }

    private function extractResumeModules(array $previousResult, array $selectedModules)
    {
        if (empty($previousResult['completed_modules']) || !is_array($previousResult['completed_modules'])) {
            return array();
        }

        $selectedMap = array_fill_keys($selectedModules, true);
        $resumable = array();

        foreach ($previousResult['completed_modules'] as $module) {
            if (!isset($selectedMap[$module])) {
                continue;
            }

            $resumable[] = $module;
        }

        return array_values(array_unique($resumable));
    }

    private function enforceExplicitSampleChoice(array $selectedModules, $sampleContent)
    {
        $selectedMap = array_fill_keys($selectedModules, true);
        $results = array();
        $db = new Database();

        if (!isset($selectedMap['mod_page']) || !$this->tableExists($db, 'mod_page')) {
            return $results;
        }

        if ($sampleContent === 'yes') {
            $results['mod_page'] = array(
                'state' => 'warning',
                'message' => $this->buildCopyRef('page_seed_kept_enabled'),
            );
            return $results;
        }

        $deletedPages = $this->deleteLegacyPageSeeds($db);
        $results['mod_page'] = array(
            'state' => 'skipped',
            'message' => $deletedPages > 0
                ? $this->buildCopyRef('page_seed_removed', array($deletedPages))
                : $this->buildCopyRef('page_seed_not_found'),
        );

        return $results;
    }

    private function enforceProjectSampleExclusions(array $selectedModules, $sampleContent)
    {
        return array();
    }

    private function deleteLegacyPageSeeds(Database $db)
    {
        $queries = array(
            "DELETE FROM mod_page
             WHERE user_id = '1'
               AND (
                    (root = 'y' AND seo IN ('English_Pages', 'Rûpelên_Kurdî', 'لاپەڕەکانی_کوردی', 'الصفحات_العربية', 'Deutsche_Seiten', 'Pages_Françaises', 'Svenska_Sidor', 'Türkçe_Sayfalar', 'Pelê_Zazakî'))
                 OR (root = 'n' AND seo IN ('about', 'contact', 'Contact', 'Têkilî', 'پەیوەندی', 'اتصل_بنا', 'Kontakt', 'İletişim', 'Îrtibat', 'About_Us', 'Derbarê_me', 'دەربarê_me', 'من_nuxn', 'Über_uns', 'À_propos', 'Om_oss', 'Hakkımızda', 'Ma_Heqa'))
               )",
        );

        return $this->runDeleteQueries($db, $queries);
    }

    private function runDeleteQueries(Database $db, array $queries)
    {
        $deleted = 0;

        foreach ($queries as $query) {
            if ($db->query($query) === false) {
                continue;
            }

            if (method_exists($db, 'affected_rows')) {
                $deleted += (int) $db->affected_rows();
            }
        }

        return $deleted;
    }

    private function tableExists(Database $db, $table)
    {
        $adapter = (string) $db->getAdapterType();

        if ($adapter !== '' && strpos($adapter, 'PDO_SQLITE') !== false) {
            $db->fetch_array("SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name='" . addslashes($table) . "'");
        } elseif ($adapter !== '' && strpos($adapter, 'POSTGRESQL') !== false) {
            $db->fetch_array("SELECT table_name FROM information_schema.tables WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . addslashes($table) . "'");
        } else {
            $db->fetch_array("SHOW TABLES LIKE '" . addslashes($table) . "'");
        }

        return $db->getSize() !== 0;
    }

    private function classifyFailure($module)
    {
        $nonRecoverableModules = array(
            'lang',
            'mods',
            'mod_settings',
            'mod_user',
            'mod_user_group',
        );

        if (in_array($module, $nonRecoverableModules, true)) {
            return array(
                'recoverable' => false,
                'reason_key' => 'reason_bootstrap_blocked',
            );
        }

        return array(
            'recoverable' => true,
            'reason_key' => 'reason_resume_available',
        );
    }

    private function buildCopyRef($key, array $params = array())
    {
        return array(
            'key' => $key,
            'params' => $params,
        );
    }
}
