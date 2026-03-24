<?php
$lang['mod_user']=[
    'success' => 'Serkeftî!',
    'error_title' => 'Çewtî Çêbû!',
    'error_label' => 'Çewtî!',
    'password_strength' => [
        'weak' => 'Lawaz',
        'fair' => 'Baş',
        'good' => 'Çêtir',
        'strong' => 'Zexm',
    ],
    'password_match' => [
        'success' => 'Şîfre wekhev in',
        'error' => 'Şîfre ne wekhev in',
    ],
    'js' => [
        'updating' => 'Tê nûkirin...',
        'warning' => 'Hişyarî',
        'weak_password' => 'Şîfreya Lawaz',
        'password_len_req' => 'Herî kêm 6 karakter',
        'password_complex_req' => 'Tîpên mezin, biçûk û hejmar',
    ],
    'button' => [
        'add' => 'Lêzêde Bike',
        'edit' => 'Biguhere',
        'refresh' => 'Nû Bike',
        'delete' => 'Jê Bibe',
        'gallery' => 'Galeriya Wêneyan',
        'file' => 'Rêveberiya Dosyayan',
        'cancel' => 'Betal',
        'deactivate' => 'Rake',
        'yes' => 'Erê',
        'no' => 'Na',
        'select_image' => 'Wêne Hilbijêre',
    ],
    '1'=>'Navê Bikarhêner',
						'2'=>'Şîfre',
						'3'=>'E-peyam',
						'4'=>'Têketina Endam',
						'5'=>'Rûpela Endam',
						'6'=>'Têketin serkeftî ye. <br/> Ji kerema xwe re raweste, tu tê berêvekirin.',
						'7'=>'Peywira te tune vê bikî',
						'8'=>'Ji kerema xwe re têketinê bike.',
						'9'=>'Têketin',
						'10'=>'Derketin',
						'11'=>'Guhertina şîfreyê',
						'12'=>'Biguhere',
						'13'=>'Şîfreya niha',
    					'current_password' => 'Şîfreya Heyî',
						'14'=>'Şîfreya nû',
						'15'=>'Dîsa şîfreya nû',
						'16'=>'Şîfreya te bi serkeftin hate guherandin',
						'17'=>'Guhertina Agahiyan',
						'18'=>'Navê Te',
						'19'=>'Paşnavê Te',
						'20'=>'Navnîşana E-Peyamê',
						'21'=>'Rojbûna Te',
						'22'=>'Agahiyên te bi serkeftin hatin guherandin',
						'23'=>'Telefona Destan',
						'24'=>'Wêneyê Profîlê',
						'25'=>'Min şîfre ji bîr kir',
						'26'=>'Çalakirina profîlê',
						'27'=>'Bibe Endam',
						'28'=>'Bajar',
						'29'=>'Navê Quncikê',
    '30' => 'Di heqê xwe de binivîse',
    '31' => 'Enê pir 255 tip Dikarî Binîvsînî.',
    'installer' => [
        'sql_error' => 'Di dema barkirina users.sql de çewtî çêbû',
        'settings_added' => '%s li tabloya mod_user_settings hate zêdekirin',
        'settings_error' => 'Di dema barkirina Mîhengên Endamtiyê de çewtî çêbû',
        'settings_exists' => '%s di tabloya mod_user_settings de jixwe heye',
        'user_added' => '-----<b>%s</b> - Bikarhêner li danegehê hate zêdekirin!',
        'user_error' => '-----<b>%s</b> - Bikarhêner li danegehê nehate zêdekirin!',
        'user_exists' => '----- <b>%s</b> - Bikarhêner di danegehê de jixwe heye',
    ]
];

$lang['mod_user']['admin']=[
						'add_area'=>'Cihê Lêzêdekirina Endaman',
						'2'=>'Endamekî Nû',
						'added'=>'Endam Hate Lêzêdekirin',
						'4'=>'Endamekî Nû',
						'list' =>'Lîsteya Endaman',
						'edit_area'=>'Cihê Guhertina Endam',
						'7'=>'Endamî Biguhere',
						'updated'=>'Endam Hate Rojanekirin',
						'acl'=>'Rayedariya Koman ( ERK )',
						'10'=>'Kiryar',
						'11'=>'Tomar Bike',
						'permissions_updated'=>'Raye Hatin Guherandin',
						'13'=>'Koma Endaman',
						'management'=>'Rêveberiya Endaman',
						'15'=>'Mîhengên Endaman',
						'16'=>'Mîhengên Endaman Biguhere',
						'settings_updated'=>'Mîhengên Endaman Hatin Rojanekirin',
						'18'=>'Erê',
						'19'=>'Na',
						'delete' => 'Jê Bibe',
						'21'=>'Tu bawer î ku dixwazî wêneyê xwe yê profîlê jê bibî?',
						'22'=>'Agahiyên te bi serkeftin hatin jêbirin<br>Lê wêneyê profîlê nehate guhertin!',
						'23'=>'Wêneyê profîla te hate jêbirin',
                        'columns' => 
                        [
                            'id' => 'ID',
                            'username' => 'Navê Bikarhêner',
                            'email' => 'E-peyam',
                            'name' => 'Nav',
                            'last_name' => 'Paşnav',
                            'status' => 'Rewşa Hesab',
                            'group_id' => 'Koma Endaman',
                            'image' => 'Wêne',
                            'city' => 'Bajar',
                            'blog_name' => 'Navê Blogê',
                            'about' => 'Di heqê de',
                        ],
];

