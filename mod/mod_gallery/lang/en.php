<?php
/**
 * Gallery Management Module English Language File
 * Amed Framework CMS - Multilingual Content Management System
 *
 * This file contains all English translations for the gallery management module.
 * Professional translations with modern CMS terminology.
 */

$lang['mod_gallery'] = [
    // Image Upload Interface Labels
    'admin' => [
        1 => 'Image Upload Area',
        2 => 'Click the Browse button below to select images!',
        3 => 'You can select multiple images by holding down the <b>CTRL</b> key on your keyboard.',
        4 => 'Uploading...',
        5 => 'Preview',
        6 => 'Waiting in upload queue...',
        7 => 'File',
        8 => 'Selected Files Count',
        9 => 'Files Waiting in Queue',
        10 => 'The size of the selected image \\\'+file.name+\\\' is too large.',
        11 => 'Browse',
        12 => 'Back',
        14 => 'Image Management',
        17 => 'Are you sure you want to delete this image?',
        18 => 'Delete Image',
        19 => 'Yes',
        20 => 'Cancel',
        21 => 'Save',
        22 => 'Image Upload Area for %s Content',
        23 => 'Image Name',
        24 => 'Double-click on the image you want to rename.',
        25 => 'No images found!',
        26 => '---All Images---',
        27 => 'No registered images found!',
        28 => 'Select a category and add images',
        29 => 'View Original Image',
        30 => 'Edit Image Name',
        31 => 'Remove Image',
        35 => '\'   File %s uploaded to server as %s!   \'',
        36 => 'Image Deleted',
        37 => '"%s" Gallery Images',
        38 => 'Add Images',
        39 => 'Image Edited',
        40 => 'Image named "+original+" has been renamed to "+text+"',
    ],

    // Error Messages
    'hata' => [
        1 => 'Could not get image source',
        2 => 'Specified file is not an image',
        3 => 'Please specify the file where the image will be written',
        4 => 'Only png, jpg, jpeg, gif, bmp files can be generated as output',
        5 => 'Image could not be created',
        6 => 'Image output could not be generated',
    ],

    // Module Description
    'module' => [
        'description' => 'Gallery Module',
    ],

    // AJAX Response Messages
    'ajax' => [
        'success' => 'Success',
        'error' => 'Error',
    ],

    // Role Management
    'roll' => [
        'ad' => 'Gallery Management Roles',
        'add' => 'Can add images.',
        'edit' => 'Can edit added images',
        'delete' => 'Can delete added images',
        'list' => 'Can view image management',
        'moderate' => 'Can manage images belonging to others',
        'moderate_edit' => 'Can edit images belonging to others',
        'moderate_delete' => 'Can delete images belonging to others',
    ],

    // Role-based Error Messages
    'role_errors' => [
        1 => 'You do not have permission to edit images belonging to someone else',
        2 => 'You do not have permission to delete images belonging to someone else.',
        'add' => 'You do not have permission to add images',
        'delete' => 'You do not have permission to delete images',
        'edit' => 'You do not have permission to edit images',
        'list' => 'You do not have permission to view image management',
        'gallery' => 'You do not have permission to add images to galleries',
        'file' => 'You do not have permission to add files to galleries',
    ],

    // General
    'warning' => 'Warning!',
    'info' => 'Info!',
    'message' => 'Message!',
    'help' => 'Help!',
];