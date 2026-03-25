<?php
/**
 * User Module - English Language File
 *
 * @package Rojname
 * @module  mod_user
 * @version 1.0.0
 * @author  AKM Software
 * @license Proprietary
 */

// Prevent direct access
if (!defined('AKM_LOADED')) {
    die('Direct access to this file is not allowed.');
}

$lang['mod_user'] = [
    'add' => 'Add User',
    'edit' => 'Edit User',
    'list' => 'User List',
    'information' => 'Information!',
    'oke' => 'Operation Successful!',
    'success' => 'Success!',
    'last_step' => 'Last Step',
    'error_title' => 'Error Occurred!',
    'error_label' => 'Error!',
    'error' => 'Fatal Error Occurred!',
    'remove_image' => 'Are you sure you want to remove this image?',
    'remove_image_oke' => 'Image Removed',
    'choose_wish' => 'Please choose if you want',
    'remove_record' => 'Are you sure you want to delete this record?',
    'message' =>
        [
            'oke' =>
                [
                    'add' => 'User Added!',
                    'edit' => 'User Updated!',
                ],
            'error' =>
                [
                    'add' => 'User Could Not Be Added!',
                    'edit' => 'User Could Not Be Updated!',
                ],
        ],
    'errors' =>
        [
            'record' => 'The specified record could not be found!',
        ],
    'hata' =>
        [
            '1' => 'Username field is required',
            '2' => 'Password field is required',
            '3' => 'Email field is required',
            '4' => 'Please enter a valid email address',
            '5' => 'Password must be at least 6 characters',
            '6' => 'Passwords do not match',
            '7' => 'Username already taken',
            '8' => 'Email address already in use',
            '9' => 'Username or password is incorrect',
            '10' => 'No such user found',
            '11' => 'Your account is not yet active',
            '12' => 'Permission Error',
            '13' => 'Please fill in all fields',
        ],
    'button' =>
        [
            'add' => 'Add',
            'edit' => 'Edit',
            'refresh' => 'Refresh',
            'delete' => 'Delete',
            'gallery' => 'Image Gallery',
            'file' => 'File Manager',
            'cancel' => 'Cancel',
            'deactivate' => 'Remove',
            'yes' => 'Yes',
            'no' => 'No',
            'select_image' => 'Select Image',
        ],
    '1' => 'Username',
    '2' => 'Password',
    '3' => 'E-mail',
    '4' => 'Admin Panel',
    '5' => 'User Panel',
    '6' => 'Login successful. Please wait while you are redirected..',
    '7' => 'You do not have permission to perform this operation',
    '8' => 'Please log in.',
    '9' => 'Login',
    '10' => 'Logout',
    '11' => 'Password Change',
    '12' => 'Change',
    '13' => 'Current Password',
    'current_password' => 'وشەی تێپەڕی ئێستا',
    '14' => 'New Password',
    '15' => 'Confirm New Password',
    '16' => 'Your password has been successfully changed!',
    '17' => 'Information Change',
    '18' => 'Your Name',
    '19' => 'Your Surname',
    '20' => 'Your E-mail Address',
    '21' => 'Your Date of Birth',
    '22' => 'Your information has been successfully updated!',
    '23' => 'Mobile Phone',
    '24' => 'Profile Picture',
    '25' => 'Forgot Password',
    '26' => 'Account Activation',
    '27' => 'Register',
    '28' => 'City',
    '29' => 'Blog Name',
    '30' => 'About Me',
    '31' => 'Maximum 255 characters.',
    '32' => 'You have %s unread messages.',
    'groups' => [
        'Site Yöneticisi' => 'Site Manager',
        'Editörler' => 'Editors',
        'Normal Üye' => 'Member'
    ]
];

$lang['mod_user']['admin'] = [
    'add_area' => 'User Addition Area',
    '2' => 'Add User',
    'added' => 'User Added!',
    '4' => 'Add New User',
    'list' => 'User List',
    'edit_area' => 'User Editing Area',
    '7' => 'Edit User',
    'updated' => 'User Successfully Updated',
    'acl' => 'User Group Role Control (ACL)',
    '10' => 'Roles',
    '11' => 'Refresh Permissions',
    'permissions_updated' => 'User Permissions Successfully Updated',
    '13' => 'User Group',
    'management' => 'Membership Management',
    '15' => 'Membership Settings',
    'site_settings' => 'Site Settings',
    'social_media' => 'Social Media',
    '16' => 'Update Membership Settings',
    'settings_updated' => 'Membership Settings Updated!',
    '18' => 'Yes',
    '19' => 'No',
    'delete' => 'Delete',
    '21' => 'Are you sure you want to remove your profile picture?',
    '22' => 'Your information has been successfully updated!<br>
						But we could not change your profile picture!',
    '23' => 'Your profile picture has been removed',
    'columns' =>
        [
            'id' => 'ID',
            'username' => 'ناوی بەکارهێنەر',
            'email' => 'ئیمەیڵ',
            'name' => 'ناو',
            'last_name' => 'پاشناو',
            'status' => 'بارودۆخی هەژمار',
            'group_id' => 'گرووپی بەکارهێنەر',
            'image' => 'وێنە',
            'city' => 'شار',
            'blog_name' => 'ناوی بلۆگ',
            'about' => 'دەربارەی من',
        ],
    'radio' =>
        [
            'publish_yes' => 'Yes',
            'publish_no' => 'No',
            'deck_yes' => 'Yes',
            'deck_no' => 'No',
        ],
];

