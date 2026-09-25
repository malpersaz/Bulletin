(function ($) {
	$.fn.validationEngineLanguage = function () { };
	$.validationEngineLanguage = {
		newLang: function () {
			$.validationEngineLanguage.allRules = {
				"required": {
					"regex": "none",
					"alertText": "* Divê were nivîsîn",
					"alertTextCheckboxMultiple": "* Ji kerema xwe vebijêrkekê hilbijêre",
					"alertTextCheckboxe": "* Ji kerema xwe vê qutîkê nîşan bike"
				},
				"length": {
					"regex": "none",
					"alertText": "* Li vê zeviyê herî kêm ",
					"alertText2": " herî zêde ",
					"alertText3": " tîp dikarin bên nivîsîn"
				},
				"maxCheckbox": {
					"regex": "none",
					"alertText": "* Sînorê hilbijartinê hat derbaskirin"
				},
				"minCheckbox": {
					"regex": "none",
					"alertText": "* Ji kerema xwe herî kêm ",
					"alertText2": " vebijêrkan hilbijêre"
				},
				"confirm": {
					"regex": "none",
					"alertText": "* Daneyên hatine nivîsandin li hev nakin"
				},
				"telephone": {
					"regex": "/^[0-9\\-\\(\\)\\ ]+$/",
					"alertText": "* Hejmara telefonê ne derbasdar e"
				},
				"phone_mobile": {
					"nname": "phone_mobile",
					"alertText": "* Ji kerema xwe hejmareke derbasdar a mobîlê binivîse"
				},
				"email": {
					"regex": "/^[a-zA-Z0-9_\\.\\-]+\\@([a-zA-Z0-9\\-]+\\.)+[a-zA-Z0-9]{2,4}$/",
					"alertText": "* Navnîşana e-postayê ne derbasdar e"
				},
				"date": {
					"regex": "/^[0-9]{4}\\-[0-9]{1,2}\\-[0-9]{1,2}$/",
					"alertText": "* Dîroka nederbasdar, divê bi formata YYYY-MM-DD be"
				},
				"length_fixed": {
					"regex": "/^[0-9]{10}$/",
					"alertText": "* Dirêjahî nederbasdar e, 10 reqem pêwîst in"
				},
				"onlyNumber": {
					"regex": "/^[0-9\\ ]+$/",
					"alertText": "* Tenê dikare hejmar be"
				},
				"currency": {
					"regex": "/^[0-9\\ \\.\\,]+$/",
					"alertText": "* Tenê dikare hejmar be"
				},
				"noSpecialCharacters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Valahî û tîpên taybet bi kar neyîne"
				},
				"noSpecialCaracters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Valahî û tîpên taybet bi kar neyîne"
				},
				"onlyLetter": {
					"regex": "/^[a-zA-Z\\ \\']+$/",
					"alertText": "* Tenê ji tîpan dikare pêk were"
				},
				"onlyAlphanumeric": {
					"regex": "/^[0-9a-zA-Z\\ \\']+$/",
					"alertText": "* Tenê ji tîp û hejmaran dikare pêk were"
				},
				"password_match": {
					"nname": "password_match",
					"alertText": "* Şîfre û dubareya wê divê wekhev bin"
				},
				"validate2fields": {
					"nname": "validate2fields",
					"alertText": "* Divê navek û paşnavek hebe"
				},
				"ajaxUser": {
					"file": "validateUser.php",
					"extraData": "name=eric",
					"alertTextOk": "* Ev bikarhêner derbasdar e",
					"alertTextLoad": "* Tê girêdan, li bendê bin...",
					"alertText": "* Ev bikarhêner berê hatiye tomarkirin"
				},
				"ajaxName": {
					"file": "validateUser.php",
					"alertText": "* Ev nav berê hatiye girtin",
					"alertTextOk": "* Ev nav derbasdar e",
					"alertTextLoad": "* Tê barkirin, li bendê bin..."
				},
				"username_check": {
					"file": "mod_user/check_username",
					"alertText": "* Ev navê bikarhêner berê hatiye tomarkirin",
					"alertTextOk": "* Dikare were bikaranîn",
					"extraData": "",
					"alertTextLoad": "* Tê kontrolkirin..."
				},
				"check_user_exists": {
					"file": "mod_user/check_user_exists",
					"alertText": "* Navê bikarhêner ê diyarkirî nehat dîtin!",
					"alertTextOk": "* Serkeftî",
					"extraData": "",
					"alertTextLoad": "* Tê kontrolkirin..."
				},
				"email_check": {
					"file": "mod_user/check_email",
					"alertText": "* Ev navnîşana e-postayê berê hatiye tomarkirin",
					"alertTextOk": "* Dikare were bikaranîn",
					"extraData": "",
					"alertTextLoad": "* Tê kontrolkirin..."
				}
			};
		}
	};
})(jQuery);

$(document).ready(function () {
	$.validationEngineLanguage.newLang();
});