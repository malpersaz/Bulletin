<?php
header('Content-Type: text/html; charset=utf-8');
require_once('../koberz.init.php');
define('installer_dir', root_dir . 'installer' . DS);

require_once(installer_dir . 'installerbase.php');
require_once(installer_dir . 'app' . DS . 'InstallerStateStore.php');
require_once(installer_dir . 'app' . DS . 'InstallerModuleCatalog.php');
require_once(installer_dir . 'app' . DS . 'InstallerDependencyResolver.php');
require_once(installer_dir . 'app' . DS . 'InstallerEnvironmentCheck.php');
class installer extends installerbase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function install($mods)
    {
        //$this->db->start_transaction();
        foreach ($mods as $mod) {
            try {
                $m = $this->load_mod($mod);
            } catch (Exception $e) {
                //$this->db->rollback();
                echo $e->getMessage();
                exit();
            }
            if ($m->install() !== true) {
                //$this->db->rollback();
                $this->error("Error occurred while installing '" . $mod . "'");
                exit();
            }
            //$m->uninstall();
        }
        //$this->db->commit();
    }

    public function load_mod($modul)
    {
        if (file_exists(installer_dir . $modul . DS . 'index.php') === false) {
            throw new Exception("Module '" . $modul . "' was not found");
        }
        require_once(installer_dir . $modul . DS . "index.php");
        return new $modul();
    }

    public function uninstall($mods)
    {
        //$this->db->start_transaction();
        foreach ($mods as $mod) {
            try {
                $m = $this->load_mod($mod);
            } catch (Exception $e) {
                //$this->db->rollback();
                echo $e->getMessage();
                exit();
            }
            if ($m->uninstall() !== true || $m->uninstall_blocked === TRUE) {
                //$this->db->rollback();
                $this->error("Error occurred while uninstalling '" . $mod . "'");
                exit();
            }
            //$m->uninstall();
        }
        //$this->db->commit();
        //print_r(Database::$_sql_querys);
    }

    public function run_installation(array $mods)
    {
        $lang = Load::lang('lang');
        $output = array();
        $success = true;

        ob_start();

        $this->system_mesaj($lang['lang']['system']['starting']);

        $this->system_mesaj(sprintf($lang['lang']['system']['installing'], 'lang'));
        if ($this->load_mod('lang')->install() !== TRUE) {
            $this->error("Error occurred while installing 'lang'");
            $success = false;
        }

        if ($success === true) {
            $this->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mods'));
            if ($this->load_mod('mods')->install() !== TRUE) {
                $this->error("Error occurred while installing 'mods'");
                $success = false;
            }
        }

        if ($success === true) {
            $this->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mod_user (part 1)'));
            $mod_user = $this->load_mod('mod_user');
            if ($mod_user->install() !== TRUE) {
                $this->error("Error occurred while installing 'mod_user'");
                $success = false;
            }
        }

        if ($success === true) {
            $this->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mod_user_group'));
            if ($this->load_mod('mod_user_group')->install() !== TRUE) {
                $this->error("Error occurred while installing 'mod_user_group'");
                $success = false;
            }
        }

        if ($success === true) {
            $this->system_mesaj(sprintf($lang['lang']['system']['installing'], 'mod_user (part 2)'));
            if ($mod_user->install_finish() !== TRUE) {
                $this->error("Error occurred while finishing 'mod_user' installation");
                $success = false;
            }
        }

        if ($success === true) {
            $mods = array_values(array_diff($mods, array('lang', 'mods', 'mod_user', 'mod_user_group')));
            $this->install($mods);
        }

        $rawOutput = ob_get_clean();
        $normalizedOutput = preg_replace('/<br\s*\/?>/i', PHP_EOL, $rawOutput);
        $normalizedOutput = trim(strip_tags($normalizedOutput));

        if ($normalizedOutput !== '') {
            $output = preg_split('/\r\n|\r|\n/', $normalizedOutput);
        }

        return array(
            'success' => $success,
            'output' => $output,
            'installed_at' => NOW,
        );
    }
}

Load::lib('Session');
$stateStore = new InstallerStateStore();
$catalog = new InstallerModuleCatalog();
$resolver = new InstallerDependencyResolver();
$environment = new InstallerEnvironmentCheck();
$state = $stateStore->getState();