$lang['mod_user']['admin']['settings'] = [
    '1' => 'Open',
    '2' => 'Closed',
    '3' => 'New User Group',
    '4' => 'Is Membership Open?',
    '5' => 'Membership Activation',
    '6' => 'Automatic Approval',
    '7' => 'Email Approval',
    '8' => 'Admin Approval',
    '9' => 'Can two people connect to the system with the same account at the same time?',
    '10' => 'Yes',
    '11' => 'No'


];
// Flexi Grid Required
$lang['mod_user']['grid'] = [
    '1' => 'Showing {from} to {to} of {total} results.',
    '2' => 'Page',
    '3' => 'Page Count',
    '4' => 'Data loading, please wait...',
    '5' => 'No data found',
    '6' => 'Connection Error',
    '7' => 'Search',
    '8' => 'Username',
    '9' => 'E-mail',
    '10' => 'Add New User',
    '11' => 'Edit',
    '12' => 'Refresh',
    'delete' => 'Delete',
    '14' => 'User List',
    '15' => 'Please select a row to edit',
    '16' => 'Please select at least one row to delete!',
    '17' => "Total <font color=red>' + $('.trSelected',grid).length + '</font> users will be deleted. <br/>Are you sure you want to perform this operation?</br> Remember that all content added by these users, categories etc.. Everything will be deleted!",
    '18' => 'User list refreshed',
    '19' => 'User deleted',
    '20' => 'Yes',
    '21' => 'Cancel',
    '22' => 'User Group',
    '23' => 'Activate',
    '24' => 'Please select the account you want to activate',
    '25' => 'Are you sure you want to activate the selected accounts?',
    '26' => 'Accounts activated!',
    '27' => 'Active',
    '28' => 'Waiting for Email Activation',
    '29' => 'Waiting for Admin Activation.',
    '30' => 'Account Status',
    '31'=>  'Please select at least one row',
    'status' =>
        [
            'key' => 'Activate',
            'value' => 'Are you sure you want to activate this user?',
        ],
    'deactivate' =>
        [
            'key' => 'Deactivate',
            'value' => 'Are you sure you want to deactivate this user?',
        ],
];
// Errors
$lang['mod_user']['error'] = [
    '1' => 'Please specify a username',
    '2' => 'Please specify a password',
    '3' => 'Please specify an e-mail',
    '4' => 'Please specify a valid e-mail address',
    '5' => 'The specified username is registered in the system',
    '6' => 'The specified e-mail is registered in the system',
    '7' => 'Your username can consist of a-z A-Z 0-9 characters',
    '8' => 'Your username can be 3 to 12 characters',
    '9' => 'Your password can be 6 to 16 characters',
    '10' => 'The username you entered could not be found',
    '11' => 'Username or password is incorrect.',
    '12' => 'Permission Error',
    '13' => 'Please write your current password',
    '14' => 'Please write your new password',
    '15' => 'Please repeat your new password',
    '16' => 'Your new password and new password confirmation must be the same.',
    '17' => 'The specified password is incorrect',
    '18' => 'Please specify your name',
    '19' => 'Please specify your surname',
    '20' => 'Please specify your date of birth',
    '21' => 'Your name can be 2 to 50 characters',
    '22' => 'Your surname can be 2 to 50 characters',
    '23' => 'Your name can consist of [^a-zA-Z] characters',
    '24' => 'Your surname can consist of [^a-zA-Z] characters',
    '25' => 'Please enter a valid date. Date format should be YYYY-MM-DD.',
    '26' => 'Please write a valid e-mail address.',
    '27' => 'The mobile phone number you entered is invalid.',
    '28' => 'Your account was found but is not active.<br/> Please activate your membership
						<a href="activation/" style="color:#000">here</a>.',
    '29' => 'IP protection is defined for this account and your IP address does not match',
    '30' => 'This account does not appear to be connected to the system right now, you may have forgotten to open your account on another computer!',
    '31' => 'User not found!',
    '32' => 'System Error'
];


$lang['mod_user']['admin']['active'] = [
    '1' => 'Hello Dear <b>%s %s</b>.',
    '2' => 'Your account located at %s has been activated by the administrator<br/>
    You can now log in with your username and password if you wish.'
];


// User Registration

$lang['mod_user']['register'] = [
    '1' => 'User registration has been temporarily stopped.<br/>
		Thank you for your understanding.',
    '2' => 'User Registration Form',
    '3' => 'Your Name',
    '4' => 'Your Surname',
    '5' => 'Your Username',
    '6' => 'E-mail',
    '7' => 'Your Password',
    '8' => 'Confirm Password',
    '9' => 'Your Phone Number',
    '10' => 'Security Code',
    '11' => 'Register',
    '12' => 'Please fill out the form to register.',
    '13' => 'Your membership has been successfully completed! You can now log in to your account.',
    '14' => 'Your membership has been successfully completed. <br/>However, your account is not yet active. Please verify your account by clicking the activation link sent to your email address.',
    '15' => 'Membership Activation',
    '16' => 'Password Reset Request',
    '17' => 'Your New Password',
    '18' => 'Membership Registration',
    '19' => 'Your account has been successfully created. <br/>For security reasons, your account must be approved by an administrator. You will be notified via email after approval.'
];

$lang['mod_user']['register']['error'] = [
    '1' => 'Please specify your name',
    '2' => 'Please specify your surname',
    '3' => 'Please specify your username',
    '4' => 'Please specify your e-mail address',
    '5' => 'Please specify your mobile phone number',
    '6' => 'There are invalid characters in your name',
    '7' => 'There are invalid characters in your surname',
    '8' => 'Your username can be 2 to 12 characters',
    '9' => 'Your username can consist of a-z A-Z 0-9 characters',
    '10' => 'Your password can be 6 to 16 characters',
    '11' => 'Please specify a valid e-mail address',
    '12' => 'Please specify a valid mobile phone number.',
    '13' => 'The specified username is registered in the system',
    '14' => 'The specified e-mail address is registered in the system',
    '15' => 'You entered the security code incorrectly, please check and try again.',
    '16' => 'An error occurred while sending activation e-mail.<br/>
	 Please contact the system administrator. '
];

// JavaScript validation messages
$lang['mod_user']['register']['js'] = [
    'checking' => '⏳ Checking...',
    'username_available' => '✅ Username available',
    'username_exists' => '❌ This username is already taken',
    'username_short' => 'Username must be at least 2 characters',
    'email_available' => '✅ Email available',
    'email_exists' => '❌ This email is already registered',
    'check_failed' => '⚠️ Check could not be performed',
    'register_success' => 'Your membership has been successfully created! You can log in.',
    'errors_title' => 'Errors:'
];

// Country codes
$lang['mod_user']['register']['country_codes'] = [
    'select' => 'Select Country',
    'phone' => 'Phone'
];

// Activation

