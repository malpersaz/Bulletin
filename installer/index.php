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
    $stubConfig .= "Registry::add('database', 'installed');\n";
    $stubConfig .= "Registry::add('db_config', \$db);\n";
    $stubConfig .= "unset(\$db);\n";
    file_put_contents($bootstrapDbConfig, $stubConfig);
}

require_once('../init.php');
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

    public function load_mod($module)
    {
        if (file_exists(installer_dir . $module . DS . 'index.php') === false) {
            throw new Exception("Module '" . $module . "' was not found");
        }

        require_once(installer_dir . $module . DS . 'index.php');
        return new $module();
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

function installer_is_rtl($languageCode)
{
    return in_array(strtolower((string) $languageCode), array('ar', 'fa', 'ckb'), true);
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

$session = Load::lib('Session');
foreach (['id', 'username', 'group_id', 'permissions', 'sid', 'session_token', 'okunmamis'] as $authKey) {
    if ($session->has($authKey)) {
        $session->remove($authKey);
    }
}
$stateStore = new InstallerStateStore();
$catalog = new InstallerModuleCatalog();
$resolver = new InstallerDependencyResolver();
$environment = new InstallerEnvironmentCheck();
$taskRunner = new InstallerTaskRunner();
$availableInstallerLanguages = installer_available_languages();
$projectLanguages = installer_project_languages();
$state = $stateStore->getState();
installer_capture_project_setup_state($state);

$localDbConfigFile = root_dir . 'config' . DIRECTORY_SEPARATOR . 'db.config.local.php';
$hasLocalDbConfig = file_exists($localDbConfigFile);
$registeredDbConfig = Registry::has('db_config') ? Registry::get('db_config') : array();
$isDbConfigured = $hasLocalDbConfig && !empty($registeredDbConfig['installed']) && is_array($registeredDbConfig['installed']);

if ($state['step'] === 'finish') {
    // If the system is not installed but the session state shows 'finish',
    // we clear the state so that the installer can restart cleanly.
    $state = $stateStore->reset();
}

if (!$isDbConfigured && in_array($state['step'], array('setup', 'sample', 'progress', 'finish'))) {
    $state = $stateStore->merge(array(
        'database_configured' => false,
        'step' => 'database',
    ));
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
    Registry::set('lang', $state['installer_language']);
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

    if (!$isDbConfigured && in_array($action, array('save_setup', 'go_back_to_setup', 'run_install', 'retry_install'))) {
        $errorMessage = isset($ui['errors']['database_not_configured']) ? $ui['errors']['database_not_configured'] : 'Database configuration was not found. Please configure the database before continuing.';
        $state = $stateStore->merge(array(
            'database_configured' => false,
            'step' => 'database',
        ));
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
            'project_default_language' => $installerLanguage,
            'step' => 'environment',
        ));
        installer_capture_project_setup_state($state);
        Registry::set('lang', $installerLanguage);
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
            'project_default_language' => $installerLanguage,
            'step' => 'language',
        ));
        installer_capture_project_setup_state($state);
        Registry::set('lang', $installerLanguage);
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
                $localDbConfigFile = root_dir . 'config' . DIRECTORY_SEPARATOR . 'db.config.local.php';
                if (file_exists($localDbConfigFile)) {
                    require_once $localDbConfigFile;
                }
                $registeredDbConfig = Registry::has('db_config') ? Registry::get('db_config') : array();
                $isDbConfigured = !empty($registeredDbConfig['installed']) && is_array($registeredDbConfig['installed']);
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
        $defaultLangFallback = isset($state['project_default_language']) && !empty($state['project_default_language'])
            ? $state['project_default_language']
            : (isset($state['installer_language']) ? $state['installer_language'] : 'en');
        $projectName = isset($_POST['project_name']) ? trim($_POST['project_name']) : installer_site_build_name();
        $projectLanguage = isset($_POST['project_default_language']) ? $_POST['project_default_language'] : $defaultLangFallback;
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
            $projectLanguage = $defaultLangFallback;
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
            try {
                $installer = new installer();
                $previousResult = array();
                if ($action === 'retry_install' && is_array($state['install_result'])) {
                    $previousResult = $state['install_result'];
                }
                $result = $taskRunner->run($installer, $selection['resolved'], $catalog, $sampleContent, $previousResult);
                if (!empty($result['success'])) {
                    $projectAddress = isset($state['project_address']) ? $state['project_address'] : '';
                    if ($projectAddress !== '') {
                        $configWriter = new InstallerConfigWriter();
                        $configWriter->writeSiteConfig($projectAddress);
                    }
                }
                $state = $stateStore->merge(array(
                    'install_result' => $result,
                    'step' => !empty($result['success']) ? 'finish' : 'progress',
                ));
            } catch (Throwable $e) {
                $errorMessage = sprintf($ui['errors']['database_connect_failed'], $e->getMessage());
                $state = $stateStore->merge(array(
                    'database_configured' => false,
                    'step' => 'database',
                ));
            }
        }
    }
}