$lang['mod_user']['admin']['settings']=[
						'1'=>'Vekirî',
						'2'=>'Girtî',
						'3'=>'Komeke Nû',
						'4'=>'Dikarin bibin endam',
						'5'=>'Pêwistiya çalakirinê',
						
];
// Required for Flexi Grid
$lang['mod_user']['grid']=[
						'1'=>'Bi giştî ji {total} endaman ji {from}\\\'ê heta {to}\\\'an tê nîşandan.',
						'2'=>'Rûpel',
						'3'=>'Hejmara Rûpelan',
						'4'=>'Dand tên lêbarkirin, ji kerema xwe re raweste ...',
						'5'=>'Qet dane nehatin dîtin',
						'6'=>'Çewtiya têkiliyê',
						'7'=>'Lêgerîn',
						'8'=>'Navê Bikarhênerî',
						'9'=>'E-Peyam',
						'10'=>'Endamekî Nû',
						'11'=>'Biguhere',
						'12'=>'Nû Bike',
						'delete' => 'Jê Bibe',
						'14'=>'Lîsteya Endaman',
						'15'=>'Ji bo guhertinê endamekî hilbijêre',
						'16'=>'Ji bo jêbirinê herî kêm endamekî hilbijêre!',
						'17'=>"Bi giştî dê <font color=red>' + $('.trSelected',grid).length + '</font> endam bên jêbirin. <br/>Tu bawer î ku dixwazî wisa bikî?</br> Ji bîr meke ku hemû naverokên endam nivîsîne jî dê bêne jêbirin",
						'18'=>'Lîsteya Endaman Hate Nûkirin',
						'19'=>'Endam hate jêbirin',
						'20'=>'Erê',
						'21'=>'Betal',
						'22'=>'Koma Endam',
						'activate' => 'Aktîf Bike',
						'deactivate' => 'Pasîf Bike',
						'status' => [
							'key' => 'Aktîf Bike',
							'value' => 'Tu bawer î ku dixwazî vê endamî aktîf bikî?'
						],
						'deactivate' => [
							'key' => 'Pasîf Bike',
							'value' => 'Tu bawer î ku dixwazî vê endamî pasîf bikî?'
						],
						'26'=>'Hesab Hatin Çalakirin!',
						'27'=>'Çalak',
						'28'=>'Li hêviya çalakirina E-peyamê ye.',
						'29'=>'Li hêviya çalakirina rêveber e.',
						'30'=>'Rewşa Hesab',
						'31'=>'Ji kerema xwe re herî kêm rêzeke hilbijêre'
];
// Errors
$lang['mod_user']['error'] = [
    'specify_username' => 'Ji kerema xwe re navekî bikarhênerî binivîse!',
    'specify_password' => 'Tika ye, şîfreyekê binivîse!',
    'specify_email' => 'Tika ye, navnîşana e-peyamekê binivîse',
    'invalid_email' => 'Tika ye, navnîşaneke derbasdar a e-peyamê belî bike!',
    'username_registered' => 'Ev navê bikarhênerî di pergalê de jixwe heye!',
    'email_registered' => 'Ev navnîşana e-peyamê di pergalê de jixwe heye!',
    'username_invalid_chars' => 'Navê Bikarhêner divê dikare tenê ji tîpên a-z A-Z 0-9 pêk were',
    'username_length_error' => 'Navê Bikarhênerî herî kêm ji 3 tîpan, herî pir ji 12 tîpan dikare pêk were',
    'password_length_error' => 'Şîfreya te herî kêm ji 6 tîpan herî pir ji 16 tîpan dikare pêk were',
    'username_not_found' => 'Navê Bikarhêner nehate dîtin',
    'invalid_credentials' => 'Navê Bikarhêner an jî şîfre çewt e.',
    'permission_error' => 'Çewtiya Rayedarî',
    'current_password_required' => 'Ji kerema xwe re niha şîfreya ku tu bi kar tînî binivîse!',
    'new_password_required' => 'Ji kerema xwe re şîfreya xwe ya nû binivîse',
    'confirm_password_required' => 'Ji kerema xwe re şîfreya xwe ya nû dubare bike',
    'passwords_mismatch' => 'Divê şîfreya te ya nû û dubareya wê wekî hev bin.',
    'current_password_incorrect' => 'Şîfreya te belî kir şaş e',
    'name_required' => 'Ji kerema xwe re navê xwe binivîse',
    'surname_required' => 'Tika ye, paşnavê xwe binivîse',
    'dob_required' => 'Tika ye, dîroka jidayikbûna xwe binivîse',
    'name_length_error' => 'Navê te dikare herî kêm divê ji 3 tîp herî pir ji 12 tîpan pêk were',
    'surname_length_error' => 'Paşnavê te dikare herî kêm ji 2 tîpan, herî pir ji 50 tîpan pêk were',
    'name_invalid_chars' => 'Navê te dikare tenê ji tîpên [^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ] pêk were',
    'surname_invalid_chars' => 'Paşnavê te dikare tenê ji tîpên [^a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ] pêk were',
    'date_invalid' => 'Ji kerema xwe re dîrokeke derbasdar binivîse. Awayê dîrokê divê wekî Sal Meh Roj were nivîsîn.',
    'email_invalid_format' => 'Tika ye, navnîşana e-peyameke derbasdar binivîse.',
    'phone_invalid' => 'Hejmara telefona destan ku te nivîsî ne derbasdar e.',
    'account_not_active' => 'Profîla te heye lê ne çalak e.<br/> Ji kerema xwe re endamtiya xwe  
						<a href="activation/" style="color:#000">Çalak Bike</a>.'
];

