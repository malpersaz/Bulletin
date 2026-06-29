<?php
/**
 * Slider Module - Swedish Language File
 *
 * @package AKM Business
 * @module  mod_slider
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

$lang['mod_slider']= [
  'add' => 'Lägg till Bildspel',
  'edit' => 'Redigera Bildspel',
  'list' => 'Bildspelshantering',
  'information' => 'Information!',
  'oke' => 'Operationen Lyckades!',
  'hata' => 'Ett Fel Uppstod!',
  'error' => 'Allvarligt Fel Uppstod!',
  'remove_image' => 'Är du säker på att du vill ta bort den här bilden?',
  'remove_image_oke' => 'Bilden har tagits bort',
  'choose_wish' => 'Välj om du vill',
  'remove_record' => 'Är du säker på att du vill radera denna post?',
  'messages' => [
    'confirm_delete' => 'Är du säker på att du vill radera detta?',
    'success_delete' => 'Posten raderades framgångsrikt!',
  ],
  'message' => [
    'oke' => 
     [
      'add' => 'Bildspel Tillagt!',
      'edit' => 'Bildspel Redigerat!',
    ],
    'error' => 
     [
      'add' => 'Kunde inte lägga till bildspel!',
      'edit' => 'Kunde inte redigera bildspel!',
    ],
    'information' => 
     [
      'moderate' => 'Dubbelklicka på innehållet du vill redigera',
      'move' => 'Dra och släpp innehållet du vill sortera om',
    ],
  ],
  'errors' => 
   [
    'record' => 'Den angivna posten kunde inte hittas!',
  ],
  'button' => [
    'ok' => 'OK',
    'cancel' => 'Avbryt',
    'save' => 'Spara',
    'yes' => 'Ja',
    'no' => 'Nej',
    'add' => 'Lägg till',
    'edit' => 'Redigera',
    'yenile' => 'Uppdatera',
    'delete' => 'Radera',
    'gallery' => 'Bildgalleri',
    'vazgec' => 'Avbryt',
    'kaldir' => 'Ta bort',
    'evet' => 'Ja',
    'hayir' => 'Nej',
  ],
  'grid' => 
   [
    0 => 'Visar {from} till {to} av {total} resultat.',
    1 => 'Sida',
    2 => 'Sidantal',
    3 => 'Data laddas, vänligen vänta...',
    4 => 'Ingen data hittades',
    5 => 'Anslutningsfel',
    6 => 'Sök',
    7 => 'Vänligen välj minst en rad',
    8 => 'Vänligen välj endast en rad',
    'publish' => 
     [
      'key' => 'Publicera',
      'value' => 'Är du säker på att du vill publicera detta bildspel?',
    ],
    'unpublish' => 
     [
      'key' => 'Avpublicera',
      'value' => 'Är du säker på att du vill avpublicera detta bildspel?',
    ],
  ],
  'validator' => 
   [
    'required' => 'Vänligen lämna inte fältet %s tomt',
    'length' => '%s kan vara minst %s och högst %s tecken',
    'email' => 'Vänligen ange en giltig e-postadress i fältet %s.',
    'unique' => '%s som du angav är redan registrerad. Vänligen ange en annan %s!',
    'inarray' => 'Fältet %s kan endast ta värdena \' %s \'',
  ],
  'roll' => 
   [
    'ad' => 'Roller för Bildspelshantering',
    'add' => 'Kan Lägga till Bildspel',
    'edit' => 'Kan Redigera Bildspel',
    'delete' => 'Kan Radera Bildspel',
    'list' => 'Kan Visa Bildspelshantering',
    'change_status' => 'Ska tillagt bildspel publiceras omedelbart?',
    'edit_status' => 'Ska redigerat bildspel publiceras omedelbart?',
    'approval' => 'Kan godkänna bildspel som väntar på godkännande?',
    'gallery' => 'Kan lägga till galleri till innehåll?',
    'moderate_edit' => 'Kan redigera bildspelsposter som inte tillhör dem',
    'moderate_delete' => 'Kan radera bildspelsposter som inte tillhör dem',
  ],
  'role_errors' => 
   [
    'moderate_edit' => 'Du kan inte redigera en bildspelspost som inte tillhör dig',
    'moderate_delete' => 'Du kan inte radera en bildspelspost som inte tillhör dig',
    'add' => 'Du har inte behörighet att lägga till ett bildspel',
    'edit' => 'Du har inte behörighet att redigera ett bildspel',
    'list' => 'Du har inte behörighet att visa bildspelshantering',
    'delete' => 'Du har inte behörighet att radera ett bildspel',
  ],
  'admin' => 
   [
    'columns' => 
     [
      'id' => 'ID',
      'lang' => 'Språk',
      'name' => 'Bildspelsnamn',
      'url' => 'Bildspels-URL',
      'status' => 'Publicera?',
      'target' => 'Vid klick',
      'created_at' => 'Datum',
      'description' => 'Beskrivning',
      'image' => 'Bild',
      'image_desktop' => 'Skrivbord - 16:9',
      'image_mobile' => 'Mobil - 3:4',
      'acl_id' => 'Vem kan se detta innehåll? ( <a href=\'mod_acl/list/\' target=\'_blank\'>ACL - Åtkomstkontrollista</a> )',
    ],
    'radio' => 
     [
      'publish_yes' => 'Ja',
      'publish_no' => 'Nej',
      'target_e' => 'Öppna på samma sida',
      'target_h' => 'Öppna på ny sida',
    ],
  ],
  'datatable' => ['emptyTable' => 'Inga poster hittades', 'loadingRecords' => 'Laddar...'],
  'wizard' => ['label' => ['upload_text' => 'Klicka och ladda upp bild']],
  'cropper' => [
    'title_desk' => 'Skrivbord - 16:9',
    'title_mob' => 'Mobil - 3:4',
    'error_plugin' => 'Cropper.js eller jQuery-plugin har inte laddats.',
    'error_title' => 'Fel',
    'finish' => 'Slutför',
    'error_canvas' => 'Fel vid skapande av canvas.',
  ],
  'acl_all' => 'Offentlig',
  'empty' => ['title' => 'Inga poster hittades', 'no_data' => 'Inga bildspel har lagts till ännu.', 'no_search_results' => 'Inga bildspel hittades som matchar dina sökkriterier.'],
  'search_placeholder' => 'Sök Bildspel...',
  'modules' => [
    'news' => 'Nyheter',
    'publication' => 'Publikation',
    'content' => 'Innehåll',
    'product' => 'Produkt',
  ],
];
