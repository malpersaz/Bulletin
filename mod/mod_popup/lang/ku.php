<?php
/**
 * Popup/Modal Management Module - Kurdish Language File
 *
 * @package AKM Business CMS
 * @module  mod_popup
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Popup/Modal Management Module Language Translations
 */
$lang['mod_popup'] = [

    // === Basic Actions ===
    'add' => 'Popup Zêde Bike',
    'edit' => 'Popup Biguhere',
    'list' => 'Lîsteya Popup',

    // === System Messages ===
    'oke' => 'Operasyon Serkeft!',
    'hata' => 'Çewtî Çêbû!',
    'error' => 'Çewtiya Giran Çêbû!',
    'information' => 'Agahdarî!',

    // === Image Management ===
    'remove_image' => 'Ji derxistina vê wêneyê bawer î?',
    'remove_image_oke' => 'Wêne hate rakirin',
    'choose_wish' => 'Heke bixwazî hilbijêre',
    'remove_record' => 'Ji rakirana vê tomarê bawer î?',

    // === Messages ===
    'message' => [
        'oke' => [
            'add' => 'Popup hate zêdekirin!',
            'edit' => 'Popup hate sererastkirin!',
        ],
        'error' => [
            'add' => 'Popup nehat zêdekirin!',
            'edit' => 'Popup nehat sererastkirin!',
        ],
        'information' => [
            'moderate' => 'Ji bo sererastkirina naverokê du caran bitikîne',
            'move' => 'Ji bo guhertina rêza naverokê bikşîne û berde',
        ],
    ],

    // === Error Messages ===
    'errors' => [
        'record' => 'Tomara ku tu destnîşan kirî nehat dîtin!',
    ],

    // === Buttons ===
    'button' => [
        'add' => 'Zêde Bike',
        'edit' => 'Biguhere',
        'yenile' => 'Nû Bike',
        'delete' => 'Rake',
        'gallery' => 'Galeriya Wêneyan',
        'file' => 'Rêveberê Pelan',
        'vazgec' => 'Betal',
        'kaldir' => 'Rake',
        'evet' => 'Erê',
        'hayir' => 'Na',
    ],

    // === Data Grid Messages ===
    'grid' => [
        0 => 'Ji {total} encamên {from} heta {to} tên nîşandan.',
        1 => 'Rûpel',
        2 => 'Hejmara Rûpelan',
        3 => 'Daneyên têne barkirin, jî kerema xwe bisekinin...',
        4 => 'Tu dane nehat dîtin',
        5 => 'Çewtiya Girêdanê',
        6 => 'Lêgerîn',
        7 => 'Ji kerema xwe herî kêm rêzekê hilbijêrin',
        8 => 'Ji kerema xwe tenê rêzekê hilbijêrin',
        'publish' => [
            'key' => 'Weşîne',
            'value' => 'Ma hûn bawer in ku hûn dixwazin vê popup biweşînin?',
        ],
        'unpublish' => [
            'key' => 'Ji Weşandinê Rake',
            'value' => 'Ma hûn bawer in ku hûn dixwazin vê popup ji weşanê derxin?',
        ],
    ],

    // === Validation Messages ===
    'validator' => [
        'required' => 'Naverok bê %s nayê şandin',
        'length' => '%s dikare herî kurt %s û herî dirêj %s tîpan be',
        'email' => 'Ji kerema xwe navekî e-postayê yê derbasdar li qada %s binivîsin.',
        'unique' => '%s ku te nivîsî di pergalê de qeydkirî ye. Ji kerema xwe %s-ekê din destnîşan bikin!',
        'inarray' => 'Qada %s dikare tenê nirxên \' %s \' bigire',
    ],

    // === Permission Roles ===
    'roll' => [
        'ad' => 'Rolenên Rêveberiya Popup',
        'add' => 'Dikare popup zêde bike',
        'edit' => 'Dikare popup Biguhere',
        'delete' => 'Dikare popup rake',
        'list' => 'Dikare rêveberiya popupê bibîne',
        'moderate_edit' => 'Dikare tomarên popup ên ku yên wî ne Biguhere',
        'moderate_delete' => 'Dikare tomarên popup ên ku yên wî ne rake',
    ],

    // === Permission Errors ===
    'role_errors' => [
        'moderate_edit' => 'Tu nikarî tomarên popup ên ku yên te ne sererast bikî',
        'moderate_delete' => 'Tu nikarî tomarên popup ên ku yên te ne rakî',
        'add' => 'Destûra te ji bo zêdekirina popupê tune',
        'edit' => 'Destûra te ji bo sererastkirina popupê tune',
        'list' => 'Destûra te ji bo dîtina rêveberiya popupê tune',
        'delete' => 'Destûra te ji bo rakirina popupê tune',
    ],

    // === Admin Panel Configuration ===
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Ziman',
            'ad' => 'Navê Popup',
            'tarih' => 'Destpêka Weşanê',
            'etarih' => 'Dawîya Weşanê',
            'status' => 'Aktîv î?',
            'content' => 'Ravekirin',
            'sure' => 'Girtina Otomatîk (Bi Saniyeyan).',
            'video' => 'Cureyê Vîdeoyê',
            'link' => 'Girêdana Wêneyê an Girêdana Vîdeoyê',
            'image' => 'Wêne',
        ],
        'radio' => [
            'publish_yes' => 'Erê',
            'publish_no' => 'Na',
        ],
        'select_value' => [
            'video_url' => 'Yên din',
            'video_youtube' => 'YouTube',
            'video_vimeo' => 'Vimeo',
            'video_instagram' => 'Instagram',
        ],
    ],

];