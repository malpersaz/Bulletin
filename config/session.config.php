<?php

/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/26/11 - 1:58 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Dosya:session.config.php
 * Açıklama:Session Ayarları
 */
if (defined('root_dir') === False)    exit(':)');
Kayit::ekle('session_name', 'AKMCMS'); // Session İsmi
#Kayit::ekle('session_path',root_dir.'session'); // Session Dizini
Kayit::ekle('session_db', FALSE); // Session Db'demi Tutulsun
Kayit::ekle('session_check_ip', FALSE); // İp Denetimi Yapılsınmı : TRUE
Kayit::ekle('session_check_agent', FALSE); // USER AGENT Denetimi Yapılsınmı : FALSE
// Sessionun Yaşam süresi eğer 60 dakika hareket etmezse düşer.
Kayit::ekle('session_life_time', 60); // Dakika Cinsinden
// Kayit::ekle('session_time_out',10); // Kullanıcı Kaç dakika hareket etmezse session silinsin
Kayit::ekle('session_active_check', TRUE);
// Eğer Kullanıcı 60 Dakika Hareket etmezse session öldürür.
// Kayit::ekle('session_active_time',60);
