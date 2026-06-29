<?php
/**
 * Datei-Verwaltungsmodul - Deutsche Sprachdatei
 *
 * @package AKM Business
 * @module  dosya
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Direkten Zugriff verhindern
if (!defined('AKM_LOADED')) {
    die('Direkter Zugriff auf diese Datei ist nicht erlaubt.');
}

$lang['file'] = [
    // === Kernoperationen ===
    'upload' => [
        'success' => 'Datei erfolgreich hochgeladen!',
        'failed' => 'Datei konnte nicht hochgeladen werden!',
        'error_extension' => 'Ungültige Dateiendung. Erlaubt sind nur Dateien mit den Endungen: :extensions.',
        'error_directory' => 'Verzeichnis konnte nicht erstellt werden!',
        'error_upload' => 'Beim Hochladen der Datei ist ein Fehler aufgetreten',
        'error_move' => 'Die hochgeladene Datei konnte nicht verschoben werden',
    ],
    'delete' => [
        'success' => 'Datei erfolgreich gelöscht!',
        'failed' => 'Datei konnte nicht gelöscht werden!',
        'error_not_found' => 'Datei wurde nicht gefunden!',
        'error_permission' => 'Keine Berechtigung zum Löschen der Datei',
    ],
    // === Dateitypen ===
    'types' => [
        'image' => 'Bild',
        'document' => 'Dokument',
        'video' => 'Video',
        'audio' => 'Audio',
        'archive' => 'Archiv',
        'other' => 'Andere',
    ],
    // === Statusmeldungen ===
    'status' => [
        'uploading' => 'Wird hochgeladen...',
        'processing' => 'Wird verarbeitet...',
        'complete' => 'Abgeschlossen',
        'error' => 'Fehler',
        'queued' => 'In der Warteschlange',
        'cancelled' => 'Abgebrochen',
    ],
    // === Validierungsmeldungen ===
    'validation' => [
        'required' => 'Die Dateiauswahl ist erforderlich',
        'invalid_type' => 'Ungültiger Dateityp',
        'size_exceeded' => 'Die Datei überschreitet die maximal zulässige Größe',
        'corrupted' => 'Die Datei scheint beschädigt zu sein',
        'not_found' => 'Die angegebene Datei konnte nicht gefunden werden',
    ],
    // === Fehlermeldungen ===
    'errors' => [
        'general' => 'Beim Verarbeiten der Datei ist ein Fehler aufgetreten',
        'permission_denied' => 'Zugriff verweigert. Bitte überprüfen Sie die Dateirechte',
        'disk_full' => 'Der Server-Speicherplatz ist voll',
        'network_error' => 'Während des Uploads ist ein Netzwerkfehler aufgetreten',
        'server_error' => 'Serverfehler. Bitte versuchen Sie es später erneut',
        'timeout' => 'Upload-Zeitüberschreitung. Bitte erneut versuchen',
    ],
    // === Erfolgsmeldungen ===
    'success' => [
        'file_uploaded' => 'Datei erfolgreich hochgeladen!',
        'file_deleted' => 'Datei erfolgreich gelöscht!',
        'file_moved' => 'Datei erfolgreich verschoben!',
        'file_processed' => 'Datei erfolgreich verarbeitet!',
    ],
    // === Dateiinformationen ===
    'info' => [
        'name' => 'Dateiname',
        'size' => 'Dateigröße',
        'type' => 'Dateityp',
        'extension' => 'Erweiterung',
        'uploaded_at' => 'Hochgeladen am',
        'modified_at' => 'Zuletzt geändert',
        'path' => 'Dateipfad',
        'url' => 'Datei-URL',
    ],
    // === Aktionen ===
    'actions' => [
        'upload' => 'Datei hochladen',
        'download' => 'Herunterladen',
        'delete' => 'Löschen',
        'rename' => 'Umbenennen',
        'move' => 'Verschieben',
        'copy' => 'Kopieren',
        'view' => 'Ansehen',
        'edit' => 'Bearbeiten',
    ],
    // === Dateigrößeneinheiten ===
    'size_units' => [
        'bytes' => 'Bytes',
        'kb' => 'KB',
        'mb' => 'MB',
        'gb' => 'GB',
        'tb' => 'TB',
    ],
    // === Unterstützte Erweiterungen ===
    'extensions' => [
        'images' => 'jpg, jpeg, png, gif, webp, svg',
        'documents' => 'pdf, doc, docx, xls, xlsx, ppt, pptx, txt',
        'videos' => 'mp4, avi, mov, wmv, flv, webm',
        'audio' => 'mp3, wav, ogg, flac, aac',
        'archives' => 'zip, rar, 7z, tar, gz',
    ],
    // === Konfiguration ===
    'config' => [
        'max_file_size' => 'Maximale Dateigröße: :size',
        'allowed_types' => 'Erlaubte Dateitypen: :types',
        'upload_path' => 'Upload-Verzeichnis: :path',
        'max_files' => 'Maximale Dateianzahl: :count',
    ],
    // === Platzhalter ===
    'placeholders' => [
        'select_file' => 'Datei auswählen...',
        'search_files' => 'Dateien durchsuchen...',
        'file_name' => 'Dateiname eingeben...',
    ],
    // === Leere Zustände ===
    'empty' => [
        'no_files' => 'Keine Dateien gefunden',
        'no_results' => 'Keine Dateien entsprechen Ihren Suchkriterien',
        'upload_first' => 'Laden Sie Ihre erste Datei hoch, um zu beginnen',
    ],
    // === Hilfetexte ===
    'help' => [
        'upload_multiple' => 'Sie können mehrere Dateien mit gedrückter STRG-Taste auswählen',
        'drag_drop' => 'Ziehen Sie Dateien hierher, um sie hochzuladen',
        'file_format' => 'Unterstützte Formate: :formats',
        'max_size' => 'Maximale Dateigröße: :size',
    ],
    // === Bestätigungen ===
    'confirmations' => [
        'delete_single' => 'Sind Sie sicher, dass Sie diese Datei löschen möchten?',
        'delete_multiple' => 'Sind Sie sicher, dass Sie :count ausgewählte Dateien löschen möchten?',
        'replace' => 'Eine Datei mit diesem Namen existiert bereits. Möchten Sie sie ersetzen?',
    ],
    // === Fortschrittsmeldungen ===
    'progress' => [
        'uploading_file' => 'Lade :filename hoch...',
        'processing_file' => 'Verarbeite :filename...',
        'validating_file' => 'Überprüfe :filename...',
        'moving_file' => 'Verschiebe :filename...',
    ],
];