$availableInstallerLanguages = array(
    'de' => 'DE',
    'en' => 'EN',
    'fr' => 'FR',
    'ku' => 'KU',
    'ckb' => 'CKB',
    'sv' => 'SV',
    'zza' => 'ZZA',
);

if (isset($availableInstallerLanguages[$state['installer_language']])) {
    Kayit::yenile('lang', $state['installer_language']);
}

$lang = Load::lang('lang');
$ui = $lang['lang']['installer_mobile'];

$checks = $environment->run();
$isBlocked = $environment->hasBlockingFailures($checks);
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'reset_setup') {
        $state = $stateStore->reset();
        Kayit::yenile('lang', $state['installer_language']);
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
            'step' => 'environment',
        ));
        Kayit::yenile('lang', $installerLanguage);
        $checks = $environment->run();
        $isBlocked = $environment->hasBlockingFailures($checks);
    }

    if ($action === 'go_back_to_welcome') {
        $state = $stateStore->merge(array('step' => 'welcome'));
    }

    if ($action === 'go_back_to_language') {
        $state = $stateStore->merge(array('step' => 'language'));
    }

    if ($action === 'continue_to_setup') {
        if ($isBlocked) {
            $errorMessage = $ui['errors']['resolve_environment'];
            $state = $stateStore->merge(array('step' => 'environment'));
        } else {
            $state = $stateStore->merge(array('step' => 'setup'));
        }
    }

    if ($action === 'save_setup') {
        $projectName = isset($_POST['project_name']) ? trim($_POST['project_name']) : 'Rojname';
        $projectLanguage = isset($_POST['project_default_language']) ? $_POST['project_default_language'] : 'ku';
        $sampleContent = isset($_POST['sample_content']) ? $_POST['sample_content'] : 'no';

        if (!isset($availableInstallerLanguages[$projectLanguage])) {
            $projectLanguage = 'ku';
        }

        if ($sampleContent !== 'yes') {
            $sampleContent = 'no';
        }

        if ($projectName === '') {
            $projectName = 'Rojname';
        }

        $state = $stateStore->merge(array(
            'project_name' => $projectName,
            'project_default_language' => $projectLanguage,
            'sample_content' => $sampleContent,
            'step' => 'review',
        ));
    }

    if ($action === 'go_back_to_environment') {
        $state = $stateStore->merge(array('step' => 'environment'));
    }

    if ($action === 'run_install') {
        $checks = $environment->run();
        $isBlocked = $environment->hasBlockingFailures($checks);

        if ($isBlocked) {
            $errorMessage = $ui['errors']['blocked_install'];
            $state = $stateStore->merge(array('step' => 'environment'));
        } else {
            $installer = new installer();
            $selectedModules = $resolver->resolve($catalog->getDefaultSelectedModules(), $catalog);
            $result = $installer->run_installation($selectedModules);
            $state = $stateStore->merge(array(
                'install_result' => $result,
                'step' => 'result',
            ));
        }
    }
}

$state = $stateStore->getState();
$selectedModules = $resolver->resolve($catalog->getDefaultSelectedModules(), $catalog);
$summary = $catalog->getSummary($selectedModules);
$result = isset($state['install_result']) ? $state['install_result'] : null;

