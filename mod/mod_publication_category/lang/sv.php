<?php
/**
 * Publication Category Module - Swedish Language File
 *
 * @package AKM ARTIST CMS
 * @module  mod_publication_category
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Category Module Language Translations
 */
$lang['mod_publication_category'] = [

    // === Basic Navigation ===
    'list' => 'Kategorilista',
    'add' => 'Lägg till kategori',
    'edit' => 'Redigera kategori',
    'information' => 'Information',
    'success' => 'Framgång',
    'error' => 'Ett fel uppstod',
    'remove_record' => 'Är du säker på att du vill ta bort den här posten?',
    'choose_wish' => 'Välj efter önskemål',
    'remove_image' => 'Är du säker på att du vill ta bort den här bilden?',

    // === Message Structure ===
    'message' => [
        'success' => [
            'add' => 'Kategorin har lagts till!',
            'edit' => 'Kategorin har uppdaterats!',
        ],
        'error' => [
            'add' => 'Det gick inte att lägga till kategorin!',
            'edit' => 'Det gick inte att uppdatera kategorin!',
        ],
        'information' => [
            'moderate' => 'Dubbelklicka på kategorin du vill redigera',
            'move' => 'Dra och släpp kategorin för att ändra ordningen',
        ],
    ],

    'errors' => [
        'record' => 'Den angivna posten kunde inte hittas!',
    ],

    'button' => [
        'add' => 'Lägg till',
        'edit' => 'Redigera',
        'refresh' => 'Uppdatera',
        'delete' => 'Ta bort',
        'cancel' => 'Avbryt',
        'remove' => 'Ta bort',
        'yes' => 'Ja',
        'no' => 'Nej',
        'save' => 'Spara',
        'gallery' => 'Galleri',
        'file' => 'Fil',
    ],

    'grid' => [
        'showing_results' => 'Visar {from} till {to} av {total} resultat',
        'page' => 'Sida',
        'page_count' => 'Antal sidor',
        'loading' => 'Laddar data, vänligen vänta...',
        'no_data' => 'Inga data hittades',
        'connection_error' => 'Anslutningsfel',
        'search' => 'Sök',
        'select_at_least_one' => 'Välj minst en rad',
        'select_only_one' => 'Välj endast en rad',
    ],

    'validator' => [
        'required' => 'Lämna inte fältet %s tomt',
        'length' => '%s måste vara mellan %s och %s tecken',
        'email' => 'Ange en giltig e-postadress i fältet %s',
        'unique' => 'Det angivna %s är redan registrerat i systemet!',
        'inarray' => 'Fältet %s accepterar endast värdena \' %s \'',
    ],

    'role' => [
        'name' => 'Roller för hantering av publikationskategorier',
        'add' => 'Kan lägga till kategori',
        'edit' => 'Kan redigera kategori',
        'delete' => 'Kan ta bort kategori',
        'list' => 'Kan visa kategorihantering',
        'moderate_edit' => 'Kan redigera andras kategorier',
        'moderate_delete' => 'Kan ta bort andras kategorier',
    ],

    'role_errors' => [
        'moderate_edit' => 'Du kan inte redigera andras kategorier',
        'moderate_delete' => 'Du kan inte ta bort andras kategorier',
        'add' => 'Du har inte behörighet att lägga till kategorier',
        'edit' => 'Du har inte behörighet att redigera kategorier',
        'list' => 'Du har inte behörighet att visa kategorihantering',
        'delete' => 'Du har inte behörighet att ta bort kategorier',
    ],

    'actions' => [
        'add' => 'Lägg till kategori',
        'edit' => 'Redigera kategori',
        'list' => 'Kategorilista',
        'delete' => 'Ta bort kategori',
        'cancel' => 'Avbryt',
        'save' => 'Spara',
        'update' => 'Uppdatera',
        'remove' => 'Ta bort',
    ],

    'messages' => [
        'success' => [
            'added' => 'Kategorin har lagts till!',
            'updated' => 'Kategorin har uppdaterats!',
            'deleted' => 'Kategorin har tagits bort!',
            'reordered' => 'Kategoriordningen har uppdaterats!',
            'image_removed' => 'Bilden har tagits bort!',
        ],
        'error' => [
            'not_added' => 'Det gick inte att lägga till kategorin!',
            'not_updated' => 'Det gick inte att uppdatera kategorin!',
            'not_deleted' => 'Det gick inte att ta bort kategorin!',
            'not_found' => 'Den angivna kategorin kunde inte hittas!',
            'image_not_removed' => 'Det gick inte att ta bort bilden!',
            'validation' => 'Fyll i de obligatoriska fälten korrekt!',
        ],
        'info' => [
            'edit_double_click' => 'Dubbelklicka på kategorin du vill redigera',
            'drag_reorder' => 'Dra och släpp kategorin för att ändra ordningen',
        ],
    ],

    'options' => [
        'yes' => 'Ja',
        'no' => 'Nej',
    ],

    'form' => [
        'basic_info' => 'Grundläggande information',
        'seo_settings' => 'SEO-inställningar',
        'display_settings' => 'Visningsinställningar',
        'advanced_settings' => 'Avancerade inställningar',
        'id' => 'ID',
        'name' => 'Kategorinamn',
        'parent_category' => 'Överordnad kategori',
        'order' => 'Ordning',
        'language' => 'Visningsspråk',
        'is_root' => 'Huvudkategori (dold från visning)',
        'seo_url' => 'SEO URL',
        'meta_title' => 'Meta-titel (för sökmotorer)',
        'meta_keywords' => 'Meta-nyckelord (för sökmotorer)',
        'meta_description' => 'Meta-beskrivning (för sökmotorer)',
        'add_to_menu' => 'Lägg till kategori i menyn',
        'image' => 'Kategoribild',
        'description' => 'Kategoribeskrivning',
        'access_control' => 'Åtkomstkontroll',
    ],

    'columns' => [
        'id' => 'ID',
        'language' => 'Visningsspråk',
        'name' => 'Kategorinamn',
        'parent_category' => 'Överordnad kategori',
        'order' => 'Ordning',
        'root_category' => 'Huvudkategori',
        'seo_url' => 'SEO URL',
        'meta_title' => 'Meta-titel',
        'meta_keywords' => 'Meta-nyckelord',
        'meta_description' => 'Meta-beskrivning',
        'add_to_menu' => 'Lägg till i meny',
        'image' => 'Bild',
        'status' => 'Status',
        'actions' => 'Åtgärder',
    ],

    'buttons' => [
        'add' => 'Lägg till ny kategori',
        'edit' => 'Redigera markerad',
        'delete' => 'Ta bort markerad',
        'refresh' => 'Uppdatera',
        'cancel' => 'Avbryt',
        'remove' => 'Ta bort',
        'yes' => 'Ja',
        'no' => 'Nej',
    ],

    'validation' => [
        'required' => 'Lämna inte fältet %s tomt',
        'length' => '%s måste vara mellan %s och %s tecken',
        'email' => 'Ange en giltig e-postadress',
        'unique' => '%s är redan registrerat i systemet',
        'inarray' => 'Fältet %s accepterar endast värdena \' %s \'',
        'url' => 'Ange en giltig URL',
        'slug' => 'Ange en giltig URL-slug',
    ],

    'permissions' => [
        'title' => 'Behörigheter för kategorihantering',
        'add' => 'Kan lägga till kategori',
        'edit' => 'Kan redigera kategori',
        'delete' => 'Kan ta bort kategori',
        'list' => 'Kan visa kategorihantering',
        'edit_others' => 'Kan redigera andras kategorier',
        'delete_others' => 'Kan ta bort andras kategorier',
    ],

    'permission_errors' => [
        'edit_others' => 'Du kan inte redigera andras kategorier',
        'delete_others' => 'Du kan inte ta bort andras kategorier',
        'add' => 'Du har inte behörighet att lägga till kategorier',
        'edit' => 'Du har inte behörighet att redigera kategorier',
        'list' => 'Du har inte behörighet att visa kategorihantering',
        'delete' => 'Du har inte behörighet att ta bort kategorier',
    ],

    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Visningsspråk',
            'name' => 'Kategorinamn',
            'parent_id' => 'Överordnad kategori',
            'order' => 'Ordning',
            'root' => 'Huvudkategori (dold från visning)',
            'seo' => 'Kategori-URL',
            'title' => 'Titel (för sökmotorer)',
            'keywords' => 'Nyckelord (för sökmotorer)',
            'description' => 'Beskrivning (för sökmotorer)',
            'menu' => 'Skicka kategori till meny',
            'image' => 'Bild',
            'acl_id' => 'Åtkomstkontroll',
        ],
        'select_value' => [
            'root_e' => 'Ja',
            'root_h' => 'Nej',
        ],
    ],

    'confirmations' => [
        'remove_image' => 'Är du säker på att du vill ta bort den här bilden?',
        'remove_record' => 'Är du säker på att du vill ta bort den här posten?',
        'delete_single' => 'Är du säker på att du vill ta bort den här kategorin?',
        'delete_multiple' => 'Är du säker på att du vill ta bort :count markerade kategorier?',
        'make_root' => 'Vill du göra detta till en huvudkategori?',
        'remove_root' => 'Vill du ta bort statusen som huvudkategori?',
    ],

    'success_messages' => [
        'operation_completed' => 'Åtgärden slutfördes!',
        'changes_saved' => 'Ändringar sparade!',
        'category_published' => 'Kategorin har publicerats!',
        'category_unpublished' => 'Kategorin har avpublicerats!',
    ],

    'error_messages' => [
        'general' => 'Ett fel uppstod. Vänligen försök igen.',
        'network' => 'Nätverksfel. Kontrollera din internetanslutning.',
        'server' => 'Serverfel. Vänligen försök igen senare.',
        'fatal' => 'Ett allvarligt fel uppstod!',
        'has_subcategories' => 'Kan inte ta bort en kategori som innehåller underkategorier',
        'has_content' => 'Kan inte ta bort en kategori som innehåller innehåll',
    ],

    'help' => [
        'category_name' => 'Ange ett tydligt namn för kategorin',
        'parent_category' => 'Välj överordnad kategori',
        'order' => 'Bestämmer visningsordningen',
        'root_category' => 'Huvudkategorier är organisatoriska och visas inte offentligt',
        'seo_url' => 'URL-vänlig version av kategorinamnet',
        'meta_title' => 'Titel som visas i sökresultat',
        'meta_description' => 'Beskrivning som visas i sökresultat',
        'add_to_menu' => 'Lägg automatiskt till kategorin i navigeringsmenyn',
        'access_control' => 'Ange vilka användarroller som har åtkomst',
    ],

    'placeholders' => [
        'category_name' => 'Ange kategorinamn...',
        'seo_url' => 'kategori-url-slug',
        'meta_title' => 'Ange meta-titel...',
        'meta_description' => 'Ange meta-beskrivning...',
        'meta_keywords' => 'nyckelord1, nyckelord2',
        'category_description' => 'Ange kategoribeskrivning (valfritt)...',
    ],

    'empty' => [
        'no_categories' => 'Inga kategorier har skapats ännu',
        'no_results' => 'Inga kategorier hittades som matchar din sökning',
        'add_first' => 'Klicka på knappen för att skapa den första kategorin',
        'no_subcategories' => 'Inga underkategorier hittades',
        'no_content' => 'Inget innehåll i den här kategorin ännu',
    ],

    'loading' => [
        'saving' => 'Sparar...',
        'loading' => 'Laddar...',
        'processing' => 'Bearbetar...',
        'deleting' => 'Tar bort...',
    ],

    'tree_view' => [
        'expand_all' => 'Expandera alla',
        'collapse_all' => 'Fäll ihop alla',
        'show_hierarchy' => 'Visa hierarki',
        'show_flat' => 'Platt vy',
        'root_only' => 'Endast huvudkategorier',
        'leaf_only' => 'Endast underkategorier',
    ],

    'seo' => [
        'auto_generate_slug' => 'Generera URL-slug automatiskt',
        'preview_snippet' => 'Förhandsgranska sökresultat',
        'character_count' => 'Antal tecken: :count',
        'recommended_length' => 'Rekommenderat: :min-:max tecken',
        'good' => 'Bra',
        'too_short' => 'För kort',
        'too_long' => 'För långt',
    ],

    'bulk_operations' => [
        'bulk_edit' => 'Massredigera',
        'bulk_delete' => 'Massta bort',
        'bulk_move' => 'Flytta till kategori',
        'bulk_publish' => 'Publicera markerade',
        'bulk_unpublish' => 'Avpublicera markerade',
        'select_all' => 'Markera alla',
        'deselect_all' => 'Avmarkera alla',
    ],

    'ajax' => [
        'success' => 'Framgång',
        'error' => 'Fel',
    ],

    'responses' => [
        'success' => 'Success',
        'error' => 'Error',
        'fatal_error' => 'ERROR',
    ],

];
