<?php
header('Content-Type: text/html; charset=utf-8');

$bootstrapDbConfig = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.config.php';
if (!file_exists($bootstrapDbConfig)) {
    $stubConfig = "<?php\n\n";
    $stubConfig .= "if (defined('root_dir') === False) exit(':)');\n\n";
    $stubConfig .= "\$localConfig = __DIR__ . DIRECTORY_SEPARATOR . 'db.config.local.php';\n";
    $stubConfig .= "if (file_exists(\$localConfig)) {\n";
    $stubConfig .= "    require_once \$localConfig;\n";
    $stubConfig .= "    return;\n";
    $stubConfig .= "}\n\n";
    $stubConfig .= "\$db = array();\n";
    $stubConfig .= "Kayit::ekle('database', 'installed');\n";
    $stubConfig .= "Kayit::ekle('db_config', \$db);\n";
    $stubConfig .= "unset(\$db);\n";
    file_put_contents($bootstrapDbConfig, $stubConfig);
}

require_once('../koberz.init.php');
define('installer_dir', root_dir . 'installer' . DS);
require_once(lib_dir . 'InstallationState.php');

require_once(installer_dir . 'installerbase.php');
require_once(installer_dir . 'app' . DS . 'InstallerStateStore.php');
require_once(installer_dir . 'app' . DS . 'InstallerModuleCatalog.php');
require_once(installer_dir . 'app' . DS . 'InstallerDependencyResolver.php');
require_once(installer_dir . 'app' . DS . 'InstallerEnvironmentCheck.php');
require_once(installer_dir . 'app' . DS . 'InstallerTaskRunner.php');
require_once(installer_dir . 'app' . DS . 'InstallerConfigWriter.php');
require_once(installer_dir . 'app' . DS . 'SqliteSqlTransformer.php');

$installationState = new InstallationState();
$installCheck = $installationState->inspect();

if (!empty($installCheck['is_installed'])) {
    header('Location: ../', true, 302);
    exit;
}

class installer extends installerbase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function install($mods)
    {
        foreach ($mods as $mod) {
            try {
                $m = $this->load_mod($mod);
            } catch (Exception $e) {
                echo $e->getMessage();
                exit();
            }

            if ($m->install() !== true) {
                $this->error("Error occurred while installing '" . $mod . "'");
                exit();
            }
        }
    }

    public function load_mod($modul)
    {
        if (file_exists(installer_dir . $modul . DS . 'index.php') === false) {
            throw new Exception("Module '" . $modul . "' was not found");
        }

        require_once(installer_dir . $modul . DS . 'index.php');
        return new $modul();
    }

    public function uninstall($mods)
    {
        foreach ($mods as $mod) {
            try {
                $m = $this->load_mod($mod);
            } catch (Exception $e) {
                echo $e->getMessage();
                exit();
            }

            if ($m->uninstall() !== true || $m->uninstall_blocked === true) {
                $this->error("Error occurred while uninstalling '" . $mod . "'");
                exit();
            }
        }
    }
}

function installer_available_languages()
{
    return InstallerLang::availableLanguages();
}

function installer_language_option($languageCode, array $ui)
{
    $availableLanguages = InstallerLang::availableLanguages();
    if (!empty($availableLanguages[$languageCode])) {
        return $availableLanguages[$languageCode];
    }

    if (!empty($ui['language_options'][$languageCode])) {
        return $ui['language_options'][$languageCode];
    }

    return array('label' => strtoupper($languageCode), 'code' => strtoupper($languageCode));
}

function installer_project_languages()
{
    $projectLanguages = array();

    foreach (InstallerLang::availableLanguages() as $code => $language) {
        $projectLanguages[$code] = $language['label'];
    }

    return $projectLanguages;
}

function installer_normalize_database_params(array $input, InstallerConfigWriter $writer)
{
    return $writer->normalizeParams(array(
        'adapter' => isset($input['database_adapter']) ? $input['database_adapter'] : 'PDO_MYSQL',
        'host' => isset($input['database_host']) ? $input['database_host'] : '',
        'user' => isset($input['database_user']) ? $input['database_user'] : '',
        'pass' => isset($input['database_pass']) ? $input['database_pass'] : '',
        'database' => isset($input['database_name']) ? $input['database_name'] : '',
        'database_path' => isset($input['database_path']) ? $input['database_path'] : installer_default_sqlite_path(),
    ));
}

function installer_module_label($module, array $ui)
{
    if (!empty($ui['module_labels'][$module])) {
        return $ui['module_labels'][$module];
    }

    return ucwords(str_replace('_', ' ', preg_replace('/^mod_/', '', $module)));
}

function installer_step_class($stateStep, $currentStep)
{
    if ($stateStep === 'progress' && $currentStep === 'finish') {
        return 'step-pill is-active';
    }

    return $stateStep === $currentStep ? 'step-pill is-active' : 'step-pill';
}