// User Registration

$lang['mod_user']['register']=[
						'1'=>'Ji demek kin destûra tomarkirinê hatiye rawestandin.<br/>
							Spas ji bo bihîstyarî û famkariya we.',
						'2'=>'Zeviya Tomarbûna Endam',
						'3'=>'Navê Te',
						'4'=>'Paşnavê Te',
						'5'=>'Navê Bikarhênerî',
						'6'=>'E-Peyam',
						'7'=>'Şîfreya Te',
						'8'=>'Şîfre Dubare',
						'9'=>'Telefona Destan',
						'10'=>'Koda Ewlekariyê',
						'11'=>'Tomar Bike',
						'12'=>'Ji bo tomariya endamiyê divê tu zevîya jêrîn tijî bikî.',
						'13'=>'Endametiya te bi serkeftin pêk hat. Niha tu dikarî têketinê pêk bînî.',
						'14'=>'Endametiya te bi serkeftin pêk hat. Lê profîla te ne çalak e.
						<br/> Ji bo tu projîla xwe çalak bikî me ji navnîşana e-peyama te re peyamek şand, ew ê bi te re bibe alîkar.',
						'15'=>'Çalakirina Endametiyê',
						'16'=>'Daxwaza Guherina Şîfreyê',
						'17'=>'Şîfreya Nû',
						'18'=>'Bibe Endam'
];

$lang['mod_user']['register']['error']=[
						'1'=>'Tika ye, navê xwe binivîse',
						'2'=>'Tika ye, paşnavê xwe binivîse',
						'3'=>'Tika ye navê xwe yê bikarhênerî binivîse',
						'4'=>'Tika ye, navnîşana e-peyama xwe binivîse',
						'5'=>'Tika ye, hejmara telefona xwe ya destan binivîse',
						'6'=>'Di navê te de tîpên ne derbasdar hene',
						'7'=>'Di paşnavê te de tîpên ne derbasdar hene',
						'8'=>'Navê te yê bikarhênerî herî kêm ji 3 tîpan, herî pir ji 12 tîpan dikare pêk were',
						'9'=>'Navê bikarhênerî dikare tenê ji tîpên navbera a-z A-Z 0-9 pêk were',
						'10'=>'Şîfre divê herî kêm ji 6 tîpan herî pir ji 16 tîpan pêk were',
						'11'=>'Tika ye, navnîşaneke derbasdar ji bo e-peyamê binivîse',
						'12'=>'Tika ye hejmareke derbasdar ji bo telefona destan binivîse.',
						'13'=>'Navê bikarhênerî ku te nivîsî, berê hatiye tomarkirin',
						'14'=>'Navnîşana e-peyamê ku te nivîsî, berê hatiye tomarkirin',
						'15'=>'Te koda ewlekariyê çewt nivîsî, ji kerema xwe re dîsa biceribîne.',
						'16'=>'Di şandina e-peyama çalakirinê de çewtiyek çêbû.<br/>
						 Ji kerema xwe re bi rêveberê pergalê re têkeve têkiliyê. '
];



// JavaScript validation messages
$lang['mod_user']['register']['js'] = [
    'checking' => '⏳ Tê kontrolkirin...',
    'username_available' => '✅ Navê bikarhênerî gengaz e',
    'username_exists' => '❌ Ev navê bikarhênerî berê hatiye girtin',
    'username_short' => 'Navê bikarhênerî divê herî kêm 2 karakter be',
    'email_available' => '✅ E-peyama tê bikarhênan',
    'email_exists' => '❌ Ev e-peyam berê hatiye tomarkirin',
    'check_failed' => '⚠️ Kontrol nehat pêkanîn',
    'register_success' => 'Endamtiya we bi serkeftin hate çêkirin! Dikarî têketinê pêk bînî.',
    'errors_title' => 'Çewtî:'
];

