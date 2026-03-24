<?php
/**
 * AKMbusiness - Kategoriyan (mod_kategori) ji DB'ya kevin bikişîne DB'ya nû (mod_news_category)
 * Vê pelê li hostingê bar bike û di gerokê (browser) de veke.
 */

// Agahiyên DB'ya kevin
$old_host = 'localhost';
$old_db   = 'd025b30b';
$old_user = 'd025b30b';
$old_pass = 'B07E8R#p4(7@Xx+q_z3S'; // Şîfreya DB'ya kevin binivîse

// Agahiyên DB'ya nû
$new_host = 'localhost';
$new_db   = 'd046988c';
$new_user = 'd046988c';
$new_pass = 'rA,D!U8(iMU1@87nN9ov'; // Şîfreya DB'ya nû binivîse

try {
    // Girêdana DB'ya kevin (PDO)
    $pdoOld = new PDO("mysql:host=$old_host;dbname=$old_db;charset=utf8", $old_user, $old_pass);
    $pdoOld->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Girêdana DB'ya nû (PDO)
    $pdoNew = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Hemû kategoriyan ji tabloya kevin (mod_kategori) bikişîne
    $sqlOld = "SELECT id, lang, ad, alt, sira, root, seo, title, keywords, aciklama, resim, user_id FROM mod_kategori ORDER BY id ASC";
    $stmt = $pdoOld->query($sqlOld);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertedCount = 0;
    
    foreach ($categories as $cat) {
        $rootStatus = ($cat['root'] == 'e') ? 'y' : 'n';

        $sqlInsert = "INSERT INTO mod_news_category (
            lang, name, parent_id, `order`, root, seo, title,
            keywords, description, image, menu, acl_id, user_id
        ) VALUES (
            :lang, :name, :parent_id, :order, :root, :seo, :title,
            :keywords, :description, :image, :menu, :acl_id, :user_id
        )";

        $insertStmt = $pdoNew->prepare($sqlInsert);
        $insertStmt->execute([
            ':lang'        => 'ku',
            ':name'        => $cat['ad'],
            ':parent_id'   => 4,
            ':order'       => $cat['sira'],
            ':root'        => $rootStatus,
            ':seo'         => $cat['seo'] ?? '',
            ':title'       => $cat['title'] ?? '',
            ':keywords'    => $cat['keywords'] ?? '',
            ':description' => $cat['aciklama'] ?? '',
            ':image'       => $cat['resim'] ?? null,
            ':menu'        => 0,
            ':acl_id'      => 0,
            ':user_id'     => $cat['user_id']
        ]);

        $insertedCount++;
    }

    echo "<h3>Serkeftî: $insertedCount kategorî bi serkeftî hatin derbas kirin bo DB'ya nû. Hemû kategoriyan bi parent_id = 4 hatin tomar kirin.</h3>";

} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . $e->getMessage() . "</p>";
}
?>