function installer_check_copy(array $check, array $ui)
{
    $key = $check['key'];
    $meta = isset($check['meta']) ? $check['meta'] : array();

    if ($key === 'php_version') {
        return array(
            'label' => $ui['checks']['php_version']['label'],
            'detail' => sprintf($ui['checks']['php_version']['detail'], isset($meta['version']) ? $meta['version'] : ''),
        );
    }

    if ($key === 'extensions') {
        $detail = empty($meta['missing'])
            ? $ui['checks']['extensions']['ok']
            : sprintf($ui['checks']['extensions']['missing'], installer_check_item_list(isset($meta['missing']) ? $meta['missing'] : array(), isset($ui['checks']['extensions']['items']) ? $ui['checks']['extensions']['items'] : array()));

        return array(
            'label' => $ui['checks']['extensions']['label'],
            'detail' => $detail,
        );
    }

    if ($key === 'directories') {
        $detail = empty($meta['blocked'])
            ? $ui['checks']['directories']['ok']
            : sprintf($ui['checks']['directories']['blocked'], installer_check_item_list(isset($meta['blocked']) ? $meta['blocked'] : array(), isset($ui['checks']['directories']['items']) ? $ui['checks']['directories']['items'] : array()));

        return array(
            'label' => $ui['checks']['directories']['label'],
            'detail' => $detail,
        );
    }

    return array('label' => '', 'detail' => '');
}

function installer_check_item_list(array $items, array $labels)
{
    $resolved = array();

    foreach ($items as $item) {
        $resolved[] = isset($labels[$item]) ? $labels[$item] : $item;
    }

    return implode(', ', $resolved);
}

function installer_module_list(array $modules, array $ui)
{
    $labels = array();

    foreach ($modules as $module) {
        $labels[] = installer_module_label($module, $ui);
    }

    return implode(', ', $labels);
}

function installer_runtime_state_label($state, array $ui)
{
    $key = 'state_' . $state;
    return isset($ui['progress'][$key]) ? $ui['progress'][$key] : strtoupper((string) $state);
}

function installer_failure_reason_label(array $result, array $ui)
{
    if (empty($result['failure_reason'])) {
        return '';
    }

    $key = $result['failure_reason'];
    return isset($ui['progress'][$key]) ? $ui['progress'][$key] : '';
}

function installer_result_text($entry, array $ui)
{
    if (!is_array($entry) || empty($entry['key'])) {
        return is_string($entry) ? $entry : '';
    }

    if (empty($ui['result_messages'][$entry['key']])) {
        return '';
    }

    $text = $ui['result_messages'][$entry['key']];
    $params = isset($entry['params']) && is_array($entry['params']) ? $entry['params'] : array();

    if (empty($params)) {
        return $text;
    }

    return vsprintf($text, $params);
}

function installer_result_list_text(array $entries, array $ui)
{
    $parts = array();

    foreach ($entries as $entry) {
        $text = installer_result_text($entry, $ui);
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    return implode(' ', $parts);
}

function installer_normalize_contact_number($number, $countryCode = '')
{
    $number = trim((string) $number);
    $countryCode = trim((string) $countryCode);

    if ($number === '') {
        return '';
    }

    if (strpos($number, '+') === 0) {
        $digits = preg_replace('/\D+/', '', $number);
        return $digits === '' ? '' : '+' . $digits;
    }

    $digits = preg_replace('/\D+/', '', $number);
    if ($digits === '') {
        return '';
    }

    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
        return $digits === '' ? '' : '+' . $digits;
    }

    $countryDigits = preg_replace('/\D+/', '', $countryCode);
    if ($countryDigits === '') {
        return $digits;
    }

    if (strpos($digits, '0') === 0) {
        $digits = ltrim($digits, '0');
    }

    return '+' . $countryDigits . $digits;
}

Load::lib('Session');
$stateStore = new InstallerStateStore();
$catalog = new InstallerModuleCatalog();
$resolver = new InstallerDependencyResolver();
$environment = new InstallerEnvironmentCheck();
$taskRunner = new InstallerTaskRunner();
$availableInstallerLanguages = installer_available_languages();
$projectLanguages = installer_project_languages();
$state = $stateStore->getState();
installer_capture_project_setup_state($state);

if ($state['step'] === 'finish') {
    // Heke pergal ne sazkirî be lê di sessionê de rewş wekî 'finish' xuya bike,
    // em rewşê paqij dikin da ku Sazker ji nû ve dest pê bike.
    $state = $stateStore->reset();
}

if ($state['step'] === 'modules') {
    $state = $stateStore->merge(array('step' => 'sample'));
}

if (empty($state['requested_modules'])) {
    $selection = $resolver->buildSelection($catalog->getDefaultRequestedModules(), $catalog);
    $state = $stateStore->merge(array(
        'requested_modules' => $selection['requested'],
        'resolved_modules' => $selection['resolved'],
    ));
}

if (isset($availableInstallerLanguages[$state['installer_language']])) {
    Kayit::yenile('lang', $state['installer_language']);
}

$lang = InstallerLang::load();
$ui = $lang['installer_mobile'];
$checks = $environment->run();
$isBlocked = $environment->hasBlockingFailures($checks);
$errorMessage = '';
$buildConfigMissing = installer_site_build_name() === '';

