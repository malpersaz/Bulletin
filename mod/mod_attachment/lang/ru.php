<?php
/**
 * Attachment Module - Russian Language File
 */

if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_attachment'] = [];

$lang['mod_attachment']['admin'] = [
    'upload_area' => 'Область загрузки файлов',
    'select_file_instruction' => 'Выберите файл, нажав кнопку «Обзор» ниже!',
    'ctrl_key_instruction' => 'Вы можете выбрать и загрузить несколько файлов, удерживая клавишу <b>CTRL</b> на клавиатуре.',
    'loading' => 'Загрузка...',
    'view' => 'Просмотр',
    'waiting_queue' => 'Ожидание в очереди загрузки...',
    'file' => 'Файл',
    'selected_files_count' => 'Количество выбранных файлов',
    'waiting_files_count' => 'Количество файлов в очереди',
    'file_size_error' => 'Выбранный файл слишком большой.',
    'browse' => 'Обзор',
    'back' => 'Назад',
    'file_management' => 'Управление файлами',
    'confirm_delete_file' => 'Вы уверены, что хотите удалить этот файл?',
    'delete_file' => 'Удалить файл',
    'yes' => 'Да',
    'cancel' => 'Отмена',
    'save' => 'Сохранить',
    'content_upload_area' => 'Область загрузки файлов для контента %s',
    'file_name' => 'Имя файла',
    'no_file_found' => 'Файлы не найдены!',
    'all_files' => '--- Все файлы ---',
    'no_saved_file_found' => 'Сохраненные файлы не найдены!',
    'select_category_add_file' => 'Выберите категорию и добавьте файлы',
    'download_file' => 'Скачать файл',
    'edit_file_name' => 'Редактировать имя файла',
    'remove_image' => 'Удалить файл',
    'file_uploaded_success' => 'Файл %s успешно загружен на сервер!',
    'file_deleted' => 'Файл удален',
    'files_belonging_to' => 'Файлы, относящиеся к «%s»',
    'add_files' => 'Добавить файл',
    'file_edited' => 'Файл изменен',
    'file_renamed' => 'Имя файла успешно изменено',
    'file_upload_failed' => 'Не удалось загрузить файл.',
    'statistics' => 'Статистика',
    'new' => 'Новый',
    'panel' => 'Панель',
    'warning' => 'Предупреждение!',
    'info' => 'Информация!',
    'message' => 'Сообщение!',
    'help' => 'Помощь!',
];

$lang['mod_attachment']['hata'] = [
    'source_not_retrieved' => 'Не удалось получить исходный файл',
    'specified_file_invalid' => 'Указанный файл недействителен',
    'specify_destination_file' => 'Пожалуйста, укажите файл для записи',
    'supported_formats_only' => 'В качестве вывода поддерживаются только файлы png, jpg, jpeg, gif, bmp',
    'file_creation_failed' => 'Не удалось создать файл',
    'file_output_failed' => 'Не удалось сгенерировать вывод файла',
];

$lang['mod_attachment']['errors'] = [
    'missing_module_name' => 'Отсутствует информация о модуле для файловой операции.',
    'missing_record_id' => 'Отсутствует информация о записи для файловой операции.',
    'invalid_module' => 'Для этой записи модуль файлов не определен.',
    'module_file_permission_missing' => 'Для этого модуля не определены права доступа к файлам.',
    'invalid_record' => 'Связанная запись не найдена.',
    'invalid_file' => 'Связанная запись файла не найдена.',
    'invalid_record_title_field' => 'Поле mod_value для этого модуля недействительно.',
    'upload_field_missing' => 'Отсутствует информация о поле загрузки.',
    'uploaded_file_missing' => 'Файл для загрузки не предоставлен.',
];

$lang['mod_attachment']['role'] = [
    'name' => 'Роли управления файлами',
    'add' => 'Может добавлять файлы.',
    'edit' => 'Может редактировать загруженные файлы',
    'delete' => 'Может удалять загруженные файлы',
    'list' => 'Может просматривать управление файлами',
    'moderate' => 'Может управлять файлами других пользователей',
];

$lang['mod_attachment']['module'] = [
    'description' => 'Модуль файлов',
];

$lang['mod_attachment']['ajax'] = [
    'success' => 'Успешно',
    'error' => 'Ошибка',
];

$lang['mod_attachment']['role_errors'] = [
    'no_edit_permission' => 'У вас нет прав на редактирование файлов других пользователей',
    'no_delete_permission' => 'У вас нет прав на удаление файлов других пользователей.',
    'add' => 'У вас нет прав на добавление файлов',
    'delete' => 'У вас нет прав на удаление файлов',
    'edit' => 'У вас нет прав на редактирование файлов',
    'list' => 'У вас нет прав на просмотр управления файлами',
];
