<?php
$lang['mod_acl'] = [
    'ziyaretci'=>'Guest',
    'add' => 'Add ACL',
    'edit' => 'Edit Content',
    'list' => 'ACL List',
    'information' => 'Information!',
    'oke' => 'Operation Successful!',
    'hata' => 'Error Occurred!',
    'error' => 'Fatal Error Occurred!',
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_oke' => 'Image Removed',
    'choose_wish' => 'Please choose if you want',
    'remove_record' => 'Are you sure you want to delete this record?',
    'message' =>
        [
            'oke' =>
                [
                    'add' => 'ACL Added!',
                    'edit' => 'ACL Updated!',
                ],
            'error' =>
                [
                    'add' => 'ACL Could Not Be Added!',
                    'edit' => 'ACL Could Not Be Updated!',
                ],
            'information' =>
                [
                    'moderate' => 'Double-click on the content you want to edit',
                    'move' => 'Drag and drop the content you want to reorder',
                ],
        ],
    'errors' =>
        [
            'record' => 'The specified record could not be found!',
        ],
    'button' =>
        [
            'add' => 'Add',
            'edit' => 'Edit',
            'search' => 'Search',
            'reset' => 'Reset',
            'yenile' => 'Refresh',
            'delete' => 'Delete',
            'gallery' => 'Image Gallery',
            'vazgec' => 'Cancel',
            'kaldir' => 'Remove',
            'evet' => 'Yes',
            'hayir' => 'No',
        ],
    'grid' =>
        [
            0 => 'Showing {from} to {to} of {total} results.',
            1 => 'Page',
            2 => 'Page Count',
            3 => 'Data loading, please wait...',
            4 => 'No data found',
            5 => 'Connection Error',
            6 => 'Search',
            7 => 'Please select at least one row',
            8 => 'Please select only one row',
        ],
    'validator' =>
        [
            'required' => 'Please do not leave the %s field empty',
            'length' => '%s can be minimum %s and maximum %s characters',
            'email' => 'Please enter a valid email address in the %s field.',
            'unique' => 'The %s you entered is already registered in the system. Please specify another %s!',
            'inarray' => 'The %s field can only take values of \'%s\'',
        ],
    'roll' =>
        [
            'ad' => 'ACL Management Roles',
            'add' => 'Can Add ACL',
            'edit' => 'Can Edit ACL',
            'delete' => 'Can Delete ACL',
            'list' => 'Can View ACL Management',
            'moderate_edit' => 'Can Edit ACL Records Not Belonging to Them',
            'moderate_delete' => 'Can Edit ACL Records Not Belonging to Them',
        ],
    'role_errors' =>
        [
            'moderate_edit' => 'You cannot edit ACL records not belonging to you',
            'moderate_delete' => 'You cannot delete ACL records not belonging to you',
            'add' => 'You do not have permission to add ACL',
            'edit' => 'You do not have permission to edit ACL',
            'list' => 'You do not have permission to view ACL management',
            'delete' => 'You do not have permission to delete ACL',
        ],
    'admin' =>
        [
            'list' => 'ACL List',
            'columns' =>
                [
                    'id' => 'ID',
                    'ad' => 'ACL Name',
                    'acl'=>'User Groups'
                ],
        ],
];
