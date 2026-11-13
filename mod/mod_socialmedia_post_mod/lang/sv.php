<?php

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_socialmedia_post_mod'] = [
    'named' => 'Moduler för Sociala Inlägg',
    'list' => 'Lista över Sociala Inläggsmoduler',
    'add' => 'Lägg till Social Inläggsmodul',
    'edit' => 'Redigera Social Inläggsmodul',
    'information' => 'Information',
    'success' => 'Lyckades',
    'error' => 'Ett fel uppstod',
    'remove_record' => 'Är du säker på att du vill ta bort den här posten?',
    'message' => [
        'success' => [
            'add' => 'Social inläggsmodul har lagts till!',
            'edit' => 'Social inläggsmodul har uppdaterats!',
            'delete' => 'Social inläggsmodul har tagits bort!',
        ],
        'error' => [
            'add' => 'Det gick inte att lägga till modulen!',
            'edit' => 'Det gick inte att uppdatera modulen!',
        ],
    ],
    'errors' => [
        'record' => 'Den angivna posten kunde inte hittas!',
    ],
    'button' => [
        'add' => 'Lägg till',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
        'refresh' => 'Uppdatera',
        'cancel' => 'Avbryt',
        'remove' => 'Ta bort',
        'yes' => 'Ja',
        'no' => 'Nej',
    ],
    'validator' => [
        'required' => 'Lämna inte fältet %s tomt',
        'length' => '%s måste vara mellan %s och %s tecken',
        'unique' => '%s är redan registrerad i systemet!',
    ],
    'role' => [
        'name' => 'Behörigheter för Sociala Inläggsmoduler',
        'add' => 'Kan lägga till social inläggsmodul',
        'edit' => 'Kan redigera social inläggsmodul',
        'delete' => 'Kan ta bort social inläggsmodul',
        'list' => 'Kan visa sociala inläggsmoduler',
        'moderate_edit' => 'Kan redigera andras poster',
        'moderate_delete' => 'Kan ta bort andras poster',
    ],
    'role_errors' => [
        'moderate_edit' => 'Du kan inte redigera andras poster',
        'moderate_delete' => 'Du kan inte ta bort andras poster',
        'add' => 'Du saknar behörighet att lägga till moduler',
        'edit' => 'Du saknar behörighet att redigera moduler',
        'list' => 'Du saknar behörighet att visa moduler',
        'delete' => 'Du saknar behörighet att ta bort moduler',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Modulnamn',
            'mod_name' => 'Tekniskt Modulnamn',
            'mod_key' => 'Modulnyckel (ID)',
            'mod_value' => 'Modulvärde (Titel)',
        ],
    ],
];
