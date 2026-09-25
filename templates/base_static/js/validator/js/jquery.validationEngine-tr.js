(function ($) {
	$.fn.validationEngineLanguage = function () { };
	$.validationEngineLanguage = {
		newLang: function () {
			$.validationEngineLanguage.allRules = {
				"required": {
					"regex": "none",
					"alertText": "* Bu alan zorunludur",
					"alertTextCheckboxMultiple": "* Lütfen bir seçenek seçin",
					"alertTextCheckboxe": "* Lütfen bu kutuyu işaretleyin"
				},
				"length": {
					"regex": "none",
					"alertText": "* Bu alana en az ",
					"alertText2": " en fazla ",
					"alertText3": " karakter girilebilir"
				},
				"maxCheckbox": {
					"regex": "none",
					"alertText": "* İzin verilen seçim sayısı aşıldı"
				},
				"minCheckbox": {
					"regex": "none",
					"alertText": "* Lütfen en az ",
					"alertText2": " seçenek seçin"
				},
				"confirm": {
					"regex": "none",
					"alertText": "* Girilen veriler birbiriyle eşleşmiyor"
				},
				"telephone": {
					"regex": "/^[0-9\\-\\(\\)\\ ]+$/",
					"alertText": "* Geçersiz telefon numarası"
				},
				"phone_mobile": {
					"nname": "phone_mobile",
					"alertText": "* Lütfen geçerli bir cep telefonu numarası girin"
				},
				"email": {
					"regex": "/^[a-zA-Z0-9_\\.\\-]+\\@([a-zA-Z0-9\\-]+\\.)+[a-zA-Z0-9]{2,4}$/",
					"alertText": "* Geçersiz e-posta adresi"
				},
				"date": {
					"regex": "/^[0-9]{4}\\-[0-9]{1,2}\\-[0-9]{1,2}$/",
					"alertText": "* Geçersiz tarih, YYYY-MM-DD formatında olmalıdır"
				},
				"length_fixed": {
					"regex": "/^[0-9]{10}$/",
					"alertText": "* Geçersiz uzunluk, 10 hane olmalıdır"
				},
				"onlyNumber": {
					"regex": "/^[0-9\\ ]+$/",
					"alertText": "* Yalnızca sayılardan oluşabilir"
				},
				"currency": {
					"regex": "/^[0-9\\ \\.\\,]+$/",
					"alertText": "* Yalnızca sayılardan oluşabilir"
				},
				"noSpecialCharacters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Boşluk ve özel karakter kullanmayınız"
				},
				"noSpecialCaracters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Boşluk ve özel karakter kullanmayınız"
				},
				"onlyLetter": {
					"regex": "/^[a-zA-Z\\ \\']+$/",
					"alertText": "* Yalnızca harflerden oluşabilir"
				},
				"onlyAlphanumeric": {
					"regex": "/^[0-9a-zA-Z\\ \\']+$/",
					"alertText": "* Bu alan yalnızca harf ve rakamlardan oluşabilir"
				},
				"password_match": {
					"nname": "password_match",
					"alertText": "* Şifre ve şifre tekrarı aynı olmalıdır"
				},
				"validate2fields": {
					"nname": "validate2fields",
					"alertText": "* İsim ve soyisim alanları doldurulmalıdır"
				},
				"ajaxUser": {
					"file": "validateUser.php",
					"extraData": "name=eric",
					"alertTextOk": "* Bu kullanıcı adı kullanılabilir",
					"alertTextLoad": "* Kontrol ediliyor, lütfen bekleyin...",
					"alertText": "* Bu kullanıcı adı zaten kayıtlı"
				},
				"ajaxName": {
					"file": "validateUser.php",
					"alertText": "* Bu isim zaten kayıtlı",
					"alertTextOk": "* Bu isim kullanılabilir",
					"alertTextLoad": "* Yükleniyor, lütfen bekleyin..."
				},
				"username_check": {
					"file": "mod_user/check_username",
					"alertText": "* Yazdığınız kullanıcı adı kayıtlı, lütfen başka bir ad belirleyin",
					"alertTextOk": "* Kullanılabilir",
					"extraData": "",
					"alertTextLoad": "* Kontrol ediliyor..."
				},
				"check_user_exists": {
					"file": "mod_user/check_user_exists",
					"alertText": "* Belirttiğiniz kullanıcı adı bulunamadı!",
					"alertTextOk": "* Başarılı",
					"extraData": "",
					"alertTextLoad": "* Kontrol ediliyor..."
				},
				"email_check": {
					"file": "mod_user/check_email",
					"alertText": "* Yazdığınız e-posta adresi kayıtlı, lütfen başka bir e-posta belirleyin",
					"alertTextOk": "* Kullanılabilir",
					"extraData": "",
					"alertTextLoad": "* Kontrol ediliyor..."
				}
			};
		}
	};
})(jQuery);

$(document).ready(function () {
	$.validationEngineLanguage.newLang();
});
