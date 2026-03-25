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
    'password_strength' => [
        'weak' => 'Weak',
        'fair' => 'Fair',
        'good' => 'Good',
        'strong' => 'Strong',
    ],
    'password_match' => [
        'success' => 'Passwords match',
        'error' => 'Passwords do not match',
    ],
    'js' => [
        'updating' => 'Updating...',
        'warning' => 'Warning',
        'weak_password' => 'Weak Password',
        'password_len_req' => 'At least 6 characters',
        'password_complex_req' => 'Upper, lower case and numbers',
    ],
    'error_fatal' => 'Fatal Error Occurred!',
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
    'error' =>
        [
            'req_username' => 'Username field is required',
            'req_password' => 'Password field is required',
            'req_email' => 'Email field is required',
            'invalid_email' => 'Please enter a valid email address',
            'min_password' => 'Password must be at least 6 characters',
            'passwords_mismatch' => 'Passwords do not match',
            'username_taken' => 'Username already taken',
            'email_taken' => 'Email address already in use',
            'invalid_credentials' => 'Username or password is incorrect',
            'user_not_found' => 'No such user found',
            'account_inactive' => 'Your account is not yet active',
            'permission_error' => 'Permission Error',
            'fill_all_fields' => 'Please fill in all fields',
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
    'username' => 'Username',
    'password' => 'Password',
    'email' => 'E-mail',
    'admin_panel' => 'Admin Panel',
    'user_panel' => 'User Panel',
    'login_success_wait' => 'Login successful. Please wait while you are redirected..',
    'no_permission' => 'You do not have permission to perform this operation',
    'please_login' => 'Please log in.',
    'login' => 'Login',
    'logout' => 'Logout',
    'password_change' => 'Password Change',
    'change' => 'Change',
    'current_password_label' => 'Current Password',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_new_password' => 'Confirm New Password',
    'password_changed_success' => 'Your password has been successfully changed!',
    'info_change' => 'Information Change',
    'your_name' => 'Your Name',
    'your_surname' => 'Your Surname',
    'your_email' => 'Your E-mail Address',
    'your_dob' => 'Your Date of Birth',
    'info_updated_success' => 'Your information has been successfully updated!',
    'mobile_phone' => 'Mobile Phone',
    'profile_picture' => 'Profile Picture',
    'forgot_password' => 'Forgot Password',
    'account_activation' => 'Account Activation',
    'register' => 'Register',
    'city' => 'City',
    'blog_name' => 'Blog Name',
    'about_me' => 'About Me',
    'max_chars_255' => 'Maximum 255 characters.',
    'unread_messages_count' => 'You have %s unread messages.',
    'membership_date' => 'Membership Date',
    'update_date' => 'Update Date',
    'groups' => [
        'site_manager' => 'Site Manager',
        'editors' => 'Editors',
        'normal_member' => 'Member'
    ],
    'installer' => [
        'sql_error' => 'Error occurred while loading users.sql',
        'settings_added' => '%s added to mod_user_settings table',
        'settings_error' => 'Error occurred while loading Membership Settings',
        'settings_exists' => '%s already exists in mod_user_settings table',
        'user_added' => '-----<b>%s</b> - User added to database!',
        'user_error' => '-----<b>%s</b> - User could not be added to database!',
        'user_exists' => '----- <b>%s</b> - User already exists in database',
    ]
];

