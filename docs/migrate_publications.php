<?php
/**
 * Rojname - Weşanên kevn (mod_makale) ji DB'ya kevn bikişîne DB'ya nû (mod_publication)
 * Vê pelê li hostingê bar bike û di gerokê (browser) de veke.
 *
 * Nîşe:
 * - Karakter/punctuation temizliği ji migrate_news.php hat girtin.
 * - Kategorî eşleştirmesi rast bi rast old kat_id -> new category_id map bi destan hatiye dayîn.
 */

// Agahiyên DB'ya kevin
$old_host = 'localhost';
$old_db   = 'old_database';
$old_user = 'old_user';
$old_pass = '******';

// Agahiyên DB'ya nû
$new_host = 'localhost';
$new_db   = 'new_database';
$new_user = 'new_user';
$new_pass = '******';

$categoryMap = [
    1  => 8,
    2  => 27,
    3  => 9,
    4  => 10,
    5  => 11,
    6  => 12,
    7  => 13,
    8  => 14,
    9  => 15,
    10 => 16,
    11 => 17,
    12 => 18,
    13 => 19,
    14 => 20,
    15 => 21,
    16 => 22,
    17 => 23,
    18 => 24,
    19 => 25,
    20 => 26,
];

try {
    $pdoOld = new PDO("mysql:host=$old_host;dbname=$old_db;charset=utf8", $old_user, $old_pass);
    $pdoOld->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdoNew = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $search = ['&amp;#039;', '&amp;quot;', '&amp;amp;', '&#039;', '&quot;', '&#39;', '&amp;', '&lt;', '&gt;', '&#96;', '&amp;#96;'];
    $replace = ["'", '"', '&', "'", '"', "'", '&', '<', '>', "'", "'"];

    $sqlOld = "SELECT id, lang, kat_id, ad, seo, giris, icerik, title, keywords, aciklama,
                      yayin, tarih, dtarih, hit, user_id, son_dakika, objectif, spot,
                      gallery_id, dosya_id, resim, acl_id, video
               FROM mod_makale
               ORDER BY id ASC";
    $stmt = $pdoOld->query($sqlOld);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertedCount = 0;
    $missingCategoryCount = 0;
    $missingCategoryIds = [];

    $sqlInsert = "INSERT IGNORE INTO mod_publication (
        id, lang, name, category_id, status, created_at, updated_at,
        deck, summary, content, seo, title, keywords, description,
        gallery_id, file_id, image, acl_id, video, user_id
    ) VALUES (
        :id, :lang, :name, :category_id, :status, :created_at, :updated_at,
        :deck, :summary, :content, :seo, :title, :keywords, :description,
        :gallery_id, :file_id, :image, :acl_id, :video, :user_id
    )";
    $insertStmt = $pdoNew->prepare($sqlInsert);

    foreach ($publications as $item) {
        $lang = trim((string) $item['lang']) ?: 'ku';
        $oldCategoryId = (int) $item['kat_id'];
        if (!isset($categoryMap[$oldCategoryId])) {
            $missingCategoryCount++;
            $missingCategoryIds[$oldCategoryId] = true;
            continue;
        }
        $newCategoryId = (int) $categoryMap[$oldCategoryId];

        $createdAt = empty($item['tarih']) ? '0000-00-00 00:00:00' : $item['tarih'];
        $updatedAt = ($item['dtarih'] === '0000-00-00 00:00:00' || empty($item['dtarih']))
            ? null
            : $item['dtarih'];

        $status = ($item['yayin'] == 'e') ? 'y' : 'n';
        $deck = ($item['spot'] == 'e') ? 'y' : 'n';

        $insertStmt->execute([
            ':id'          => (int) $item['id'],
            ':lang'        => $lang,
            ':name'        => str_replace($search, $replace, $item['ad'] ?? ''),
            ':category_id' => $newCategoryId,
            ':status'      => $status,
            ':created_at'  => $createdAt,
            ':updated_at'  => $updatedAt,
            ':deck'        => $deck,
            ':summary'     => str_replace($search, $replace, $item['giris'] ?? ''),
            ':content'     => str_replace($search, $replace, $item['icerik'] ?? ''),
            ':seo'         => trim((string) ($item['seo'] ?? '')),
            ':title'       => str_replace($search, $replace, $item['title'] ?? ''),
            ':keywords'    => str_replace($search, $replace, $item['keywords'] ?? ''),
            ':description' => str_replace($search, $replace, $item['aciklama'] ?? ''),
            ':gallery_id'  => !empty($item['gallery_id']) ? (int) $item['gallery_id'] : 0,
            ':file_id'     => !empty($item['dosya_id']) ? (int) $item['dosya_id'] : 0,
            ':image'       => !empty($item['resim']) ? $item['resim'] : null,
            ':acl_id'      => isset($item['acl_id']) ? (int) $item['acl_id'] : 0,
            ':video'       => !empty($item['video']) ? $item['video'] : null,
            ':user_id'     => !empty($item['user_id']) ? (int) $item['user_id'] : 1,
        ]);

        if ($insertStmt->rowCount() > 0) {
            $insertedCount++;
        }
    }

    echo "<h3>Serkeftî: $insertedCount weşan (mod_makale) hatin kişandin bo tabloya mod_publication a nû.</h3>";
    echo "<p>Kategorî eşleştirmesi: manuel old kat_id -> new category_id map bi serkeftî hate bikaranîn.</p>";
    echo "<p>Map de nehatî dîtin old kat_id sebebiyle nehatiye kişandin rekord sayısı: $missingCategoryCount</p>";
    echo "<p>Nîşe: Stûnên wekî <em>hit, son_dakika, objectif</em> ji ber ku di tabloya nû de tune ne, hatin derve hiştin. ID'yên weşanan sedî-sed hatin parastin.</p>";

    if (!empty($missingCategoryIds)) {
        echo "<h4>Map de olmayan old kat_id değerleri</h4><ul>";
        foreach (array_keys($missingCategoryIds) as $oldId) {
            echo "<li>old kat_id=" . (int) $oldId . "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}
?>
