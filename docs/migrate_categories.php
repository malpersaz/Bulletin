<?php
/**
 * Rojname - Kategoriyan (mod_categori) ji DB'ya kevin bikişîne DB'ya nû (mod_category)
 * Vê pelê li hostingê bar bike û di gerokê (browser) de veke.
 */

// Agahiyên DB'ya kevin
$old_host = 'localhost';
$old_db   = 'd025b30b';
$old_user = 'd025b30b';
$old_pass = '******'; // Şîfreya DB'ya kevin binivîse

// Agahiyên DB'ya nû
$new_host = 'localhost';
$new_db   = 'd046988c';
$new_user = 'd046988c';
$new_pass = '******'; // Şîfreya DB'ya nû binivîse

try {
    // Girêdana DB'ya kevin (PDO)
    $pdoOld = new PDO("mysql:host=$old_host;dbname=$old_db;charset=utf8", $old_user, $old_pass);
    $pdoOld->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Girêdana DB'ya nû (PDO)
    $pdoNew = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Hemû kategoriyan ji tabloya kevin (mod_categori) bikişîne
    $sqlOld = "SELECT id, lang, ad, alt, sira, root, seo, title, keywords, aciklama, resim, user_id FROM mod_categori ORDER BY id ASC";
    $stmt = $pdoOld->query($sqlOld);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertedCount = 0;
    
    // Xerîta ID'yan (ID Map). Ev pir girîng e ji bo ew kategoriyên binî (subcategories).
    // Ji ber ku ID'yên nû otomotîk tên afirandin (mînak: piştî 14an), divê em bizanibin 
    // ka kategoriya mak a kevin, bû kîjan ID ya nû daku em pergalê tevlîhev nekin.
    $idMap = [];

    // array ya kategoriyên mabûyî
    $remaining = $categories;

    // Em vê loopê digerînin da ku pêşî mak (parent) werin zêdekirin, piştre jî zarokên wê.
    while (count($remaining) > 0) {
        $insertedInThisPass = false;

        foreach ($remaining as $key => $cat) {
            $oldId = $cat['id'];
            $oldAlt = $cat['alt']; // parent_id ya kevin

            $canInsert = false;
            $newParentId = 4; // Li gorî daxwaza te, esasa Kurdî (ku) nirxê wê dîrekt bûye 4.

            // Heke "alt == 0" be wate ev berê mak bû. Niha dibe binkategoriya zimanê Kurdî (4)
            if ($oldAlt == 0) {
                $canInsert = true;
                $newParentId = 4;
            } 
            // Heke na, anku kategoriya jêrîn be, binêre ka maka wê (parent) li DB'ya nû zêde bû ye?
            elseif (isset($idMap[$oldAlt])) {
                $canInsert = true;
                $newParentId = $idMap[$oldAlt]; // newParentId êdî ID'ya nû ya maka wî digire!
            }

            if ($canInsert) {
                // Root status -> 'e' (evet) -> 'y' (yes), nexwe 'n' (no)
                $rootStatus = 'n';
                if ($cat['root'] == 'e') {
                    $rootStatus = 'y';
                }

                $sqlInsert = "INSERT INTO mod_category (
                    lang, name, parent_id, `order`, root, seo, title, 
                    keywords, description, image, menu, acl_id, user_id
                ) VALUES (
                    :lang, :name, :parent_id, :order, :root, :seo, :title, 
                    :keywords, :description, :image, :menu, :acl_id, :user_id
                )";
                
                $insertStmt = $pdoNew->prepare($sqlInsert);
                $insertStmt->execute([
                    ':lang'        => 'ku', // Te xwest tenê (ku) bin
                    ':name'        => $cat['ad'],
                    ':parent_id'   => $newParentId,
                    ':order'       => $cat['sira'],
                    ':root'        => $rootStatus,
                    ':seo'         => $cat['seo'] ?? '',
                    ':title'       => $cat['title'] ?? '',
                    ':keywords'    => $cat['keywords'] ?? '',
                    ':description' => $cat['aciklama'] ?? '',
                    ':image'       => $cat['resim'] ?? null,
                    ':menu'        => 0, // di wêneyê DB ya te ya nû de '0' ye
                    ':acl_id'      => 0, // di wêneyê DB ya te ya nû de '0' ye
                    ':user_id'     => $cat['user_id']
                ]);

                // Em ID'ya afirandî ya nû vedişêrin da ku ew kategoriyên binî wêk bikarbînin.
                $idMap[$oldId] = $pdoNew->lastInsertId();
                $insertedCount++;
                
                unset($remaining[$key]);
                $insertedInThisPass = true;
            }
        }

        // Heke em gihiştin rewşekê ku tu categoryek teza nikare bê zêdekirin 
        // (mînak, oldAlt destnîşan dike IDyek ku êdî jêbûye / tune ye, ew dimîne (orphan))
        // Em rasterast wan jî datînin jêr parent_id = 4.
        if (!$insertedInThisPass && count($remaining) > 0) {
            foreach ($remaining as $key => $cat) {
            
                $rootStatus = ($cat['root'] == 'e') ? 'y' : 'n';

                $sqlInsert = "INSERT INTO mod_category (
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
                    ':parent_id'   => 4, // orphans jî bo parent 4
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
                unset($remaining[$key]);
            }
            break; // qediya
        }
    }

    echo "<h3>Serkeftî: $insertedCount kategorî bi serkeftî hatin derbas kirin bo DB'ya nû û ID'yên xêzewî yên maka xwe parastin.</h3>";

} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . $e->getMessage() . "</p>";
}
?>