$lang['mod_user']['activation'] = [
    '1' => 'Account Activation',
    '2' => 'Please write your activation code in the field below.',
    '3' => 'Activation Code',
    '4' => 'Security Code',
    '5' => 'Activate',
    '6' => 'If you have lost your activation code
	<a href="resend-activation" style="color:#000">Click here</a> to request a new activation code.',
    '7' => 'Your account has been successfully activated. If you wish
	<a href="login" style="color:#000">Click here</a> to log in to your account.',
    '8' => 'Membership Activation'
];
$lang['mod_user']['activation']['error'] = [
    '1' => 'You entered the security code incorrectly, please check your activation code
		and try again.',
    '2' => 'Please write your activation code.',
    '3' => 'The specified activation code is invalid,
		Please check and try again.'
];

$lang['mod_user']['resend_activation'] = [
    '1' => 'Your activation code has been sent. Please check your e-mail to activate your account',
    '2' => 'So that we can send you an activation code.<br/> Write the e-mail address associated with your account in the field below.',
    '3' => 'Activation Code',
    '4' => 'E-mail',
    '5' => 'Security Code',
    '6' => 'Send',
    '7' => 'Activation Code'
];
$lang['mod_user']['resend_activation']['error'] = [
    '1' => 'You entered the security code incorrectly, please check your activation code
		and try again.',
    '2' => 'Please write your e-mail address.',
    '3' => 'The specified activation code is invalid,
		Please check and try again.'
];


// Password Reset

$lang['mod_user']['forgot_password'] = [
    '1' => 'Password Reset',
    '2' => 'Please write your e-mail address in the field below.',
    '3' => 'E-mail Address',
    '4' => 'Send',
    '5' => 'Cancel',
    '6' => 'E-mail address is required!',
    '7' => 'Invalid e-mail address!',
    '8' => 'Please write a valid e-mail address.'
];
$lang['mod_user']['forgot_password']['error'] = [
    '1' => 'The specified e-mail address is not registered in the system!',
    '2' => 'The e-mail you specified is not registered in the system!',
    '3' => 'The specified e-mail address is invalid,
		Please check and try again.'
];

$lang['mod_user']['sifre'] = [
    '1' => 'Password Reset',
    '2' => 'Please write your new password in the field below.',
    '3' => 'New Password',
    '4' => 'Confirm New Password',
    '5' => 'Change',
    '6' => 'Password field is required!',
    '7' => 'Password confirmation field is required!',
    '8' => 'Your new password must be at least 6 characters.',
    '9' => 'Passwords do not match!',
    '10' => 'The password reset link you used is invalid or has expired. Please request a new password reset.',
    '11' => 'Your password has been successfully changed. You can now log in with your new password.'
];
$lang['mod_user']['sifre']['error'] = [
    '1' => 'The password reset link you used is invalid or has expired. Please request a new password reset.'
];

// JavaScript validation messages
$lang['mod_user']['register']['js'] = [
    'checking' => '⏳ Checking...',
    'username_available' => '✅ Username available',
    'username_exists' => '❌ This username is already taken',
    'username_short' => 'Username must be at least 2 characters',
    'email_available' => '✅ Email available',
    'email_exists' => '❌ This email is already registered',
    'check_failed' => '⚠️ Check failed',
    'register_success' => 'Your registration has been successfully created! You can now log in.',
    'errors_title' => 'Errors:',
    'register_success' => 'Your registration has been successfully created! You can now log in.'
];

// JavaScript validation messages
$lang['mod_user']['register']['js']['validation_required'] = 'This field is required';
$lang['mod_user']['register']['js']['validation_required_name'] = 'Please enter your name';
$lang['mod_user']['register']['js']['validation_required_surname'] = 'Please enter your surname';
$lang['mod_user']['register']['js']['validation_required_username'] = 'Please enter a username';
$lang['mod_user']['register']['js']['validation_username_short'] = 'Username must be at least 2 characters';
$lang['mod_user']['register']['js']['validation_username_long'] = 'Username must not exceed 20 characters';
$lang['mod_user']['register']['js']['validation_required_email'] = 'Please enter your email address';
$lang['mod_user']['register']['js']['validation_invalid_email'] = 'Please enter a valid email address';
$lang['mod_user']['register']['js']['validation_required_password'] = 'Please enter a password';
$lang['mod_user']['register']['js']['validation_password_short'] = 'Password must be at least 6 characters';
$lang['mod_user']['register']['js']['validation_password_long'] = 'Password must not exceed 20 characters';
$lang['mod_user']['register']['js']['validation_confirm_password'] = 'Please confirm your password';
$lang['mod_user']['register']['js']['validation_required_phone'] = 'Please enter your phone number';
$lang['mod_user']['register']['js']['validation_required_captcha'] = 'Please enter the security code';

// Captcha helper text
$lang['mod_user']['register']['captcha_refresh'] = 'Click the image to refresh the code';

// Country codes
$lang['mod_user']['register']['country_codes'] = [
    'select' => 'Select Country',
    'phone' => 'Phone'
];

// Panel Welcome Messages
$lang['mod_user']['panel'] = [
    'welcome' => 'Hello Dear %s',
    'description' => 'You can choose what you want to do from the menus. You can change your membership settings from the top right.',
    'pending_content' => 'Pending',
    'view_reports' => 'View Detailed Reports',
    'welcome_main' => 'Welcome,',
    'panel_title' => 'Admin Panel',
    'goto_content' => 'Go to Content Management',
    'search_placeholder' => 'Search Content...',
    'quick_access' => 'Quick Access',
    'home_title' => 'Home Page',
    'admin_brand' => 'Rojname Admin',
];

// User Profile Menu Items
$lang['mod_user']['profile_menu'] = [
    'edit_profile' => 'Edit Profile',
    'change_password' => 'Change Password',
    'inbox' => 'Inbox',
    'sent_box' => 'Sent Box',
    'new_message' => 'New Message',
    'to_multiple_users' => 'To Multiple Users',
    'to_specific_group' => 'To a Specific Group',
    'to_all_members' => 'To All Members',
];

