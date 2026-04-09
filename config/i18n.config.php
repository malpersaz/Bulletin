<?php
/**
 * Amed Framework
 * User: a.k.mutlu
 * Date: 4/26/11 - 3:16 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: i18n.config.php
 * Description: Multi-language settings
 */
// Enable or disable multilingual support.
Kayit::ekle('i18n', TRUE);
// Fallback bootstrap state. Runtime should prefer the database-backed registry when available.
Kayit::ekle('i18n_bootstrap', array(
    'default_lang' => 'ku',
    'active_langs' => array('en', 'ku', 'ckb', 'zza', 'ar', 'de', 'fr', 'sv', 'tr', 'fa'),
));
