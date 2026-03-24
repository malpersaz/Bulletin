<?php
/**
 * Rojname - mod_makalekat tabloya kevn ji DB'ya kevn bikişîne tabloya nû
 * mod_publication_category.
 *
 * Vê pelê li hostingê bar bike û di browser de bixebitîne.
 * Berî xebitandinê, jêr agahiyên DB'yan rast bike.
 */

$old_host = 'localhost';
$old_db   = 'old_database';
$old_user = 'old_user';
$old_pass = '******';

$new_host = 'localhost';
$new_db   = 'new_database';
$new_user = 'new_user';
$new_pass = '******';

/**
 * Heke li DB'ya nû kategoriya root ya ziman tune be, ev navan têne bikaranîn.
 */
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
        echo '<h3>Taşınacak kayıt bulunamadı.</h3>';
        exit;
    }

    $pdoNew->beginTransaction();

    $existingRoots = [];
    $rootStmt = $pdoNew->query("SELECT id, lang FROM mod_publication_category WHERE root = 'y' ORDER BY id ASC");
    foreach ($rootStmt->fetchAll(PDO::FETCH_ASSOC) as $rootRow) {
        if (!isset($existingRoots[$rootRow['lang']])) {
            $existingRoots[$rootRow['lang']] = (int) $rootRow['id'];
        }
    }

    $insertSql = "INSERT INTO mod_publication_category (
        lang, name, parent_id, `order`, root, seo, title, keywords, description, image, menu, acl_id, user_id
    ) VALUES (
        :lang, :name, :parent_id, :order, :root, :seo, :title, :keywords, :description, :image, :menu, :acl_id, :user_id
    )";
    $insertStmt = $pdoNew->prepare($insertSql);

    $ensureRootCategory = function ($lang) use ($pdoNew, &$existingRoots, $rootNames, $insertStmt) {
        if (isset($existingRoots[$lang])) {
            return $existingRoots[$lang];
        }

        $rootName = isset($rootNames[$lang]) ? $rootNames[$lang] : ('#' . strtoupper($lang));

        $insertStmt->execute([
            ':lang'        => $lang,
            ':name'        => $rootName,
            ':parent_id'   => 0,
            ':order'       => 1,
            ':root'        => 'y',
            ':seo'         => '#',
            ':title'       => '#',
            ':keywords'    => '#',
            ':description' => '#',
            ':image'       => null,
            ':menu'        => 0,
            ':acl_id'      => 0,
            ':user_id'     => 1,
        ]);

        $existingRoots[$lang] = (int) $pdoNew->lastInsertId();
        return $existingRoots[$lang];
    };

    $idMap = [];
    $remaining = $categories;
    $insertedCount = 0;
    $createdRootCount = 0;

    while (count($remaining) > 0) {
        $insertedInThisPass = false;

        foreach ($remaining as $key => $cat) {
            $oldId = (int) $cat['id'];
            $oldParentId = (int) $cat['alt'];
            $lang = trim((string) $cat['lang']);
            $isRoot = ($cat['root'] === 'e');

            $canInsert = false;
            $newParentId = 0;
            $newRootValue = $isRoot ? 'y' : 'n';

            if ($isRoot) {
                $canInsert = true;
                $newParentId = 0;
            } elseif ($oldParentId === 0) {
                $canInsert = true;
                $newParentId = $ensureRootCategory($lang);
                if (!isset($idMap['__root__' . $lang])) {
                    $createdRootCount++;
                    $idMap['__root__' . $lang] = $newParentId;
                }
            } elseif (isset($idMap[$oldParentId])) {
                $canInsert = true;
                $newParentId = $idMap[$oldParentId];
            }

            if (!$canInsert) {
                continue;
            }

            $insertStmt->execute([
                ':lang'        => $lang,
                ':name'        => $cat['ad'],
                ':parent_id'   => $newParentId,
                ':order'       => (int) $cat['sira'],
                ':root'        => $newRootValue,
                ':seo'         => $cat['seo'] ?? '',
                ':title'       => $cat['title'] ?? '',
                ':keywords'    => $cat['keywords'] ?? '',
                ':description' => $cat['aciklama'] ?? '',
                ':image'       => !empty($cat['resim']) ? $cat['resim'] : null,
                ':menu'        => 0,
                ':acl_id'      => 0,
                ':user_id'     => !empty($cat['user_id']) ? (int) $cat['user_id'] : 1,
            ]);

            $idMap[$oldId] = (int) $pdoNew->lastInsertId();
            $insertedCount++;
            $insertedInThisPass = true;
            unset($remaining[$key]);
        }

        if ($insertedInThisPass) {
            continue;
        }

        foreach ($remaining as $key => $cat) {
            $lang = trim((string) $cat['lang']);
            $isRoot = ($cat['root'] === 'e');
            $fallbackParentId = $isRoot ? 0 : $ensureRootCategory($lang);

            if (!$isRoot && !isset($idMap['__root__' . $lang])) {
                $createdRootCount++;
                $idMap['__root__' . $lang] = $fallbackParentId;
            }

            $insertStmt->execute([
                ':lang'        => $lang,
                ':name'        => $cat['ad'],
                ':parent_id'   => $fallbackParentId,
                ':order'       => (int) $cat['sira'],
                ':root'        => $isRoot ? 'y' : 'n',
                ':seo'         => $cat['seo'] ?? '',
                ':title'       => $cat['title'] ?? '',
                ':keywords'    => $cat['keywords'] ?? '',
                ':description' => $cat['aciklama'] ?? '',
                ':image'       => !empty($cat['resim']) ? $cat['resim'] : null,
                ':menu'        => 0,
                ':acl_id'      => 0,
                ':user_id'     => !empty($cat['user_id']) ? (int) $cat['user_id'] : 1,
            ]);

            $idMap[(int) $cat['id']] = (int) $pdoNew->lastInsertId();
            $insertedCount++;
            unset($remaining[$key]);
        }

        break;
    }

    $pdoNew->commit();

    echo '<h3>Serkeftî</h3>';
    echo '<p>Toplam ' . $insertedCount . ' kayıt mod_publication_category tablosuna taşındı.</p>';
    echo '<p>İhtiyaç halinde ' . count($existingRoots) . ' dil için root kategori kullanıldı/oluşturuldu.</p>';
    if ($createdRootCount > 0) {
        echo '<p>Migration sırasında otomatik root kategori oluşturulan dil sayısı: ' . $createdRootCount . '</p>';
    }
} catch (PDOException $e) {
    if (isset($pdoNew) && $pdoNew instanceof PDO && $pdoNew->inTransaction()) {
        $pdoNew->rollBack();
    }

    echo '<h3>Çewtî derket</h3><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