if ($buildConfigMissing) {
    $errorMessage = $ui['errors']['missing_build_config'];
    $state = $stateStore->merge(array('step' => 'language'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($buildConfigMissing && $action !== 'preview_language' && $action !== 'save_language' && $action !== 'go_language') {
        $errorMessage = $ui['errors']['missing_build_config'];
        $action = '';
    }

    if ($action === 'go_language') {
        $state = $stateStore->merge(array('step' => 'language'));
    }

    if ($action === 'save_language') {
        $installerLanguage = isset($_POST['installer_language']) ? $_POST['installer_language'] : 'en';
        if (!isset($availableInstallerLanguages[$installerLanguage])) {
            $installerLanguage = 'en';
        }

        $state = $stateStore->merge(array(
            'installer_language' => $installerLanguage,
            'installer_language_confirmed' => true,
            'step' => 'environment',
        ));
        installer_capture_project_setup_state($state);
        Kayit::yenile('lang', $installerLanguage);
        InstallerLang::reset($installerLanguage);
        $lang = InstallerLang::load($installerLanguage);
        $ui = $lang['installer_mobile'];
        $checks = $environment->run();
        $isBlocked = $environment->hasBlockingFailures($checks);
    }

    if ($action === 'preview_language') {
        $installerLanguage = isset($_POST['installer_language']) ? $_POST['installer_language'] : 'en';
        if (!isset($availableInstallerLanguages[$installerLanguage])) {
            $installerLanguage = 'en';
        }

        $state = $stateStore->merge(array(
            'installer_language' => $installerLanguage,
            'installer_language_confirmed' => false,
            'step' => 'language',
        ));
        installer_capture_project_setup_state($state);
        Kayit::yenile('lang', $installerLanguage);
        InstallerLang::reset($installerLanguage);
        $lang = InstallerLang::load($installerLanguage);
        $ui = $lang['installer_mobile'];
        $checks = $environment->run();
        $isBlocked = $environment->hasBlockingFailures($checks);
    }

    if ($action === 'go_back_to_language') {
        $state = $stateStore->merge(array('step' => 'language'));
    }

    if ($action === 'continue_to_setup') {
        if ($isBlocked) {
            $errorMessage = $ui['errors']['resolve_environment'];
            $state = $stateStore->merge(array('step' => 'environment'));
        } else {
            $state = $stateStore->merge(array('step' => 'database'));
        }
    }

    if ($action === 'save_database') {
        try {
            $writer = new InstallerConfigWriter();
            $dbParams = installer_normalize_database_params($_POST, $writer);

            $state = $stateStore->merge(array(
                'database_adapter' => $dbParams['adapter'],
                'database_host' => $dbParams['host'],
                'database_user' => $dbParams['user'],
                'database_pass' => $dbParams['pass'],
                'database_name' => $dbParams['database'],
                'database_path' => $dbParams['database_path'],
            ));

            if ($dbParams['adapter'] === 'PDO_SQLITE') {
                $sqliteTarget = installer_prepare_sqlite_directory($dbParams['database_path']);
                $fullPath = $sqliteTarget['path'];
                $dir = $sqliteTarget['directory'];
                new PDO("sqlite:" . $fullPath);
                @chmod($dir, $sqliteTarget['directory_mode']);
                if (file_exists($fullPath)) {
                    @chmod($fullPath, $sqliteTarget['file_mode']);
                }
            } elseif ($dbParams['adapter'] === 'PDO_MYSQL') {
                $dsn = "mysql:host=" . $dbParams['host'] . ";dbname=" . $dbParams['database'];
                $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5);
                new PDO($dsn, $dbParams['user'], $dbParams['pass'], $options);
            } else {
                throw new InvalidArgumentException($ui['errors']['database_invalid_adapter']);
            }

            if ($writer->write($dbParams)) {
                $state = $stateStore->merge(array('database_configured' => true, 'step' => 'setup'));
                $checks = $environment->run();
                $isBlocked = $environment->hasBlockingFailures($checks);
            } else {
                $errorMessage = $ui['errors']['database_write_failed'];
            }
        } catch (Exception $e) {
            $errorMessage = sprintf($ui['errors']['database_connect_failed'], $e->getMessage());
        }
    }

    if ($action === 'save_setup') {
        $projectName = isset($_POST['project_name']) ? trim($_POST['project_name']) : installer_site_build_name();
        $projectLanguage = isset($_POST['project_default_language']) ? $_POST['project_default_language'] : 'ku';
        $projectDescription = isset($_POST['project_description']) ? trim($_POST['project_description']) : '';
        $projectOwnerName = isset($_POST['project_owner_name']) ? trim($_POST['project_owner_name']) : '';
        $projectEstablishedYear = isset($_POST['project_established_year']) ? trim($_POST['project_established_year']) : '';
        $projectPhoneCountryCode = isset($_POST['project_phone_country_code']) ? trim($_POST['project_phone_country_code']) : '+90';
        $projectWhatsappCountryCode = isset($_POST['project_whatsapp_country_code']) ? trim($_POST['project_whatsapp_country_code']) : '+90';
        $projectPhone = installer_normalize_contact_number(isset($_POST['project_phone']) ? $_POST['project_phone'] : '', $projectPhoneCountryCode);
        $projectWhatsapp = installer_normalize_contact_number(isset($_POST['project_whatsapp']) ? $_POST['project_whatsapp'] : '', $projectWhatsappCountryCode);
        $projectEmail = isset($_POST['project_email']) ? trim($_POST['project_email']) : '';
        $projectOfficeAddress = isset($_POST['project_office_address']) ? trim($_POST['project_office_address']) : '';
        $projectWorkAddress = isset($_POST['project_work_address']) ? trim($_POST['project_work_address']) : '';
        $projectAddress = isset($_POST['project_address']) ? trim($_POST['project_address']) : '';
        if ($projectAddress !== '') {
            $projectAddress = rtrim($projectAddress, '/') . '/';
        }

        if (!isset($projectLanguages[$projectLanguage])) {
            $projectLanguage = 'ku';
        }

        if ($projectName === '') {
            $projectName = installer_site_build_name();
        }

        $selection = $resolver->buildSelection($catalog->getDefaultRequestedModules(), $catalog);
        $state = $stateStore->merge(array(
            'project_name' => $projectName,
            'project_default_language' => $projectLanguage,
            'project_description' => $projectDescription,
            'project_owner_name' => $projectOwnerName,
            'project_established_year' => $projectEstablishedYear,
            'project_phone' => $projectPhone,
            'project_phone_country_code' => $projectPhoneCountryCode,
            'project_whatsapp' => $projectWhatsapp,
            'project_whatsapp_country_code' => $projectWhatsappCountryCode,
            'project_email' => $projectEmail,
            'project_office_address' => $projectOfficeAddress,
            'project_work_address' => $projectWorkAddress,
            'project_address' => $projectAddress,
            'requested_modules' => $selection['requested'],
            'resolved_modules' => $selection['resolved'],
            'install_result' => null,
            'step' => 'sample',
        ));
        installer_capture_project_setup_state($state);
    }

    if ($action === 'go_back_to_environment') {
        $state = $stateStore->merge(array('step' => 'environment'));
    }

    if ($action === 'go_back_to_setup') {
        $state = $stateStore->merge(array('step' => 'setup'));
    }

    if ($action === 'run_install' || $action === 'retry_install') {
        $checks = $environment->run();
        $isBlocked = $environment->hasBlockingFailures($checks);
        $sampleContent = isset($_POST['sample_content']) && $_POST['sample_content'] === 'yes' ? 'yes' : 'no';

        if ($isBlocked) {
            $errorMessage = $ui['errors']['blocked_install'];
            $state = $stateStore->merge(array(
                'sample_content' => $sampleContent,
                'step' => 'environment',
            ));
        } else {
            $selection = $resolver->buildSelection($state['requested_modules'], $catalog);
            $state = $stateStore->merge(array(
                'sample_content' => $sampleContent,
                'resolved_modules' => $selection['resolved'],
            ));
            installer_capture_project_setup_state($state);
            $installer = new installer();
            $previousResult = array();
            if ($action === 'retry_install' && is_array($state['install_result'])) {
                $previousResult = $state['install_result'];
            }
            $result = $taskRunner->run($installer, $selection['resolved'], $catalog, $sampleContent, $previousResult);
            if (!empty($result['success'])) {
                $projectAddress = isset($state['project_address']) ? $state['project_address'] : '';
                if ($projectAddress !== '') {
                    $projectAddress = rtrim($projectAddress, '/') . '/';
                    $localConfigFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'site.config.local.php';
                    $localConfigContent = "<?php\n\n";
                    $localConfigContent .= "/**\n * Amed Framework - Site Local Configuration\n";
                    $localConfigContent .= " * Generated by Installer\n";
                    $localConfigContent .= " * Date: " . date('Y-m-d H:i:s') . "\n */\n\n";
                    $localConfigContent .= "if (defined('root_dir') === False) exit(':)');\n\n";
                    $localConfigContent .= "\$site_local = array(\n";
                    $localConfigContent .= "    'address' => '" . addslashes($projectAddress) . "',\n";
                    $localConfigContent .= "    'saddress' => '" . addslashes($projectAddress) . "',\n";
                    $localConfigContent .= "    'iaddress' => '" . addslashes($projectAddress) . "static/',\n";
                    $localConfigContent .= "    'taddress' => '" . addslashes($projectAddress) . "templates/',\n";
                    $localConfigContent .= ");\n";
                    @file_put_contents($localConfigFile, $localConfigContent);
                }
            }
            $state = $stateStore->merge(array(
                'install_result' => $result,
                'step' => !empty($result['success']) ? 'finish' : 'progress',
            ));
        }
    }

}

$state = $stateStore->getState();
$selection = $resolver->buildSelection($state['requested_modules'], $catalog);
$summary = $selection['summary'];
$result = isset($state['install_result']) ? $state['install_result'] : null;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($state['installer_language'], ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo htmlspecialchars($ui['hero']['badge'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/css/intlTelInput.css">
    <style>
        :root {
            --bg: #f4efe6;
            --surface: #fffaf2;
            --surface-strong: #fff;
            --ink: #1f1f18;
            --muted: #6c6758;
            --accent: #0f766e;
            --accent-strong: #115e59;
            --danger: #b42318;
            --danger-soft: #fef3f2;
            --ok: #166534;
            --ok-soft: #ecfdf3;
            --border: #e7dcc9;
            --warning: #b54708;
            --warning-soft: #fffaeb;
            --shadow: 0 18px 42px rgba(63, 46, 20, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(15, 118, 110, 0.14), transparent 28%),
                linear-gradient(180deg, #f8f4ec 0%, var(--bg) 100%);
            color: var(--ink);
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            display: flex;
            justify-content: center;
            padding: 20px 14px 104px;
        }

        .shell {
            width: 100%;
            max-width: 430px;
            background: rgba(255, 250, 242, 0.92);
            border: 1px solid rgba(231, 220, 201, 0.9);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .hero {
            padding: 24px 20px 16px;
            background: linear-gradient(180deg, rgba(15, 118, 110, 0.12), rgba(255,255,255,0));
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.10);
            color: var(--accent-strong);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        h1, h2, h3, p { margin: 0; }

        .hero h1 {
            margin-top: 14px;
            font-size: 28px;
            line-height: 1.1;
        }

        .hero p {
            margin-top: 10px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .steps {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 20px 16px;
        }

        .step-pill {
            flex: 1 1 calc(25% - 8px);
            min-width: 0;
            border-radius: 999px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .step-pill.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .content { padding: 0 20px 20px; }

        .panel,
        .group-panel {
            background: var(--surface-strong);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 18px;
            margin-bottom: 14px;
        }

        .finish-panel {
            background: linear-gradient(180deg, rgba(15, 118, 110, 0.12), rgba(255,255,255,0.98));
        }

        .panel h2,
        .group-panel h3 {
            font-size: 20px;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .group-panel h3 {
            font-size: 16px;
        }

        .panel p,
        .group-panel p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .status-list,
        .summary-list,
        .module-list,
        .module-grid,
        .output-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .status-item,
        .summary-item,
        .module-item,
        .output-item {
            border-radius: 18px;
            border: 1px solid var(--border);
            padding: 14px;
            background: var(--surface);
        }

        .status-item.is-ok,
        .module-item.is-completed {
            background: var(--ok-soft);
            border-color: rgba(22, 101, 52, 0.18);
        }

        .status-item.is-fail,
        .module-item.is-failed {
            background: var(--danger-soft);
            border-color: rgba(180, 35, 24, 0.16);
        }

        .module-item.is-skipped {
            background: rgba(255,255,255,0.88);
        }

        .status-top,
        .summary-top,
        .module-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .status-label,
        .summary-label,
        .module-label {
            font-size: 14px;
            font-weight: 700;
        }

        .status-chip,
        .module-state,
        .chip {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-chip.is-ok,
        .module-state.is-completed,
        .chip.is-accent {
            background: rgba(22, 101, 52, 0.12);
            color: var(--ok);
        }

        .status-chip.is-fail,
        .module-state.is-failed {
            background: rgba(180, 35, 24, 0.12);
            color: var(--danger);
        }

        .module-state.is-skipped,
        .chip.is-neutral {
            background: rgba(108, 103, 88, 0.10);
            color: var(--muted);
        }

        .module-state.is-warning {
            background: rgba(181, 71, 8, 0.12);
            color: var(--warning);
        }

        .chip.is-warning {
            background: rgba(181, 71, 8, 0.12);
            color: var(--warning);
        }

        .status-detail,
        .summary-detail,
        .module-detail {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .language-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .language-option input,
        .module-checkbox input {
            display: none;
        }

        .language-card {
            display: flex;
            flex-direction: column;
            min-height: 74px;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
            gap: 6px;
            text-align: center;
            line-height: 1.15;
        }

        .language-card small {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .language-option input:checked + .language-card {
            background: rgba(15, 118, 110, 0.12);
            color: var(--accent-strong);
            border-color: rgba(15, 118, 110, 0.4);
        }

        .field { margin-top: 16px; }

        .choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .choice-card {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 56px;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface);
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
        }

        .choice-card input {
            display: none;
        }

        .choice-indicator {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(108, 103, 88, 0.45);
            background: #fff;
            flex: 0 0 auto;
        }

        .choice-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
        }

        .choice-card:has(input:checked) {
            border-color: rgba(15, 118, 110, 0.42);
            background: rgba(15, 118, 110, 0.08);
        }

        .choice-card:has(input:checked) .choice-indicator {
            border-color: var(--accent);
            box-shadow: inset 0 0 0 4px #fff;
            background: var(--accent);
        }

        .panel-actions {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            min-height: 48px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--surface);
            padding: 12px 14px;
            color: var(--ink);
            font-size: 15px;
        }

        .phone-field {
            display: grid;
            gap: 8px;
        }

        .phone-shell {
            position: relative;
            width: 100%;
            min-height: 48px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--surface);
            padding: 8px 12px;
        }

        .phone-shell .iti {
            width: 100%;
        }

        .phone-shell .iti--allow-dropdown {
            width: 100%;
        }

        .phone-shell .iti__country-list {
            background: var(--surface-strong);
            color: var(--ink);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .phone-shell .iti__selected-country-primary {
            height: 32px;
            border-radius: 10px;
        }

        .phone-shell .iti__flag-container {
            top: 8px;
            bottom: 8px;
            left: 4px;
        }

        .phone-shell input[type="tel"] {
            border: 0;
            background: transparent;
            min-height: 32px;
            padding: 0 0 0 52px;
        }

        .phone-shell input[type="tel"]:focus {
            outline: none;
            box-shadow: none;
        }

        .module-card {
            display: block;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: var(--surface);
            padding: 14px;
        }

        .module-checkbox input:checked + .module-card {
            border-color: rgba(15, 118, 110, 0.42);
            background: rgba(15, 118, 110, 0.08);
        }

        .module-checkbox input:disabled + .module-card {
            opacity: 0.9;
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .radio-group {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 52px;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink);
            font-size: 14px;
        }

        .notice,
        .warning-box,
        .error-box {
            margin-bottom: 14px;
            border-radius: 18px;
            padding: 14px;
            font-size: 13px;
            line-height: 1.5;
        }

        .notice {
            border: 1px solid rgba(15, 118, 110, 0.18);
            background: rgba(15, 118, 110, 0.08);
            color: var(--accent-strong);
            margin-top: 16px;
        }

        .warning-box {
            border: 1px solid rgba(181, 71, 8, 0.16);
            background: var(--warning-soft);
            color: var(--warning);
        }

        .error-box {
            background: var(--danger-soft);
            border: 1px solid rgba(180, 35, 24, 0.16);
            color: var(--danger);
        }

        .details {
            margin-top: 10px;
            border-top: 1px solid rgba(231, 220, 201, 0.9);
            padding-top: 10px;
        }

        .details summary {
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: var(--accent-strong);
        }

        .details-list {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .detail-log {
            font-size: 12px;
            color: var(--muted);
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 12px;
        }

        .actions {
            position: sticky;
            bottom: 0;
            display: grid;
            gap: 10px;
            padding: 14px 20px 20px;
            background: linear-gradient(180deg, rgba(244,239,230,0), rgba(244,239,230,0.98) 30%);
        }

        .button,
        .button-link {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 18px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .button-primary,
        .button-link {
            background: var(--accent);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--border);
        }

        @media (max-width: 360px) {
            .step-pill {
                flex-basis: calc(33.333% - 8px);
            }

            .choice-grid,
            .language-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div class="eyebrow"><?php echo htmlspecialchars($ui['hero']['badge'], ENT_QUOTES, 'UTF-8'); ?></div>
            <h1><?php echo htmlspecialchars($ui['hero']['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if (!empty($ui['hero']['body'])): ?>
                <p><?php echo htmlspecialchars($ui['hero']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>

        <div class="steps">
            <div class="<?php echo installer_step_class($state['step'], 'language'); ?>"><?php echo htmlspecialchars($ui['steps']['language'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'environment'); ?>"><?php echo htmlspecialchars($ui['steps']['checks'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'database'); ?>"><?php echo htmlspecialchars($ui['steps']['database'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'setup'); ?>"><?php echo htmlspecialchars($ui['steps']['setup'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'sample'); ?>"><?php echo htmlspecialchars($ui['steps']['sample'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'finish'); ?>"><?php echo htmlspecialchars($ui['steps']['finish'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="content">
            <?php if ($errorMessage !== ''): ?>
                <div class="error-box"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($state['step'] === 'language'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['language']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['language']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <form method="post" id="language-form">
                        <input type="hidden" name="action" value="preview_language" id="language-action">
                        <div class="language-grid">
                            <?php foreach ($availableInstallerLanguages as $languageCode => $enabled): ?>
                                <?php $languageOption = installer_language_option($languageCode, $ui); ?>
                                <label class="language-option">
                                    <input type="radio" name="installer_language" value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $state['installer_language'] === $languageCode ? 'checked' : ''; ?>>
                                    <span class="language-card"><?php echo htmlspecialchars($languageOption['label'], ENT_QUOTES, 'UTF-8'); ?><small><?php echo htmlspecialchars($languageOption['code'], ENT_QUOTES, 'UTF-8'); ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="actions">
                            <button type="submit" class="button button-primary" onclick="document.getElementById('language-action').value='save_language';"><?php echo htmlspecialchars($ui['actions']['continue'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($state['step'] === 'environment'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['environment']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="status-list">
                        <?php foreach ($checks as $check): ?>
                            <?php $checkCopy = installer_check_copy($check, $ui); ?>
                            <div class="status-item <?php echo $check['ok'] ? 'is-ok' : 'is-fail'; ?>">
                                <div class="status-top">
                                    <div class="status-label"><?php echo htmlspecialchars($checkCopy['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="status-chip <?php echo $check['ok'] ? 'is-ok' : 'is-fail'; ?>">
                                        <?php echo $check['ok'] ? htmlspecialchars($ui['environment']['ready'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($ui['environment']['blocked'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                                <div class="status-detail"><?php echo htmlspecialchars($checkCopy['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form method="post" class="actions">
                    <input type="hidden" name="action" value="continue_to_setup">
                    <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['continue_to_setup'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
                <form method="post" class="actions">
                    <input type="hidden" name="action" value="go_back_to_language">
                    <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            <?php endif; ?>

            <?php if ($state['step'] === 'database'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['database']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>

                    <form method="post" id="db_form">
                        <input type="hidden" name="action" value="save_database">
                        
                        <div class="field">
                            <label><?php echo htmlspecialchars($ui['database']['adapter_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="choice-grid">
                                <label class="choice-card">
                                    <input type="radio" name="database_adapter" value="PDO_MYSQL" <?php echo $state['database_adapter'] === 'PDO_MYSQL' ? 'checked' : ''; ?> onchange="toggleDbFields()">
                                    <span class="choice-indicator" aria-hidden="true"></span>
                                    <span class="choice-label"><?php echo htmlspecialchars($ui['database']['adapter_mysql'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                                <label class="choice-card">
                                    <input type="radio" name="database_adapter" value="PDO_SQLITE" <?php echo $state['database_adapter'] === 'PDO_SQLITE' ? 'checked' : ''; ?> onchange="toggleDbFields()">
                                    <span class="choice-indicator" aria-hidden="true"></span>
                                    <span class="choice-label"><?php echo htmlspecialchars($ui['database']['adapter_sqlite'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            </div>
                        </div>

                        <div id="mysql_fields" style="display: <?php echo $state['database_adapter'] === 'PDO_MYSQL' ? 'block' : 'none'; ?>;">
                            <div class="field">
                                <label for="database_host"><?php echo htmlspecialchars($ui['database']['mysql_host_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_host" name="database_host" type="text" value="<?php echo htmlspecialchars($state['database_host'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label for="database_user"><?php echo htmlspecialchars($ui['database']['mysql_user_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_user" name="database_user" type="text" value="<?php echo htmlspecialchars($state['database_user'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label for="database_pass"><?php echo htmlspecialchars($ui['database']['mysql_pass_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_pass" name="database_pass" type="password" value="<?php echo htmlspecialchars($state['database_pass'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label for="database_name"><?php echo htmlspecialchars($ui['database']['mysql_name_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_name" name="database_name" type="text" value="<?php echo htmlspecialchars($state['database_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div id="sqlite_fields" style="display: <?php echo $state['database_adapter'] === 'PDO_SQLITE' ? 'block' : 'none'; ?>;">
                            <div class="field">
                                <label for="database_path"><?php echo htmlspecialchars($ui['database']['sqlite_path_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_path" name="database_path" type="text" value="<?php echo htmlspecialchars($state['database_path'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div class="panel-actions">
                            <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['continue'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                    <form method="post" class="panel-actions">
                        <input type="hidden" name="action" value="go_back_to_environment">
                        <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>

                    <script>
                        function toggleDbFields() {
                            const adapter = document.querySelector('input[name="database_adapter"]:checked').value;
                            document.getElementById('mysql_fields').style.display = adapter === 'PDO_MYSQL' ? 'block' : 'none';
                            document.getElementById('sqlite_fields').style.display = adapter === 'PDO_SQLITE' ? 'block' : 'none';
                        }
                    </script>
                </div>
            <?php endif; ?>

            <?php if ($state['step'] === 'setup'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['setup']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <form method="post">
                        <input type="hidden" name="action" value="save_setup">
                        <div class="field">
                            <label for="project_name"><?php echo htmlspecialchars($ui['setup']['project_name_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_name" name="project_name" type="text" value="<?php echo htmlspecialchars($state['project_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label for="project_default_language"><?php echo htmlspecialchars($ui['setup']['project_default_language_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <select id="project_default_language" name="project_default_language">
                                <?php foreach ($projectLanguages as $languageCode => $languageLabel): ?>
                                    <option value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $state['project_default_language'] === $languageCode ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="project_address"><?php echo htmlspecialchars($ui['setup']['project_address_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_address" name="project_address" type="url" value="<?php echo htmlspecialchars($state['project_address'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label for="project_description"><?php echo htmlspecialchars($ui['setup']['project_description_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <textarea id="project_description" name="project_description"><?php echo htmlspecialchars($state['project_description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="field">
                            <label for="project_owner_name"><?php echo htmlspecialchars($ui['setup']['project_owner_name_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_owner_name" name="project_owner_name" type="text" value="<?php echo htmlspecialchars($state['project_owner_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label for="project_established_year"><?php echo htmlspecialchars($ui['setup']['project_established_year_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_established_year" name="project_established_year" type="number" inputmode="numeric" value="<?php echo htmlspecialchars($state['project_established_year'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label for="project_phone"><?php echo htmlspecialchars($ui['setup']['project_phone_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="phone-field">
                                <input type="hidden" id="project_phone_country_code" name="project_phone_country_code" value="<?php echo htmlspecialchars($state['project_phone_country_code'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="phone-shell">
                                    <input id="project_phone" name="project_phone" type="tel" value="<?php echo htmlspecialchars($state['project_phone'], ENT_QUOTES, 'UTF-8'); ?>" data-phone-store-mode="full" data-country-target-id="project_phone_country_code" autocomplete="tel" inputmode="tel">
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label for="project_whatsapp"><?php echo htmlspecialchars($ui['setup']['project_whatsapp_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="phone-field">
                                <input type="hidden" id="project_whatsapp_country_code" name="project_whatsapp_country_code" value="<?php echo htmlspecialchars($state['project_whatsapp_country_code'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="phone-shell">
                                    <input id="project_whatsapp" name="project_whatsapp" type="tel" value="<?php echo htmlspecialchars($state['project_whatsapp'], ENT_QUOTES, 'UTF-8'); ?>" data-phone-store-mode="full" data-country-target-id="project_whatsapp_country_code" autocomplete="tel" inputmode="tel">
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label for="project_email"><?php echo htmlspecialchars($ui['setup']['project_email_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_email" name="project_email" type="email" value="<?php echo htmlspecialchars($state['project_email'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label for="project_office_address"><?php echo htmlspecialchars($ui['setup']['project_office_address_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <textarea id="project_office_address" name="project_office_address"><?php echo htmlspecialchars($state['project_office_address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="field">
                            <label for="project_work_address"><?php echo htmlspecialchars($ui['setup']['project_work_address_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <textarea id="project_work_address" name="project_work_address"><?php echo htmlspecialchars($state['project_work_address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="actions">
                            <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['continue'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="action" value="go_back_to_environment">
                        <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($state['step'] === 'sample'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['sample']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <form method="post">
                        <input type="hidden" name="action" value="run_install">
                        <div class="field">
                            <label><?php echo htmlspecialchars($ui['sample']['sample_content_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="sample_content" value="no" <?php echo $state['sample_content'] === 'no' ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($ui['sample']['sample_content_no'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="sample_content" value="yes" <?php echo $state['sample_content'] === 'yes' ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($ui['sample']['sample_content_yes'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="actions">
                            <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['run_installer'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="action" value="go_back_to_setup">
                        <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($state['step'] === 'progress' && $result): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['progress']['failure_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['progress']['failure_body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="summary-list">
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['progress']['current_state_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars(installer_runtime_state_label($result['lifecycle_state'], $ui), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php if (!empty($result['failed_module'])): ?>
                            <div class="summary-item">
                                <div class="summary-top">
                                    <div class="summary-label"><?php echo htmlspecialchars($ui['progress']['failed_module_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="summary-detail"><?php echo htmlspecialchars(installer_module_label($result['failed_module'], $ui), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['progress']['failure_reason_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars(installer_failure_reason_label($result, $ui), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php if (!empty($result['warnings'])): ?>
                            <div class="summary-item">
                                <div class="summary-top">
                                    <div class="summary-label"><?php echo htmlspecialchars($ui['finish']['warnings_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="summary-detail"><?php echo htmlspecialchars(installer_result_list_text($result['warnings'], $ui), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($result['failed_module']) && !empty($result['modules'][$result['failed_module']]['logs'])): ?>
                        <details class="details">
                            <summary><?php echo htmlspecialchars($ui['progress']['logs_label'], ENT_QUOTES, 'UTF-8'); ?></summary>
                            <div class="details-list">
                                <?php foreach ($result['modules'][$result['failed_module']]['logs'] as $line): ?>
                                    <div class="detail-log"><?php echo htmlspecialchars(installer_result_text($line, $ui), ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>

                <?php if (!empty($result['failure_recoverable'])): ?>
                    <form method="post" class="actions">
                        <input type="hidden" name="action" value="retry_install">
                        <input type="hidden" name="sample_content" value="<?php echo htmlspecialchars($state['sample_content'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['retry_install'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                <?php endif; ?>
                <form method="post" class="actions">
                    <input type="hidden" name="action" value="go_back_to_setup">
                    <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            <?php endif; ?>

            <?php if ($state['step'] === 'finish' && $result && !empty($result['success'])): ?>
                <div class="panel finish-panel">
                    <h2><?php echo htmlspecialchars($ui['finish']['success_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (!empty($result['warnings'])): ?>
                        <div class="summary-list">
                            <div class="summary-item">
                                <div class="summary-top">
                                    <div class="summary-label"><?php echo htmlspecialchars($ui['finish']['warnings_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="summary-detail"><?php echo htmlspecialchars(installer_result_list_text($result['warnings'], $ui), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <a class="button-link" href="../mod_user/panel"><?php echo htmlspecialchars($ui['actions']['open_admin'], ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/js/intlTelInput.min.js"></script>
    <script>
        (function () {
            var languageForm = document.getElementById('language-form');
            if (!languageForm) {
                return;
            }

            var languageInputs = languageForm.querySelectorAll('input[name="installer_language"]');
            for (var i = 0; i < languageInputs.length; i += 1) {
                languageInputs[i].addEventListener('change', function () {
                    languageForm.submit();
                });
            }
        }());

        (function () {
            function initInstallerPhoneInput(inputId) {
                var input = document.getElementById(inputId);
                if (!input || input.dataset.intlReady === '1') {
                    return;
                }

                var hiddenInput = document.getElementById(input.dataset.countryTargetId);
                var form = input.closest('form');
                var iti = window.intlTelInput(input, {
                    initialCountry: 'tr',
                    preferredCountries: ['tr', 'iq', 'de', 'gb', 'us'],
                    separateDialCode: true,
                    strictMode: true,
                    autoPlaceholder: 'aggressive'
                });

                function syncCountryCode() {
                    if (!hiddenInput) {
                        return;
                    }

                    var countryData = iti.getSelectedCountryData();
                    if (countryData && countryData.dialCode) {
                        hiddenInput.value = '+' + countryData.dialCode;
                    }
                }

                if (input.value.trim() !== '') {
                    iti.setNumber(input.value.trim());
                }

                syncCountryCode();

                input.addEventListener('input', function () {
                    var value = input.value.trim();
                    if (value.indexOf('0') === 0) {
                        input.value = value.replace(/^0+/, '');
                    }
                });

                input.addEventListener('countrychange', function () {
                    syncCountryCode();
                });

                if (form && form.dataset.installerPhoneSubmitBound !== '1') {
                    form.addEventListener('submit', function () {
                        var phoneInputs = form.querySelectorAll('input[data-phone-store-mode="full"]');

                        for (var i = 0; i < phoneInputs.length; i += 1) {
                            var phoneInput = phoneInputs[i];
                            var phoneIti = window.intlTelInput.getInstance(phoneInput);
                            var countryTarget = document.getElementById(phoneInput.dataset.countryTargetId);

                            if (!phoneIti) {
                                continue;
                            }

                            var countryData = phoneIti.getSelectedCountryData();
                            if (countryTarget && countryData && countryData.dialCode) {
                                countryTarget.value = '+' + countryData.dialCode;
                            }

                            var digits = phoneInput.value.replace(/\D/g, '');
                            if (countryData && countryData.dialCode) {
                                digits = digits.replace(/^0+/, '');
                                phoneInput.value = '+' + countryData.dialCode + digits;
                            } else {
                                phoneInput.value = digits;
                            }
                        }
                    });

                    form.dataset.installerPhoneSubmitBound = '1';
                }

                input.dataset.intlReady = '1';
            }

            initInstallerPhoneInput('project_phone');
            initInstallerPhoneInput('project_whatsapp');
        }());
    </script>
</body>
</html>