// AJAX Responses
$lang['mod_user']['ajax'] = [
    'login_success' => 'Login successful',
    'login_error' => 'Username or password is incorrect',
    'validation_error' => 'Please correct the errors and try again',
    'ssid_invalid' => 'Invalid SSID',
    'membership_deleted' => 'Membership may have been deleted',
    'success' => 'Success',
    'error' => 'Error occurred during login',
    'connection_error' => 'Connection error. Please try again.',
    'invalid_credentials' => 'Invalid username or password',
    'account_inactive' => 'Your account is not active',
    'permission_denied' => 'Permission denied',
];

// === Restriction Errors  ===
$lang['mod_user']['role_errors'] = [
    'add' => 'You do not have permission to add users',
    'delete' => 'You do not have permission to delete users',
    'edit' => 'You do not have permission to edit users',
    'list' => 'You do not have permission to view user list',
    'profil' => 'You do not have permission to view user profiles',
    'settings' => 'You do not have permission to access user settings',
    'register' => 'You do not have permission to access user registration',
    'login' => 'You do not have permission to access user login',
    'logout' => 'You do not have permission to access user logout',
    'activation' => 'You do not have permission to access user activation',
    'forgot_password' => 'You do not have permission to access password recovery',
    'profil_resim' => 'You do not have permission to change profile pictures',
    'acl' => 'You do not have permission to manage user permissions',
    'moderate_edit' => 'You do not have permission to edit other users',
    'moderate_delete' => 'You do not have permission to delete other users',
];

// Additional Validation Messages
$lang['mod_user']['validation']['username_min_length'] = 'Username must be at least 2 characters';

// JavaScript Modal Messages
$lang['mod_user']['js_modal'] = [
    'pleaseFillAll' => 'Please fill in all fields',
    'connectionError' => 'Connection error. Please try again.',
    'timeoutError' => 'Request timeout',
    'networkError' => 'Network error - please check your connection',
    'serverError' => 'Server response error',
    'signingIn' => 'Signing in...',
    'loginSuccess' => 'Login successful!',
    'redirecting' => 'Redirecting...',
    'loginFailed' => 'Login failed',
    'alternativeOptions' => 'Alternative Options',
];

// List Page
$lang['mod_user']['list'] = [
    'management' => 'Record Management',
    'action' => 'Action...',
    'apply' => 'Apply',
    'actions' => 'Actions',
    'select' => 'Select...',
    'search' => 'Search',
    'reset' => 'Reset',
];

// DataTable
$lang['mod_user']['datatable'] = [
    'sortAscending' => ': activate to sort column ascending',
    'sortDescending' => ': activate to sort column descending',
    'emptyTable' => 'No data available in table',
    'info' => '<span class=\'seperator\'>|</span> Showing <b>_START_</b> to <b>_END_</b> of <b>_TOTAL_</b> entries',
    'infoEmpty' => 'No entries found',
    'infoFiltered' => '(filtered from _MAX_ total entries)',
    'lengthMenu' => '<span class=\'seperator\'>|</span> Show _MENU_ entries',
    'search' => 'Search:',
    'zeroRecords' => 'No matching records found',
    'loadingRecords' => 'Loading...',
    'processing' => 'Processing...',
    'first' => 'First',
    'last' => 'Last',
    'next' => 'Next',
    'previous' => 'Previous',
    'selected_records' => '_TOTAL_ records selected:  ',
    'ajax_error' => 'Could not complete request. Please check your internet connection.',
    'print' => 'Print',
    'print_info' => 'Please press "CTRL+P" to print and "ESC" to exit.',
    'copy' => 'Copy',
    'select_action' => 'Please select an action',
    'loading_message' => 'Processing...',
];

// Roles
$lang['mod_user']['roll'] = [
    'roles' => 'User Roles',
    'add' => 'Can Add Users',
    'edit' => 'Can Edit Users',
    'delete' => 'Can Delete Users',
    'list' => 'Can View User Management',
    'acl' => 'Can Edit User Permissions',
    'settings' => 'Can Change User Settings',
    'link' => 'Can View Admin Links',
    'login' => 'Can Login',
    'logout' => 'Can Logout',
    'register' => 'Can Register',
    'activation' => 'Can Activate Account',
    'forgot_password' => 'Can Reset Password',
    'profil_resim' => 'Can Change Profile Picture',
    'moderate_edit' => 'Can Edit Other Users',
    'moderate_delete' => 'Can Delete Other Users',
    'profil' => 'Can View Profile'
];

// List Page
$lang['mod_user']['list'] = [
    'management' => 'بەڕێوەبردنی تۆمار',
    'action' => 'کردار...',
    'apply' => 'جێبەجێکردن',
    'actions' => 'کردارەکان',
    'select' => 'هەڵبژاردن...',
    'search' => 'گەڕان',
    'reset' => 'سفرکردنەوە',
];

// DataTable
$lang['mod_user']['datatable'] = [
    'sortAscending' => ': چالاککردن بۆ ڕیزکردنی ستوون بەرەو سەرەوە',
    'sortDescending' => ': چالاککردن بۆ ڕیزکردنی ستوون بەرەو خوارەوە',
    'emptyTable' => 'هیچ داتایەک لە خشتەکەدا بەردەست نییە',
    'info' => '<span class=\'seperator\'>|</span> پیشاندانی <b>_START_</b> تا <b>_END_</b> لە <b>_TOTAL_</b> تۆمار',
    'infoEmpty' => 'هیچ تۆمارێک نەدۆزرایەوە',
    'infoFiltered' => '(پاڵێوراوە لە _MAX_ کۆی گشتی تۆمارەکان)',
    'lengthMenu' => '<span class=\'seperator\'>|</span> پیشاندانی _MENU_ تۆمار',
    'search' => 'گەڕان:',
    'zeroRecords' => 'هیچ تۆمارێکی گونجاو نەدۆزرایەوە',
    'loadingRecords' => 'بارکردن...',
    'processing' => 'پرۆسەکردن...',
    'first' => 'یەکەم',
    'last' => 'کۆتایی',
    'next' => 'دواتر',
    'previous' => 'پێشوو',
    'selected_records' => '_TOTAL_ تۆمار هەڵبژێردرا: ',
    'ajax_error' => 'داواکاری تەواو نەبوو. تکایە هێڵی ئینتەرنێتەکەت بپشکنە.',
    'print' => 'چاپکردن',
    'print_info' => 'تکایە "CTRL+P" بۆ چاپکردن و "ESC" بۆ دەرچوون فشار بدە.',
    'copy' => 'لەبەرگرتنەوە',
    'select_action' => 'تکایە کردارێک هەڵبژێرە',
    'loading_message' => 'پرۆسەکردن...',
];

