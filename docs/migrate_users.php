<?php
/**
 * Rojname - Bikarhêneran ji DB'ya kevin bikişîne DB'ya nû
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

    // Hemû bikarhênerên ne 'admin' ji DB'ya kevin bikişîne
    $sqlOld = "SELECT id, group_id, durum, ad, soyad, cep, dtarih, utarih, username, password, email, sid, ip, resim, yer, kose_adi, hakkinda FROM mod_user WHERE username != 'admin'";
    $stmt = $pdoOld->query($sqlOld);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertedCount = 0;

    foreach ($users as $user) {
        // Rewşa (durum) bikarhêneran ji ya kevin (e, h, a) li ya nû (y, n, a) biguherîne
        $status = 'n';
        if ($user['durum'] == 'e') {
            $status = 'y'; // evet -> yes
        } elseif ($user['durum'] == 'h') {
            $status = 'n'; // hayır -> no
        } elseif ($user['durum'] == 'a') {
            $status = 'a'; // aktif / alıkonulmuş (di nû de jî a ye)
        }

        // Dîrokan serast bike (date -> datetime)
        $updated_at = null;
        if (!empty($user['dtarih']) && $user['dtarih'] !== '0000-00-00') {
            $updated_at = $user['dtarih'] . ' 00:00:00';
        }

        // Pirsira (query) lêzêdekirinê ji bo DB'ya nû
        $sqlInsert = "INSERT IGNORE INTO mod_user (
            id, group_id, status, name, last_name, phone, 
            updated_at, created_at, username, password, email, 
            sid, ip, image, city, blog_name, about
        ) VALUES (
            :id, :group_id, :status, :name, :last_name, :phone, 
            :updated_at, :created_at, :username, :password, :email, 
            :sid, :ip, :image, :city, :blog_name, :about
        )";

        $insertStmt = $pdoNew->prepare($sqlInsert);
        $insertStmt->execute([
            ':id'         => $user['id'],
            ':group_id'   => $user['group_id'],
            ':status'     => $status,
            ':name'       => $user['ad'],
            ':last_name'  => $user['soyad'],
            ':phone'      => $user['cep'],
            ':updated_at' => $updated_at,
            ':created_at' => $user['utarih'],
            ':username'   => $user['username'],
            ':password'   => $user['password'],
            ':email'      => $user['email'],
            ':sid'        => $user['sid'] ?? null,
            ':ip'         => $user['ip'] ?? '',
            ':image'      => $user['resim'] ?? '',
            ':city'       => $user['yer'] ?? '',
            ':blog_name'  => $user['kose_adi'] ?? '',
            ':about'      => $user['hakkinda'] ?? ''
        ]);

        $insertedCount++;
    }

    echo "<h3>Serkeftî: $insertedCount bikarhêner hatin kişandin bo DB'ya nû.</h3>";

} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . $e->getMessage() . "</p>";
}
?>
