<?php
/**
 * AKMbusiness - Nûçeyan (mod_haber) ji DB'ya kevin bikişîne DB'ya nû (mod_content)
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

    // QONAXA 1: Hemû nûçeyan (mod_haber) bikişîne
    $sqlOld = "SELECT id, lang, kat_id, ad, seo, giris, icerik, title, keywords, aciklama, 
               resim, spot, yayin, url, tarih, dtarih, hit, user_id, dosya_id, 
               son_dakika, objectif, gallery_id, acl_id, video 
               FROM mod_haber ORDER BY id ASC";
    
    $stmt = $pdoOld->query($sqlOld);
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertedCount = 0;

    foreach ($contents as $item) {
        
        // Hemû naverokan davêje bin kategoriya yekane (25) wek te xwest.
        $newCategoryId = 25;

        // Tespîtkirina demê (dîrok):
        // tarih (old) = created_at (new)
        // dtarih (old/on update target) = updated_at (new)
        $createdAt = empty($item['tarih']) 
                        ? '0000-00-00 00:00:00' 
                        : $item['tarih'];
                        
        $updatedAt = ($item['dtarih'] === '0000-00-00 00:00:00' || empty($item['dtarih'])) 
                        ? '0000-00-00 00:00:00' 
                        : $item['dtarih'];

        // Veguherîna enum: e -> y, h -> n
        // yayin -> status
        $status = ($item['yayin'] == 'e') ? 'y' : 'n';
        // spot -> deck 
        $deck = ($item['spot'] == 'e') ? 'y' : 'n';

        $search = ['&amp;#039;', '&amp;quot;', '&amp;amp;', '&#039;', '&quot;', '&#39;', '&amp;', '&lt;', '&gt;', '&#96;', '&amp;#96;'];
        $replace = ["'", '"', '&', "'", '"', "'", '&', '<', '>', "'", "'"];
        
        // Pirsira zêdekirinê bo DB'ya nû (stûnên tune ne: url, hit, son_dakika, objectif derketin)
        $sqlInsert = "INSERT IGNORE INTO mod_content (
            id, lang, name, category_id, status, created_at, updated_at, 
            deck, summary, content, seo, title, keywords, description, 
            gallery_id, file_id, image, acl_id, video, user_id
        ) VALUES (
            :id, :lang, :name, :category_id, :status, :created_at, :updated_at, 
            :deck, :summary, :content, :seo, :title, :keywords, :description, 
            :gallery_id, :file_id, :image, :acl_id, :video, :user_id
        )";
        
        $insertStmt = $pdoNew->prepare($sqlInsert);
        $insertStmt->execute([
            ':id'          => $item['id'], // Em heman ID'yê diparêzin!!
            ':lang'        => ($item['lang'] == '') ? 'ku' : $item['lang'],
            ':name'        => str_replace($search, $replace, $item['ad'] ?? ''),
            ':category_id' => $newCategoryId,
            ':status'      => $status,
            ':created_at'  => $createdAt,
            ':updated_at'  => $updatedAt,
            ':deck'        => $deck,
            ':summary'     => str_replace($search, $replace, $item['giris'] ?? ''),
            ':content'     => str_replace($search, $replace, $item['icerik'] ?? ''),
            ':seo'         => $item['seo'],
            ':title'       => str_replace($search, $replace, $item['title'] ?? ''),
            ':keywords'    => str_replace($search, $replace, $item['keywords'] ?? ''),
            ':description' => str_replace($search, $replace, $item['aciklama'] ?? ''),
            ':gallery_id'  => $item['gallery_id'],
            ':file_id'     => $item['dosya_id'], // dosya_id -> file_id
            ':image'       => $item['resim'],
            ':acl_id'      => $item['acl_id'],
            ':video'       => $item['video'],
            ':user_id'     => $item['user_id']
        ]);

        // Ger serkeftî be (MySQL INSERT IGNORE heke duplicate be bypass dike)
        if ($insertStmt->rowCount() > 0) {
            $insertedCount++;
        }
    }

    echo "<h3>Serkeftî: $insertedCount nûçe (mod_haber) hatin kişandin bo tabloya mod_content a nû.</h3>";
    echo "<p>Nîşe: Stûnên wekî <em>hit, son_dakika, objectif</em> ji ber daxwaza te li derve hatin hiştin. Herwiha nasnameya ID'yên nûçeyan sedî-sed hatin parastin da ku lînkên te xira nebin!</p>";

} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . $e->getMessage() . "</p>";
}
?>