// JavaScript validation messages - for compatibility with register key
$lang['mod_user']['register']['js'] = [
    'checking' => '⏳ Tê kontrolkirin...',
    'username_available' => '✅ Navê bikarhênerî gengaz e',
    'username_exists' => '❌ Ev navê bikarhênerî berê hatiye girtin',
    'username_short' => 'Navê bikarhênerî divê herî kêm 2 karakter be',
    'email_available' => '✅ E-peyama tê bikarhênan',
    'email_exists' => '❌ Ev e-peyam berê hatiye tomarkirin',
    'check_failed' => '⚠️ Kontrol nehat pêkanîn',
    'register_success' => 'Endamtiya we bi serkeftin hate çêkirin! Dikarî têketinê pêk bînî.',
    'errors_title' => 'Çewtî:'
];

// Country codes
$lang['mod_user']['register']['country_codes'] = [
    'country_select' => 'Welat...',
    'phone' => 'Telefon'
];

// Country codes - for compatibility with register key
$lang['mod_user']['register']['country_codes'] = [
    'country_select' => 'Welat...',
    'phone' => 'Telefon'
];

// Activation

$lang['mod_user']['activation']=[
						'1'=>'Çalakirina profîlê',
						'2'=>'Ji kerema xwe re li zeviya jêrîn koda çalakirinê binivîse.',
						'3'=>'Koda çalakirinê',
						'4'=>'Koda ewlekariyê',
						'5'=>'Çalak bike',
						'6'=>'Heke te koda xwe ya çalakirinê winda kiribe
						<a href="re_activation" style="color:#000">Ji Vir</a> careke din Koda Çalakirinê bixwaze.',
						'7'=>'Profîla te bi serkeftin hate çalakirin  
						<a href="mod_user/login" style="color:#000">Ji Vir</a> tu dikarî têketina profîla xwe pêk bînî.',
						'8'=>'Çalakirina Endametiyê'
];
$lang['mod_user']['activation']['error']=[
						'1'=>'Te koda çalakirinê çewt nivîsî, ji kerema xwe re koda çalakirinê saxtî bike dîsa biceribîne.',
						'2'=>'Tika ye, koda çalakirinê binivîse.',
						'3'=>'Koda çalakirinê ku te nivîsî ne derbasdar e,
							tika ye, pîştî te saxtî kir dîsa biceribîne.'
];

$lang['mod_user']['re_activation']=[
						'1'=>'Koda çalakirinê ji navnîşana e-peyama te re hate şandin. Ki kerema xwe re bi riya wê peyamê profîla xwe çalak bike.',
						'2'=>'Ji em bikaribin ji te re koda çalakirinê bişînin,<br/> ji kerema xwe re li jêr navnîşana e-peyama xwe binivîse.',
						'3'=>'Koda Çalakirinê',
						'4'=>'E-Peyam',
						'5'=>'Koda Ewlekariyê',
						'6'=>'Bişîne',
						'7'=>'Koda Çalakirinê'
];
$lang['mod_user']['re_activation']['error']=[
						'1'=>'Di şandina e-peyama çalakirinê de çewtiyek çêbû.<br/>
						 Ji kerema xwe re bi rêveberê pergalê re têkeve têkiliyê. ',
						'2'=>'Te koda ewlekariyê çewt nivîsî, ji kerema xwe re dîsa bicerîbîne.',
						'3'=>'Ji kerema xwe re navnîşana e-peyama te ku profîla te girêdayî ye, binivîse',
						'4'=>'Te navnîşaneke ne derbasdar ji bo e-peyamê nivîsî.',
						'5'=>'Ev navnîşana e-peyamê ku te nivîsî het niha qet ji bo profîlekê nehatiye bikaranîn.',
						'6'=>'Profîla vê navnîşana e-peyamê ku te nivîsî, jixwe çalak e!'
	
];


$lang['mod_user']['forgot_password']=[
						'1'=>'Ji bo tu şîfreya xwe betal bikî me ji te re e-peyamek şand.<br/>Tika ye, navnîşana E-Peyama xwe saxtî bike û tiştên nîşanî te dide bike.',
						'2'=>'Ji bo betalkirina şîfreya te.<br/> Li jêr navnîşana e-peyama ku profîla te girêdayî wê ye, binivîse.',
						'3'=>'Guhertina Şîfreyê',
						'4'=>'E-Peyam',
						'5'=>'Koda Ewlekariyê',
						'6'=>'Bişîne',
						'7'=>'Min şîfre ji bîr kir'
];
$lang['mod_user']['forgot_password']['error']=[
						'1'=>'Di şandina Koda Betalkirinê de çewtî çêbû, ji kerema xwe re bi Rêveberê Pergalê re bikeve têkiliyê.',
						'2'=>'Te koda ewlekariyê çewt nivîsî, saxtî bike û dîsa biceribîne.',
						'3'=>'Ji kerema xwe re navnîşana e-peyamê ku profîla te girêdayî wê ye, binivîse',
						'4'=>'Te navnîşaneke ne derbasdar ji bo e-peyamê nivîsî.',
						'5'=>'Navnîşana e-peyamê ku te nivîsî, ji bo profîlekê nehatiye tomarkirin.'	
];

