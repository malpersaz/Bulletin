<?php
/**
 * Rojname - Ji bo serastkirina dîrokên nûçeyan
 * Tenê carekê li ser gerokê bixebitîne daku created_at û updated_at paşvegerin rastiya xwe.
 */

// Agahiyên DB'ya kevin
$old_host = 'localhost';
$old_db   = 'd025b30b';
$old_user = 'd025b30b';
$old_pass = '******'; // Şîfreya DB'ya kevin

// Agahiyên DB'ya nû
$new_host = 'localhost';
$new_db   = 'd046988c';
$new_user = 'd046988c';
$new_pass = '******'; // Şîfreya DB'ya nû

try {
    $pdoOld = new PDO("mysql:host=$old_host;dbname=$old_db;charset=utf8", $old_user, $old_pass);
    $pdoOld->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdoNew = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Dîrokên orîjînal ên tabloya kevin bikişîne
    $sqlOld = "SELECT id, tarih, dtarih FROM mod_haber";
    $oldNews = $pdoOld->query($sqlOld)->fetchAll(PDO::FETCH_ASSOC);

    $fixedCount = 0;
    $updateStmt = $pdoNew->prepare("UPDATE mod_content SET created_at = :created_at, updated_at = :updated_at WHERE id = :id");

    foreach ($oldNews as $item) {
        $createdAt = empty($item['tarih']) 
                        ? '0000-00-00 00:00:00' 
                        : $item['tarih'];
                        
        $updatedAt = ($item['dtarih'] === '0000-00-00 00:00:00' || empty($item['dtarih'])) 
                        ? '0000-00-00 00:00:00' 
                        : $item['dtarih'];

        $updateStmt->execute([
            ':created_at' => $createdAt,
            ':updated_at' => $updatedAt,
            ':id'         => $item['id']
        ]);
        $fixedCount++;
    }

    echo "<h3>Serkeftî: Dîrokên $fixedCount nûçeyan hatin rastkirin.</h3>";
    echo "<p>Niha <code>tarih</code> -> <code>created_at</code> heye. U qeydên nehatî guhertin <code>dtarih(0000-00-00)</code> -> <code>NULL updated_at</code> bûn. Bi vî awayî dîrokên nûçeyên te resen man!</p>";

} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . $e->getMessage() . "</p>";
}
?>