function installer_step_class($stateStep, $currentStep)
{
    if ($stateStep === $currentStep) {
        return 'step-pill is-active';
    }

    return 'step-pill';
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
            : sprintf($ui['checks']['extensions']['missing'], $meta['missing']);
        return array(
            'label' => $ui['checks']['extensions']['label'],
            'detail' => $detail,
        );
    }

    if ($key === 'directories') {
        $detail = empty($meta['blocked'])
            ? $ui['checks']['directories']['ok']
            : sprintf($ui['checks']['directories']['blocked'], $meta['blocked']);
        return array(
            'label' => $ui['checks']['directories']['label'],
            'detail' => $detail,
        );
    }

    if ($key === 'database') {
        if (empty($meta['detail']) || $meta['detail'] === 'No active database configuration found.') {
            $detail = $ui['checks']['database']['missing'];
        } else {
            list($adapter, $databaseKey) = explode('|', $meta['detail']);
            $detail = sprintf($ui['checks']['database']['detail'], $adapter, $databaseKey);
        }

        return array(
            'label' => $ui['checks']['database']['label'],
            'detail' => $detail,
        );
    }

    return array('label' => '', 'detail' => '');
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($state['installer_language'], ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo htmlspecialchars($ui['hero']['badge'], ENT_QUOTES, 'UTF-8'); ?></title>
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
            --shadow: 0 18px 42px rgba(63, 46, 20, 0.10);
        }

        * {
            box-sizing: border-box;
        }

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
            padding: 20px 14px 96px;
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

        h1, h2, h3, p {
            margin: 0;
        }

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
            gap: 8px;
            overflow-x: auto;
            padding: 0 20px 16px;
        }

        .step-pill {
            flex: 0 0 auto;
            border-radius: 999px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .step-pill.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .content {
            padding: 0 20px 20px;
        }

        .panel {
            background: var(--surface-strong);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 18px;
            margin-bottom: 14px;
        }

        .panel h2 {
            font-size: 20px;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .panel p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .status-list,
        .summary-list,
        .output-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .status-item,
        .summary-item,
        .output-item {
            border-radius: 18px;
            border: 1px solid var(--border);
            padding: 14px;
            background: var(--surface);
        }

        .status-item.is-ok {
            background: var(--ok-soft);
            border-color: rgba(22, 101, 52, 0.18);
        }

        .status-item.is-fail {
            background: var(--danger-soft);
            border-color: rgba(180, 35, 24, 0.16);
        }

        .status-top,
        .summary-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .status-label,
        .summary-label {
            font-size: 14px;
            font-weight: 700;
        }

        .status-chip {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-chip.is-ok {
            background: rgba(22, 101, 52, 0.12);
            color: var(--ok);
        }

        .status-chip.is-fail {
            background: rgba(180, 35, 24, 0.12);
            color: var(--danger);
        }

        .status-detail,
        .summary-detail {
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

        .language-option input {
            display: none;
        }

        .language-card {
            display: flex;
            min-height: 74px;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
        }

        .language-option input:checked + .language-card {
            background: rgba(15, 118, 110, 0.12);
            color: var(--accent-strong);
            border-color: rgba(15, 118, 110, 0.4);
        }

        .field {
            margin-top: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 48px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--surface);
            padding: 12px 14px;
            color: var(--ink);
            font-size: 15px;
        }

        .radio-group {
            display: grid;
            gap: 10px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink);
            font-size: 14px;
        }

        .notice {
            margin-top: 16px;
            border-radius: 18px;
            border: 1px solid rgba(15, 118, 110, 0.18);
            background: rgba(15, 118, 110, 0.08);
            color: var(--accent-strong);
            padding: 14px;
            font-size: 13px;
            line-height: 1.5;
        }

        .error-box {
            margin-bottom: 14px;
            border-radius: 18px;
            padding: 14px;
            background: var(--danger-soft);
            border: 1px solid rgba(180, 35, 24, 0.16);
            color: var(--danger);
            font-size: 13px;
            line-height: 1.5;
        }

        .actions {
            position: sticky;
            bottom: 0;
            display: grid;
            gap: 10px;
            padding: 14px 20px 20px;
            background: linear-gradient(180deg, rgba(244,239,230,0), rgba(244,239,230,0.98) 30%);
        }

        .button {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 18px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
        }

        .button-primary {
            background: var(--accent);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--border);
        }

        .meta {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .meta strong {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div class="eyebrow"><?php echo htmlspecialchars($ui['hero']['badge'], ENT_QUOTES, 'UTF-8'); ?></div>
            <h1><?php echo htmlspecialchars($ui['hero']['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($ui['hero']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="steps">
            <div class="<?php echo installer_step_class($state['step'], 'welcome'); ?>"><?php echo htmlspecialchars($ui['steps']['welcome'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'language'); ?>"><?php echo htmlspecialchars($ui['steps']['language'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'environment'); ?>"><?php echo htmlspecialchars($ui['steps']['checks'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'setup'); ?>"><?php echo htmlspecialchars($ui['steps']['setup'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'review'); ?>"><?php echo htmlspecialchars($ui['steps']['review'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="<?php echo installer_step_class($state['step'], 'result'); ?>"><?php echo htmlspecialchars($ui['steps']['result'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="content">
            <?php if ($errorMessage !== ''): ?>
                <div class="error-box"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($state['step'] === 'welcome'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['welcome']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['welcome']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="summary-list">
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['welcome']['project_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars($ui['welcome']['project_detail'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['welcome']['language_scope_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars($ui['welcome']['language_scope_detail'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>

                <form method="post" class="actions">
                    <input type="hidden" name="action" value="go_language">
                    <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['start_setup'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            <?php endif; ?>

            <?php if ($state['step'] === 'language'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['language']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['language']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <form method="post">
                        <input type="hidden" name="action" value="save_language">
                        <div class="language-grid">
                            <?php foreach ($availableInstallerLanguages as $languageCode => $languageLabel): ?>
                                <label class="language-option">
                                    <input type="radio" name="installer_language" value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $state['installer_language'] === $languageCode ? 'checked' : ''; ?>>
                                    <span class="language-card"><?php echo htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="actions">
                            <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['continue'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="action" value="go_back_to_welcome">
                        <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($state['step'] === 'environment'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['environment']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['environment']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
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

            <?php if ($state['step'] === 'setup'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['setup']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['setup']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <form method="post">
                        <input type="hidden" name="action" value="save_setup">
                        <div class="field">
                            <label for="project_name"><?php echo htmlspecialchars($ui['setup']['project_name_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <input id="project_name" name="project_name" type="text" value="<?php echo htmlspecialchars($state['project_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label for="project_default_language"><?php echo htmlspecialchars($ui['setup']['project_default_language_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <select id="project_default_language" name="project_default_language">
                                <?php foreach ($availableInstallerLanguages as $languageCode => $languageLabel): ?>
                                    <option value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $state['project_default_language'] === $languageCode ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label><?php echo htmlspecialchars($ui['setup']['sample_content_label'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="sample_content" value="no" <?php echo $state['sample_content'] === 'no' ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($ui['setup']['sample_content_no'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="sample_content" value="yes" <?php echo $state['sample_content'] === 'yes' ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($ui['setup']['sample_content_yes'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="notice"><?php echo htmlspecialchars($ui['setup']['sample_content_notice'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="actions">
                            <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['continue_to_review'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="action" value="go_back_to_environment">
                        <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($state['step'] === 'review'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($ui['review']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($ui['review']['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="summary-list">
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['review']['project_name_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars($state['project_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['review']['installer_language_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars(strtoupper($state['installer_language']), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['review']['project_default_language_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars(strtoupper($state['project_default_language']), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['review']['module_summary_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars(sprintf($ui['review']['module_summary_detail'], (int) $summary['required'], (int) $summary['optional'], (int) $summary['project_specific'], (int) $summary['sample_content_capable']), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-top">
                                <div class="summary-label"><?php echo htmlspecialchars($ui['review']['sample_content_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="summary-detail"><?php echo htmlspecialchars($state['sample_content'] === 'yes' ? $ui['review']['sample_content_enabled'] : $ui['review']['sample_content_disabled'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>

                <form method="post" class="actions">
                    <input type="hidden" name="action" value="run_install">
                    <button type="submit" class="button button-primary"><?php echo htmlspecialchars($ui['actions']['run_installer'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
                <form method="post" class="actions">
                    <input type="hidden" name="action" value="go_back_to_environment">
                    <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['back'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            <?php endif; ?>

            <?php if ($state['step'] === 'result'): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars(($result && !empty($result['success'])) ? $ui['result']['success_title'] : $ui['result']['failure_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars(($result && !empty($result['success'])) ? $ui['result']['success_body'] : $ui['result']['failure_body'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="meta">
                        <div>
                            <strong><?php echo htmlspecialchars($ui['result']['installed_at'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars(isset($result['installed_at']) ? $result['installed_at'] : NOW, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                    <div class="output-list">
                        <?php if ($result && !empty($result['output'])): ?>
                            <?php foreach ($result['output'] as $line): ?>
                                <div class="output-item"><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="output-item"><?php echo htmlspecialchars($ui['result']['no_output'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post" class="actions">
                    <input type="hidden" name="action" value="reset_setup">
                    <button type="submit" class="button button-secondary"><?php echo htmlspecialchars($ui['actions']['reset_state'], ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
