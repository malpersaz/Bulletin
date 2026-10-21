<?php
/**
 * File Management Module - Turkish Language File
 *
 * @package AKM Bulletin CMS
 * @module  file
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['file'] = [
    // === Core Operations ===
    'upload' => [
        'success' => 'Dosya başarıyla yüklendi!',
        'failed' => 'Dosya yükleme başarısız oldu!',
        'error_extension' => 'Geçersiz dosya uzantısı. Yalnızca şu uzantılara sahip dosyalara izin verilir: :extensions.',
        'error_directory' => 'Dizin oluşturulamadı!',
        'error_upload' => 'Dosya yüklenirken hata oluştu',
        'error_move' => 'Yüklenen dosya hedef konuma taşınamadı',
    ],
    'delete' => [
        'success' => 'Dosya başarıyla silindi!',
        'failed' => 'Dosya silme başarısız oldu!',
        'error_not_found' => 'Dosya bulunamadı!',
        'error_permission' => 'Dosya silinirken yetki reddedildi',
    ],
    // === File Types ===
    'types' => [
        'image' => 'Görsel',
        'document' => 'Belge',
        'video' => 'Video',
        'audio' => 'Ses',
        'archive' => 'Arşiv',
        'other' => 'Diğer',
    ],
    // === Status Messages ===
    'status' => [
        'uploading' => 'Yükleniyor...',
        'processing' => 'İşleniyor...',
        'complete' => 'Tamamlandı',
        'error' => 'Hata',
        'queued' => 'Sırada',
        'cancelled' => 'İptal Edildi',
    ],
    // === Validation Messages ===
    'validation' => [
        'required' => 'Dosya seçimi zorunludur',
        'invalid_type' => 'Geçersiz dosya türü',
        'size_exceeded' => 'Dosya boyutu izin verilen maksimum boyutu aşıyor',
        'corrupted' => 'Dosya bozuk görünüyor',
        'not_found' => 'Belirtilen dosya bulunamadı',
    ],
    // === Error Messages ===
    'errors' => [
        'general' => 'Dosya işlenirken bir hata oluştu',
        'permission_denied' => 'Yetki reddedildi. Dosya ve dizin izinlerini kontrol edin',
        'disk_full' => 'Sunucu disk alanı dolu',
        'network_error' => 'Yükleme sırasında ağ hatası oluştu',
        'server_error' => 'Sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin',
        'timeout' => 'Yükleme zaman aşımına uğradı. Lütfen tekrar deneyin',
    ],
    // === Success Messages ===
    'success' => [
        'file_uploaded' => 'Dosya başarıyla yüklendi!',
        'file_deleted' => 'Dosya başarıyla silindi!',
        'file_moved' => 'Dosya başarıyla taşındı!',
        'file_processed' => 'Dosya başarıyla işlendi!',
    ],
    // === File Information ===
    'info' => [
        'name' => 'Dosya Adı',
        'size' => 'Dosya Boyutu',
        'type' => 'Dosya Türü',
        'extension' => 'Uzantı',
        'uploaded_at' => 'Yüklenme Tarihi',
        'modified_at' => 'Son Değişiklik',
        'path' => 'Dosya Yolu',
        'url' => 'Dosya URL',
    ],
    // === Actions ===
    'actions' => [
        'upload' => 'Dosya Yükle',
        'download' => 'İndir',
        'delete' => 'Sil',
        'rename' => 'Yeniden Adlandır',
        'move' => 'Taşı',
        'copy' => 'Kopyala',
        'view' => 'Görüntüle',
        'edit' => 'Düzenle',
    ],
    // === File Size Units ===
    'size_units' => [
        'bytes' => 'Bayt',
        'kb' => 'KB',
        'mb' => 'MB',
        'gb' => 'GB',
        'tb' => 'TB',
    ],
    // === Supported Extensions ===
    'extensions' => [
        'images' => 'jpg, jpeg, png, gif, webp, svg',
        'documents' => 'pdf, doc, docx, xls, xlsx, ppt, pptx, txt',
        'videos' => 'mp4, avi, mov, wmv, flv, webm',
        'audio' => 'mp3, wav, ogg, flac, aac',
        'archives' => 'zip, rar, 7z, tar, gz',
    ],
    // === Configuration ===
    'config' => [
        'max_file_size' => 'Maksimum dosya boyutu: :size',
        'allowed_types' => 'İzin verilen dosya türleri: :types',
        'upload_path' => 'Yükleme dizini: :path',
        'max_files' => 'Maksimum dosya sayısı: :count',
    ],
    // === Placeholders ===
    'placeholders' => [
        'select_file' => 'Bir dosya seçin...',
        'search_files' => 'Dosyalarda ara...',
        'file_name' => 'Dosya adını girin...',
    ],
    // === Empty States ===
    'empty' => [
        'no_files' => 'Dosya bulunamadı',
        'no_results' => 'Arama kriterlerinize uygun dosya bulunamadı',
        'upload_first' => 'Başlamak için ilk dosyanızı yükleyin',
    ],
    // === Helper Text ===
    'help' => [
        'upload_multiple' => 'CTRL tuşuna basılı tutarak birden fazla dosya seçebilirsiniz',
        'drag_drop' => 'Dosyaları yüklemek için buraya sürükleyip bırakın',
        'file_format' => 'Desteklenen formatlar: :formats',
        'max_size' => 'Maksimum dosya boyutu: :size',
    ],
    // === Confirmation Messages ===
    'confirmations' => [
        'delete_single' => 'Bu dosyayı silmek istediğinizden emin misiniz?',
        'delete_multiple' => 'Seçilen :count dosyayı silmek istediğinizden emin misiniz?',
        'replace' => 'Bu isimde bir dosya zaten mevcut. Değiştirmek istiyor musunuz?',
    ],
    // === Progress Messages ===
    'progress' => [
        'uploading_file' => ':filename yükleniyor...',
        'processing_file' => ':filename işleniyor...',
        'validating_file' => ':filename doğrulanıyor...',
        'moving_file' => ':filename taşınıyor...',
    ],
];