$lang['mod_user']['password_reset']=[
						'1'=>'Şîfreyê Biguhere',
						'2'=>'Ji şîfreya te ya nû were çêkirin ji kerema xwe re li jêr Koda Betalkirina Şîfryeê binivîse.',
						'3'=>'Koda Çalakirinê',
						'4'=>'Koda Ewlekariyê',
						'5'=>'Bişîne',
						'6'=>'Pîroz be, ji e-peyama te nîşan kir re şîfreyeke nû hate şandin.<br/>Ji kerema xwe re e-peyama xwe saxtî bike.'
];
$lang['mod_user']['password_reset']['error']=[
						'1'=>'Te koda ewlekariyê çewt nivîsî, ji kerema xwe re dîsa binivîse.',
						'2'=>'Tika ye, koda çalakirinê binivîse.',
						'3'=>'Koda çalakirinê ku te nivîsî ne derbasdar e,
							Ji kerema xwe re dîsa biceribîne.',
						'4'=>'Di şandina e-peyamê de çewtî çêbû. Ji kerema xwe re bi Rêveberê Pergalê re têkeve têkiliyê!'
];



// Roles
$lang['mod_user']['roll']=[
						'roles'=>'Rayeyên Endamiyê',
						'add' =>'Dikare kesan bike endam',
						'edit'=>'Dikare endaman biguhere',
						'delete'=>'Dikare endaman jê bibe',
						'list'=>'Bila rêveberiya endaman bibîne',
						'acl'=>'Dikare rayeyên endaman biguhere',
						'settings'=>'Dikare mîhengên endamtiyê biguhere',
						'link'=>'Dikare lînkên rêveberan bibîne'
];
// AJAX Responses
$lang['mod_user']['ajax'] = [
    'login_success' => 'Têketin serkeftî bû',
    'login_error' => 'Navê bikarhêner an jî şîfre çewt e',
    'validation_error' => 'Ji kerema xwe re çewtiyan rast bikin û dîsa biceribînin',
    'ssid_invalid' => 'SSID ne derbasdar e',
    'membership_deleted' => 'Dibe ku endamtiyê hate jêbirin',
    'success' => 'Serkeftî',
    'error' => 'Di dema têketinê de çewtî çêbû',
    'connection_error' => 'Çewtiya têkiliyê. Ji kerema xwe re dîsa biceribînin.',
    'invalid_credentials' => 'Navê bikarhêner an jî şîfre ne derbasdar e',
    'account_inactive' => 'Hesabê te ne çalak e',
    'permission_denied' => 'Destûr nehatiye dayîn',
];

// JavaScript validation messages
$lang['mod_user']['register']['js'] = [
    'validation_required' => 'Ji kerema xwe re vê qadê dagirin',
    'validation_required_name' => 'Ji kerema xwe re navê xwe binivîsin',
    'validation_required_surname' => 'Ji kerema xwe re paşnavê xwe binivîsin',
    'validation_required_username' => 'Ji kerema xwe re navê bikarhêner binivîsin',
    'validation_username_short' => 'Navê bikarhêner divê herî kêm 2 karakter be',
    'validation_username_long' => 'Navê bikarhêner ne girintir ji 20 karakter be',
    'validation_required_email' => 'Ji kerema xwe re navnîşana e-peyamê binivîsin',
    'validation_invalid_email' => 'Ji kerema xwe re navnîşaneke derbasdar a e-peyamê binivîsin',
    'validation_required_password' => 'Ji kerema xwe re şîfreyê binivîsin',
    'validation_password_short' => 'Şîfre divê herî kêm 6 karakter be',
    'validation_password_long' => 'Şîfre ne girintir ji 20 karakter be',
    'validation_confirm_password' => 'Ji kerema xwe re şîfreyê dîsa binivîsin',
    'validation_required_phone' => 'Ji kerema xwe re hejmara telefonê binivîsin',
    'validation_required_captcha' => 'Ji kerema xwe re koda ewlekariyê binivîsin',
];

// Captcha helper text
$lang['mod_user']['register']['captcha_refresh'] = 'Ji bo nûkirina kodê li wêneyê bitikînin';

// Panel Welcome Messages
$lang['mod_user']['panel'] = [
    'welcome' => 'Silava dor bi dor %s',
    'description' => 'Ji pêşekên rûpelê tu dikarî guhartin û lêkirinan bikî.',
    'total_content' => 'Hemû Naverok',
    'pending_content' => 'Di Rêzê De',
    'draft_content' => 'Pêşnûma',
    'view_reports' => 'Lîsteya Naverokan',
    'welcome_main' => 'Tu bi xêr hatî,',
    'panel_title' => 'Rêveberiya Naverokan',
    'goto_content' => 'Biçe Rêveberiya Naverokê',
    'search_placeholder' => 'Li Naverokê Bigere...',
    'quick_access' => 'Gihaştina Lezgîn',
    'home_title' => 'Serûpel',
    'admin_brand' => 'Rêveberiya Rojname'
];

