<?php
/**
 * Slider Management Module - Russian Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_slider'] = [
    'add' => 'Добавить слайд',
    'edit' => 'Редактировать слайд',
    'list' => 'Управление слайдером',
    'information' => 'Информация!',
    'success' => 'Операция успешно выполнена!',
    'error' => 'Произошла ошибка!',
    'remove_image' => 'Вы уверены, что хотите удалить это изображение?',
    'remove_image_success' => 'Изображение удалено',
    'choose_wish' => 'Выберите при желании',
    'remove_record' => 'Вы уверены, что хотите удалить эту запись?',
    'messages' => [
        'confirm_delete' => 'Вы уверены, что хотите удалить этот слайд?',
        'success_delete' => 'Слайд успешно удален!',
    ],
    'message' => [
        'success' => [
            'add' => 'Слайд добавлен!',
            'edit' => 'Слайд обновлен!',
        ],
        'error' => [
            'add' => 'Не удалось добавить слайд!',
            'edit' => 'Не удалось обновить слайд!',
        ],
        'information' => [
            'moderate' => 'Дважды кликните по записи для редактирования',
            'move' => 'Перетащите запись для изменения порядка',
        ],
    ],
    'errors' => [
        'record' => 'Указанная запись не найдена!',
    ],
    'button' => [
        'ok' => 'ОК',
        'cancel' => 'Отмена',
        'save' => 'Сохранить',
        'yes' => 'Да',
        'no' => 'Нет',
        'add' => 'Добавить',
        'edit' => 'Редактировать',
        'refresh' => 'Обновить',
        'delete' => 'Удалить',
        'gallery' => 'Галерея изображений',
        'remove' => 'Удалить',
    ],
    'grid' => [
        'showing_results' => 'Показано с {from} по {to} из {total} результатов.',
        'page' => 'Страница',
        'page_count' => 'Количество страниц',
        'loading' => 'Загрузка данных, пожалуйста, подождите...',
        'no_data' => 'Данные не найдены',
        'connection_error' => 'Ошибка подключения',
        'search' => 'Поиск',
        'select_at_least_one' => 'Пожалуйста, выберите хотя бы одну строку',
        'select_only_one' => 'Пожалуйста, выберите только одну строку',
        'publish' => [
            'key' => 'Опубликовать',
            'value' => 'Вы уверены, что хотите опубликовать этот слайд?',
        ],
        'unpublish' => [
            'key' => 'Снять с публикации',
            'value' => 'Вы уверены, что хотите снять с публикации этот слайд?',
        ],
    ],
    'validator' => [
        'required' => 'Пожалуйста, заполните поле %s',
        'length' => 'Длина поля %s должна составлять от %s до %s символов',
        'email' => 'Пожалуйста, введите корректный адрес электронной почты',
        'unique' => 'Значение %s уже зарегистрировано в системе. Пожалуйста, укажите другое %s!',
        'inarray' => 'Поле %s может принимать только следующие значения: \' %s \'',
    ],
    'role' => [
        'name' => 'Роли управления слайдером',
        'add' => 'Может добавлять слайды',
        'edit' => 'Может редактировать слайды',
        'delete' => 'Может удалять слайды',
        'list' => 'Может просматривать управление слайдером',
        'gallery' => 'Может управлять галереями слайдов',
        'file' => 'Может управлять файлами слайдов',
        'edit_status' => 'Может изменять статус слайдов',
        'moderate_edit' => 'Может редактировать слайды других пользователей',
        'moderate_delete' => 'Может удалять слайды других пользователей',
        'move' => 'Может изменять порядок слайдов',
        'moderate' => 'Может модерировать слайды',
        'change_status' => 'Should the added slider be published immediately?',
        'approval' => 'Can approve sliders waiting for approval?',
    ],
    'role_errors' => [
        'moderate_edit' => 'Вы не можете редактировать слайды других пользователей',
        'moderate_delete' => 'Вы не можете удалять слайды других пользователей',
        'add' => 'У вас нет прав на добавление слайдов',
        'edit' => 'У вас нет прав на редактирование слайдов',
        'list' => 'У вас нет прав на просмотр управления слайдером',
        'delete' => 'У вас нет прав на удаление слайдов',
    ],
    'admin' => [
        'columns' => [
            'id' => 'ID',
            'language' => 'Язык',
            'name' => 'Название слайда',
            'title' => 'Заголовок',
            'content' => 'Описание',
            'image' => 'Изображение',
            'status' => 'Статус',
            'order' => 'Порядок',
            'link' => 'Ссылка',
            'actions' => 'Действия',
            'lang' => 'Language',
            'url' => 'Slider URL',
            'target' => 'When Clicked',
            'created_at' => 'Date',
            'description' => 'Description',
            'image_desktop' => 'Desktop - 16:9',
            'image_mobile' => 'Mobile - 3:4',
            'acl_id' => 'Who can view this content? ( <a href=\'mod_acl/list/\' target=\'_blank\'>ACL - Access Control List</a> )',
        ],
        'radio' => [
            'publish_yes' => 'Да',
            'publish_no' => 'Нет',
            'target_e' => 'Open in Same Page',
            'target_h' => 'Open in Different Page',
        ],
    ],
    'datatable' => [
        'emptyTable' => 'Нет данных в таблице',
        'loadingRecords' => 'Загрузка записей...',
    ],
    'wizard' => [
        'label' => [
            'title' => 'Заголовок слайда',
            'content' => 'Описание слайда',
            'image' => 'Изображение слайда',
            'link' => 'Ссылка кнопки',
            'upload_text' => 'Click and upload image',
        ],
    ],
    'cropper' => [
        'title_desk' => 'Обрезка для ПК',
        'title_mob' => 'Обрезка для мобильных',
        'error_plugin' => 'Не удалось загрузить плагин обрезки изображений',
        'error_title' => 'Ошибка изображения',
        'finish' => 'Завершить обрезку',
        'error_canvas' => 'Error on crop canvas.',
    ],
    'acl_all' => 'Все пользователи (Общедоступно)',
    'empty' => [
        'title' => 'Слайды еще не созданы',
        'no_data' => 'Нет добавленных слайдов.',
        'no_search_results' => 'Слайды, соответствующие поиску, не найдены.',
    ],
    'search_placeholder' => 'Поиск слайдов...',
    'modules' => [
        'news' => 'Новости',
        'publication' => 'Публикации',
        'content' => 'Материалы',
        'product' => 'Товары',
        'video' => 'Видео',
    ],
];
