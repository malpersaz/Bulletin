<?php
/**
 * AKMbusiness - Ji bo guhastina nîşaneyên wek &#039; ber bi ' de
 * Tenê carekê li ser gerokê bixebitîne daku nûçeyên te safî bibin.
 */

$new_host = 'localhost';
$new_db   = 'd046988c';
$new_user = 'd046988c';
$new_pass = '******'; // Şîfreya DB'ya nû binivîse

try {
    $pdoNew = new PDO("mysql:host=$new_host;dbname=$new_db;charset=utf8", $new_user, $new_pass);
    $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Hemû nûçeyan bikişîne daku em navê wan yê şikestî rast bikin
    $stmt = $pdoNew->query("SELECT id, name, title, summary, content, description, keywords FROM mod_content");
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixedCount = 0;

    $updateStmt = $pdoNew->prepare("UPDATE mod_content SET name = :name, title = :title, summary = :summary, content = :content, description = :description, keywords = :keywords WHERE id = :id");

    $search = ['&amp;#039;', '&amp;quot;', '&amp;amp;', '&#039;', '&quot;', '&#39;', '&amp;', '&lt;', '&gt;', '&#96;', '&amp;#96;'];
    $replace = ["'", '"', '&', "'", '"', "'", '&', '<', '>', "'", "'"];

    foreach ($contents as $item) {
        $newName = str_replace($search, $replace, $item['name'] ?? '');
        $newTitle = str_replace($search, $replace, $item['title'] ?? '');
        $newSummary = str_replace($search, $replace, $item['summary'] ?? '');
        $newContent = str_replace($search, $replace, $item['content'] ?? '');
        $newDesc = str_replace($search, $replace, $item['description'] ?? '');
        $newKeywords = str_replace($search, $replace, $item['keywords'] ?? '');

        // Heke guherîn çêbûbe em di DB'yê de rast dikin:
        if ($newName !== $item['name'] || $newTitle !== $item['title'] || $newSummary !== $item['summary'] || $newContent !== $item['content'] || $newDesc !== $item['description'] || $newKeywords !== $item['keywords']) {
            $updateStmt->execute([
                ':name'  => $newName,
                ':title' => $newTitle,
                ':summary' => $newSummary,
                ':content' => $newContent,
                ':description' => $newDesc,
                ':keywords' => $newKeywords,
                ':id'    => $item['id']
            ]);
            $fixedCount++;
        }
    }

    echo "<h3>Serkeftî: $fixedCount nûçe hatin paqijkirin.</h3>";
    echo "<p>Nîşaneyên wek &amp;#039; bi serkeftî bûn nîşanên rast <b>'</b> û <b>\"</b>.</p>";

} catch (PDOException $e) {
    echo "<h3>Çewtî derket:</h3> <p>" . $e->getMessage() . "</p>";
}
?>
