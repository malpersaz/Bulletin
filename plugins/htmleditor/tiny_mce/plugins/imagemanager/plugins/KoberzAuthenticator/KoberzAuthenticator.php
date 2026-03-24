<?php
/**
 * Rojname İçerik Yönetim Sistemi
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * Kullanıcı: a.k.mutlu
 * Zaman: 23.03.2011 - 13:45
 */
if (!defined('basepath') or !constant('basepath')) die('Direct access not allowed!');

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "../../../../../../../koberz.init.php");


//require_once('../../../koberz.init.php');


class Moxiecode_KoberzAuthenticator extends Moxiecode_ManagerPlugin
{

    function Moxiecode_KoberzAuthenticator()
    {
    }

    function onAuthenticate(&$man)
    {
        Load::lib('Session');
        if (
            Roll::hasPermission('mod_page', 'ekle') === True OR
            Roll::hasPermission('mod_page', 'duzenle') === True OR

            Roll::hasPermission('mod_komisyon', 'ekle') === True OR
            Roll::hasPermission('mod_komisyon', 'duzenle') === True OR

            Roll::hasPermission('mod_announcements', 'ekle') === True OR
            Roll::hasPermission('mod_announcements', 'duzenle') === True OR



            Roll::hasPermission('mod_content', 'ekle') === True OR
            Roll::hasPermission('mod_content', 'duzenle') === True
        ) {
            return True;

        } else {
            exit('Yetki Hatası!');
            return False;

        }
    }

}

// Add plugin to MCManager
$man->registerPlugin("KoberzAuthenticator", new Moxiecode_KoberzAuthenticator());

?>