$state = $stateStore->getState();
$selection = $resolver->buildSelection($state['requested_modules'], $catalog);
$summary = $selection['summary'];
$result = isset($state['install_result']) ? $state['install_result'] : null;
$isRtl = installer_is_rtl($state['installer_language']);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($state['installer_language'], ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo htmlspecialchars($ui['hero']['badge'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/css/intlTelInput.css">
    <link rel="stylesheet" href="assets/css/installer.css">
</head>
<body>
    <div class="shell">
        <header class="hero">
            <div class="eyebrow"><?php echo htmlspecialchars($ui['hero']['badge'], ENT_QUOTES, 'UTF-8'); ?></div>
            <h1><?php echo htmlspecialchars($ui['hero']['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if (!empty($ui['hero']['body'])): ?>
                <p><?php echo htmlspecialchars($ui['hero']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </header>

        <nav class="steps" aria-label="Progress">
            <div class="<?php echo installer_step_class($state['step'], 'language'); ?>"><?php echo htmlspecialchars($ui['steps']['language'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'environment'); ?>"><?php echo htmlspecialchars($ui['steps']['checks'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'database'); ?>"><?php echo htmlspecialchars($ui['steps']['database'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'setup'); ?>"><?php echo htmlspecialchars($ui['steps']['setup'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'sample'); ?>"><?php echo htmlspecialchars($ui['steps']['sample'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'finish'); ?>"><?php echo htmlspecialchars($ui['steps']['finish'], ENT_QUOTES, 'UTF-8'); ?></div>
        </nav>

        <main class="content">
            <?php if ($errorMessage !== ''): ?>
                <div class="error-box" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($state['step'] === 'language'): ?>
                <section class="panel">
                    <h2><?php echo htmlspecialchars($ui['language']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (!empty($ui['language']['body'])): ?>
                        <p><?php echo htmlspecialchars($ui['language']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <form method="post" id="language-form">
                        <input type="hidden" name="action" value="preview_language" id="language-action">
                        <div class="language-grid">
                            <?php foreach ($availableInstallerLanguages as $languageCode => $enabled): ?>
                                <?php $languageOption = installer_language_option($languageCode, $ui); ?>
                                <label class="language-option">
                                    <input type="radio" name="installer_language" value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $state['installer_language'] === $languageCode ? 'checked' : ''; ?>>
                                    <span class="language-card">
                                        <span class="language-meta">
                                            <span class="language-name"><?php echo htmlspecialchars($languageOption['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="language-code"><?php echo htmlspecialchars($languageOption['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </span>
                                        <span class="language-check" aria-hidden="true">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="actions">
                            <button type="submit" class="button button-primary" id="language-save-button"><?php echo htmlspecialchars($ui['actions']['continue'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($state['step'] === 'environment'): ?>
                <section class="panel">
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
                </section>

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
                <section class="panel">
                    <h2><?php echo htmlspecialchars($ui['database']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>

                    <form method="post" id="db_form" data-adapter="<?php echo htmlspecialchars($state['database_adapter'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="save_database">
                        
                        <div class="field">
                            <label><?php echo htmlspecialchars($ui['database']['adapter_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="choice-grid">
                                <label class="choice-card">
                                    <input type="radio" name="database_adapter" value="PDO_MYSQL" <?php echo $state['database_adapter'] === 'PDO_MYSQL' ? 'checked' : ''; ?>>
                                    <span class="choice-indicator" aria-hidden="true"></span>
                                    <span class="choice-label"><?php echo htmlspecialchars($ui['database']['adapter_mysql'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                                <label class="choice-card">
                                    <input type="radio" name="database_adapter" value="PDO_SQLITE" <?php echo $state['database_adapter'] === 'PDO_SQLITE' ? 'checked' : ''; ?>>
                                    <span class="choice-indicator" aria-hidden="true"></span>
                                    <span class="choice-label"><?php echo htmlspecialchars($ui['database']['adapter_sqlite'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            </div>
                        </div>

                        <div id="mysql_fields">
                            <div class="field">
                                <label for="database_host"><?php echo htmlspecialchars($ui['database']['mysql_host_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_host" name="database_host" type="text" value="<?php echo htmlspecialchars($state['database_host'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" spellcheck="false">
                            </div>
                            <div class="field">
                                <label for="database_user"><?php echo htmlspecialchars($ui['database']['mysql_user_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_user" name="database_user" type="text" value="<?php echo htmlspecialchars($state['database_user'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" spellcheck="false">
                            </div>
                            <div class="field">
                                <label for="database_pass"><?php echo htmlspecialchars($ui['database']['mysql_pass_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_pass" name="database_pass" type="password" value="<?php echo htmlspecialchars($state['database_pass'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                            </div>
                            <div class="field">
                                <label for="database_name"><?php echo htmlspecialchars($ui['database']['mysql_name_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_name" name="database_name" type="text" value="<?php echo htmlspecialchars($state['database_name'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" spellcheck="false">
                            </div>
                        </div>

                        <div id="sqlite_fields">
                            <div class="field">
                                <label for="database_path"><?php echo htmlspecialchars($ui['database']['sqlite_path_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                <input id="database_path" name="database_path" type="text" value="<?php echo htmlspecialchars($state['database_path'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" spellcheck="false">
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
                </section>
            <?php endif; ?>

            <?php if ($state['step'] === 'setup'): ?>
                <section class="panel">
                    <h2><?php echo htmlspecialchars($ui['setup']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <form method="post">
                        <input type="hidden" name="action" value="save_setup">
                        <div class="field">
                            <label for="project_name"><?php echo htmlspecialchars($ui['setup']['project_name_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_name" name="project_name" type="text" value="<?php echo htmlspecialchars($state['project_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="field">
                            <label for="project_default_language"><?php echo htmlspecialchars($ui['setup']['project_default_language_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <select id="project_default_language" name="project_default_language">
                                <?php
                                $selectedProjectLanguage = !empty($state['project_default_language']) ? $state['project_default_language'] : (!empty($state['installer_language']) ? $state['installer_language'] : 'en');
                                foreach ($projectLanguages as $languageCode => $languageLabel): ?>
                                    <option value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedProjectLanguage === $languageCode ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="project_address"><?php echo htmlspecialchars($ui['setup']['project_address_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_address" name="project_address" type="url" value="<?php echo htmlspecialchars($state['project_address'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com/">
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
                            <input id="project_email" name="project_email" type="email" value="<?php echo htmlspecialchars($state['project_email'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email">
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
                </section>
            <?php endif; ?>

            <?php if ($state['step'] === 'sample'): ?>
                <section class="panel">
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
                </section>
            <?php endif; ?>

            <?php if ($state['step'] === 'progress' && $result): ?>
                <section class="panel">
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
                </section>

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
                <section class="panel finish-panel">
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
                </section>

                <div class="actions">
                    <a class="button-link" href="../mod_user/panel"><?php echo htmlspecialchars($ui['actions']['open_admin'], ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/js/intlTelInput.min.js"></script>
    <script src="assets/js/installer.js" defer></script>
</body>
</html>
