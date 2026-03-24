<?php
/**
 * Amed Framework
 * Kullanıcı: a.k.mutlu
 * Tarih: 4/26/11 - 1:58 PM
 * Telif Hakkı:AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Dosya:email.config.php
 * Açıklama:E-Posta Ayarları
 */
if(defined('root_dir')===False)	exit(':)');
$email['smtp']=array(
					'host'=>'***************',
					'user'=>'username',
					'pass'=>'************',
					'port'=>'587',
					'mail'=>'info@sitename.com',
					'ad'=>'Your Site Name'
					);
Kayit::ekle('email_config',$email);
unset($email);