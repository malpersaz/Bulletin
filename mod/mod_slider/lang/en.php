<?php
$lang['mod_slider']= [
  'add' => 'Add Slider',
  'edit' => 'Edit Slider',
  'list' => 'Slider List',
  'information' => 'Information!',
  'oke' => 'Operation Successful!',
  'hata' => 'An Error Occurred!',
  'error' => 'Fatal Error Occurred!',
  'remove_image' => 'Are you sure you want to remove this image?',
  'remove_image_oke' => 'Image Removed',
  'choose_wish' => 'Select if you wish',
  'remove_record' => 'Are you sure you want to delete this record?',
  'messages' => [
    'confirm_delete' => 'Are you sure you want to delete this?',
    'success_delete' => 'Record successfully deleted!',
  ],
  'message' => [
    'oke' => 
     [
      'add' => 'Slider Added!',
      'edit' => 'Slider Edited!',
    ],
    'error' => 
     [
      'add' => 'Could not add slider!',
      'edit' => 'Could not edit slider!',
    ],
    'information' => 
     [
      'moderate' => 'Double click the content you want to edit',
      'move' => 'Drag and drop the content you want to reorder',
    ],
  ],
  'errors' => 
   [
    'record' => 'The specified record could not be found!',
  ],
  'button' => [
    'ok' => 'OK',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'yes' => 'Yes',
    'no' => 'No',
    'add' => 'Add',
    'edit' => 'Edit',
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
    0 => 'Showing {from} to {to} out of {total} Results.',
    1 => 'Page',
    2 => 'Page Count',
    3 => 'Data is loading, please wait...',
    4 => 'No data found',
    5 => 'Connection Error',
    6 => 'Search',
    7 => 'Please Select At Least One Row',
    8 => 'Please Select Only One Row',
    'publish' => 
     [
      'key' => 'Publish',
      'value' => 'Are you sure you want to publish this slider?',
    ],
    'unpublish' => 
     [
      'key' => 'Unpublish',
      'value' => 'Are you sure you want to unpublish this slider?',
    ],
  ],
  'validator' => 
   [
    'required' => 'Please do not leave the %s field empty',
    'length' => '%s can be minimum %s and maximum %s characters',
    'email' => 'Please enter a valid E-Mail address in the %s field.',
    'unique' => 'The %s you entered is registered. Please specify another %s!',
    'inarray' => '%s field can only take \' %s \' values',
  ],
  'roll' => 
   [
    'ad' => 'Slider Management Roles',
    'add' => 'Can Add Slider',
    'edit' => 'Can Edit Slider',
    'delete' => 'Can Delete Slider',
    'list' => 'Can View Slider Management',
    'change_status' => 'Should the added slider be published immediately?',
    'edit_status' => 'Should the edited slider be published immediately?',
    'approval' => 'Can approve sliders waiting for approval?',
    'gallery' => 'Can add gallery to content?',
    'moderate_edit' => 'Can edit slider record that does not belong to them',
    'moderate_delete' => 'Can delete slider record that does not belong to them',
  ],
  'role_errors' => 
   [
    'moderate_edit' => 'You cannot edit a slider record that does not belong to you',
    'moderate_delete' => 'You cannot delete a slider record that does not belong to you',
    'add' => 'You do not have permission to add a slider',
    'edit' => 'You do not have permission to edit a slider',
    'list' => 'You do not have permission to view slider management',
    'delete' => 'You do not have permission to delete a slider',
  ],
  'admin' => 
   [
    'columns' => 
     [
      'id' => 'ID',
      'lang' => 'Language',
      'name' => 'Slider Name',
      'url' => 'Slider URL',
      'status' => 'Publish?',
      'target' => 'When Clicked',
      'created_at' => 'Date',
      'description' => 'Description',
      'image' => 'Image',
      'image_desktop' => 'Desktop - 16:9',
      'image_mobile' => 'Mobile - 3:4',
      'acl_id' => 'Who can view this content? ( <a href=\'mod_acl/list/\' target=\'_blank\'>ACL - Access Control List</a> )',
    ],
    'radio' => 
     [
      'publish_yes' => 'Yes',
      'publish_no' => 'No',
      'target_e' => 'Open in Same Page',
      'target_h' => 'Open in Different Page',
    ],
  ],
  'datatable' => ['emptyTable' => 'No records found', 'loadingRecords' => 'Loading...'],
  'wizard' => ['label' => ['upload_text' => 'Click and upload image']],
  'cropper' => [
    'title_desk' => 'Desktop - 16:9',
    'title_mob' => 'Mobile - 3:4',
    'error_plugin' => 'Cropper.js or jQuery plugin not loaded.',
    'error_title' => 'Error',
    'finish' => 'Finish',
    'error_canvas' => 'Error on crop canvas.',
  ],
  'acl_all' => 'Public',
  'empty' => ['title' => 'No Records Found', 'no_data' => 'No sliders have been added yet.', 'no_search_results' => 'No sliders found matching your search criteria.'],
  'search_placeholder' => 'Search Sliders...',
];
