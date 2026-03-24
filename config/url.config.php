<?php
$url=array();
$url['logout']='mod_user/logout';
$url['login']='mod_user/login';
$url['register']='mod_user/register';
$url['activation']='mod_user/activation';
$url['activation/']='mod_user/activation';
$url['activation/(:any)']='mod_user/activation/$1';
$url['resend-activation']='mod_user/re_activation';
$url['forgot-password']='mod_user/forgot_password';
$url['reset-password']='mod_user/password_reset';

// Static pages
$url['about']='index/sayfa/hakkimizda';
$url['contact']='index/sayfa/iletisim';
$url['reset-password/']='mod_user/password_reset';
$url['reset-password/(:any)']='mod_user/password_reset/$1';

// Captcha
$url['captcha']='mod_user/captcha';

#   mod_pictures && mod_imagecategories
$url['gallery']='index/gallery';
$url['gallery/(:any)']='index/gallery/$1';

//Announcement
$url['announcement/(:num)-(:any)']='index/announcement/$1';
$url['announcements']='index/announcements';
$url['announcements/']='index/announcements';
$url['announcements/(:any)']='index/announcements/$1';

//Page
$url['page/(:any)']='index/sayfa/$1';



// mod_video_embed
$url['video/(:num)-(:any)']='index/video/$1';
$url['video']='index/videos';
$url['video/(:num)']='index/videos/$1';

$url['videos']='index/videos';
$url['videos/']='index/videos';
$url['videos/(:any)']='index/videos/$1';
$url['videos/(:any)/(:num)']='index/videos/$1/$2';

// Content and Category
$url['article/(:any)']='index/content/$1';
$url['category']='index/contents';
$url['category/']='index/contents';
$url['category/(:any)']='index/contents/$1';
$url['category/(:any)/(:num)']='index/contents/$1/$2';

// File
$url['download/(:num)']='mod_attachment/indir/$1';

// Avatar upload routes
$url['avatar/upload']='mod_user/avatarUpload';
$url['avatar/delete']='mod_user/avatarDelete';
$url['avatar/(:any)']='mod_user/getAvatarUrl/$1';

Kayit::ekle('url_config',$url);
unset($url);