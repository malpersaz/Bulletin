<?php
/**
 * Amed Framework
 * User: AKM
 * Date: 4/26/11 - 3:16 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: i18n.config.php
 * Description: Multi-language settings
 */
// Enable or disable multilingual support.
Registry::add('i18n', TRUE);
// Fallback bootstrap state. Runtime should prefer the database-backed registry when available.
// Keep this mirror-aligned with the production `lang` table (status='y' rows): it only activates
// when the DB is unavailable/empty (installer, DB failure, partial boot) and then must not claim
// unpublished languages. Publishing a new language is a DB-side action (lang.status='y'), not an edit here.
Registry::add('i18n_bootstrap', array(
    'default_lang' => 'tr',
    'active_langs' => array('tr'),
));
