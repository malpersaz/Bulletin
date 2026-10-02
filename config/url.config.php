<?php
$url=array();
$url['logout']='mod_user/logout';
$url['logout/(:any)']='mod_user/logout/$1';
$url['login']='mod_user/login';
$url['register']='mod_user/register';
$url['activation']='mod_user/activation';
$url['activation/']='mod_user/activation';
$url['activation/(:any)']='mod_user/activation/$1';
$url['resend-activation']='mod_user/resend_activation';
$url['forgot-password']='mod_user/forgot_password';
$url['reset-password']='mod_user/password_reset';

// Captcha
$url['reset-password/']='mod_user/password_reset';
$url['reset-password/(:any)']='mod_user/password_reset/$1';

// Captcha
$url['captcha']='mod_user/captcha';
$url['dm/(:any)']='mod_user_pm/send/$1';

#   mod_pictures && mod_imagecategories
$url['gallery']='index/gallery';
$url['gallery/(:any)']='index/gallery/$1';

//Announcement
$url['announcement/(:num)-(:any)']='index/announcement/$1';
$url['announcement/(:num)-']='index/announcement/$1';
$url['announcements']='index/announcements';
$url['announcements/']='index/announcements';
$url['announcements/(:any)']='index/announcements/$1';

// Page
$url['page/(:any)']='index/page/$1';

// Statutory Legal & Policy Routes
$url['legal'] = 'index/legal';
$url['legal/'] = 'index/legal';
$url['legal/(:any)'] = 'index/legal/$1';
$url['cookie-policy'] = 'index/cookie_policy';
$url['cookie-policy/'] = 'index/cookie_policy';
$url['cookies'] = 'index/cookie_policy';

// Sitemaps
$url['rss.xml'] = 'index/rss';
$url['seo'] = 'mod_seo/index';
$url['seo/settings'] = 'mod_seo/settings';
$url['sitemap.xml'] = 'index/sitemapIndex';
$url['newsSitemap.xml'] = 'index/newsSitemap';
$url['publicationSitemap.xml'] = 'index/publicationSitemap';
$url['categorySitemap.xml'] = 'index/categorySitemap';
$url['pageSitemap.xml'] = 'index/pageSitemap';
$url['announcementSitemap.xml'] = 'index/announcementSitemap';
$url['videoSitemap.xml'] = 'index/videoSitemap';
$url['gallerySitemap.xml'] = 'index/gallerySitemap';
$url['contentSitemap.xml'] = 'index/contentSitemap';

// LLMS AI Feeds
$url['llms.txt'] = 'index/llms';
$url['llms-full.txt'] = 'index/llmsFull';
$url['.well-known/llms.txt'] = 'index/llms';

// Legacy Redirects
$url['nuce/(:any)'] = 'index/legacyRedirect/$1';
$url['tekst/(:any)'] = 'index/legacyRedirectPublication/$1';

// mod_video_embed
$url['video/(:num)-(:any)']='index/video/$1';
$url['video/(:num)-']='index/video/$1';
$url['video']='index/videos';
$url['video/']='index/videos';
$url['video/(:num)']='index/video/$1';
$url['video/(:any)']='index/video/$1';

$url['videos']='index/videos';
$url['videos/']='index/videos';
$url['videos/(:any)']='index/videos/$1';
$url['videos/(:any)/(:num)']='index/videos/$1/$2';

// Content and Category
$url['article/(:any)']='index/content/$1';
$url['content/detail/(:any)']='index/content/$1';
$url['content/(:any)']='index/content/$1';
$url['content']='index/contents';
$url['content/']='index/contents';
$url['contents']='index/contents';
$url['contents/']='index/contents';
$url['contents/(:num)']='index/contents/$1';
$url['contents/(:any)']='index/contents/$1';
$url['contents/(:any)/(:num)']='index/contents/$1/$2';
$url['category']='index/contents';
$url['category/']='index/contents';
$url['category/(:num)']='index/contents/$1';
$url['category/(:any)']='index/contents/$1';
$url['category/(:any)/(:num)']='index/contents/$1/$2';

// News and News Category
$url['news/detail/(:any)']='index/news/$1';
$url['news/(:any)']='index/news/$1';
$url['newses']='index/newses';
$url['newses/']='index/newses';
$url['newses/(:any)']='index/newses/$1';
$url['newses/(:any)/(:num)']='index/newses/$1/$2';
$url['news-archive']='index/news_archive';

// Publications and Publication Category
$url['publication/detail/(:any)']='index/publication/$1';
$url['publication/(:any)']='index/publication/$1';
$url['publications']='index/publications';
$url['publications/']='index/publications';
$url['publications/(:any)']='index/publications/$1';
$url['publications/(:any)/(:num)']='index/publications/$1/$2';
$url['publication-archive']='index/publication_archive';
$url['authors']='index/authors';
$url['authors/(:num)']='index/authors/$1';
$url['author/(:any)']='index/author/$1';
$url['author/(:any)/(:num)']='index/author/$1/$2';

// File
$url['download/(:num)']='mod_attachment/indir/$1';

// Avatar upload routes
$url['avatar/upload']='mod_user/avatarUpload';
$url['avatar/delete']='mod_user/avatarDelete';
$url['avatar/(:any)']='mod_user/getAvatarUrl/$1';

// mod_redirect routes
$url['mod_redirect/list'] = 'mod_redirect/list';
$url['mod_redirect/ajax_get_redirects'] = 'mod_redirect/ajax_get_redirects';
$url['mod_redirect/ajax_add_redirect'] = 'mod_redirect/ajax_add_redirect';
$url['mod_redirect/ajax_edit_redirect'] = 'mod_redirect/ajax_edit_redirect';
$url['mod_redirect/add'] = 'mod_redirect/add';
$url['mod_redirect/edit'] = 'mod_redirect/edit';
$url['mod_redirect/edit/(:any)'] = 'mod_redirect/edit/$1';
$url['mod_redirect/delete'] = 'mod_redirect/delete';

Registry::add('url_config', $url);
unset($url);