// Filter & Sort Generic Keys
$lang['mod_user']['filter'] = [
    'title' => 'پاڵاوتن و ڕیزکردن',
    'sort_label' => 'ڕیزبەندی',
    'close' => 'داخستن',
    'newest' => 'نوێترین',
    'oldest' => 'کۆنترین',
    'title_az' => 'ناونیشان (A-Z)',
    'title_za' => 'ناونیشان (Z-A)',
    'view_count' => 'ژمارەی بینین'
];

// FAB Menu
$lang['mod_user']['fab'] = [
    'add_announcement' => 'Add Announcement',
    'add_video' => 'Add Video',
    'add_content' => 'Add Content',
    'add_publication' => 'Add Publication',
    'add_announcement_alert' => 'Add Announcement Form',
    'add_video_alert' => 'Add Video Form',
    'add_content_alert' => 'Add Content Wizard',
    'add_publication_alert' => 'Add Publication Wizard'
];

// --- Otomatîk Zêdekirin (Senkronîzasyon) ---
$lang['mod_user']['password_strength']['weak'] = 'Zayıf';
$lang['mod_user']['password_strength']['fair'] = 'Orta';
$lang['mod_user']['password_strength']['good'] = 'İyi';
$lang['mod_user']['password_strength']['strong'] = 'Güçlü';
$lang['mod_user']['password_match']['success'] = 'Şifreler eşleşti';
$lang['mod_user']['password_match']['error'] = 'Şifreler uyuşmuyor';
$lang['mod_user']['js']['updating'] = 'Güncelleniyor...';
$lang['mod_user']['js']['warning'] = 'Uyarı';
$lang['mod_user']['js']['weak_password'] = 'Zayıf Şifre';
$lang['mod_user']['js']['password_len_req'] = 'En az 6 karakter';
$lang['mod_user']['js']['password_complex_req'] = 'Büyük, küçük harf ve rakamlar';
$lang['mod_user']['error_fatal'] = 'Ölümcül Hata Oluştu!';
$lang['mod_user']['username'] = 'Kullanıcı Adı *';
$lang['mod_user']['password'] = 'Şifre *';
$lang['mod_user']['email'] = 'E-posta *';
$lang['mod_user']['admin_panel'] = 'Yönetim Paneli';
$lang['mod_user']['user_panel'] = 'Üye Paneli';
$lang['mod_user']['login_success_wait'] = 'Giriş Başarılı. Lütfen Bekleyin Yönlendiriliyorsunuz..';
$lang['mod_user']['no_permission'] = 'Bu İşlemi Yapmaya Permissionniz Yok';
$lang['mod_user']['please_login'] = 'Lütfen Giriş yapın.';
$lang['mod_user']['login'] = 'Giriş';
$lang['mod_user']['logout'] = 'Çıkış';
$lang['mod_user']['password_change'] = 'Şifre Değişikliği';
$lang['mod_user']['change'] = 'Değiştir';
$lang['mod_user']['current_password_label'] = 'Şimdiki Şifreniz';
$lang['mod_user']['new_password'] = 'Yeni Şifreniz';
$lang['mod_user']['confirm_new_password'] = 'Yeni Şifre Tekrar';
$lang['mod_user']['password_changed_success'] = 'Şifreniz Başarıyla Değiştirildi!';
$lang['mod_user']['info_change'] = 'Bilgi Değişikliği';
$lang['mod_user']['your_name'] = 'Adınız *';
$lang['mod_user']['your_surname'] = 'Soyadınız *';
$lang['mod_user']['your_email'] = 'E-Posta Adresiniz';
$lang['mod_user']['your_dob'] = 'Doğum Tarihiniz';
$lang['mod_user']['info_updated_success'] = 'Bilgileriniz başarıyla güncellendi!';
$lang['mod_user']['mobile_phone'] = 'Cep No *';
$lang['mod_user']['profile_picture'] = 'Profil Resmi';
$lang['mod_user']['forgot_password']['password'] = 'Şifrenizi Sıfırlayabilmemiz İçin.<br/> Aşağıdaki Alana Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın.';
$lang['mod_user']['forgot_password']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['forgot_password']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['forgot_password']['error']['reset_code_send_error'] = 'Error occurred while sending password reset code. Please contact the system administrator.';
$lang['mod_user']['forgot_password']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['forgot_password']['error']['email_required'] = 'Please write the e-mail address associated with your account.';
$lang['mod_user']['forgot_password']['error']['invalid_email_format'] = 'You entered an invalid e-mail address.';
$lang['mod_user']['forgot_password']['error']['email_not_registered'] = 'The specified e-mail address is not registered to any account.';
$lang['mod_user']['forgot_password']['info_1'] = 'We have sent you an e-mail so you can reset your password.<br/>Please check your e-mail and follow the steps specified in the e-mail.';
$lang['mod_user']['forgot_password']['info_2'] = 'So that we can reset your password.<br/> Write the e-mail address associated with your account in the field below.';
$lang['mod_user']['forgot_password']['title'] = 'Password Reset';
$lang['mod_user']['forgot_password']['email'] = 'E-mail';
$lang['mod_user']['forgot_password']['security_code'] = 'Security Code';
$lang['mod_user']['forgot_password']['send_button'] = 'Send';
$lang['mod_user']['forgot_password']['forgot_password_title'] = 'Forgot Password';
$lang['mod_user']['account_activation'] = 'Hesap Aktifleştirme';
$lang['mod_user']['register']['error']['name_required'] = 'Please specify your name';
$lang['mod_user']['register']['error']['surname_required'] = 'Please specify your surname';
$lang['mod_user']['register']['error']['username_required'] = 'Please specify your username';
$lang['mod_user']['register']['error']['email_required'] = 'Please specify your e-mail address';
$lang['mod_user']['register']['error']['phone_required'] = 'Please specify your mobile phone number';
$lang['mod_user']['register']['error']['name_invalid_chars'] = 'There are invalid characters in your name';
$lang['mod_user']['register']['error']['surname_invalid_chars'] = 'There are invalid characters in your surname';
$lang['mod_user']['register']['error']['username_length'] = 'Your username can be 2 to 12 characters';
$lang['mod_user']['register']['error']['username_invalid_chars'] = 'Your username can consist of a-z A-Z 0-9 characters';
$lang['mod_user']['register']['error']['password_length'] = 'Your password can be 6 to 16 characters';
$lang['mod_user']['register']['error']['invalid_email'] = 'Please specify a valid e-mail address';
$lang['mod_user']['register']['error']['invalid_phone'] = 'Please specify a valid mobile phone number.';
$lang['mod_user']['register']['error']['username_registered'] = 'The specified username is registered in the system';
$lang['mod_user']['register']['error']['email_registered'] = 'The specified e-mail address is registered in the system';
$lang['mod_user']['register']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['register']['error']['activation_email_error'] = 'An error occurred while sending activation e-mail.<br/>
	 Please contact the system administrator. ';
