<?php
/**
 * Image Categories Module - Russian Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_imagecategories'] = [
    'add' => 'Добавить категорию изображений',
    'edit' => 'Редактировать категорию изображений',
    'list' => 'Категории изображений',
    'remove_record' => 'Вы уверены, что хотите удалить эту категорию изображений?',
    'remove_image' => 'Вы уверены, что хотите удалить это изображение?',
    'empty_state' => 'Категории изображений пока не добавлены.',
    'message' => [
        'success' => [
            'add' => 'Категория изображений успешно добавлена.',
            'edit' => 'Категория изображений успешно обновлена.',
            'delete' => 'Категория изображений успешно удалена.',
            'reordered' => 'Порядок категорий изображений обновлен.',
            'image_removed' => 'Изображение категории удалено.',
        ],
        'error' => [
            'add' => 'Не удалось добавить категорию изображений.',
            'edit' => 'Не удалось обновить категорию изображений.',
        ],
    ],
    'errors' => [
        'record' => 'Указанная категория изображений не найдена.',
        'delete_failed' => 'Не удалось удалить категорию изображений.',
        'parent_self' => 'Категория не может быть родительской для самой себя.',
        'parent_selection_required' => 'Пожалуйста, выберите родительскую категорию.',
        'language_root_parent_locked' => 'Языковые корневые категории должны оставаться в системном корне.',
        'parent_language_mismatch' => 'Язык родительской категории должен совпадать с языком категории.',
        'parent_cycle' => 'Категория не может быть перемещена под саму себя или свои подкатегории.',
        'menu_parent_not_found' => 'Выбранный родительский пункт меню не найден.',
        'menu_language_mismatch' => 'Язык родительского пункта меню должен совпадать с языком категории.',
        'order_update_failed' => 'Не удалось обновить порядок категорий.',
        'image_remove_failed' => 'Не удалось удалить изображение категории.',
    ],
    'parent_labels' => [
        'system_root' => 'Системный корень',
        'language_root' => 'Корень :language',
    ],
    'button' => [
        'add' => 'Добавить',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'cancel' => 'Отмена',
        'save' => 'Сохранить',
        'remove' => 'Удалить',
    ],
    'validator' => [
        'required' => 'Пожалуйста, заполните поле %s.',
        'length' => 'Длина поля %s должна составлять от %s до %s символов.',
        'unique' => 'Значение %s уже существует. Пожалуйста, выберите другое %s.',
        'inarray' => 'Поле %s может принимать только следующие значения: %s.',
    ],
    'role' => [
        'name' => 'Роли управления категориями изображений',
        'add' => 'Может добавлять категории изображений',
        'edit' => 'Может редактировать категории изображений',
        'delete' => 'Может удалять категории изображений',
        'list' => 'Может просматривать управление категориями изображений',
        'moderate_edit' => 'Может редактировать категории изображений других пользователей',
        'moderate_delete' => 'Может удалять категории изображений других пользователей',
        'gallery' => 'Can add gallery to image categories',
    ],
    'role_errors' => [
        'add' => 'У вас нет прав на добавление категорий изображений.',
        'edit' => 'У вас нет прав на редактирование категорий изображений.',
        'delete' => 'У вас нет прав на удаление категорий изображений.',
        'list' => 'У вас нет прав на просмотр управления категориями изображений.',
        'moderate_edit' => 'Вы не можете редактировать категории изображений других пользователей.',
        'moderate_delete' => 'Вы не можете удалять категории изображений других пользователей.',
        'gallery' => 'You do not have permission to add gallery to image categories.',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Название категории',
            'parent_id' => 'Родительская категория',
            'image' => 'Изображение',
            'order' => 'Порядок',
            'actions' => 'Действия',
            'lang' => 'Language',
            'content' => 'Short Description',
            'root' => 'Root Visibility',
            'seo' => 'SEO URL',
            'title' => 'Meta Title',
            'keywords' => 'Meta Keywords',
            'description' => 'Meta Description',
            'menu' => 'Menu Parent',
            'acl_id' => 'Access Control',
        ],
        'radio' => [
            'root_y' => 'Use as Structural Root',
            'root_n' => 'Show in Listings',
        ],
    ],
    'status' => [
        'root' => 'Root',
        'nested' => 'Nested',
    ],
    'form' => [
        'basics' => 'Category Basics',
        'structure' => 'Structure and Visibility',
        'seo' => 'SEO Details',
        'publishing' => 'Publishing Integrations',
        'image_help' => 'Upload a category cover for image listings and menus.',
        'parent_none' => 'Top-level category',
        'menu_none' => 'Do not create a menu item',
        'acl_none' => 'Public access',
    ],
    'list_ui' => [
        'add_cta' => 'Add',
        'delete_cta' => 'Delete',
        'empty_cta' => 'Create the first category',
        'select_record' => 'Select a category first.',
        'delete_title' => 'Delete category',
        'delete_confirm_button' => 'Yes, delete',
        'delete_cancel_button' => 'Cancel',
        'network_error' => 'The request could not be sent. Please check your connection.',
        'unlock_reorder' => 'Unlock Reorder',
        'lock_reorder' => 'Lock Reorder',
        'mode_unlocked' => 'Reorder mode unlocked.',
        'reorder_title' => 'Update category order',
        'reorder_confirm' => 'Do you want to move this category to its new location?',
    ],
];
