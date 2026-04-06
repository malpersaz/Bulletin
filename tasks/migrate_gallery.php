<?php
/**
 * Veguhestina daneyên mod_gallery ji databaseke kevin bo ya nû.
 * Ev skrîpt tenê ji bo vî karî hatî amadekirin.
 */

// Agahiyên DB'ya kevin (Old DB)
$old_host = 'localhost';
$old_db   = 'd025b30b';
$old_user = 'd025b30b';
$old_pass = '******'; // Lira şîfreya DB'ya kevin binivîse

// Agahiyên DB'ya nû (New DB)
$new_host = 'localhost';
$new_db   = 'd046988c';
$new_user = 'd046988c';
$new_pass = '******'; // Lira şîfreya DB'ya nû binivîse

try {
    // Têkilî bi databasa kevin re
    $old_conn = new PDO("mysql:host=$old_host;dbname=$old_db;charset=utf8", $old_user, $old_pass);
    $old_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Têkilî bi DB'ya kevin re hat danîn.\n";

    // Têkilî bi databasa nû re
    $new_conn = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $new_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Têkilî bi DB'ya nû re hat danîn.\n";

    // Daneyên kevin bikişîne
    $sql_select = "SELECT * FROM mod_gallery";
    $stmt_select = $old_conn->query($sql_select);
    $old_data = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

    echo "Hejmara daneyên ku dê bên veguhestin: " . count($old_data) . "\n";

    // Daneyên nû lê zêde bike
    $sql_insert = "INSERT INTO mod_gallery (id, name, url, created_at, mod_name, vid, user_id) 
                   VALUES (:id, :name, :url, :created_at, :mod_name, :vid, :user_id)";
    $stmt_insert = $new_conn->prepare($sql_insert);

    $success_count = 0;
    $error_count = 0;

    foreach ($old_data as $row) {
        try {
            $stmt_insert->execute([
                ':id'         => $row['id'],
                ':name'       => $row['ad'], // Mapping: ad -> name
                ':url'        => $row['url'],
                ':created_at' => $row['tarih'], // Mapping: tarih -> created_at
                ':mod_name'   => $row['mod_name'],
                ':vid'        => $row['vid'],
                ':user_id'    => $row['user_id']
            ]);
            $success_count++;
        } catch (PDOException $e) {
            echo "Xeletî di rêzika ID " . $row['id'] . " de: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }

    echo "\n-----------------------------------\n";
    echo "Veguhestin qediya!\n";
    echo "Serkeftî: $success_count\n";
    echo "Xeletî: $error_count\n";
    echo "-----------------------------------\n";

} catch (PDOException $e) {
    die("Xeletiya Têkiliyê: " . $e->getMessage());
}
