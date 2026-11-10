<?php
/**
 * Migration & Seeding Task: mod_event Semantic Taxonomy Disentanglement (v1.0.60)
 *
 * Implements 5 phases:
 * Phase 1: Idempotent Schema Alteration (mod_event)
 * Phase 2: Re-create events View
 * Phase 3: Category Taxonomy Synchronization (mod_event_category) & 301 Redirects (mod_redirect)
 * Phase 4: Re-classify Existing Event Records (id IN 1..44)
 * Phase 5: Seed Missing Memorial, Religious, Professional & Special Records
 *
 * Amed Framework / Bulletin CMS
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../init.php';

$db = new Database();

echo "========================================================\n";
echo " Starting mod_event Taxonomy & Holiday Migration (v1.0.60)\n";
echo "========================================================\n";

// =============================================================================
// PHASE 1: Idempotent Schema Alteration (mod_event)
// =============================================================================
echo "\n[Phase 1] Checking and applying mod_event table alterations...\n";

// 1. is_official_holiday
$db->fetch_array("SHOW COLUMNS FROM `mod_event` LIKE 'is_official_holiday'");
if ($db->getSize() === 0) {
    echo " -> Adding is_official_holiday column...\n";
    $db->query("ALTER TABLE `mod_event` ADD COLUMN `is_official_holiday` enum('y','n') NOT NULL DEFAULT 'n' AFTER `status`");
} else {
    echo " -> Column is_official_holiday already exists.\n";
}

// 2. holiday_duration
$db->fetch_array("SHOW COLUMNS FROM `mod_event` LIKE 'holiday_duration'");
if ($db->getSize() === 0) {
    echo " -> Adding holiday_duration column...\n";
    $db->query("ALTER TABLE `mod_event` ADD COLUMN `holiday_duration` decimal(3,1) NOT NULL DEFAULT 0.0 AFTER `is_official_holiday`");
} else {
    $row = $db->toArray()[0] ?? [];
    $type = strtolower((string)($row['Type'] ?? ''));
    if (strpos($type, 'decimal(3,1)') === false) {
        echo " -> Upgrading holiday_duration to decimal(3,1)...\n";
        $db->query("ALTER TABLE `mod_event` MODIFY COLUMN `holiday_duration` decimal(3,1) NOT NULL DEFAULT 0.0");
    } else {
        echo " -> Column holiday_duration already decimal(3,1).\n";
    }
}

// 3. is_recurring
$db->fetch_array("SHOW COLUMNS FROM `mod_event` LIKE 'is_recurring'");
if ($db->getSize() === 0) {
    echo " -> Adding is_recurring column...\n";
    $db->query("ALTER TABLE `mod_event` ADD COLUMN `is_recurring` enum('y','n') NOT NULL DEFAULT 'n' AFTER `holiday_duration`");
} else {
    echo " -> Column is_recurring already exists.\n";
}

// 4. Indexes
$db->fetch_array("SHOW INDEX FROM `mod_event` WHERE `Key_name` = 'idx_event_official_holiday'");
if ($db->getSize() === 0) {
    echo " -> Adding idx_event_official_holiday index...\n";
    $db->query("ALTER TABLE `mod_event` ADD INDEX `idx_event_official_holiday` (`is_official_holiday`, `start_date`)");
} else {
    echo " -> Index idx_event_official_holiday already exists.\n";
}

$db->fetch_array("SHOW INDEX FROM `mod_event` WHERE `Key_name` = 'idx_event_recurring'");
if ($db->getSize() === 0) {
    echo " -> Adding idx_event_recurring index...\n";
    $db->query("ALTER TABLE `mod_event` ADD INDEX `idx_event_recurring` (`is_recurring`)");
} else {
    echo " -> Index idx_event_recurring already exists.\n";
}

// =============================================================================
// PHASE 2: Re-create events View
// =============================================================================
echo "\n[Phase 2] Re-creating events SQL view...\n";

$db->query("DROP VIEW IF EXISTS `events`");
$sqlCreateView = "CREATE VIEW `events` AS
SELECT
  mod_event.id,
  mod_event.lang,
  mod_event.category_id AS category_id,
  mod_event.name,
  mod_event.subject,
  mod_event.start_date,
  mod_event.end_date,
  mod_event.status,
  mod_event.is_official_holiday,
  mod_event.holiday_duration,
  mod_event.is_recurring,
  mod_event.slider,
  mod_event.deck,
  mod_event.summary,
  mod_event.content,
  mod_event.location,
  mod_event.participants,
  mod_event.guests,
  mod_event.seo,
  mod_event.title,
  mod_event.keywords,
  mod_event.description,
  mod_event.image,
  mod_event.gallery_id AS gallery_id,
  mod_event.file_id AS file_id,
  mod_event.video,
  mod_event.acl_id AS acl_id,
  mod_event.view_count,
  mod_event.created_at,
  mod_event.updated_at,
  mod_event.user_id AS user_id,
  mod_event_category.name AS category_name,
  mod_event_category.name AS categoryname,
  mod_event_category.seo AS category_seo,
  mod_event_category.image AS category_image,
  mod_event_category.acl_id AS category_acl_id,
  mod_user.username,
  mod_user.name AS u_name,
  mod_user.last_name AS u_last_name,
  mod_user.email,
  mod_user.image AS user_image,
  mod_user_group.name AS group_name,
  (
    SELECT COUNT(mod_gallery.id)
    FROM mod_gallery
    WHERE mod_event.id = mod_gallery.vid
      AND mod_gallery.mod_name = 'mod_event'
  ) AS gallery_count
FROM mod_event
LEFT JOIN mod_user ON mod_event.user_id = mod_user.id
LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
LEFT JOIN mod_event_category ON mod_event.category_id = mod_event_category.id";

$db->query($sqlCreateView);
echo " -> View events successfully re-created.\n";

// =============================================================================
// PHASE 3: Category Taxonomy Synchronization & 301 Redirects
// =============================================================================
echo "\n[Phase 3] Synchronizing category taxonomy & 301 redirects...\n";

// Update Category 6: General Holidays
$db->query("UPDATE `mod_event_category` SET
    `name` = 'Genel Tatiller',
    `seo` = 'Genel_Tatiller',
    `title` = 'Genel Tatiller ve Resmi İdari Tatil Takvimi',
    `keywords` = 'genel tatiller, resmi tatil, yılbaşı tatili, 1 mayıs, 15 temmuz, arife tatili',
    `description` = '2429 sayılı Kanun kapsamındaki genel tatil günleri, arife yarım gün izinleri ve kamu tatil takvimi.',
    `order` = 6
WHERE `id` = 6");
echo " -> Category 6 updated to Genel Tatiller.\n";

// Update Category 7: National Holidays
$db->query("UPDATE `mod_event_category` SET
    `name` = 'Milli Bayramlar',
    `seo` = 'Milli_Bayramlar',
    `title` = 'Milli Bayramlar ve Ulusal Egemenlik Tören Takvimi',
    `keywords` = 'milli bayramlar, ulusal bayram, 29 ekim, 23 nisan, 19 mayıs, 30 ağustos',
    `description` = 'Cumhuriyet Bayramı, Ulusal Egemenlik ve Çocuk Bayramı, Gençlik ve Spor Bayramı ile Zafer Bayramı takvimi.',
    `order` = 7
WHERE `id` = 7");
echo " -> Category 7 updated to Milli Bayramlar.\n";

$categoriesToUpdate = [
    8 => [
        'id' => 8,
        'name' => 'Dini Günler & Bayramlar',
        'seo' => 'Dini_Günler_ve_Bayramlar',
        'order' => 8,
        'lang' => 'tr',
        'parent_id' => 0,
        'root' => 'n',
        'title' => 'Dini Günler, Kandiller ve Dini Bayramlar Takvimi',
        'keywords' => 'dini günler, dini bayramlar, ramazan bayramı, kurban bayramı, kandiller, kadir gecesi, aşure günü',
        'description' => 'Ramazan ve Kurban Bayramları, kandil geceleri, üç aylar, Kadir Gecesi ve Aşure Günü takvimi.',
        'user_id' => 1,
    ],
    9 => [
        'id' => 9,
        'name' => 'Anma Günleri',
        'seo' => 'Anma_Günleri',
        'order' => 9,
        'lang' => 'tr',
        'parent_id' => 0,
        'root' => 'n',
        'title' => 'Resmi ve Kültürel Anma Günleri Takvimi',
        'keywords' => 'anma günleri, 10 kasım, atatürk, 18 mart çanakkale, gaziler günü, ahmed arif, cahit sıtkı tarancı',
        'description' => '10 Kasım Atatürk\'ü Anma Günü, 18 Mart Çanakkale Şehitleri, Gaziler Günü ve Diyarbakır kültür-edebiyat anma günleri.',
        'user_id' => 1,
    ],
    10 => [
        'id' => 10,
        'name' => 'Önemli Günler & Haftalar',
        'seo' => 'Önemli_Günler_ve_Haftalar',
        'order' => 10,
        'lang' => 'tr',
        'parent_id' => 0,
        'root' => 'n',
        'title' => 'Önemli Günler, Kültürel Kutlamalar ve Farkındalık Haftaları',
        'keywords' => 'önemli günler, önemli haftalar, newroz, kadınlar günü, anneler günü, babalar günü, farkındalık',
        'description' => 'Newroz, Dünya Kadınlar Günü, Anneler ve Babalar Günü, çevre, insan hakları ve toplumsal farkındalık haftaları.',
        'user_id' => 1,
    ],
    11 => [
        'id' => 11,
        'name' => 'Mesleki Günler',
        'seo' => 'Mesleki_Günler',
        'order' => 11,
        'lang' => 'tr',
        'parent_id' => 0,
        'root' => 'n',
        'title' => 'Mesleki Günler ve Emek Kutlama Takvimi',
        'keywords' => 'mesleki günler, öğretmenler günü, çalışan gazeteciler, tıp bayramı, avukatlar günü, polis haftası',
        'description' => 'Öğretmenler Günü, Çalışan Gazeteciler Günü, Tıp Bayramı, Avukatlar Günü, Hemşireler ve Madenciler Günü takvimi.',
        'user_id' => 1,
    ],
];

foreach ($categoriesToUpdate as $catId => $cat) {
    $db->fetch_array("SELECT `id` FROM `mod_event_category` WHERE `id` = {$catId} OR (`seo` = '" . addslashes($cat['seo']) . "' AND `lang` = '{$cat['lang']}')");
    if ($db->getSize() > 0) {
        $existingId = (int) $db->toArray()[0]['id'];
        echo " -> Updating category #{$existingId} ({$cat['name']})...\n";
        $db->query("UPDATE `mod_event_category` SET
            `name` = '" . addslashes($cat['name']) . "',
            `seo` = '" . addslashes($cat['seo']) . "',
            `order` = {$cat['order']},
            `title` = '" . addslashes($cat['title']) . "',
            `keywords` = '" . addslashes($cat['keywords']) . "',
            `description` = '" . addslashes($cat['description']) . "'
        WHERE `id` = {$existingId}");
    } else {
        echo " -> Inserting category #{$catId} ({$cat['name']})...\n";
        $db->query("INSERT INTO `mod_event_category`
            (`id`, `lang`, `name`, `parent_id`, `order`, `root`, `seo`, `title`, `keywords`, `description`, `user_id`)
            VALUES
            ({$cat['id']}, '{$cat['lang']}', '" . addslashes($cat['name']) . "', {$cat['parent_id']}, {$cat['order']}, '{$cat['root']}', '" . addslashes($cat['seo']) . "', '" . addslashes($cat['title']) . "', '" . addslashes($cat['keywords']) . "', '" . addslashes($cat['description']) . "', {$cat['user_id']})");
    }
}

// 301 Redirects in mod_redirect
$redirects = [
    'tr/events/Resmi_Tatil' => 'tr/events/Genel_Tatiller',
    'events/Resmi_Tatil'    => 'tr/events/Genel_Tatiller',
    'tr/events/Bayramlar'   => 'tr/events/Milli_Bayramlar',
    'events/Bayramlar'      => 'tr/events/Milli_Bayramlar',
];

foreach ($redirects as $oldUrl => $newUrl) {
    $safeOld = addslashes($oldUrl);
    $safeNew = addslashes($newUrl);
    $db->query("INSERT INTO `mod_redirect` (`old_url`, `new_url`, `status_code`)
                VALUES ('{$safeOld}', '{$safeNew}', 301)
                ON DUPLICATE KEY UPDATE `new_url` = '{$safeNew}', `status_code` = 301");
    echo " -> Redirect synced: {$oldUrl} -> {$newUrl}\n";
}

// =============================================================================
// PHASE 4: Re-classify Existing Event Records (id IN 1..44)
// =============================================================================
echo "\n[Phase 4] Re-classifying existing production records (id IN 1..44)...\n";

// Guard: skip destructive id-range reclassification when the v1.0.60 taxonomy is
// already populated. Production ids shifted after the post-v1.0.60 re-seeding, so
// the historical id map (1..44) no longer identifies the rows it was written for.
$db->fetch_array("SELECT `id` FROM `mod_event` WHERE `category_id` IN (8, 9, 10, 11) LIMIT 1");
$taxonomyApplied = ($db->getSize() > 0);

if ($taxonomyApplied) {
    echo " -> [SKIP] New-taxonomy categories (8-11) already populated; id-range reclassification skipped (protects re-seeded data).\n";
} else {
    // 1. Religious Feasts -> Category 8
    // Ramadan Feast (id IN 7, 19) -> 3.0 days
    $db->query("UPDATE `mod_event` SET
        `category_id` = 8,
        `is_official_holiday` = 'y',
        `holiday_duration` = 3.0,
        `is_recurring` = 'y'
    WHERE `id` IN (7, 19)");
    echo " -> Reclassified Ramazan Bayramı records (id 7, 19) to Cat 8 (duration 3.0).\n";

    // Sacrifice Feast (id IN 10, 21) -> 4.0 days
    $db->query("UPDATE `mod_event` SET
        `category_id` = 8,
        `is_official_holiday` = 'y',
        `holiday_duration` = 4.0,
        `is_recurring` = 'y'
    WHERE `id` IN (10, 21)");
    echo " -> Reclassified Kurban Bayramı records (id 10, 21) to Cat 8 (duration 4.0).\n";

    // 2. National Holidays (id IN 8, 9, 11, 12, 20, 22, 23, 24) -> Category 7 (1.0 day)
    $db->query("UPDATE `mod_event` SET
        `category_id` = 7,
        `is_official_holiday` = 'y',
        `holiday_duration` = 1.0,
        `is_recurring` = 'y'
    WHERE `id` IN (8, 9, 11, 12, 20, 22, 23, 24)");
    echo " -> Reclassified National Holidays (id 8, 9, 11, 12, 20, 22, 23, 24) in Cat 7 (duration 1.0).\n";

    // 3. General Holidays full-day (id IN 1, 3, 5, 13, 15, 17) -> Category 6 (1.0 day)
    $db->query("UPDATE `mod_event` SET
        `category_id` = 6,
        `is_official_holiday` = 'y',
        `holiday_duration` = 1.0,
        `is_recurring` = 'y'
    WHERE `id` IN (1, 3, 5, 13, 15, 17)");
    echo " -> Reclassified General Holidays full-day (id 1, 3, 5, 13, 15, 17) in Cat 6 (duration 1.0).\n";

    // 4. Eve Half-Days (id IN 2, 4, 6, 14, 16, 18) -> Category 6 (0.5 day)
    $db->query("UPDATE `mod_event` SET
        `category_id` = 6,
        `is_official_holiday` = 'y',
        `holiday_duration` = 0.5,
        `is_recurring` = 'y'
    WHERE `id` IN (2, 4, 6, 14, 16, 18)");
    echo " -> Reclassified Arife half-days (id 2, 4, 6, 14, 16, 18) in Cat 6 (duration 0.5).\n";

    // 5. Nationwide Exams (id IN 25..44) -> Category 5 (is_official_holiday = 'n', duration 0.0)
    $db->query("UPDATE `mod_event` SET
        `category_id` = 5,
        `is_official_holiday` = 'n',
        `holiday_duration` = 0.0,
        `is_recurring` = 'y'
    WHERE `id` BETWEEN 25 AND 44");
    echo " -> Standardized Nationwide Exams (id 25..44) in Cat 5 (is_official_holiday = 'n').\n";
}

// =============================================================================
// PHASE 5: Seed Missing Memorial, Religious, Professional & Special Records
// =============================================================================
echo "\n[Phase 5] Seeding 27 new Memorial, Religious, Professional & Special records...\n";

$newEvents = [
    // -------------------------------------------------------------------------
    // Category 9: Memorial Days (7 Records)
    // -------------------------------------------------------------------------
    [
        'category_id' => 9,
        'name' => "10 Kasım Atatürk'ü Anma Günü 2026",
        'seo' => '10_Kasım_Atatürkü_Anma_Günü_2026',
        'subject' => "Gazi Mustafa Kemal Atatürk'ün Ebediyete İntikalinin 88. Yıl Dönümü Anma Programı",
        'start_date' => '2026-11-10 09:05:00',
        'end_date' => '2026-11-10 23:59:59',
        'deck' => 'y',
        'location' => 'Anıtkabir / Ankara ve Diyarbakır Anıt Park',
        'summary' => "Gazi Mustafa Kemal Atatürk'ün aramızdan ayrılışının 88. yıl dönümünde Diyarbakır ve Türkiye genelinde saat 09.05'te saygı duruşu ve anma törenleri icra edilecek.",
        'content' => "<h2>Gazi Mustafa Kemal Atatürk'ü Anma Günü</h2><p>Türkiye Cumhuriyeti'nin kurucusu Gazi Mustafa Kemal Atatürk'ün ebediyete irtihalinin 88. yıl dönümünde, saat 09.05'te sirenler eşliğinde saygı duruşunda bulunulacak, bayraklar yarıya indirilecektir.</p><p>Diyarbakır Valiliği koordinesinde Anıt Park'ta çelenk sunma töreni icra edilecek, ardından Cahit Sıtkı Tarancı Kültür Merkezi'nde anma oratoryosu ve sergiler düzenlenecektir.</p>",
    ],
    [
        'category_id' => 9,
        'name' => "10 Kasım Atatürk'ü Anma Günü 2027",
        'seo' => '10_Kasım_Atatürkü_Anma_Günü_2027',
        'subject' => "Gazi Mustafa Kemal Atatürk'ün Ebediyete İntikalinin 89. Yıl Dönümü Anma Programı",
        'start_date' => '2027-11-10 09:05:00',
        'end_date' => '2027-11-10 23:59:59',
        'deck' => 'y',
        'location' => 'Anıtkabir / Ankara ve Diyarbakır Anıt Park',
        'summary' => "Cumhuriyetimizin kurucusu Mustafa Kemal Atatürk'ün ebediyete irtihalinin 89. yıl dönümünde yurt genelinde ve Diyarbakır'da düzenlenecek resmi anma etkinlikleri programı.",
        'content' => "<h2>Cumhuriyetimizin Kurucusuna Saygı</h2><p>10 Kasım 2027 Çarşamba günü saat 09.05'te tüm yurtta hayat duracak, sirenler eşliğinde saygı duruşu ve İstiklal Marşı icra edilecektir.</p><p>Anma programı kapsamında okullarda, kültür merkezlerinde ve üniversitelerde Atatürk'ün fikirleri, devrimleri ve çağdaşlaşma vizyonu panellerle ele alınacaktır.</p>",
    ],
    [
        'category_id' => 9,
        'name' => '18 Mart Şehitleri Anma Günü ve Çanakkale Deniz Zaferi 2027',
        'seo' => '18_Mart_Şehitleri_Anma_Günü_ve_Çanakkale_Deniz_Zaferi_2027',
        'subject' => "18 Mart Şehitleri Anma Günü ve Çanakkale Zaferi'nin 112. Yıl Dönümü Törenleri",
        'start_date' => '2027-03-18 09:00:00',
        'end_date' => '2027-03-18 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Hava Şehitliği ve Kültür Merkezleri',
        'summary' => "Çanakkale Deniz Zaferi'nin 112. yıl dönümü ve 18 Mart Şehitleri Anma Günü vesilesiyle şehitliklerde ve kültür merkezlerinde anma programları düzenlenecektir.",
        'content' => "<h2>Çanakkale Deniz Zaferi'nin 112. Yıl Dönümü</h2><p>18 Mart 1915 Çanakkale Zaferi, milletimizin bağımsızlık ve hürriyet azminin abideleştiği tarihtir. Bu kutlu gün vesilesiyle vatan uğruna canlarını feda eden tüm aziz şehitlerimiz rahmet ve minnetle anılmaktadır.</p><p>Diyarbakır Hava Şehitliği'nde düzenlenecek resmi anma töreninde dualar edilecek, şehit kabirlerine karanfiller bırakılacaktır.</p>",
    ],
    [
        'category_id' => 9,
        'name' => '19 Eylül Gaziler Günü 2027',
        'seo' => '19_Eylül_Gaziler_Günü_2027',
        'subject' => '19 Eylül Gaziler Günü Resmi Törenleri ve Gazilere Saygı Buluşması',
        'start_date' => '2027-09-19 09:00:00',
        'end_date' => '2027-09-19 23:59:59',
        'deck' => 'n',
        'location' => 'Diyarbakır Valiliği ve Anıt Park',
        'summary' => 'Mustafa Kemal Atatürk\'e Mareşal rütbesi ve Gazi unvanı verilişinin yıl dönümünde kahraman gazilerimiz için Diyarbakır Valiliği öncülüğünde saygı programı düzenlenecektir.',
        'content' => "<h2>Kahraman Gazilerimize Sonsuz Saygı</h2><p>TBMM tarafından Gazi Mustafa Kemal Atatürk'e 'Mareşal' rütbesi ve 'Gazi' unvanı tevcih edildiği 19 Eylül günü, yurt genelinde 'Gaziler Günü' olarak idrak edilmektedir.</p><p>Etkinlikler kapsamında gazi dernekleri ziyaret edilecek, gazilerimiz ve şehit aileleri onuruna anma programları tertip edilecektir.</p>",
    ],
    [
        'category_id' => 9,
        'name' => "Ahmed Arif'i Anma Günü 2027 (Diyarbakır)",
        'seo' => 'Ahmed_Arifi_Anma_Günü_2027_Diyarbakır',
        'subject' => "Diyarbakırlı Şair Ahmed Arif'in Vefat Yıl Dönümü Edebi Anma Programı",
        'start_date' => '2027-06-02 10:00:00',
        'end_date' => '2027-06-02 22:00:00',
        'deck' => 'y',
        'location' => 'Ahmed Arif Edebiyat Müze Kütüphanesi / Sur, Diyarbakır',
        'summary' => "Diyarbakır'ın usta şairi Ahmed Arif'in 36. ölüm yıl dönümünde Sur ilçesindeki Ahmed Arif Edebiyat Müzesi'nde şiir dinletileri ve edebiyat panelleri gerçekleştirilecektir.",
        'content' => "<h2>Hasretinden Prangalar Eskittim Şairine Vefa</h2><p>'Hasretinden Prangalar Eskittim' kitabıyla Türk edebiyatında derin izler bırakan Diyarbakırlı şair Ahmed Arif, vefatının 36. yıl dönümünde doğup büyüdüğü kadim Sur kentinde anılmaktadır.</p><p>Sur Ahmed Arif Edebiyat Müze Kütüphanesi'nde gerçekleştirilecek anma gününde şairin el yazmaları sergilenecek ve şiir dinletisi düzenlenecektir.</p>",
    ],
    [
        'category_id' => 9,
        'name' => "Cahit Sıtkı Tarancı'yı Anma Günü 2027 (Diyarbakır)",
        'seo' => 'Cahit_Sıtkı_Tarancıyı_Anma_Günü_2027_Diyarbakır',
        'subject' => "Cahit Sıtkı Tarancı'nın Edebi Mirası ve Vefat Yıl Dönümü Anma Etkinliği",
        'start_date' => '2027-10-12 10:00:00',
        'end_date' => '2027-10-12 22:00:00',
        'deck' => 'y',
        'location' => 'Cahit Sıtkı Tarancı Kültür ve Sanat Müzesi / Sur, Diyarbakır',
        'summary' => "Otuz Beş Yaş şairi Cahit Sıtkı Tarancı'nın vefat yıl dönümü kapsamında Sur ilçesindeki tarihi müze evinde edebi söyleşiler ve şiir okuma etkinlikleri organize edilecek.",
        'content' => "<h2>Otuz Beş Yaş Şairi Diyarbakır'da Anılıyor</h2><p>Cumhuriyet dönemi Türk edebiyatının unutulmaz ismi, Diyarbakır doğumlu şair ve yazar Cahit Sıtkı Tarancı, 12 Ekim 1956'da aramızdan ayrılmıştı.</p><p>Tarancı'nın doğduğu tarihi Diyarbakır konağında düzenlenen anma programında, edebiyatçılar ve şiirseverler Tarancı'nın şiir dünyası ve Diyarbakır sevgisini paylaşacaktır.</p>",
    ],
    [
        'category_id' => 9,
        'name' => '6 Şubat Deprem Şehitlerini Anma Günü 2027',
        'seo' => '6_Şubat_Deprem_Şehitlerini_Anma_Günü_2027',
        'subject' => '6 Şubat Kahramanmaraş Depremlerinde Yitirdiğimiz Canları Anma Programı',
        'start_date' => '2027-02-06 04:17:00',
        'end_date' => '2027-02-06 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır ve Deprem Bölgesi İlleri',
        'summary' => "6 Şubat 2023 depremlerinin 4. yıl dönümünde Diyarbakır ve bölge genelinde hayatını kaybeden vatandaşlarımız için saat 04.17'de anma buluşmaları ve mevlid programları icra edilecektir.",
        'content' => "<h2>Asrın Felaketinde Yitirdiğimiz Canları Unutmadık</h2><p>6 Şubat 2023 tarihinde meydana gelen ve 11 ilimizi derinden sarsan büyük depremlerin 4. yıl dönümünde, vefat eden vatandaşlarımız rahmet ve dualarla anılmaktadır.</p><p>Diyarbakır'da yıkılan binaların anma alanlarında karanfiller bırakılacak, camilerde mevlid-i şerif okunarak afet bilinci vurgulanacaktır.</p>",
    ],

    // -------------------------------------------------------------------------
    // Category 8: Religious Days and Feasts - Non-Holiday Observances (7 Records)
    // -------------------------------------------------------------------------
    [
        'category_id' => 8,
        'name' => '2026 Regaip Kandili',
        'seo' => '2026_Regaip_Kandili',
        'subject' => 'Üç Ayların Müjdecisi Regaip Kandili Özel İbadet ve Dua Gecesi',
        'start_date' => '2026-12-10 18:00:00',
        'end_date' => '2026-12-10 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Ulu Camii ve Tüm Camiler',
        'summary' => 'Recep ayının ilk perşembe gecesini cumaya bağlayan Regaip Kandili vesilesiyle camilerde vaaz, mevlit ve dua programları düzenlenecek; manevi iklim paylaşılacaktır.',
        'content' => "<h2>Mübarek Üç Ayların İlk Kandili: Regaip</h2><p>Regaip Kandili, rahmet, mağfiret ve arınma mevsimi olan mübarek üç ayların (Recep, Şaban, Ramazan) başladığını müjdeleyen ilk kandil gecesidir.</p><p>Diyarbakır'ın 5. Harem-i Şerif'i kabul edilen tarihi Ulu Camii'nde yatsı namazı öncesinde özel kandil programı icra edilecektir.</p>",
    ],
    [
        'category_id' => 8,
        'name' => '2027 Miraç Kandili',
        'seo' => '2027_Miraç_Kandili',
        'subject' => 'Peygamber Efendimizin Göğe Yükselişi Miraç Kandili İbadet Programı',
        'start_date' => '2027-01-04 18:00:00',
        'end_date' => '2027-01-04 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Tarihi Camileri',
        'summary' => "İsra ve Miraç mucizesinin idrak edildiği mübarek Miraç Kandili'nde Diyarbakır Ulu Camii başta olmak üzere tarihi camilerde özel kandil programları icra edilecektir.",
        'content' => "<h2>İlahi Huzura Yükseliş: Miraç Gecesi</h2><p>Peygamberimiz Hz. Muhammed'in (s.a.v.) Mescid-i Haram'dan Mescid-i Aksa'ya, oradan da semaya yükseldiği kutlu yolculuk olan Miraç, namaz ibadetinin müminlere hediye edildiği gecedir.</p><p>Recep ayının 27. gecesi idrak edilen bu mübarek gecede dualar edilecek, Kur'an tilavetleri gerçekleştirilecektir.</p>",
    ],
    [
        'category_id' => 8,
        'name' => '2027 Berat Kandili',
        'seo' => '2027_Berat_Kandili',
        'subject' => 'Ramazan Ayının Müjdecisi Berat Kandili Arınma ve Dua Gecesi',
        'start_date' => '2027-01-22 18:00:00',
        'end_date' => '2027-01-22 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır ve Bölge Camileri',
        'summary' => 'Şaban ayının 15. gecesi idrak edilen Berat Kandili vesilesiyle günahlardan arınma, af ve mağfiret duaları eşliğinde camilerde kandil programları gerçekleştirilecektir.',
        'content' => "<h2>Günahlardan Berat ve Arınma Gecesi</h2><p>Berat Kandili, kulun ilahi rahmete sığınarak af ve mağfiret dilediği, Ramazan ayına sayılı günler kala manevi hazırlıkların zirveye ulaştığı gecedir.</p><p>Camilerimizde icra edilecek kandil programlarında birlik, kardeşlik ve barış için niyazda bulunulacaktır.</p>",
    ],
    [
        'category_id' => 8,
        'name' => '2027 Ramazan Ayı Başlangıcı',
        'seo' => '2027_Ramazan_Ayı_Başlangıcı',
        'subject' => 'On Bir Ayın Sultanı Ramazan-ı Şerif Ayının İlk Oruç Günü',
        'start_date' => '2027-02-08 00:00:00',
        'end_date' => '2027-02-08 23:59:59',
        'deck' => 'y',
        'location' => 'Yurt Geneli ve Diyarbakır',
        'summary' => 'Hicri 1448 yılı Ramazan ayının ilk orucu 8 Şubat 2027 Pazartesi günü tutulacak; iftar, teravih ve mukabele programları ile bereket ayı karşılanacaktır.',
        'content' => "<h2>On Bir Ayın Sultanı Ramazan Başlıyor</h2><p>Hicri 1448 yılının Ramazan ayı, 7 Şubat'ı 8 Şubat'a bağlayan gece ilk sahura kalkılmasıyla başlayacaktır. 8 Şubat 2027 Pazartesi günü ilk oruç açılacaktır.</p><p>Ramazan ayı boyunca Diyarbakır'ın tarihi sokaklarında iftar çadırları kurulacak, mukabeleler okunacak ve yardımlaşma seferberliği yaşanacaktır.</p>",
    ],
    [
        'category_id' => 8,
        'name' => '2027 Kadir Gecesi',
        'seo' => '2027_Kadir_Gecesi',
        'subject' => 'Bin Aydan Daha Hayırlı Kadir Gecesi İbadet ve İhya Programı',
        'start_date' => '2027-03-04 18:00:00',
        'end_date' => '2027-03-04 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Ulu Camii ve Şehitlikler',
        'summary' => "Kur'an-ı Kerim'in indirildiği bin aydan daha hayırlı Kadir Gecesi'nde Diyarbakır'ın ulu mabetlerinde sabaha kadar sürecek dua, hatim ve teheccüd buluşmaları organize edilecektir.",
        'content' => "<h2>Bin Aydan Hayırlı Mübarek Kadir Gecesi</h2><p>Kur'an-ı Kerim'in Levh-i Mahfuz'dan dünya semasına indirildiği Kadir Gecesi, Ramazan ayının 27. gecesi olarak idrak edilmektedir.</p><p>Diyarbakır genelinde camiler sabaha kadar açık kalacak, sakal-ı şerif ziyaretleri ve tesbih namazları ile bu kutlu gece ihya edilecektir.</p>",
    ],
    [
        'category_id' => 8,
        'name' => '2027 Aşure Günü (10 Muharrem)',
        'seo' => '2027_Aşure_Günü_10_Muharrem',
        'subject' => 'Hicri 1449 Muharrem Ayı ve Aşure Günü İkram ve Anma Etkinlikleri',
        'start_date' => '2027-06-15 00:00:00',
        'end_date' => '2027-06-15 23:59:59',
        'deck' => 'n',
        'location' => 'Diyarbakır Meydanları ve Aşevleri',
        'summary' => 'Muharrem ayının 10. günü idrak edilen Aşure Günü vesilesiyle birlik ve bereket sembolü aşure ikramları yapılacak, Kerbela şehitleri dualarla yad edilecektir.',
        'content' => "<h2>Bereketin ve Kerbela Hüznünün Günü: Aşure</h2><p>Hicri 1449 yılının Muharrem ayının 10. günü olan Aşure Günü, tarihte birçok mühim hadisenin ve Peygamber Efendimizin sevgili torunu Hz. Hüseyin ve Kerbela şehitlerinin anıldığı gündür.</p><p>Diyarbakır'ın çeşitli noktalarında kazanlar kaynatılarak vatandaşlara aşure dağıtılacaktır.</p>",
    ],
    [
        'category_id' => 8,
        'name' => '2027 Mevlid Kandili',
        'seo' => '2027_Mevlid_Kandili',
        'subject' => "Peygamber Efendimiz Hz. Muhammed'in Dünyaya Teşrifi Mevlid Gecesi",
        'start_date' => '2027-08-14 18:00:00',
        'end_date' => '2027-08-14 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Camileri ve Konferans Salonları',
        'summary' => "Peygamberimiz Hz. Muhammed'in (s.a.v.) veladetinin yıl dönümü olan Mevlid Kandili'nde camilerde mevlit merasimleri okunacak, kardeşlik ve barış duaları yükselecektir.",
        'content' => "<h2>Alemlere Rahmet Olarak Gönderilen Peygamberimizin Doğumu</h2><p>Rebiülevvel ayının 12. gecesi kutlanan Mevlid Kandili, Peygamberimiz Hz. Muhammed'in (s.a.v.) dünyayı teşrifini müjdeler.</p><p>Mevlid programlarında Süleyman Çelebi'nin Vesiletü'n-Necat mevlidi okunacak ve Peygamberimizin ahlakını anlatan sohbetler icra edilecektir.</p>",
    ],

    // -------------------------------------------------------------------------
    // Category 11: Professional Days (7 Records)
    // -------------------------------------------------------------------------
    [
        'category_id' => 11,
        'name' => '24 Kasım Öğretmenler Günü 2026',
        'seo' => '24_Kasım_Öğretmenler_Günü_2026',
        'subject' => 'Millet Mektepleri Başöğretmenliği ve 24 Kasım Öğretmenler Günü Kutlamaları',
        'start_date' => '2026-11-24 09:00:00',
        'end_date' => '2026-11-24 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır İl Milli Eğitim Müdürlüğü ve Okullar',
        'summary' => 'Gazi Mustafa Kemal Atatürk\'ün Başöğretmenliği kabul ettiği gün vesilesiyle fedakar öğretmenlerimizin günü il genelindeki okullarda ve kültür salonlarında törenlerle kutlanacak.',
        'content' => "<h2>Geleceğimizin Mimarı Öğretmenlerimize Şükran</h2><p>24 Kasım 1928'de Mustafa Kemal Atatürk'ün Millet Mektepleri Başöğretmenliğini kabul ettiği tarih, 1981'den bu yana Öğretmenler Günü olarak kutlanmaktadır.</p><p>Diyarbakır'da mesleğe yeni başlayan öğretmenlerin yemin töreni ve emekli öğretmenlere şeref belgeleri takdim edilecektir.</p>",
    ],
    [
        'category_id' => 11,
        'name' => '4 Aralık Dünya Madenciler Günü 2026',
        'seo' => '4_Aralık_Dünya_Madenciler_Günü_2026',
        'subject' => 'Yerin Yüzlerce Metre Altında Alın Teri Döken Maden Emekçilerini Anma Günü',
        'start_date' => '2026-12-04 09:00:00',
        'end_date' => '2026-12-04 23:59:59',
        'deck' => 'n',
        'location' => 'Madencilik Sendikaları ve Maden Sahaları',
        'summary' => 'Zorlu koşullarda yerin derinliklerinden değer üreten maden emekçilerimizin Dünya Madenciler Günü anılarak, maden kazalarında hayatını kaybeden şehit madencilerimiz anılacaktır.',
        'content' => "<h2>Yerin Yüzlerce Metre Altındaki Alın Teri</h2><p>Santa Barbara'ya atfedilen ve dünyada madencilerin dayanışma günü olarak kabul edilen 4 Aralık Dünya Madenciler Günü, emeğin en ağır şartlarda verildiği bir mesleğin günüdür.</p><p>Program kapsamında iş sağlığı ve güvenliği panelleri düzenlenecek, maden şehitlerimiz için anma törenleri yapılacaktır.</p>",
    ],
    [
        'category_id' => 11,
        'name' => '10 Ocak Çalışan Gazeteciler Günü 2027',
        'seo' => '10_Ocak_Çalışan_Gazeteciler_Günü_2027',
        'subject' => 'Basın Emekçilerinin Hakları ve 10 Ocak Çalışan Gazeteciler Günü Buluşması',
        'start_date' => '2027-01-10 09:00:00',
        'end_date' => '2027-01-10 23:59:59',
        'deck' => 'y',
        'location' => 'Güneydoğu Gazeteciler Cemiyeti (GGC) / Sümerpark, Diyarbakır',
        'summary' => "212 sayılı Basın İş Kanunu'nun yürürlüğe girdiği 10 Ocak Çalışan Gazeteciler Günü'nde Diyarbakır basını ve medya mensupları dayanışma panellerinde bir araya gelecektir.",
        'content' => "<h2>Basın Özgürlüğü ve Gazetecilerin Emeği</h2><p>10 Ocak 1961'de gazetecilerin çalışma haklarını yasal güvenceye bağlayan 212 sayılı kanunun kabul edilmesiyle kutlanmaya başlanan Çalışan Gazeteciler Günü, basın emekçilerinin hak mücadele günüdür.</p><p>Güneydoğu Gazeteciler Cemiyeti'nde yerel ve ulusal basın temsilcilerinin katılımıyla mesleki sorunlar ve etik habercilik oturumları gerçekleştirilecektir.</p>",
    ],
    [
        'category_id' => 11,
        'name' => '14 Mart Tıp Bayramı 2027',
        'seo' => '14_Mart_Tıp_Bayramı_2027',
        'subject' => 'Modern Tıp Eğitiminin Başlangıcı ve Sağlık Çalışanları Tıp Bayramı Töreni',
        'start_date' => '2027-03-14 09:00:00',
        'end_date' => '2027-03-14 23:59:59',
        'deck' => 'y',
        'location' => 'Dicle Üniversitesi Tıp Fakültesi ve Diyarbakır Tabip Odası',
        'summary' => "14 Mart 1827'de Mekteb-i Tıbbiye-i Şahane'nin kuruluşuyla başlayan modern tıp eğitiminin yıl dönümünde hekimler ve sağlık çalışanlarının mesleki bayramı törenlerle kutlanacaktır.",
        'content' => "<h2>Sağlık Kahramanlarımızın Tıp Bayramı</h2><p>14 Mart 1827'de II. Mahmut döneminde modern tıp eğitiminin temellerinin atıldığı gün, 1919'da İstanbul'un işgaline karşı tıbbiyelilerin direnişiyle milli bir ruha bürünmüştür.</p><p>Diyarbakır'daki sağlık kurumlarında hekimlerin ve sağlık personelinin özverili hizmetleri onurlandırılacak, mesleki başarı ödülleri verilecektir.</p>",
    ],
    [
        'category_id' => 11,
        'name' => '5 Nisan Avukatlar Günü 2027',
        'seo' => '5_Nisan_Avukatlar_Günü_2027',
        'subject' => 'Savunma Hakkı, Hukukun Üstünlüğü ve 5 Nisan Avukatlar Günü Programı',
        'start_date' => '2027-04-05 09:00:00',
        'end_date' => '2027-04-05 23:59:59',
        'deck' => 'n',
        'location' => 'Diyarbakır Barosu ve Diyarbakır Adliyesi',
        'summary' => "Adaletin temeli olan bağımsız savunmayı temsil eden avukatlarımızın mesleki dayanışma günü vesilesiyle Diyarbakır Barosu'nda konferans ve resepsiyonlar düzenlenecektir.",
        'content' => "<h2>Adaletin Teminatı Bağımsız Savunma</h2><p>5 Nisan 1958'de Türkiye Barolar Birliği'nin temellerinin atıldığı İzmir toplantısına atfen kabul edilen Avukatlar Günü, yurttaşların hak arama özgürlüğünün güvencesidir.</p><p>Diyarbakır Barosu bünyesinde insan hakları, adil yargılanma ve savunma dokunulmazlığı üzerine paneller düzenlenecektir.</p>",
    ],
    [
        'category_id' => 11,
        'name' => '10 Nisan Polis Haftası ve Türk Polis Teşkilatı Kuruluş Yıl Dönümü 2027',
        'seo' => '10_Nisan_Polis_Haftası_ve_Türk_Polis_Teşkilatı_Kuruluş_Yıl_Dönümü_2027',
        'subject' => "Türk Polis Teşkilatı'nın 182. Kuruluş Yıl Dönümü ve Polis Haftası Etkinlikleri",
        'start_date' => '2027-04-10 09:00:00',
        'end_date' => '2027-04-10 23:59:59',
        'deck' => 'n',
        'location' => 'Diyarbakır İl Emniyet Müdürlüğü ve Anıt Park',
        'summary' => "Huzur ve güvenliğimizin teminatı Türk Polis Teşkilatı'nın 182. kuruluş yıl dönümü kapsamında İl Emniyet Müdürlüğü tarafından resmi geçit ve halkla buluşma törenleri tertip edilecek.",
        'content' => "<h2>182 Yıllık Güven ve Huzur Teşkilatı</h2><p>10 Nisan 1845'te Polis Nizamnamesi ile kurulan Türk Polis Teşkilatı, kamu düzeni ve asayişin sağlanmasında 182 yıldır gece gündüz görev yapmaktadır.</p><p>Polis Haftası süresince şehit polislerimiz için mevlid okutulacak, okullarda trafik ve güvenlik eğitimleri verilecektir.</p>",
    ],
    [
        'category_id' => 11,
        'name' => '24 Kasım Öğretmenler Günü 2027',
        'seo' => '24_Kasım_Öğretmenler_Günü_2027',
        'subject' => 'Öğretmenler Günü 2027 Törenleri ve Eğitim Neferlerine Vefa Programı',
        'start_date' => '2027-11-24 09:00:00',
        'end_date' => '2027-11-24 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Kültür ve Kongre Merkezi',
        'summary' => 'Geleceğimizin mimarı öğretmenlerimizin 24 Kasım Öğretmenler Günü, Diyarbakır genelindeki eğitim kurumlarında mesleki kıdem ödülleri ve sanatsal etkinliklerle kutlanacaktır.',
        'content' => "<h2>Başöğretmen Atatürk'ün İzinde Fedakar Öğretmenler</h2><p>24 Kasım 2027 Çarşamba günü icra edilecek Öğretmenler Günü kutlamalarında, nesilleri bilgi ve ahlakla yetiştiren öğretmenlerimize milletçe şükran sunulacaktır.</p><p>İl genelinde dereceye giren öğretmenlere ödülleri takdim edilecek, şehit öğretmenlerimiz kabirleri başında anılacaktır.</p>",
    ],

    // -------------------------------------------------------------------------
    // Category 10: Special Observances and Weeks (6 Records)
    // -------------------------------------------------------------------------
    [
        'category_id' => 10,
        'name' => '3 Aralık Dünya Engelliler Günü 2026',
        'seo' => '3_Aralık_Dünya_Engelliler_Günü_2026',
        'subject' => 'Engelsiz Yaşam, Erişilebilirlik ve 3 Aralık Toplumsal Farkındalık Günü',
        'start_date' => '2026-12-03 09:00:00',
        'end_date' => '2026-12-03 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır Büyükşehir Belediyesi Engelli Destek Merkezi',
        'summary' => 'Engelli bireylerin toplumsal yaşama tam ve eşit katılımını desteklemek amacıyla kamu kurumları ve sivil toplum kuruluşları iş birliğiyle farkındalık sempozyumları ve spor gösterileri yapılacak.',
        'content' => "<h2>Engelleri Birlikte Aşıyoruz</h2><p>Birleşmiş Milletler tarafından 1992 yılında ilan edilen 3 Aralık Dünya Engelliler Günü, engelli bireylerin hakları, erişilebilirlik standartları ve toplumsal entegrasyonu konusunda küresel farkındalık günüdür.</p><p>Diyarbakır'da engelsiz yaşam atölyeleri, tekerlekli sandalye basketbol gösteri maçı ve resim sergileri organize edilecektir.</p>",
    ],
    [
        'category_id' => 10,
        'name' => '14 Şubat Sevgililer Günü 2027',
        'seo' => '14_Şubat_Sevgililer_Günü_2027',
        'subject' => '14 Şubat Dünya Sevgililer Günü Kültür ve Müzik Dinletisi Buluşmaları',
        'start_date' => '2027-02-14 00:00:00',
        'end_date' => '2027-02-14 23:59:59',
        'deck' => 'n',
        'location' => 'Diyarbakır Tarihi Sur Sokakları ve Kültür Mekanları',
        'summary' => "Sevgi ve bağlılığın simgesi 14 Şubat Sevgililer Günü'nde Diyarbakır'ın tarihi mekanlarında ve kafelerinde akustik konserler, hediye çarşıları ve özel etkinlikler gerçekleştirilecektir.",
        'content' => "<h2>Sevginin ve Hoşgörünün Günü</h2><p>Tüm dünyada sevgi, dostluk ve vefa duygularının tazelendiği 14 Şubat Sevgililer Günü vesilesiyle kadim Diyarbakır sokaklarında sanatsal ve müzikal dinletiler yer alacaktır.</p><p>Tarihi Hasan Paşa Hanı ve On Gözlü Köprü çevresinde sevgi temalı fotoğraf sergileri açılacaktır.</p>",
    ],
    [
        'category_id' => 10,
        'name' => '8 Mart Dünya Emekçi Kadınlar Günü 2027',
        'seo' => '8_Mart_Dünya_Emekçi_Kadınlar_Günü_2027',
        'subject' => 'Kadın Hakları, Toplumsal Eşitlik ve 8 Mart Kadınlar Günü Dayanışma Programı',
        'start_date' => '2027-03-08 09:00:00',
        'end_date' => '2027-03-08 23:59:59',
        'deck' => 'y',
        'location' => 'Diyarbakır İstasyon Meydanı ve Kültür Merkezleri',
        'summary' => "Kadınların sosyal, ekonomik ve siyasal haklarının savunulduğu 8 Mart Dünya Kadınlar Günü vesilesiyle Diyarbakır'da paneller, tiyatro gösterileri ve açık hava mitingleri düzenlenecektir.",
        'content' => "<h2>Emeğin, Eşitliğin ve Özgürlüğün Sesi Kadınlar</h2><p>8 Mart 1857'de New York'ta hakları için mücadele eden dokuma işçisi kadınların mirası olan Dünya Emekçi Kadınlar Günü, eşitlik ve adalet taleplerinin yükseltildiği gündür.</p><p>Diyarbakır'da kadın kooperatifleri ürün sergileri açacak, kadın emeği ve şiddete karşı mücadele panelleri gerçekleştirilecektir.</p>",
    ],
    [
        'category_id' => 10,
        'name' => '21 Mart Newroz Bayramı ve Bahar Ekinoksu 2027 (Diyarbakır)',
        'seo' => '21_Mart_Newroz_Bayramı_ve_Bahar_Ekinoksu_2027_Diyarbakır',
        'subject' => 'Baharın Müjdesi Newroz Ateşi ve Geleneksel Halk Kutlamaları',
        'start_date' => '2027-03-21 09:00:00',
        'end_date' => '2027-03-21 23:59:59',
        'deck' => 'y',
        'location' => 'Newroz Parkı / Bağlar, Diyarbakır',
        'summary' => "Baharın, yenilenmenin ve barışın simgesi Newroz Bayramı, Bağlar Newroz Parkı'nda yüz binlerin katılımıyla yakılacak Newroz ateşi ve müzik dinletileriyle coşkuyla kutlanacaktır.",
        'content' => "<h2>Doğanın Uyanışı ve Baharın Müjdesi: Newroz</h2><p>Mezopotamya ve kadim Ortadoğu coğrafyasında bin yıllardır baharın, dirilişin ve yeni günün habercisi olarak kutlanan Newroz Bayramı, 21 Mart ekinoksunda Diyarbakır Bağlar Newroz Parkı'nda büyük coşkuyla idrak edilecektir.</p><p>Geleneksel kıyafetler, yakılan Newroz meşaleleri, halaylar ve halk konserleri gün boyu devam edecektir.</p>",
    ],
    [
        'category_id' => 10,
        'name' => '2027 Anneler Günü (9 Mayıs Pazar)',
        'seo' => '2027_Anneler_Günü_9_Mayıs_Pazar',
        'subject' => 'Koşulsuz Sevginin Kaynağı Annelerimiz İçin Özel Vefa ve Kutlama Günü',
        'start_date' => '2027-05-09 00:00:00',
        'end_date' => '2027-05-09 23:59:59',
        'deck' => 'n',
        'location' => 'Tüm Diyarbakır ve Yurt Geneli',
        'summary' => 'Mayıs ayının ikinci pazar günü kutlanan Anneler Günü vesilesiyle hayatımızın en değerli rehberleri olan annelerimiz için sevgi buluşmaları ve tematik etkinlikler hayata geçirilecektir.',
        'content' => "<h2>Hayatımızın Işığı Annelerimize Minnetle</h2><p>Karşılıksız sevginin, şefkatin ve fedakarlığın sembolü olan annelerimiz için her yıl mayıs ayının ikinci pazar günü Anneler Günü olarak kutlanmaktadır.</p><p>Huzurevi ziyaretleri, yılın annesi ödülleri ve çocukların annelerine hazırladığı el emeği gösterilerle gün anlamlandırılacaktır.</p>",
    ],
    [
        'category_id' => 10,
        'name' => '2027 Babalar Günü (20 Haziran Pazar)',
        'seo' => '2027_Babalar_Günü_20_Haziran_Pazar',
        'subject' => 'Ailenin Çınarı ve Güven Kaynağı Babalarımız İçin Saygı ve Kutlama Günü',
        'start_date' => '2027-06-20 00:00:00',
        'end_date' => '2027-06-20 23:59:59',
        'deck' => 'n',
        'location' => 'Tüm Diyarbakır ve Yurt Geneli',
        'summary' => 'Haziran ayının üçüncü pazarında idrak edilen Babalar Günü dolayısıyla aile bağlarını güçlendiren açık hava buluşmaları, piknikler ve hatıra organizasyonları düzenlenecektir.',
        'content' => "<h2>Ailenin Güven Çınarı Babalarımız</h2><p>Haziran ayının üçüncü pazar günü kutlanan Babalar Günü, ailenin dirliğini sağlayan, evlatları için fedakarca çalışan babalarımıza teşekkür günüdür.</p><p>Dicle Vadisi ve Hevsel Bahçeleri rekreasyon alanlarında aile piknikleri ve kuşaklararası hatıra buluşmaları tertip edilecektir.</p>",
    ],
];

$seededCount = 0;
$skippedCount = 0;

foreach ($newEvents as $ev) {
    $safeSeo = addslashes($ev['seo']);
    $db->fetch_array("SELECT `id` FROM `mod_event` WHERE `seo` = '{$safeSeo}' AND `lang` = 'tr'");
    if ($db->getSize() > 0) {
        $existingId = (int) $db->toArray()[0]['id'];
        echo " -> [EXISTS] Event already present: {$ev['name']} (ID #{$existingId}). Skipping.\n";
        $skippedCount++;
        continue;
    }

    $safeName = addslashes($ev['name']);
    $safeSubject = addslashes($ev['subject']);
    $safeLocation = addslashes($ev['location'] ?? '');
    $safeSummary = addslashes($ev['summary']);
    $safeContent = addslashes($ev['content']);
    $safeTitle = addslashes($ev['name']);
    $safeKeywords = addslashes(mb_strtolower($ev['name'] . ', ' . $ev['subject'], 'UTF-8'));
    $safeDesc = addslashes($ev['summary']);
    $deckVal = $ev['deck'] ?? 'n';
    $catId = (int) $ev['category_id'];

    $sqlInsert = "INSERT INTO `mod_event` (
        `lang`, `category_id`, `name`, `subject`, `start_date`, `end_date`,
        `status`, `is_official_holiday`, `holiday_duration`, `is_recurring`,
        `slider`, `deck`, `summary`, `content`, `location`, `participants`, `guests`,
        `seo`, `title`, `keywords`, `description`, `image`, `gallery_id`, `file_id`,
        `video`, `acl_id`, `view_count`, `created_at`, `user_id`
    ) VALUES (
        'tr', {$catId}, '{$safeName}', '{$safeSubject}', '{$ev['start_date']}', '{$ev['end_date']}',
        'y', 'n', 0.0, 'y',
        'n', '{$deckVal}', '{$safeSummary}', '{$safeContent}', '{$safeLocation}', NULL, NULL,
        '{$safeSeo}', '{$safeTitle}', '{$safeKeywords}', '{$safeDesc}', NULL, 0, 0,
        NULL, 0, 0, NOW(), 1
    )";

    $db->query($sqlInsert);
    echo " -> [INSERTED] Event: {$ev['name']} (Cat #{$catId})\n";
    $seededCount++;
}

echo "\n========================================================\n";
echo " Migration Complete: {$seededCount} inserted, {$skippedCount} skipped.\n";
echo "========================================================\n";