// FAB Menu
$lang['mod_user']['fab'] = [
    'add_announcement' => 'Daxwiyaniyek Nû',
    'add_video' => 'Vîdeoyek Nû',
    'add_content' => 'Naverokek Nû',
    'add_announcement_alert' => 'Forma Daxwiyaniyê',
    'add_video_alert' => 'Forma Vîdeoyê',
    'add_content_alert' => 'Sêrbaza Naverokê'
];

// === Restriction Errors  ===
$lang['mod_user']['role_errors']=[
    'add' =>'Tu ne rayedarê tomarkirina endaman î',
    'delete'=>'Tu ne rayedarê jêbirina endaman î',
    'edit'=>'Tu ne rayedarê guhertina endaman î',
    'list'=>'Tu ne rayedarê rêveberiya endaman î',
    'profil'=>'Tu ne rayedarê dîtinina profîlên bikarhêneran î',
    'settings'=>'Tu ne rayedarê têketina mîhengên bikarhêneran î',
    'register'=>'Tu ne rayedarê têketina tomarkirina endamtiyê î',
    'login'=>'Tu ne rayedarê têketina têketina bikarhêneran î',
    'logout'=>'Tu ne rayedarê têketina derketina bikarhêneran î',
    'activation'=>'Tu ne rayedarê têketina çalakirina bikarhêneran î',
    'sifre_unuttum'=>'Tu ne rayedarê vedîtina şîfreyê î',
    'profil_resim'=>'Tu ne rayedarê guhertina wêneyên profîlê î',
    'acl'=>'Tu ne rayedarê rêveberiya destûrên bikarhêneran î',
    'moderate_edit'=>'Tu ne rayedarê guhertina bikarhênerên din î',
    'moderate_delete'=>'Tu ne rayedarê jêbirina bikarhênerên din î',
    'link'=>'Tu ne rayedarê lînkên rêveberan î'
];

// JavaScript Modal Messages
$lang['mod_user']['js_modal'] = [
    'pleaseFillAll' => 'Ji kerema xwe re hemû qadan dagirin',
    'connectionError' => 'Çewtiya têkiliyê. Ji kerema xwe re dîsa biceribînin.',
    'timeoutError' => 'Demê daxwazê bi dawî bû',
    'networkError' => 'Çewtiya torê - ji kerema xwe re têkiliya xwe kontrol bikin',
    'serverError' => 'Çewtiya bersiva serverê',
    'signingIn' => 'Têketin...',
    'loginSuccess' => 'Têketin serkeftî bû!',
    'redirecting' => 'Tê berêvekirin...',
    'loginFailed' => 'Têketin serneket',
    'alternativeOptions' => 'Vebijêrkên Alternatîf',
];

// User Profile Menu Items
$lang['mod_user']['profile_menu'] = [
    'edit_profile' => 'Profîlê Biguhere',
    'change_password' => 'Şîfreyê Biguhere',
    'inbox' => 'Sindoqa Hatiyan',
    'sent_box' => 'Sindoqa Şandiyan',
    'new_message' => 'Peyama Nû',
    'to_multiple_users' => 'Ji Pir Bikarhêneran Re',
    'to_specific_group' => 'Ji Komek Taybet Re',
    'to_all_members' => 'Ji Hemû Endaman Re',
];

$lang['mod_user']['admin']['site_settings'] = 'Mîhengên Malperê';
$lang['mod_user']['admin']['social_media'] = 'Medyaya Sosyal';

// List Page
$lang['mod_user']['list'] = [
    'management' => 'Rêveberiya Tomaran',
    'action' => 'Kiryar...',
    'apply' => 'Pêk bîne',
    'actions' => 'Kiryar',
    'select' => 'Hilbijêre...',
    'search' => 'Lêgerîn',
    'reset' => 'Sifir bike',
];

// DataTable
$lang['mod_user']['datatable'] = [
    'sortAscending' => ': çalak bike da ku stûnê ber bi jor ve rêz bikî',
    'sortDescending' => ': çalak bike da ku stûnê ber bi jêr ve rêz bikî',
    'emptyTable' => 'Di tabloyê de dane tune',
    'info' => '<span class=\'seperator\'>|</span> Nîşandana <b>_START_</b> heta <b>_END_</b> ji <b>_TOTAL_</b> tomaran',
    'infoEmpty' => 'Tomar nehatin dîtin',
    'infoFiltered' => '(ji _MAX_ tomaran hate parzûn kirin)',
    'lengthMenu' => '<span class=\'seperator\'>|</span> _MENU_ tomaran nîşan bide',
    'search' => 'Lêgerîn:',
    'zeroRecords' => 'Tomarên guncaw nehatin dîtin',
    'loadingRecords' => 'Tê daxistin...',
    'processing' => 'Tê bicihanîn...',
    'first' => 'Yekem',
    'last' => 'Dawî',
    'next' => 'Pêş',
    'previous' => 'Paş',
    'selected_records' => '_TOTAL_ tomar hatin hilbijartin: ',
    'ajax_error' => 'Daxwaz nehate temam kirin. Ji kerema xwe înterneta xwe kontrol bikin.',
    'print' => 'Çap bike',
    'print_info' => 'Ji bo çapkirinê "CTRL+P" û ji bo derketinê "ESC" bitikînin.',
    'copy' => 'Kopyala',
    'select_action' => 'Ji kerema xwe kiryarekê hilbijêrin',
    'loading_message' => 'Tê bicihanîn...',
];