$lang['mod_user']['register']['registration_stopped'] = 'User registration has been temporarily stopped.<br/>
		Thank you for your understanding.';
$lang['mod_user']['register']['form_title'] = 'User Registration Form';
$lang['mod_user']['register']['your_name'] = 'Your Name';
$lang['mod_user']['register']['your_surname'] = 'Your Surname';
$lang['mod_user']['register']['your_username'] = 'Your Username';
$lang['mod_user']['register']['email'] = 'E-mail';
$lang['mod_user']['register']['your_password'] = 'Your Password';
$lang['mod_user']['register']['confirm_password'] = 'Confirm Password';
$lang['mod_user']['register']['your_phone'] = 'Your Phone Number';
$lang['mod_user']['register']['security_code'] = 'Security Code';
$lang['mod_user']['register']['register_button'] = 'Register';
$lang['mod_user']['register']['fill_form'] = 'Please fill out the form to register.';
$lang['mod_user']['register']['registration_success'] = 'Your membership has been successfully completed! You can now log in to your account.';
$lang['mod_user']['register']['registration_success_activation'] = 'Your membership has been successfully completed. <br/>However, your account is not yet active. Please verify your account by clicking the activation link sent to your email address.';
$lang['mod_user']['register']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['register']['password_reset_request'] = 'Password Reset Request';
$lang['mod_user']['register']['your_new_password'] = 'Your New Password';
$lang['mod_user']['register']['membership_registration'] = 'Membership Registration';
$lang['mod_user']['register']['registration_success_admin'] = 'Your account has been successfully created. <br/>For security reasons, your account must be approved by an administrator. You will be notified via email after approval.';
$lang['mod_user']['city'] = 'Şehir';
$lang['mod_user']['blog_name'] = 'Blog Adı';
$lang['mod_user']['about_me'] = 'Hakkımda';
$lang['mod_user']['max_chars_255'] = 'En Fazla 255 Karekter Olabilir.';
$lang['mod_user']['groups']['site_manager'] = 'Site Yöneticisi';
$lang['mod_user']['groups']['editors'] = 'Editörler';
$lang['mod_user']['groups']['normal_member'] = 'Normal Üye';
$lang['mod_user']['membership_date'] = 'Üyelik Tarihi';
$lang['mod_user']['update_date'] = 'Güncelleme Tarihi';
$lang['mod_user']['installer']['sql_error'] = 'users.sql Yüklenirken Hata Oluştu';
$lang['mod_user']['installer']['settings_added'] = '%s mod_user_settings tablosuna eklendi';
$lang['mod_user']['installer']['settings_error'] = 'Üyelik Ayarları Yüklenirken Hata Oluştu';
$lang['mod_user']['installer']['settings_exists'] = '%s mod_user_settings tablosunda Kayıtlı';
$lang['mod_user']['installer']['user_added'] = '-----<b>%s</b> - Kullanıcısı Veritabanına Eklendi!';
$lang['mod_user']['installer']['user_error'] = '-----<b>%s</b> - Kullanıcısı Veritabanına Eklenemedi!';
$lang['mod_user']['installer']['user_exists'] = '----- <b>%s</b> - Kullanıcısı Veritabanında Kayıtlı';
$lang['mod_user']['admin']['settings']['open'] = 'Open';
$lang['mod_user']['admin']['settings']['closed'] = 'Closed';
$lang['mod_user']['admin']['settings']['new_user_group'] = 'New User Group';
$lang['mod_user']['admin']['settings']['membership_open'] = 'Is Membership Open?';
$lang['mod_user']['admin']['settings']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['admin']['settings']['automatic_approval'] = 'Automatic Approval';
$lang['mod_user']['admin']['settings']['email_approval'] = 'Email Approval';
$lang['mod_user']['admin']['settings']['admin_approval'] = 'Admin Approval';
$lang['mod_user']['admin']['settings']['allow_multiple_logins'] = 'Can two people connect to the system with the same account at the same time?';
$lang['mod_user']['admin']['settings']['yes'] = 'Yes';
$lang['mod_user']['admin']['settings']['no'] = 'No';
$lang['mod_user']['admin']['active']['hello'] = 'Hello Dear <b>%s %s</b>.';
$lang['mod_user']['admin']['active']['account_activated'] = 'Your account located at %s has been activated by the administrator<br/>
    You can now log in with your username and password if you wish.';
$lang['mod_user']['admin']['add_user'] = 'Add User';
$lang['mod_user']['admin']['add_new_user'] = 'Add New User';
$lang['mod_user']['admin']['edit_user'] = 'Edit User';
$lang['mod_user']['admin']['roles'] = 'Roles';
$lang['mod_user']['admin']['refresh_permissions'] = 'Refresh Permissions';
$lang['mod_user']['admin']['user_group'] = 'User Group';
$lang['mod_user']['admin']['membership_settings'] = 'Membership Settings';
$lang['mod_user']['admin']['update_membership_settings'] = 'Update Membership Settings';
$lang['mod_user']['admin']['yes'] = 'Yes';
$lang['mod_user']['admin']['no'] = 'No';
$lang['mod_user']['admin']['confirm_remove_image'] = 'Are you sure you want to remove your profile picture?';
$lang['mod_user']['admin']['info_updated_image_error'] = 'Your information has been successfully updated!<br>
						But we could not change your profile picture!';
