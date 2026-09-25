<?php
/**
 * Document Management Module - Russian Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_document'] = [
    'add' => 'Добавить документ',
    'edit' => 'Редактировать документ',
    'list' => 'Документы',
    'remove_image' => 'Вы уверены, что хотите удалить это изображение?',
    'remove_record' => 'Вы уверены, что хотите удалить этот документ?',
    'empty_state' => 'Документы пока не добавлены.',
    'message' => [
        'success' => [
            'add' => 'Документ успешно добавлен.',
            'edit' => 'Документ успешно обновлен.',
            'delete' => 'Документ успешно удален.',
        ],
        'error' => [
            'add' => 'Не удалось добавить документ.',
            'edit' => 'Не удалось обновить документ.',
            'delete' => 'Не удалось удалить документ.',
        ],
    ],
    'errors' => [
        'record' => 'Указанный документ не найден.',
        'delete_failed' => 'Не удалось удалить документ.',
    ],
    'button' => [
        'add' => 'Добавить',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'cancel' => 'Отмена',
        'save' => 'Сохранить',
        'manage_gallery' => 'Управление галереей',
        'manage_attachments' => 'Управление файлами',
    ],
    'validator' => [
        'required' => 'Пожалуйста, заполните поле %s.',
        'length' => 'Длина поля %s должна составлять от %s до %s символов.',
        'unique' => 'Значение %s уже существует. Пожалуйста, выберите другое %s.',
    ],
    'role' => [
        'name' => 'Роли управления документами',
        'add' => 'Может добавлять документы',
        'edit' => 'Может редактировать документы',
        'delete' => 'Может удалять документы',
        'list' => 'Может просматривать управление документами',
        'gallery' => 'Может управлять галереями документов',
        'file' => 'Может управлять вложениями документов',
        'moderate_edit' => 'Может редактировать документы других пользователей',
        'moderate_delete' => 'Может удалять документы других пользователей',
    ],
    'role_errors' => [
        'add' => 'У вас нет прав на добавление документов.',
        'edit' => 'У вас нет прав на редактирование документов.',
        'delete' => 'У вас нет прав на удаление документов.',
        'list' => 'У вас нет прав на просмотр управления документами.',
        'gallery' => 'У вас нет прав на управление галереями документов.',
        'file' => 'У вас нет прав на управление вложениями документов.',
        'moderate_edit' => 'Вы не можете редактировать документы других пользователей.',
        'moderate_delete' => 'Вы не можете удалять документы других пользователей.',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'title' => 'Название документа',
            'description' => 'Описание',
            'file' => 'Файл документа',
            'created_at' => 'Дата создания',
            'actions' => 'Действия',
            'lang' => 'Language',
            'name' => 'Document Name',
            'status' => 'Status',
            'seo' => 'SEO URL',
            'keywords' => 'Meta Keywords',
            'image' => 'Image',
            'menu' => 'Send to Menu',
        ],
        'radio' => [
            'status_y' => 'Published',
            'status_n' => 'Draft',
            'menu_y' => 'Send to Menu',
            'menu_n' => 'Do not Send',
        ],
    ],
    'status' => [
        'published' => 'Published',
        'draft' => 'Draft',
    ],
    'form' => [
        'wizard' => [
            'back' => 'Back',
            'next' => 'Next',
            'seo_meta' => 'SEO & Meta',
        ],
    ],
    'list_ui' => [
        'title' => 'Documents',
        'subtitle' => 'Manage institutional documents and files.',
        'total_stat' => 'Total',
        'live_stat' => 'Published',
        'loading' => 'Loading documents...',
        'load_more' => 'Load More',
        'image_title' => 'Document Image',
        'image_drop_title' => 'Drop a document image here',
        'image_drop_description' => 'Choose a new image, paste one, or import it from a URL.',
        'image_upload_link' => 'Upload image',
        'image_link_separator' => 'or',
        'image_link_placeholder' => 'Paste an image URL',
        'image_link_action' => 'Import from URL',
        'image_hint_loading' => 'Importing image...',
        'image_hint_invalid_type' => 'Please choose a valid image file.',
        'image_hint_no_drop_data' => 'No usable image was found in the dropped content.',
        'image_hint_remote_failed' => 'The image could not be imported from that URL.',
        'image_empty_name' => 'No image selected',
        'image_updated' => 'Document image updated successfully.',
        'image_remove_success' => 'Document image removed successfully.',
        'add_cta' => 'New Document',
        'edit_cta' => 'Edit',
        'change_image' => 'Change Image',
        'delete_cta' => 'Delete',
        'select_record' => 'Select a document first.',
        'gallery_ready' => 'Gallery Ready',
        'file_ready' => 'File Ready',
    ],
];
