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

$fixedParentId = 4;

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

    $insertSql = "INSERT INTO mod_publication_category (
        lang, name, parent_id, `order`, root, seo, title, keywords, description, image, menu, acl_id, user_id
    ) VALUES (
        :lang, :name, :parent_id, :order, :root, :seo, :title, :keywords, :description, :image, :menu, :acl_id, :user_id
    )";
    $insertStmt = $pdoNew->prepare($insertSql);

    $idMap = [];
    $remaining = $categories;
    $insertedCount = 0;

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

            if ($oldParentId === 0 || $isRoot) {
                $canInsert = true;
                $newParentId = $fixedParentId;
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
                ':root'        => 'n',
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

            $insertStmt->execute([
                ':lang'        => $lang,
                ':name'        => $cat['ad'],
                ':parent_id'   => $fixedParentId,
                ':order'       => (int) $cat['sira'],
                ':root'        => 'n',
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
    echo '<p>Tüm ana kategoriler sabit olarak parent_id=' . $fixedParentId . ' altına bağlandı.</p>';
} catch (PDOException $e) {
    if (isset($pdoNew) && $pdoNew instanceof PDO && $pdoNew->inTransaction()) {
        $pdoNew->rollBack();
    }

    echo '<h3>Çewtî derket</h3><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