$lang['mod_user']['admin']['image_removed_success'] = 'Your profile picture has been removed';
$lang['mod_user']['grid']['13'] = 'Sil';
$lang['mod_user']['grid']['showing_results'] = 'Showing {from} to {to} of {total} results.';
$lang['mod_user']['grid']['page'] = 'Page';
$lang['mod_user']['grid']['page_count'] = 'Page Count';
$lang['mod_user']['grid']['loading_data'] = 'Data loading, please wait...';
$lang['mod_user']['grid']['no_data_found'] = 'No data found';
$lang['mod_user']['grid']['connection_error'] = 'Connection Error';
$lang['mod_user']['grid']['search'] = 'Search';
$lang['mod_user']['grid']['username'] = 'Username';
$lang['mod_user']['grid']['email'] = 'E-mail';
$lang['mod_user']['grid']['add_new_user'] = 'Add New User';
$lang['mod_user']['grid']['edit'] = 'Edit';
$lang['mod_user']['grid']['refresh'] = 'Refresh';
$lang['mod_user']['grid']['user_list'] = 'User List';
$lang['mod_user']['grid']['select_row_edit'] = 'Please select a row to edit';
$lang['mod_user']['grid']['select_row_delete'] = 'Please select at least one row to delete!';
$lang['mod_user']['grid']['confirm_delete'] = 'Total <font color=red>\' + $(\'.trSelected\',grid).length + \'</font> users will be deleted. <br/>Are you sure you want to perform this operation?</br> Remember that all content added by these users, categories etc.. Everything will be deleted!';
$lang['mod_user']['grid']['list_refreshed'] = 'User list refreshed';
$lang['mod_user']['grid']['user_deleted'] = 'User deleted';
$lang['mod_user']['grid']['yes'] = 'Yes';
$lang['mod_user']['grid']['cancel'] = 'Cancel';
$lang['mod_user']['grid']['user_group'] = 'User Group';
$lang['mod_user']['grid']['activate'] = 'Activate';
$lang['mod_user']['grid']['select_account_activate'] = 'Please select the account you want to activate';
$lang['mod_user']['grid']['confirm_activate'] = 'Are you sure you want to activate the selected accounts?';
$lang['mod_user']['grid']['accounts_activated'] = 'Accounts activated!';
$lang['mod_user']['grid']['active'] = 'Active';
$lang['mod_user']['grid']['waiting_email_activation'] = 'Waiting for Email Activation';
$lang['mod_user']['grid']['waiting_admin_activation'] = 'Waiting for Admin Activation.';
$lang['mod_user']['grid']['account_status'] = 'Account Status';
$lang['mod_user']['grid']['select_at_least_one'] = 'Please select at least one row';
$lang['mod_user']['error']['specify_username'] = 'Lütfen Bir Kullanıcı Adı Belirtin';
$lang['mod_user']['error']['specify_password'] = 'Lütfen Bir Şifre Belirtin';
$lang['mod_user']['error']['specify_email'] = 'Lütfen Bir E-Posta Belirtin';
$lang['mod_user']['error']['invalid_email'] = 'Lütfen Geçerli Bir Mail Adresi Belirtin';
$lang['mod_user']['error']['username_registered'] = 'Belirtmiş Olduğunuz Kullanıcı Adı Sistemde Kayıtlı';
$lang['mod_user']['error']['email_registered'] = 'Belirtmiş Olduğunuz E-Mail Sistemde Kayıtlı';
$lang['mod_user']['error']['username_invalid_chars'] = 'Kullanıcı Adınız a-z A-Z 0-9 Karekterlerinden Oluşabilir';
$lang['mod_user']['error']['username_length_error'] = 'Kullanıcı Adınız En Az 3 En Fazla 12 Karekterden Oluşabilir';
$lang['mod_user']['error']['password_length_error'] = 'Şifreniz En Az 6 En Fazla 16 Karekterden Oluşabilir';
$lang['mod_user']['error']['username_not_found'] = 'Yazdığınız Kullanıcı Adı Bulunamadı';
$lang['mod_user']['error']['invalid_credentials'] = 'Kullanıcı Adı veya Şifre Yanlış.';
$lang['mod_user']['error']['permission_error'] = 'Permission Hatası';
$lang['mod_user']['error']['current_password_required'] = 'Lütfen Şimdi Kullandığınız Şifrenizi Yazın';
$lang['mod_user']['error']['new_password_required'] = 'Lütfen Yeni Şifrenizi Yazın';
$lang['mod_user']['error']['confirm_password_required'] = 'Lütfen Yeni Şifrenizi Tekrarlayın';
$lang['mod_user']['error']['passwords_mismatch'] = 'Yeni Şifreniz İle Yeni Şifrenizin Tekrarı Aynı Olmalıdır.';
$lang['mod_user']['error']['current_password_incorrect'] = 'Belirtmiş Olduğunuz Şifre Yanlış';
$lang['mod_user']['error']['name_required'] = 'Lütfen Adınızı Belirtin';
$lang['mod_user']['error']['surname_required'] = 'Lütfen Soyadınızı Belirtin';
$lang['mod_user']['error']['dob_required'] = 'Lütfen Doğum Tarihinizi Belirtin';
$lang['mod_user']['error']['name_length_error'] = 'Adınız En Az 2 En Fazla 50 Karekter Olabilir';
$lang['mod_user']['error']['surname_length_error'] = 'Soyadınız En Az 2 En Fazla 50 Karekter Olabilir';
$lang['mod_user']['error']['name_invalid_chars'] = 'Adınız [^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ] Karekterlerinden Oluşabilir';
$lang['mod_user']['error']['surname_invalid_chars'] = 'Soyadınız [^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ] Karekterlerinden Oluşabilir';
$lang['mod_user']['error']['date_invalid'] = 'Lütfen Geçerli Bir Tarih Girin.Tarih Formatı YYYY-MM-DD Şeklinde Olmalıdır.';
$lang['mod_user']['error']['email_invalid_format'] = 'Lütfen Geçerli Bir E-Posta Adresi Yazın.';
$lang['mod_user']['error']['phone_invalid'] = 'Yazdığınız Cep Telefonu Numarası Geçersiz.';
$lang['mod_user']['error']['account_not_active'] = 'Kaydınız bulundu, fakat mailinize gelen aktivasyonu gerçekleştirmemişsiniz.<br/> Lütfen e-posta kutunuzu kontrol edin ve aktivasyon linkine tıklayın.';
$lang['mod_user']['error']['ip_protection_error'] = 'Bu Hesap İçin İp Koruması Tanımlanmış ve İp adresiniz Uyuşmuyor';
$lang['mod_user']['error']['multi_login_error'] = 'Bu Hesap Şu Anda Sisteme Bağlı Görünüyor,Hesabınızı Başka Bir Bilgisayarda Açık Unutmuş Olabilirsiniz!';
$lang['mod_user']['error']['user_not_found'] = 'Üye Bulunamadı!';
$lang['mod_user']['error']['system_error'] = 'Sistem Hatası';
$lang['mod_user']['activation']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check your activation code
		and try again.';
