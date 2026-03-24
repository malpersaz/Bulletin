<?php
/**
 * Dry-run migration preview:
 * mod_makalekat -> mod_publication_category
 *
 * Ev versiyon DB'ya write nake.
 * Tenê dide nîşandan ka kîjan qeyd çawa were barkirin.
 */

$old_host = 'localhost';
$old_db   = 'old_database';
$old_user = 'old_user';
$old_pass = '******';

$new_host = 'localhost';
$new_db   = 'new_database';
$new_user = 'new_user';
$new_pass = '******';

$rootNames = [
    'en'  => '#ENGLISH',
    'ku'  => '#KURDI',
    'ckb' => '#سۆرانی',
    'de'  => '#DEUTSCH',
    'fr'  => '#FRANCAIS',
    'sv'  => '#SVENSKA',
    'zza' => '#ZAZAKI',
];

try {
    $pdoOld = new PDO("mysql:host=$old_host;dbname=$old_db;charset=utf8", $old_user, $old_pass);
    $pdoOld->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdoNew = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqlOld = "SELECT id, lang, ad, alt, sira, root, seo, title, keywords, aciklama, resim, user_id
               FROM mod_makalekat
               ORDER BY id ASC";
    $stmt = $pdoOld->query($sqlOld);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$categories) {
        echo '<h3>Dry-run</h3><p>Taşınacak kayıt bulunamadı.</p>';
        exit;
    }

    $existingRoots = [];
    $rootStmt = $pdoNew->query("SELECT id, lang, name FROM mod_publication_category WHERE root = 'y' ORDER BY id ASC");
    foreach ($rootStmt->fetchAll(PDO::FETCH_ASSOC) as $rootRow) {
        if (!isset($existingRoots[$rootRow['lang']])) {
            $existingRoots[$rootRow['lang']] = [
                'id' => (int) $rootRow['id'],
                'name' => $rootRow['name'],
                'existing' => true,
            ];
        }
    }

    $virtualRootSeed = -1;
    $plannedRoots = [];
    $plannedRows = [];
    $idMap = [];
    $remaining = $categories;

    $ensureRootCategory = function ($lang) use (&$existingRoots, &$plannedRoots, &$virtualRootSeed, $rootNames) {
        if (isset($existingRoots[$lang])) {
            return $existingRoots[$lang]['id'];
        }

        if (isset($plannedRoots[$lang])) {
            return $plannedRoots[$lang]['id'];
        }

        $plannedRoots[$lang] = [
            'id' => $virtualRootSeed--,
            'lang' => $lang,
            'name' => isset($rootNames[$lang]) ? $rootNames[$lang] : ('#' . strtoupper($lang)),
            'existing' => false,
        ];

        return $plannedRoots[$lang]['id'];
    };

    while (count($remaining) > 0) {
        $plannedInThisPass = false;

        foreach ($remaining as $key => $cat) {
            $oldId = (int) $cat['id'];
            $oldParentId = (int) $cat['alt'];
            $lang = trim((string) $cat['lang']);
            $isRoot = ($cat['root'] === 'e');

            $canPlan = false;
            $newParentId = 0;
            $strategy = 'direct-root';

            if ($isRoot) {
                $canPlan = true;
                $newParentId = 0;
                $strategy = 'root-record';
            } elseif ($oldParentId === 0) {
                $canPlan = true;
                $newParentId = $ensureRootCategory($lang);
                $strategy = 'under-language-root';
            } elseif (isset($idMap[$oldParentId])) {
                $canPlan = true;
                $newParentId = $idMap[$oldParentId];
                $strategy = 'under-mapped-parent';
            }

            if (!$canPlan) {
                continue;
            }

            $virtualId = 100000 + count($plannedRows) + 1;
            $idMap[$oldId] = $virtualId;
            $plannedRows[] = [
                'old_id' => $oldId,
                'new_id' => $virtualId,
                'lang' => $lang,
                'name' => $cat['ad'],
                'old_parent_id' => $oldParentId,
                'new_parent_id' => $newParentId,
                'order' => (int) $cat['sira'],
                'root' => $isRoot ? 'y' : 'n',
                'strategy' => $strategy,
                'seo' => $cat['seo'] ?? '',
            ];
            unset($remaining[$key]);
            $plannedInThisPass = true;
        }

        if ($plannedInThisPass) {
            continue;
        }

        foreach ($remaining as $key => $cat) {
            $lang = trim((string) $cat['lang']);
            $isRoot = ($cat['root'] === 'e');
            $fallbackParentId = $isRoot ? 0 : $ensureRootCategory($lang);
            $virtualId = 100000 + count($plannedRows) + 1;

            $idMap[(int) $cat['id']] = $virtualId;
            $plannedRows[] = [
                'old_id' => (int) $cat['id'],
                'new_id' => $virtualId,
                'lang' => $lang,
                'name' => $cat['ad'],
                'old_parent_id' => (int) $cat['alt'],
                'new_parent_id' => $fallbackParentId,
                'order' => (int) $cat['sira'],
                'root' => $isRoot ? 'y' : 'n',
                'strategy' => $isRoot ? 'fallback-root-record' : 'orphan-to-language-root',
                'seo' => $cat['seo'] ?? '',
            ];
            unset($remaining[$key]);
        }

        break;
    }

    echo '<h2>Dry-run Migration Preview</h2>';
    echo '<p>Bu dosya veritabanına yazmaz. Sadece planlanan taşıma işlemini gösterir.</p>';
    echo '<p>Toplam kaynak kayıt: ' . count($categories) . '</p>';
    echo '<p>Planlanan taşınacak kayıt: ' . count($plannedRows) . '</p>';
    echo '<p>Yeni oluşturulacak root kategori sayısı: ' . count($plannedRoots) . '</p>';

    if (!empty($plannedRoots)) {
        echo '<h3>Oluşturulacak Root Kategoriler</h3>';
        echo '<table border="1" cellpadding="6" cellspacing="0">';
        echo '<tr><th>Virtual ID</th><th>Lang</th><th>Name</th></tr>';
        foreach ($plannedRoots as $root) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars((string) $root['id'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($root['lang'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($root['name'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    echo '<h3>Planlanan Kayıtlar</h3>';
    echo '<table border="1" cellpadding="6" cellspacing="0">';
    echo '<tr>
            <th>Old ID</th>
            <th>Virtual New ID</th>
            <th>Lang</th>
            <th>Name</th>
            <th>Old Parent</th>
            <th>New Parent</th>
            <th>Root</th>
            <th>Order</th>
            <th>Strategy</th>
            <th>SEO</th>
          </tr>';

    foreach ($plannedRows as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars((string) $row['old_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars((string) $row['new_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['lang'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars((string) $row['old_parent_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars((string) $row['new_parent_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['root'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars((string) $row['order'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['strategy'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['seo'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
} catch (PDOException $e) {
    echo '<h3>Dry-run Error</h3><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
