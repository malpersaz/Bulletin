

(function ($) {
	$.fn.validationEngineLanguage = function () { };
	$.validationEngineLanguage = {
		newLang: function () {
			$.validationEngineLanguage.allRules = {
				"required": {    			// Add your regex rules here, you can take telephone as an example
					"regex": "none",
					"alertText": "* Bu Alan Zorunludur",
					"alertTextCheckboxMultiple": "* Lütfen Seçin",
					"alertTextCheckboxe": "* Lütfen Bir Seçenek Seçin"
				},
				"length": {
					"regex": "none",
					"alertText": "*Bu Alan En Kısa ",
					"alertText2": " En Uzun ",
					"alertText3": " Karekter Uzunluğunda Olabilir"
				},
				"maxCheckbox": {
					"regex": "none",
					"alertText": "* Checks allowed Exceeded"
				},
				"minCheckbox": {
					"regex": "none",
					"alertText": "* Lütfen Seçin ",
					"alertText2": " options"
				},
				"confirm": {
					"regex": "none",
					"alertText": "* Yazmış Olduğunuz Veri Eşleşmiyor"
				},
				"check_tcno": {
					"nname": "check_tcno",
					"alertText": "Geçersiz Tc Kimlik Numarası"
				},
				"telephone": {
					"regex": "/^[0-9\-\(\)\ ]+$/",
					"alertText": "* Geçersiz Telefon Numarası"
				},
				"email": {
					"regex": "/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/",
					"alertText": "* Geçersiz E-Posta Adresi"
				},
				"date": {
					"regex": "/^[0-9]{4}\-\[0-9]{1,2}\-\[0-9]{1,2}$/",
					"alertText": "* Geçersiz Bir Tarih Girdiniz Lütfen Tarihi YYYY-MM-DD Formatında Yazın." +
						"<br/>Örnek:2000-12-25"
				},
				"uzunluk": {
					"regex": "/^[0-9]{10}$/",
					"alertText": "* Geçersiz Uzunluk"
				},
				"onlyNumber": {
					"regex": "/^[0-9\ ]+$/",
					"alertText": "* Yalnızca Sayılardan Oluşabilir"
				},

				"para": {
					"regex": "/^[0-9\ \.\,]+$/",
					"alertText": "* Yalnızca Sayılardan Oluşabilir"
				},

				"noSpecialCaracters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Boşluk Ve Özel Karekter Kullanmayınız"
				},
				"ajaxUser": {
					"file": "validateUser.php",
					"extraData": "name=eric",
					"alertTextOk": "* This user is available",
					"alertTextLoad": "* Loading, please wait",
					"alertText": "* This user is already taken"
				},
				"ajaxName": {
					"file": "validateUser.php",
					"alertText": "* This name is already taken",
					"alertTextOk": "* This name is available",
					"alertTextLoad": "* Loading, please wait"
				},
				"onlyLetter": {
					"regex": "/^[a-zA-Z\ \']+$/",
					"alertText": "* Sadece Harflerden Oluşabilir"
				},
				"onlyrhb": {
					"regex": "/^[0-9a-zA-Z\ \']+$/",
					"alertText": "* Bu Alan Sadece Harf ve Rakamlardan Oluşabilir.Lütfen Özel Karekter Kullanmayınız"
				},
				"validate2fields": {
					"nname": "validate2fields",
					"alertText": "* You must have a firstname and a lastname"
				},
				"sifre": {
					"nname": "sifre",
					"alertText": "* Şifre ve Şifre Tekrarı Aynı Olmalıdır."
				},
				"cepii": {
					"nname": "cepii",
					"alertText": "* Lütfen Geçerli Bir Cep Numarası Belirtin."
				},
				"telii": {
					"nname": "telii",
					"alertText": "* Lütfen Geçerli Bir Telefon Numarası Belirtin."
				},
				"sifrem": {
					"nname": "sifrem",
					"alertText": "* Yeni Şifreniz İle Yeni Şifre Tekrarı Aynı Olmalıdır."
				},
				"trchar": {
					"regex": "/^[a-zA-ZığüşöçİĞÜŞÖÇîêûÎÊÛ ]+$/",
					"alertText": "* Bu Alan Sadece Türkçe Karekterlerden Oluşabilir."
				},

				"bolumvarmi": {
					"file": "mod_bolumler/check",
					"alertText": "* Yazdığınız Bölüm Adı Kayıtlı<br>Lütfen Başka bir Bölüm Adı Belirleyin",
					"alertTextOk": "* Kullanılabilinir",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				},
				"categorynamevarmi": {
					"file": "mod_categoryler/adcheck",
					"alertText": "* Yazdığınız Kategori Adı Kayıtlı<br>Lütfen Başka bir Kategori Adı Belirleyin",
					"alertTextOk": "* Kullanılabilinir",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				},
				"categoryseovarmi": {
					"file": "mod_categoryler/seocheck",
					"alertText": "* Yazdığınız Kategori Seo Kayıtlı<br>Lütfen Başka bir Kategori Seo Belirleyin",
					"alertTextOk": "* Kullanılabilinir",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				},

				"icerikseo": {
					"file": "mod_contentler/seocheck",
					"alertText": "* Yazdığınız İçerik Seo Kayıtlı<br>Lütfen Başka bir İçerik Seo Belirleyin",
					"alertTextOk": "* Kullanılabilinir",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				},
				"usernamecheck": {
					"file": "mod_user/usernamecheck",
					"alertText": "* Yazdığınız Kullanıcı Adı Kayıtlı<br>Lütfen Başka bir Kullanıcı Adı Belirleyin",
					"alertTextOk": "* Kullanılabilinir",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				},
				"uyevarmi": {
					"file": "mod_user/uyevarmi",
					"alertText": "* Belirttiğiniz Kullanıcı Adı Bulunmuyor!",
					"alertTextOk": "* Oke",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				},
				"emailcheck": {
					"file": "mod_user/emailcheck",
					"alertText": "* Yazdığınız E-Posta Adı Kayıtlı<br>Lütfen Başka bir E-Posta Belirleyin",
					"alertTextOk": "* Kullanılabilinir",
					"extraData": "",
					"alertTextLoad": "* Yükleniyor.."
				}

			}

		}
	}
})(jQuery);
$(document).ready(function () {
	$.validationEngineLanguage.newLang()
});