$lang['mod_user']['admin'] = [
    'add_area' => 'User Addition Area',
    'add_user' => 'Add User',
    'added' => 'User Added!',
    'add_new_user' => 'Add New User',
    'list' => 'User List',
    'edit_area' => 'User Editing Area',
    'edit_user' => 'Edit User',
    'updated' => 'User Successfully Updated',
    'acl' => 'User Group Role Control (ACL)',
    'roles' => 'Roles',
    'refresh_permissions' => 'Refresh Permissions',
    'permissions_updated' => 'User Permissions Successfully Updated',
    'user_group' => 'User Group',
    'management' => 'Membership Management',
    'membership_settings' => 'Membership Settings',
    'site_settings' => 'Site Settings',
    'social_media' => 'Social Media',
    'update_membership_settings' => 'Update Membership Settings',
    'settings_updated' => 'Membership Settings Updated!',
    'yes' => 'Yes',
    'no' => 'No',
    'delete' => 'Delete',
    'confirm_remove_image' => 'Are you sure you want to remove your profile picture?',
    'info_updated_image_error' => 'Your information has been successfully updated!<br>
						But we could not change your profile picture!',
    'image_removed_success' => 'Your profile picture has been removed',
    'columns' =>
        [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'E-mail',
            'name' => 'Name',
            'last_name' => 'Surname',
            'status' => 'Account Status',
            'group_id' => 'User Group',
            'image' => 'Picture',
            'city' => 'City',
            'blog_name' => 'Blog Name',
            'about' => 'About Me',
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
    'open' => 'Open',
    'closed' => 'Closed',
    'new_user_group' => 'New User Group',
    'membership_open' => 'Is Membership Open?',
    'membership_activation' => 'Membership Activation',
    'automatic_approval' => 'Automatic Approval',
    'email_approval' => 'Email Approval',
    'admin_approval' => 'Admin Approval',
    'allow_multiple_logins' => 'Can two people connect to the system with the same account at the same time?',
    'yes' => 'Yes',
    'no' => 'No'


];
// Flexi Grid Required
$lang['mod_user']['grid'] = [
    'showing_results' => 'Showing {from} to {to} of {total} results.',
    'page' => 'Page',
    'page_count' => 'Page Count',
    'loading_data' => 'Data loading, please wait...',
    'no_data_found' => 'No data found',
    'connection_error' => 'Connection Error',
    'search' => 'Search',
    'username' => 'Username',
    'email' => 'E-mail',
    'add_new_user' => 'Add New User',
    'edit' => 'Edit',
    'refresh' => 'Refresh',
    'delete' => 'Delete',
    'user_list' => 'User List',
    'select_row_edit' => 'Please select a row to edit',
    'select_row_delete' => 'Please select at least one row to delete!',
    'confirm_delete' => "Total <font color=red>' + $('.trSelected',grid).length + '</font> users will be deleted. <br/>Are you sure you want to perform this operation?</br> Remember that all content added by these users, categories etc.. Everything will be deleted!",
    'list_refreshed' => 'User list refreshed',
    'user_deleted' => 'User deleted',
    'yes' => 'Yes',
    'cancel' => 'Cancel',
    'user_group' => 'User Group',
    'activate' => 'Activate',
    'select_account_activate' => 'Please select the account you want to activate',
    'confirm_activate' => 'Are you sure you want to activate the selected accounts?',
    'accounts_activated' => 'Accounts activated!',
    'active' => 'Active',
    'waiting_email_activation' => 'Waiting for Email Activation',
    'waiting_admin_activation' => 'Waiting for Admin Activation.',
    'account_status' => 'Account Status',
    'select_at_least_one'=>  'Please select at least one row',
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
    'specify_username' => 'Please specify a username',
    'specify_password' => 'Please specify a password',
    'specify_email' => 'Please specify an e-mail',
    'invalid_email' => 'Please specify a valid e-mail address',
    'username_registered' => 'The specified username is registered in the system',
    'email_registered' => 'The specified e-mail is registered in the system',
    'username_invalid_chars' => 'Your username can consist of a-z A-Z 0-9 characters',
    'username_length_error' => 'Your username can be 3 to 12 characters',
    'password_length_error' => 'Your password can be 6 to 16 characters',
    'username_not_found' => 'The username you entered could not be found',
    'invalid_credentials' => 'Username or password is incorrect.',
    'permission_error' => 'Permission Error',
    'current_password_required' => 'Please write your current password',
    'new_password_required' => 'Please write your new password',
    'confirm_password_required' => 'Please repeat your new password',
    'passwords_mismatch' => 'Your new password and new password confirmation must be the same.',
    'current_password_incorrect' => 'The specified password is incorrect',
    'name_required' => 'Please specify your name',
    'surname_required' => 'Please specify your surname',
    'dob_required' => 'Please specify your date of birth',
    'name_length_error' => 'Your name can be 2 to 50 characters',
    'surname_length_error' => 'Your surname can be 2 to 50 characters',
    'name_invalid_chars' => 'Your name can consist of [^a-zA-Z] characters',
    'surname_invalid_chars' => 'Your surname can consist of [^a-zA-Z] characters',
    'date_invalid' => 'Please enter a valid date. Date format should be YYYY-MM-DD.',
    'email_invalid_format' => 'Please write a valid e-mail address.',
    'phone_invalid' => 'The mobile phone number you entered is invalid.',
    'account_not_active' => 'Your account was found but is not active.<br/> Please activate your membership
						<a href="activation/" style="color:#000">here</a>.',
    'ip_protection_error' => 'IP protection is defined for this account and your IP address does not match',
    'multi_login_error' => 'This account does not appear to be connected to the system right now, you may have forgotten to open your account on another computer!',
    'user_not_found' => 'User not found!',
    'system_error' => 'System Error'
];


$lang['mod_user']['admin']['active'] = [
    'hello' => 'Hello Dear <b>%s %s</b>.',
    'account_activated' => 'Your account located at %s has been activated by the administrator<br/>
    You can now log in with your username and password if you wish.'
];


// User Registration

$lang['mod_user']['register'] = [
    'registration_stopped' => 'User registration has been temporarily stopped.<br/>
		Thank you for your understanding.',
    'form_title' => 'User Registration Form',
    'your_name' => 'Your Name',
    'your_surname' => 'Your Surname',
    'your_username' => 'Your Username',
    'email' => 'E-mail',
    'your_password' => 'Your Password',
    'confirm_password' => 'Confirm Password',
    'your_phone' => 'Your Phone Number',
    'security_code' => 'Security Code',
    'register_button' => 'Register',
    'fill_form' => 'Please fill out the form to register.',
    'registration_success' => 'Your membership has been successfully completed! You can now log in to your account.',
    'registration_success_activation' => 'Your membership has been successfully completed. <br/>However, your account is not yet active. Please verify your account by clicking the activation link sent to your email address.',
    'membership_activation' => 'Membership Activation',
    'password_reset_request' => 'Password Reset Request',
    'your_new_password' => 'Your New Password',
    'membership_registration' => 'Membership Registration',
    'registration_success_admin' => 'Your account has been successfully created. <br/>For security reasons, your account must be approved by an administrator. You will be notified via email after approval.'
];

$lang['mod_user']['register']['error'] = [
    'name_required' => 'Please specify your name',
    'surname_required' => 'Please specify your surname',
    'username_required' => 'Please specify your username',
    'email_required' => 'Please specify your e-mail address',
    'phone_required' => 'Please specify your mobile phone number',
    'name_invalid_chars' => 'There are invalid characters in your name',
    'surname_invalid_chars' => 'There are invalid characters in your surname',
    'username_length' => 'Your username can be 2 to 12 characters',
    'username_invalid_chars' => 'Your username can consist of a-z A-Z 0-9 characters',
    'password_length' => 'Your password can be 6 to 16 characters',
    'invalid_email' => 'Please specify a valid e-mail address',
    'invalid_phone' => 'Please specify a valid mobile phone number.',
    'username_registered' => 'The specified username is registered in the system',
    'email_registered' => 'The specified e-mail address is registered in the system',
    'invalid_captcha' => 'You entered the security code incorrectly, please check and try again.',
    'activation_email_error' => 'An error occurred while sending activation e-mail.<br/>
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
    'errors_title' => 'Errors:',
    'validation_required' => 'This field is required',
    'validation_required_name' => 'Please enter your name',
    'validation_required_surname' => 'Please enter your surname',
    'validation_required_username' => 'Please enter a username',
    'validation_username_short' => 'Username must be at least 2 characters',
    'validation_username_long' => 'Username must not exceed 20 characters',
    'validation_required_email' => 'Please enter your email address',
    'validation_invalid_email' => 'Please enter a valid email address',
    'validation_required_password' => 'Please enter a password',
    'validation_password_short' => 'Password must be at least 6 characters',
    'validation_password_long' => 'Password must not exceed 20 characters',
    'validation_confirm_password' => 'Please confirm your password',
    'validation_required_phone' => 'Please enter your phone number',
    'validation_required_captcha' => 'Please enter the security code'
];

// Captcha helper text
$lang['mod_user']['register']['captcha_refresh'] = 'Click the image to refresh the code';

// Country codes
$lang['mod_user']['register']['country_codes'] = [
    'select' => 'Select Country',
    'phone' => 'Phone'
];

// Activation

$lang['mod_user']['activation'] = [
    'title' => 'Account Activation',
    'write_code' => 'Please write your activation code in the field below.',
    'activation_code' => 'Activation Code',
    'security_code' => 'Security Code',
    'activate_button' => 'Activate',
    'lost_code' => 'If you have lost your activation code
	<a href="re_activation" style="color:#000">Click here</a> to request a new activation code.',
    'success_activated' => 'Your account has been successfully activated. If you wish
	<a href="mod_user/login" style="color:#000">Click here</a> to log in to your account.',
    'membership_activation' => 'Membership Activation'
];
$lang['mod_user']['activation']['error'] = [
    'invalid_captcha' => 'You entered the security code incorrectly, please check your activation code
		and try again.',
    'code_required' => 'Please write your activation code.',
    'invalid_code' => 'The specified activation code is invalid,
		Please check and try again.'
];

$lang['mod_user']['re_activation'] = [
    'code_sent' => 'Your activation code has been sent. Please check your e-mail to activate your account',
    'write_email' => 'So that we can send you an activation code.<br/> Write the e-mail address associated with your account in the field below.',
    'activation_code' => 'Activation Code',
    'email' => 'E-mail',
    'security_code' => 'Security Code',
    'send_button' => 'Send',
    'activation_code_label' => 'Activation Code'
];
$lang['mod_user']['re_activation']['error'] = [
    'activation_send_error' => 'Error occurred while sending activation code. Please contact the system administrator.',
    'invalid_captcha' => 'You entered the security code incorrectly, please check and try again.',
    'email_required' => 'Please write the e-mail address associated with your account.',
    'invalid_email_format' => 'You entered an invalid e-mail address.',
    'email_not_registered' => 'The specified e-mail address is not registered to any account.',
    'account_already_active' => 'The account associated with the e-mail address you entered is already active!',
    'admin_activation_required' => 'The account associated with the e-mail address you entered can only be activated by an administrator.',
    'no_activation_code_found' => 'No activation code found for the e-mail address you entered!'
];


// Password Reset

$lang['mod_user']['forgot_password'] = [
    'info_1' => 'We have sent you an e-mail so you can reset your password.<br/>Please check your e-mail and follow the steps specified in the e-mail.',
    'info_2' => 'So that we can reset your password.<br/> Write the e-mail address associated with your account in the field below.',
    'title' => 'Password Reset',
    'email' => 'E-mail',
    'security_code' => 'Security Code',
    'send_button' => 'Send',
    'forgot_password_title' => 'Forgot Password'
];
$lang['mod_user']['forgot_password']['error'] = [
    'reset_code_send_error' => 'Error occurred while sending password reset code. Please contact the system administrator.',
    'invalid_captcha' => 'You entered the security code incorrectly, please check and try again.',
    'email_required' => 'Please write the e-mail address associated with your account.',
    'invalid_email_format' => 'You entered an invalid e-mail address.',
    'email_not_registered' => 'The specified e-mail address is not registered to any account.'
];

$lang['mod_user']['password_reset'] = [
    'title' => 'Password Reset',
    'info' => 'To generate your new password, please write your password reset activation code in the field below.',
    'activation_code' => 'Activation Code',
    'security_code' => 'Security Code',
    'send_button' => 'Send',
    'success_message' => 'Congratulations, your new password has been sent to your e-mail address.<br/>Please check your e-mail address.'
];
$lang['mod_user']['password_reset']['error'] = [
    'invalid_captcha' => 'You entered the security code incorrectly, please check and try again.',
    'code_required' => 'Please write your activation code.',
    'invalid_code' => 'The specified activation code is invalid, please check and try again.',
    'email_send_error' => 'Error occurred while sending e-mail. Please contact the system administrator!'
];

// Panel Welcome Messages
$lang['mod_user']['panel'] = [
    'welcome' => 'Hello Dear %s',
    'description' => 'You can choose what you want to do from the menus. You can change your membership settings from the top right.',
    'total_content' => 'Total Content',
    'pending_content' => 'Pending',
    'draft_content' => 'Drafts',
    'total_publication' => 'Total Publications',
    'draft_publication' => 'Draft Publications',
    'view_reports' => 'View Detailed Reports',
    'welcome_main' => 'Welcome,',
    'panel_title' => 'Admin Panel',
    'goto_content' => 'Go to Content Management',
    'search_placeholder' => 'Search Content...',
    'quick_access' => 'Quick Access',
    'home_title' => 'Home Page',
    'admin_brand' => 'Rojname Admin'
];

// FAB Menu
$lang['mod_user']['fab'] = [
    'add_announcement' => 'Add Announcement',
    'add_video' => 'Add Video',
    'add_content' => 'Add Content',
    'add_announcement_alert' => 'Add Announcement Form',
    'add_video_alert' => 'Add Video Form',
    'add_content_alert' => 'Add Content Wizard'
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

// Filter & Sort Generic Keys
$lang['mod_user']['filter'] = [
    'title' => 'Filter & Sort',
    'sort_label' => 'Sort Order',
    'close' => 'Close',
    'newest' => 'Newest',
    'oldest' => 'Oldest',
    'title_az' => 'Title (A-Z)',
    'title_za' => 'Title (Z-A)',
    'view_count' => 'View Count'
];


// --- Otomatîk Zêdekirin (Senkronîzasyon) ---
$lang['mod_user']['forgot_password']['1'] = 'Şifrenizi Sıfırlayabilmeniz İçin Size Bir E-Posta Gönderdik.<br/>Lütfen E-Posta\'nızı Kontrol Ederek E-Posta\'da  Belirtilen Adımları İzleyin.';
$lang['mod_user']['forgot_password']['password'] = 'Şifrenizi Sıfırlayabilmemiz İçin.<br/> Aşağıdaki Alana Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın.';
$lang['mod_user']['forgot_password']['3'] = 'Şifre Sıfırlama';
$lang['mod_user']['forgot_password']['4'] = 'E-Posta';
$lang['mod_user']['forgot_password']['5'] = 'Güvelik Kodu';
$lang['mod_user']['forgot_password']['6'] = 'Gönder';
$lang['mod_user']['forgot_password']['7'] = 'Şifremi Unuttum';
$lang['mod_user']['forgot_password']['error']['1'] = 'Şifre Sıfırlama Kodu Gönderilirken Hata Oluştu Lütfen Sistem Yöneticisi İle İrtibata Geçin.';
$lang['mod_user']['forgot_password']['error']['2'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['forgot_password']['error']['3'] = 'Lütfen Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın';
$lang['mod_user']['forgot_password']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['forgot_password']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['register']['1'] = 'Geçici Bir Süre İçin Üye Alımı Durdurulmuştur.<br/>
		Anlayışınız İçin Teşekkür Ederiz.';
$lang['mod_user']['register']['2'] = 'Üye Kayıt Formu';
$lang['mod_user']['register']['3'] = 'Adınız';
$lang['mod_user']['register']['4'] = 'Soyadınız';
$lang['mod_user']['register']['5'] = 'Kullanıcı Adınız';
$lang['mod_user']['register']['6'] = 'E-Posta';
$lang['mod_user']['register']['7'] = 'Şifreniz';
$lang['mod_user']['register']['8'] = 'Şifre Tekrar';
$lang['mod_user']['register']['9'] = 'Telefon Numaranız';
$lang['mod_user']['register']['10'] = 'Güvenlik Kodu';
$lang['mod_user']['register']['11'] = 'Kayıt Ol';
$lang['mod_user']['register']['12'] = 'Lütfen kayıt olmak için formu doldurun.';
$lang['mod_user']['register']['13'] = 'Üyeliğiniz Başarıyla Tamamlandı! Hesabınıza şimdi giriş yapabilirsiniz.';
$lang['mod_user']['register']['14'] = 'Üyeliğiniz Başarıyla Tamamlandı. <br/>Ancak hesabınız henüz aktif değil. Lütfen e-posta adresinize gönderdiğimiz aktivasyon bağlantısına tıklayarak hesabınızı doğrulayın.';
$lang['mod_user']['register']['15'] = 'Üyelik Aktivasyonu';
$lang['mod_user']['register']['16'] = 'Şifre Sıfırlama İsteği';
$lang['mod_user']['register']['17'] = 'Yeni Şifreniz';
$lang['mod_user']['register']['18'] = 'Üyelik Kaydı';
$lang['mod_user']['register']['19'] = 'Hesabınız başarıyla oluşturuldu. <br/>Güvenlik nedeniyle hesabınızın bir yönetici tarafından approvenması gerekmektedir. Onay işleminden sonra size e-posta ile bilgi verilecektir.';
$lang['mod_user']['register']['error']['1'] = 'Lütfen Adınızı Belirtin';
$lang['mod_user']['register']['error']['2'] = 'Lütfen Soyadınızı Belirtin';
$lang['mod_user']['register']['error']['3'] = 'Lütfen Kullanıcı Adınızı Belirtin';
$lang['mod_user']['register']['error']['4'] = 'Lütfen E-Posta Adresinizi Belirtin';
$lang['mod_user']['register']['error']['5'] = 'Lütfen Cep Telefon Numaranızı Belirtin';
$lang['mod_user']['register']['error']['6'] = 'Adınızda Geçersiz Karekterler Bulunmakta';
$lang['mod_user']['register']['error']['7'] = 'Soyadınızda Geçersiz Karekterler Bulunmakta';
$lang['mod_user']['register']['error']['8'] = 'Kullanıcı Adınız En Az 2 En Fazla 12 Karekterden Oluşabilir';
$lang['mod_user']['register']['error']['9'] = 'Kullanıcı Adınız a-z A-Z 0-9 Karekterlerinden Oluşabilir';
$lang['mod_user']['register']['error']['10'] = 'Şifreniz En Az 6 En Fazla 16 Karekterden Oluşabilir';
$lang['mod_user']['register']['error']['11'] = 'Lütfen Geçerli Bir E-Posta Adresi Belirtin';
$lang['mod_user']['register']['error']['12'] = 'Lütfen Geçerli Bir Cep Telefon Numarası Belirtin.';
$lang['mod_user']['register']['error']['13'] = 'Belirtmiş Olduğunuz Kullanıcı Adı Sistemde Kayıtlı';
$lang['mod_user']['register']['error']['14'] = 'Belirtmiş Olduğunuz E-Posta Adresi Sistemde Kayıtlı';
$lang['mod_user']['register']['error']['15'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['register']['error']['16'] = 'Aktivasyon E-Maili Gönderilirken Hata Oluştu.<br/>
	 Lütfen Sistem Yöneticisi İle İrtibata Geçin. ';
$lang['mod_user']['32'] = '%s okunmamış mesajınız var.';
$lang['mod_user']['admin']['2'] = 'Üyeyi Ekle';
$lang['mod_user']['admin']['4'] = 'Yeni Üye Ekle';
$lang['mod_user']['admin']['7'] = 'Üyeyi Düzenle';
$lang['mod_user']['admin']['10'] = 'Roller';
$lang['mod_user']['admin']['11'] = 'Permissionleri Yenile';
$lang['mod_user']['admin']['13'] = 'Üye Grubu *';
$lang['mod_user']['admin']['15'] = 'Üyelik Ayarları';
$lang['mod_user']['admin']['16'] = 'Üyelik Ayarlarını Güncelle';
$lang['mod_user']['admin']['18'] = 'Evet';
$lang['mod_user']['admin']['19'] = 'Hayır';
$lang['mod_user']['admin']['21'] = 'Profil Resminizi Kaldırmak İstediğinize Eminmisiniz?';
$lang['mod_user']['admin']['22'] = 'Bilgileriniz Başarıyla Güncellendi!<br>
						Fakat Profil Resminizi Değiştiremedik!';
$lang['mod_user']['admin']['23'] = 'Profil Resminiz Kaldırıldı';
$lang['mod_user']['admin']['settings']['1'] = 'Açık';
$lang['mod_user']['admin']['settings']['2'] = 'Kapalı';
$lang['mod_user']['admin']['settings']['3'] = 'Yeni Üye Grubu';
$lang['mod_user']['admin']['settings']['4'] = 'Üyelik Açıkmı';
$lang['mod_user']['admin']['settings']['5'] = 'Üyelik Aktivasyonu';
$lang['mod_user']['admin']['settings']['6'] = 'Otomatik Onaylı';
$lang['mod_user']['admin']['settings']['7'] = 'E-Posta Onaylı';
$lang['mod_user']['admin']['settings']['8'] = 'Admin Onaylı';
$lang['mod_user']['admin']['settings']['9'] = 'Bir Hesapla Aynı Anda İki Kişi Sisteme Bağlanabilsinmi ?';
$lang['mod_user']['admin']['settings']['10'] = 'Evet';
$lang['mod_user']['admin']['settings']['11'] = 'Hayır';
$lang['mod_user']['admin']['active']['1'] = 'Merhabalar Sayın <b>%s %s</b>.';
$lang['mod_user']['admin']['active']['2'] = '%s Adresinde Bulunan Hesabınızın Yönetici Tarafından Aktifleştirildi<br/>
Dilerseniz Artık Kullanıcı Adı Ve Şifrenizle Giriş Yapabilirsiniz.';
$lang['mod_user']['grid']['1'] = 'Toplam {total} Sonuçtan {from} den {to}\\\'a Kadar Olanlar Görüntüleniyor.';
$lang['mod_user']['grid']['2'] = 'Sayfa';
$lang['mod_user']['grid']['3'] = 'Sayfa Sayısı';
$lang['mod_user']['grid']['4'] = 'Veriler Yükleniyor, Lütfen Bekleyin ...';
$lang['mod_user']['grid']['5'] = 'Hiçbir Veri Bulunamadı';
$lang['mod_user']['grid']['6'] = 'Bağlantı Hatası';
$lang['mod_user']['grid']['7'] = 'Arama';
$lang['mod_user']['grid']['8'] = 'Kullanıcı Adı';
$lang['mod_user']['grid']['9'] = 'E-Posta';
$lang['mod_user']['grid']['10'] = 'Yeni Üye Ekle';
$lang['mod_user']['grid']['11'] = 'Edit';
$lang['mod_user']['grid']['12'] = 'Yenile';
$lang['mod_user']['grid']['13'] = 'Sil';
$lang['mod_user']['grid']['14'] = 'Üye Listesi';
$lang['mod_user']['grid']['15'] = 'Düzenleme yapmak için lütfen bir satır seçin';
$lang['mod_user']['grid']['16'] = 'Lütfen Silmek İçin En Az Bir Satır Seçin!';
$lang['mod_user']['grid']['17'] = 'Toplam <font color=red>\' + $(\'.trSelected\',grid).length + \'</font> Üye Silinecek. <br/>Bu İşlemi Yapmak İstediğinize Emin misiniz?</br> Unutmayınki Bu Üyelerin Ekledikleri İçerikler Kategoriler vs.. Herşey Silinecek!';
$lang['mod_user']['grid']['18'] = 'Üye Listesi Yenilendi';
$lang['mod_user']['grid']['19'] = 'Üye Silindi';
$lang['mod_user']['grid']['20'] = 'Evet';
$lang['mod_user']['grid']['21'] = 'Vazgeç';
$lang['mod_user']['grid']['22'] = 'Üye Grubu';
$lang['mod_user']['grid']['23'] = 'Aktifleştir';
$lang['mod_user']['grid']['24'] = 'Lütfen Aktifleştirmek İstediğiniz Hesabı Seçin';
$lang['mod_user']['grid']['25'] = 'Seçili Hesapları Aktifleştirmek İstediğinize Eminmisiniz?';
$lang['mod_user']['grid']['26'] = 'Hesaplar Aktifleştirildi!';
$lang['mod_user']['grid']['27'] = 'Aktif';
$lang['mod_user']['grid']['28'] = 'E-Posta Aktivasyonu Bekleniyor';
$lang['mod_user']['grid']['29'] = 'Yönetici Aktivasyonu Bekleniyor.';
$lang['mod_user']['grid']['30'] = 'Hesap Durumu';
$lang['mod_user']['grid']['31'] = 'Lütfen En Az Bir Satır Seçin';
$lang['mod_user']['activation']['1'] = 'Hesap Aktivasyonu';
$lang['mod_user']['activation']['2'] = 'Lütfen Aşağıdaki Alana Aktivasyon Kodunuzu Yazınız.';
$lang['mod_user']['activation']['3'] = 'Aktivasyon Kodu';
$lang['mod_user']['activation']['4'] = 'Güvenlik Kodu';
$lang['mod_user']['activation']['5'] = 'Aktifleştir';
$lang['mod_user']['activation']['6'] = 'Eğer Aktifleştme Kodunuzu Kaybettiyseniz
	<a href="re_activation" style="color:#000">Buradan</a> Tekrar Aktivasyon Kodu Talep Edebilirsiniz.';
$lang['mod_user']['activation']['7'] = 'Hesabınız Başarıyla Aktifleştirildi Dilerseniz
	<a href="mod_user/login" style="color:#000">Buradan</a> Hesabınıza Giriş Yapabilirsiniz.';
$lang['mod_user']['activation']['8'] = 'Üyelik Aktivasyonu';
$lang['mod_user']['activation']['error']['1'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Aktivasyon Kodunuzu
		Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['activation']['error']['2'] = 'Lütfen Aktivasyon Kodunuzu Yazın.';
$lang['mod_user']['activation']['error']['3'] = 'Belirttiğiniz Aktivasyon Kodu Geçersiz,
		Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['re_activation']['1'] = 'Aktivasyon Kodunuz Gönderildi Lütfen E-Posta\'nızı Kontrol Ederek Hesabınızı Aktifleştirin';
$lang['mod_user']['re_activation']['2'] = 'Size Aktivasyon Kodu Göndere Bilmemiz İçin.<br/> Aşağıdaki Alana Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın.';
$lang['mod_user']['re_activation']['3'] = 'Aktivasyon Kodu';
$lang['mod_user']['re_activation']['4'] = 'E-Posta';
$lang['mod_user']['re_activation']['5'] = 'Güvelik Kodu';
$lang['mod_user']['re_activation']['6'] = 'Gönder';
$lang['mod_user']['re_activation']['7'] = 'Aktivasyon Kodu';
$lang['mod_user']['re_activation']['error']['1'] = 'Aktivasyon Kodu Gönderilirken Hata Oluştu Lütfen Sistem Yöneticisi İle İrtibata Geçin.';
$lang['mod_user']['re_activation']['error']['2'] = 'Güvenlik Kodunu Hatalı Girdiniz, Lütfen Kontrol Ederek Tekrar Deneyiniz.';
$lang['mod_user']['re_activation']['error']['3'] = 'Lütfen Hesabınızın Bağlı Olduğu E-Posta Adresini Yazın';
$lang['mod_user']['re_activation']['error']['4'] = 'Geçersiz Bir E-Posta Adresi Yazdınız.';
$lang['mod_user']['re_activation']['error']['5'] = 'Belirtmiş Olduğunuz E-Posta Adresi Herhangi Bir Hesaba Kayıtlı Değil.';
$lang['mod_user']['re_activation']['error']['6'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Zaten Aktif Durumda!';
$lang['mod_user']['re_activation']['error']['7'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Sadece Yönetici Tarafından Aktifleştirilebilinir.';
$lang['mod_user']['re_activation']['error']['8'] = 'Yazmış Olduğunuz E-Posta Adresine Ait Aktivasyon Kodu Bulunmuyor!';
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
