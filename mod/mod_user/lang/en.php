<?php
/**
 * User Module - English Language File
 *
 * @package AKM Business
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
    'database_error' => 'The record could not be saved. Please try again.',
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
            'current_password_required' => 'Current password is required',
            'new_password_required' => 'New password is required',
            'confirm_password_required' => 'Password confirmation is required',
            'password_length_error' => 'Password must be between 6 and 20 characters',
            'passwords_mismatch' => 'Passwords do not match',
            'current_password_incorrect' => 'Current password is incorrect',
            'username_taken' => 'Username already taken',
            'email_taken' => 'Email address already in use',
            'invalid_credentials' => 'Username or password is incorrect',
            'user_not_found' => 'No such user found',
            'account_inactive' => 'Your account is not yet active',
            'permission_error' => 'Permission Error',
            'fill_all_fields' => 'Please fill in all fields',
            'ip_protection_error' => 'IP protection is defined for this account and your IP address does not match',
            'multi_login_error' => 'This account is already connected to the system from another computer!',
            'system_error' => 'System Error',
            'name_required' => 'Please specify your name',
            'surname_required' => 'Please specify your surname',
            'specify_email' => 'Please specify your e-mail address',
            'specify_username' => 'Please specify your username',
            'specify_password' => 'Please specify your password',
            'username_invalid_chars' => 'Your username can consist of a-z A-Z 0-9 characters',
            'username_length_error' => 'Your username can be 2 to 20 characters',
            'email_invalid_format' => 'Please enter a valid email address',
            'email_registered' => 'This email address is already registered',
            'username_registered' => 'This username is already registered',
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
            'deactivate' => 'Deactivate',
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

$lang['mod_user']['email_templates'] = [
    'greeting' => 'Hello <b>%s %s</b>',
    'click_here' => 'Click here',
    'copy_link_notice' => 'If you cannot click the link, copy and paste the link below into your browser.',
    'account_activation_intro' => 'Please open the link below to complete your membership application at %s.',
    'password_reset_intro' => 'Please open the link below to reset the password for your account at %s.',
    'password_reset_followup' => 'After opening the link, choose a new password to complete the reset.',
    'password_reset_request_ip' => 'IP address of the password reset request: <b>%s</b>',
    'password_reset_completed' => 'The password for your account at %s has been reset.',
    'login_after_reset' => 'to log in to your account.',
    'new_password_label' => 'Your new password',
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
    'info_updated_image_error' => 'Your information has been successfully updated!<br>But we could not change your profile picture!',
    'image_removed_success' => 'Your profile picture has been removed',
    'select_image' => 'Select Image',
    'columns' => [
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
    'radio' => [
        'publish_yes' => 'Yes',
        'publish_no' => 'No',
        'deck_yes' => 'Yes',
        'deck_no' => 'No',
    ],
    'active' => [
        'hello' => 'Hello Dear <b>%s %s</b>.',
        'account_activated' => 'Your account located at %s has been activated by the administrator.<br/>You can now log in with your username and password.'
    ]
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
    'allow_multiple_logins' => 'Allow concurrent logins with the same account?',
    'yes' => 'Yes',
    'no' => 'No'
];

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
    'confirm_delete' => "Total <font color=red>' + $('.trSelected',grid).length + '</font> users will be deleted. <br/>Are you sure you want to perform this operation?</br> Remember that all content added by these users will be deleted!",
    'list_refreshed' => 'User list refreshed',
    'user_deleted' => 'User deleted',
    'yes' => 'Yes',
    'cancel' => 'Cancel',
    'user_group' => 'User Group',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'select_account_activate' => 'Please select the account you want to activate',
    'confirm_activate' => 'Are you sure you want to activate the selected accounts?',
    'accounts_activated' => 'Accounts activated!',
    'active' => 'Active',
    'waiting_email_activation' => 'Waiting for Email Activation',
    'waiting_admin_activation' => 'Waiting for Admin Activation.',
    'account_status' => 'Account Status',
    'select_at_least_one' => 'Please select at least one row',
    'status' => [
        'key' => 'Activate',
        'value' => 'Are you sure you want to activate this user?',
    ],
    'deactivate_confirm' => [
        'key' => 'Deactivate',
        'value' => 'Are you sure you want to deactivate this user?',
    ],
];

$lang['mod_user']['register'] = [
    'registration_stopped' => 'User registration has been temporarily stopped.<br/>Thank you for your understanding.',
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
    'registration_success' => 'Your membership has been successfully completed! You can now log in.',
    'registration_success_activation' => 'Your membership has been successfully completed. <br/>Please verify your account by clicking the activation link sent to your email.',
    'membership_activation' => 'Membership Activation',
    'password_reset_request' => 'Password Reset Request',
    'your_new_password' => 'Your New Password',
    'membership_registration' => 'Membership Registration',
    'registration_success_admin' => 'Your account has been created. <br/>It must be approved by an administrator before you can log in.',
    'error' => [
        'name_required' => 'Please specify your name',
        'surname_required' => 'Please specify your surname',
        'username_required' => 'Please specify your username',
        'email_required' => 'Please specify your e-mail address',
        'phone_required' => 'Please specify your mobile phone number',
        'name_invalid_chars' => 'There are invalid characters in your name',
        'surname_invalid_chars' => 'There are invalid characters in your surname',
        'username_length' => 'Your username can be 2 to 12 characters',
        'username_length_error' => 'Your username can be 2 to 20 characters',
        'username_invalid_chars' => 'Your username can consist of a-z A-Z 0-9 characters',
        'password_length' => 'Your password can be 6 to 16 characters',
        'invalid_email' => 'Please specify a valid e-mail address',
        'invalid_phone' => 'Please specify a valid mobile phone number.',
        'username_registered' => 'The specified username is registered in the system',
        'email_registered' => 'The specified e-mail address is registered in the system',
        'invalid_captcha' => 'You entered the security code incorrectly.',
        'activation_email_error' => 'An error occurred while sending activation e-mail.'
    ],
    'js' => [
        'checking' => '⏳ Checking...',
        'username_available' => '✅ Username available',
        'username_exists' => '❌ Username already taken',
        'username_short' => 'Username must be at least 2 characters',
        'email_available' => '✅ Email available',
        'email_exists' => '❌ Email already registered',
        'check_failed' => '⚠️ Check failed',
        'register_success' => 'Membership created successfully!',
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
    ],
    'captcha_refresh' => 'Click the image to refresh the code',
    'country_codes' => [
        'select' => 'Select Country',
        'phone' => 'Phone'
    ]
];

$lang['mod_user']['activation'] = [
    'title' => 'Account Activation',
    'write_code' => 'Please write your activation code in the field below.',
    'activation_code' => 'Activation Code',
    'security_code' => 'Security Code',
    'activate_button' => 'Activate',
    'lost_code' => 'If you lost your code <a href="resend-activation">click here</a>.',
    'success_activated' => 'Account activated successfully! <a href="login">Login here</a>.',
    'membership_activation' => 'Membership Activation',
    'error' => [
        'invalid_captcha' => 'Security code is incorrect.',
        'code_required' => 'Activation code is required.',
        'invalid_code' => 'Invalid activation code.'
    ]
];

$lang['mod_user']['resend_activation'] = [
    'code_sent' => 'Activation code sent. Please check your email.',
    'write_email' => 'Enter your registered email address to receive a new code.',
    'activation_code' => 'Activation Code',
    'email' => 'E-mail',
    'security_code' => 'Security Code',
    'send_button' => 'Send',
    'activation_code_label' => 'Activation Code',
    'error' => [
        'activation_send_error' => 'Error occurred while sending code.',
        'invalid_captcha' => 'Security code is incorrect.',
        'email_required' => 'Email address is required.',
        'invalid_email_format' => 'Invalid email format.',
        'email_not_registered' => 'Email is not registered.',
        'account_already_active' => 'Account is already active!',
        'admin_activation_required' => 'Account requires administrator approval.',
        'no_activation_code_found' => 'No activation code found for this email.'
    ]
];

$lang['mod_user']['forgot_password'] = [
    'info_1' => 'If the email address matches an account, password reset instructions will be sent shortly.',
    'info_2' => 'Enter your account email address. We will send a reset link if the account can use password recovery.',
    'title' => 'Password Reset',
    'email' => 'E-mail',
    'email_placeholder' => 'name@example.com',
    'security_code' => 'Security Code',
    'captcha_placeholder' => 'Enter the code',
    'captcha_refresh' => 'Refresh security code',
    'send_button' => 'Send',
    'back_to_login' => 'Back to login',
    'forgot_password_title' => 'Forgot Password',
    'security_note' => 'For your security, reset links can be used once.',
    'redirecting' => 'Checking your request...',
    'error' => [
        'reset_code_send_error' => 'Error occurred while sending reset code.',
        'invalid_captcha' => 'Security code is incorrect.',
        'captcha_required' => 'Security code is required.',
        'email_required' => 'Email address is required.',
        'invalid_email_format' => 'Invalid email format.',
        'email_not_registered' => 'Email is not registered.'
    ],
    'js' => [
        'invalid_email' => 'Please enter a valid email address.',
        'checking' => '⏳ Checking...',
        'email_found' => '✓ Account found. A password reset link will be sent.',
        'email_not_found' => '❌ No account found with this email address.',
        'check_failed' => '⚠️ Check failed',
    ],
];

$lang['mod_user']['password_reset'] = [
    'title' => 'Password Reset',
    'info' => 'Choose a new password to complete your password reset.',
    'activation_code' => 'Activation Code',
    'security_code' => 'Security Code',
    'send_button' => 'Send',
    'success_message' => 'Your password has been reset. You can now sign in with your new password.',
    'error' => [
        'invalid_captcha' => 'Security code is incorrect.',
        'code_required' => 'Reset code is required.',
        'invalid_code' => 'Invalid reset code.',
        'expired_code' => 'This reset link has expired. Please request a new password reset link.',
        'email_send_error' => 'Error occurred while sending password email.'
    ]
];

$lang['mod_user']['panel'] = [
    'welcome' => 'Hello %s',
    'description' => 'You can manage your account and settings from this panel.',
    'pending_content' => 'Pending',
    'view_reports' => 'View Reports',
    'welcome_main' => 'Welcome,',
    'panel_title' => 'Admin Panel',
    'goto_content' => 'Go to Content',
    'search_placeholder' => 'Search...',
    'quick_access' => 'Quick Access',
    'products_metrics_title' => 'Products Snapshot',
    'metrics_active' => '%s active records',
    'metrics_products' => '%s product records',
    'metrics_clients' => '%s client records',
    'metrics_homepage' => '%s homepage records',
    'metrics_references' => '%s reference records',
    'metrics_product_categories' => '%s product category records',
    'family_quick_access' => 'Workspace Access',
    'products_spotlight_title' => 'Products Workspace',
    'products_spotlight_copy' => 'Manage products, client proof, and the supporting taxonomy from one family surface.',
    'pinned_modules' => 'Pinned Modules',
    'recent_workspace' => 'Recent Workspace',
    'add_product' => 'Add Product',
    'add_reference' => 'Add Reference',
    'add_client' => 'Add Client',
    'add_service' => 'Add Service',
    'manage_product_categories' => 'Open Category Workspace',
    'manage_service_categories' => 'Open Service Categories',
    'workspace_nav_dashboard' => 'Dashboard',
    'workspace_nav_content' => 'Content',
    'workspace_nav_media' => 'Media',
    'workspace_nav_products' => 'Products',
    'workspace_nav_more' => 'More',
    'more_sheet_title' => 'More Tools',
    'more_sheet_copy' => 'Open lower-frequency system and administration surfaces without crowding the main workspace bar.',
    'more_sheet_open_drawer' => 'Open full module directory',
    'home_title' => 'Home',
    'admin_brand' => 'AKM Business Admin'
];

$lang['mod_user']['fab'] = [
    'add_announcement' => 'Add Announcement',
    'add_product' => 'Add Product',
    'add_service' => 'Add Service',
    'add_page' => 'Add Page',
    'add_video_embed' => 'Add Video Embed',
    'add_video' => 'Add Video',
    'add_content' => 'Add News',
    'add_publication' => 'Add Publication',
    'add_announcement_alert' => 'Announcement Form',
    'add_video_alert' => 'Video Form',
    'add_content_alert' => 'News Wizard',
    'add_publication_alert' => 'Publication Wizard'
];

$lang['mod_user']['profile_menu'] = [
    'edit_profile' => 'Edit Profile',
    'change_password' => 'Change Password',
    'inbox' => 'Inbox',
    'sent_box' => 'Sent Box',
    'new_message' => 'New Message',
    'to_multiple_users' => 'To Multiple Users',
    'to_specific_group' => 'To Specific Group',
    'to_all_members' => 'To All Members',
];

$lang['mod_user']['ajax'] = [
    'login_success' => 'Login successful',
    'login_error' => 'Invalid username or password',
    'validation_error' => 'Please fix the errors',
    'ssid_invalid' => 'Invalid session ID',
    'membership_deleted' => 'Account deleted',
    'success' => 'Success',
    'error' => 'An error occurred',
    'copy_errors' => 'Copy Errors',
    'errors_copied' => 'Error details copied',
    'unexpected_response' => 'The server returned an unexpected response',
    'connection_error' => 'Connection error',
    'invalid_credentials' => 'Invalid credentials',
    'account_inactive' => 'Account inactive',
    'permission_denied' => 'Permission denied',
];

$lang['mod_user']['role_errors'] = [
    'add' => 'Unauthorized to add users',
    'delete' => 'Unauthorized to delete users',
    'edit' => 'Unauthorized to edit users',
    'list' => 'Unauthorized to view user list',
    'profil' => 'Unauthorized to view profiles',
    'settings' => 'Unauthorized to access settings',
    'register' => 'Unauthorized to register',
    'login' => 'Unauthorized to login',
    'logout' => 'Unauthorized to logout',
    'activation' => 'Unauthorized to activate accounts',
    'forgot_password' => 'Unauthorized to recover password',
    'profil_resim' => 'Unauthorized to change pictures',
    'acl' => 'Unauthorized to manage permissions',
    'moderate_edit' => 'Unauthorized to edit others',
    'moderate_delete' => 'Unauthorized to delete others',
];

$lang['mod_user']['roll'] = [
    'roles' => 'User Roles',
    'add' => 'Add Users',
    'edit' => 'Edit Users',
    'delete' => 'Delete Users',
    'list' => 'View Management',
    'acl' => 'Edit Permissions',
    'settings' => 'Change Settings',
    'link' => 'View Admin Links',
    'login' => 'Login',
    'logout' => 'Logout',
    'register' => 'Register',
    'activation' => 'Activate Account',
    'forgot_password' => 'Reset Password',
    'profil_resim' => 'Change Picture',
    'moderate_edit' => 'Edit Others',
    'moderate_delete' => 'Delete Others',
    'profil' => 'View Profile'
];

$lang['mod_user']['list'] = [
    'management' => 'Management',
    'action' => 'Action...',
    'apply' => 'Apply',
    'actions' => 'Actions',
    'select' => 'Select...',
    'search' => 'Search',
    'reset' => 'Reset',
];

$lang['mod_user']['datatable'] = [
    'sortAscending' => ': sort ascending',
    'sortDescending' => ': sort descending',
    'emptyTable' => 'No data',
    'info' => 'Showing _START_ to _END_ of _TOTAL_',
    'infoEmpty' => 'No entries',
    'infoFiltered' => '(filtered)',
    'lengthMenu' => 'Show _MENU_',
    'search' => 'Search:',
    'zeroRecords' => 'No matches',
    'loadingRecords' => 'Loading...',
    'processing' => 'Processing...',
    'first' => 'First',
    'last' => 'Last',
    'next' => 'Next',
    'previous' => 'Previous',
    'selected_records' => '_TOTAL_ selected',
    'ajax_error' => 'Request failed',
    'print' => 'Print',
    'print_info' => 'Press CTRL+P',
    'copy' => 'Copy',
    'select_action' => 'Select action',
    'loading_message' => 'Loading...',
];

$lang['mod_user']['filter'] = [
    'title' => 'Filter & Sort',
    'sort_label' => 'Sort',
    'close' => 'Close',
    'newest' => 'Newest',
    'oldest' => 'Oldest',
    'title_az' => 'A-Z',
    'title_za' => 'Z-A',
    'view_count' => 'Views'
];