$lang['mod_user']['activation']['error']['code_required'] = 'Please write your activation code.';
$lang['mod_user']['activation']['error']['invalid_code'] = 'The specified activation code is invalid,
		Please check and try again.';
$lang['mod_user']['activation']['title'] = 'Account Activation';
$lang['mod_user']['activation']['write_code'] = 'Please write your activation code in the field below.';
$lang['mod_user']['activation']['activation_code'] = 'Activation Code';
$lang['mod_user']['activation']['security_code'] = 'Security Code';
$lang['mod_user']['activation']['activate_button'] = 'Activate';
$lang['mod_user']['activation']['lost_code'] = 'If you have lost your activation code
	<a href="resend-activation" style="color:#000">Click here</a> to request a new activation code.';
$lang['mod_user']['activation']['success_activated'] = 'Your account has been successfully activated. If you wish
	<a href="login" style="color:#000">Click here</a> to log in to your account.';
$lang['mod_user']['activation']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['resend_activation']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['resend_activation']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['resend_activation']['error']['6'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Zaten Aktif Durumda!';
$lang['mod_user']['resend_activation']['error']['7'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Sadece Yönetici Tarafından Aktifleştirilebilinir.';
$lang['mod_user']['resend_activation']['error']['8'] = 'Yazmış Olduğunuz E-Posta Adresine Ait Aktivasyon Kodu Bulunmuyor!';
$lang['mod_user']['resend_activation']['error']['activation_send_error'] = 'Error occurred while sending activation code. Please contact the system administrator.';
$lang['mod_user']['resend_activation']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['resend_activation']['error']['email_required'] = 'Please write the e-mail address associated with your account.';
$lang['mod_user']['resend_activation']['error']['invalid_email_format'] = 'You entered an invalid e-mail address.';
$lang['mod_user']['resend_activation']['error']['email_not_registered'] = 'The specified e-mail address is not registered to any account.';
$lang['mod_user']['resend_activation']['error']['account_already_active'] = 'The account associated with the e-mail address you entered is already active!';
$lang['mod_user']['resend_activation']['error']['admin_activation_required'] = 'The account associated with the e-mail address you entered can only be activated by an administrator.';
$lang['mod_user']['resend_activation']['error']['no_activation_code_found'] = 'No activation code found for the e-mail address you entered!';
$lang['mod_user']['resend_activation']['code_sent'] = 'Your activation code has been sent. Please check your e-mail to activate your account';
$lang['mod_user']['resend_activation']['write_email'] = 'So that we can send you an activation code.<br/> Write the e-mail address associated with your account in the field below.';
$lang['mod_user']['resend_activation']['activation_code'] = 'Activation Code';
$lang['mod_user']['resend_activation']['email'] = 'E-mail';
$lang['mod_user']['resend_activation']['security_code'] = 'Security Code';
$lang['mod_user']['resend_activation']['send_button'] = 'Send';
$lang['mod_user']['resend_activation']['activation_code_label'] = 'Activation Code';
$lang['mod_user']['password_reset']['1'] = 'Şifre Sıfırlama';
$lang['mod_user']['password_reset']['2'] = 'Yeni Şifrenizi Üretebilmemiz İçin,Lütfen Aşağıdaki Alana Şifre Sıfırlama Aktivasyon Kodunuzu Yazınız.';
$lang['mod_user']['password_reset']['3'] = 'Aktivasyon Kodu';
$lang['mod_user']['password_reset']['4'] = 'Güvenlik Kodu';
$lang['mod_user']['password_reset']['5'] = 'Gönder';
$lang['mod_user']['password_reset']['6'] = 'Tebrikler,Yeni Şifreniz E-Posta Adresinize Gönderildi.<br/>Lütfen E-Posta Adresinizi Kontrol Ediniz.';
$lang['mod_user']['password_reset']['error']['1'] = 'Güvenlik Kodunu Hatalı Girdiniz,Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['password_reset']['error']['2'] = 'Lütfen Aktivasyon Kodunu Yazın.';
$lang['mod_user']['password_reset']['error']['3'] = 'Belirttiğiniz Aktivasyon Kodu Geçersiz,
		Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['password_reset']['error']['4'] = 'E-Posta Gönderilirken Hata Oluştur Lütfen Sistem Yöneticisi İle İrtibata Geçin!';
$lang['mod_user']['password_reset']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['password_reset']['error']['code_required'] = 'Please write your activation code.';
$lang['mod_user']['password_reset']['error']['invalid_code'] = 'The specified activation code is invalid, please check and try again.';
$lang['mod_user']['password_reset']['error']['email_send_error'] = 'Error occurred while sending e-mail. Please contact the system administrator!';
$lang['mod_user']['password_reset']['title'] = 'Password Reset';
$lang['mod_user']['password_reset']['info'] = 'To generate your new password, please write your password reset activation code in the field below.';
$lang['mod_user']['password_reset']['activation_code'] = 'Activation Code';
$lang['mod_user']['password_reset']['security_code'] = 'Security Code';
$lang['mod_user']['password_reset']['send_button'] = 'Send';
$lang['mod_user']['password_reset']['success_message'] = 'Congratulations, your new password has been sent to your e-mail address.<br/>Please check your e-mail address.';
$lang['mod_user']['unread_messages_count'] = 'You have %s unread messages.';