// Filter & Sort Generic Keys
$lang['mod_user']['filter'] = [
    'title' => 'Parzûn & Rêzkirin',
    'sort_label' => 'Rêza Rêzkirinê',
    'close' => 'Bigire',
    'newest' => 'Yê herî nû',
    'oldest' => 'Yê herî kevn',
    'title_az' => 'Sernav (A-Z)',
    'title_za' => 'Sernav (Z-A)',
    'view_count' => 'Hejmara Dîtinê'
];


// --- Otomatîk Zêdekirin (Senkronîzasyon) ---
$lang['mod_user']['add'] = 'Üye Ekle';
$lang['mod_user']['edit'] = 'Üyeyi Düzenle';
$lang['mod_user']['information'] = 'Bilgi!';
$lang['mod_user']['oke'] = 'İşlem Başarılı!';
$lang['mod_user']['last_step'] = 'Son Adım';
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
$lang['mod_user']['register']['19'] = 'Hesabınız başarıyla oluşturuldu. <br/>Güvenlik nedeniyle hesabınızın bir yönetici tarafından approvenması gerekmektedir. Onay işleminden sonra size e-posta ile bilgi verilecektir.';
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
$lang['mod_user']['register']['js']['checking'] = '⏳ Kontrol ediliyor...';
$lang['mod_user']['register']['js']['username_available'] = '✅ Kullanıcı adı uygun';
$lang['mod_user']['register']['js']['username_exists'] = '❌ Bu kullanıcı adı zaten alınmış';
$lang['mod_user']['register']['js']['username_short'] = 'Kullanıcı adı en az 2 karakter olmalıdır';
$lang['mod_user']['register']['js']['email_available'] = '✅ E-posta kullanılabilir';
$lang['mod_user']['register']['js']['email_exists'] = '❌ Bu e-posta zaten kayıtlı';
$lang['mod_user']['register']['js']['check_failed'] = '⚠️ Kontrol yapılamadı';
$lang['mod_user']['register']['js']['register_success'] = 'Üyeliğiniz başarıyla oluşturuldu! Giriş yapabilirsiniz.';
$lang['mod_user']['register']['js']['errors_title'] = 'Hatalar:';
$lang['mod_user']['register']['country_codes']['select'] = 'Ülke Seçin';
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
$lang['mod_user']['32'] = '%s okunmamış mesajınız var.';
$lang['mod_user']['groups']['site_manager'] = 'Site Yöneticisi';
$lang['mod_user']['groups']['editors'] = 'Editörler';
$lang['mod_user']['groups']['normal_member'] = 'Normal Üye';
$lang['mod_user']['membership_date'] = 'Üyelik Tarihi';
$lang['mod_user']['update_date'] = 'Güncelleme Tarihi';
$lang['mod_user']['admin']['radio']['publish_yes'] = 'Evet';
$lang['mod_user']['admin']['radio']['publish_no'] = 'Hayır';
$lang['mod_user']['admin']['radio']['deck_yes'] = 'Evet';
$lang['mod_user']['admin']['radio']['deck_no'] = 'Hayır';
$lang['mod_user']['admin']['settings']['6'] = 'Otomatik Onaylı';
$lang['mod_user']['admin']['settings']['7'] = 'E-Posta Onaylı';
$lang['mod_user']['admin']['settings']['8'] = 'Admin Onaylı';
$lang['mod_user']['admin']['settings']['9'] = 'Bir Hesapla Aynı Anda İki Kişi Sisteme Bağlanabilsinmi ?';
$lang['mod_user']['admin']['settings']['10'] = 'Evet';
$lang['mod_user']['admin']['settings']['11'] = 'Hayır';
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
$lang['mod_user']['admin']['active']['1'] = 'Merhabalar Sayın <b>%s %s</b>.';
$lang['mod_user']['admin']['active']['2'] = '%s Adresinde Bulunan Hesabınızın Yönetici Tarafından Aktifleştirildi<br/>
Dilerseniz Artık Kullanıcı Adı Ve Şifrenizle Giriş Yapabilirsiniz.';
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
$lang['mod_user']['grid']['23'] = 'Aktifleştir';
$lang['mod_user']['grid']['24'] = 'Lütfen Aktifleştirmek İstediğiniz Hesabı Seçin';
$lang['mod_user']['grid']['25'] = 'Seçili Hesapları Aktifleştirmek İstediğinize Eminmisiniz?';
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
$lang['mod_user']['grid']['select_account_activate'] = 'Please select the account you want to activate';
$lang['mod_user']['grid']['confirm_activate'] = 'Are you sure you want to activate the selected accounts?';
$lang['mod_user']['grid']['accounts_activated'] = 'Accounts activated!';
$lang['mod_user']['grid']['active'] = 'Active';
$lang['mod_user']['grid']['waiting_email_activation'] = 'Waiting for Email Activation';
$lang['mod_user']['grid']['waiting_admin_activation'] = 'Waiting for Admin Activation.';
$lang['mod_user']['grid']['account_status'] = 'Account Status';
$lang['mod_user']['grid']['select_at_least_one'] = 'Please select at least one row';
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
	<a href="re_activation" style="color:#000">Click here</a> to request a new activation code.';
$lang['mod_user']['activation']['success_activated'] = 'Your account has been successfully activated. If you wish
	<a href="mod_user/login" style="color:#000">Click here</a> to log in to your account.';
$lang['mod_user']['activation']['membership_activation'] = 'Membership Activation';
$lang['mod_user']['re_activation']['error']['7'] = 'Yazmış Olduğunuz E-Posta Adresine Bağlı Hesap Sadece Yönetici Tarafından Aktifleştirilebilinir.';
$lang['mod_user']['re_activation']['error']['8'] = 'Yazmış Olduğunuz E-Posta Adresine Ait Aktivasyon Kodu Bulunmuyor!';
$lang['mod_user']['re_activation']['error']['activation_send_error'] = 'Error occurred while sending activation code. Please contact the system administrator.';
$lang['mod_user']['re_activation']['error']['invalid_captcha'] = 'You entered the security code incorrectly, please check and try again.';
$lang['mod_user']['re_activation']['error']['email_required'] = 'Please write the e-mail address associated with your account.';
$lang['mod_user']['re_activation']['error']['invalid_email_format'] = 'You entered an invalid e-mail address.';
$lang['mod_user']['re_activation']['error']['email_not_registered'] = 'The specified e-mail address is not registered to any account.';
$lang['mod_user']['re_activation']['error']['account_already_active'] = 'The account associated with the e-mail address you entered is already active!';
$lang['mod_user']['re_activation']['error']['admin_activation_required'] = 'The account associated with the e-mail address you entered can only be activated by an administrator.';
$lang['mod_user']['re_activation']['error']['no_activation_code_found'] = 'No activation code found for the e-mail address you entered!';
$lang['mod_user']['re_activation']['code_sent'] = 'Your activation code has been sent. Please check your e-mail to activate your account';
$lang['mod_user']['re_activation']['write_email'] = 'So that we can send you an activation code.<br/> Write the e-mail address associated with your account in the field below.';
$lang['mod_user']['re_activation']['activation_code'] = 'Activation Code';
$lang['mod_user']['re_activation']['email'] = 'E-mail';
$lang['mod_user']['re_activation']['security_code'] = 'Security Code';
$lang['mod_user']['re_activation']['send_button'] = 'Send';
$lang['mod_user']['re_activation']['activation_code_label'] = 'Activation Code';
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
$lang['mod_user']['roll']['login'] = 'Giriş Yapabilir';
$lang['mod_user']['roll']['logout'] = 'Çıkış Yapabilir';
$lang['mod_user']['roll']['register'] = 'Kayıt Olabilir';
$lang['mod_user']['roll']['activation'] = 'Hesap Aktifleştirebilir';
$lang['mod_user']['roll']['forgot_password'] = 'Şifre Sıfırlayabilir';
$lang['mod_user']['roll']['profil_resim'] = 'Profil Resmi Değiştirebilir';
$lang['mod_user']['roll']['moderate_edit'] = 'Diğer Üyeleri Düzenleyebilir';
$lang['mod_user']['roll']['moderate_delete'] = 'Diğer Üyeleri Silebilir';
$lang['mod_user']['roll']['profil'] = 'Profil Görüntüleyebilir';
$lang['mod_user']['role_errors']['forgot_password'] = 'Şifre kurtarmaya erişim permissionniz yok';
$lang['mod_user']['remove_image'] = 'Are you sure you want to remove this image?';
$lang['mod_user']['remove_image_oke'] = 'Image Removed';
$lang['mod_user']['choose_wish'] = 'Please choose if you want';
$lang['mod_user']['remove_record'] = 'Are you sure you want to delete this record?';
$lang['mod_user']['message']['oke']['add'] = 'User Added!';
$lang['mod_user']['message']['oke']['edit'] = 'User Updated!';
$lang['mod_user']['message']['error']['add'] = 'User Could Not Be Added!';
$lang['mod_user']['message']['error']['edit'] = 'User Could Not Be Updated!';
$lang['mod_user']['errors']['record'] = 'The specified record could not be found!';
$lang['mod_user']['unread_messages_count'] = 'You have %s unread messages.';
$lang['mod_user']['validation']['username_min_length'] = 'Username must be at least 2 characters';
