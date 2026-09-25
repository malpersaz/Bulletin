(function ($) {
	$.fn.validationEngineLanguage = function () { };
	$.validationEngineLanguage = {
		newLang: function () {
			$.validationEngineLanguage.allRules = {
				"required": {
					"regex": "none",
					"alertText": "* This field is required",
					"alertTextCheckboxMultiple": "* Please select an option",
					"alertTextCheckboxe": "* This checkbox is required"
				},
				"length": {
					"regex": "none",
					"alertText": "* Between ",
					"alertText2": " and ",
					"alertText3": " characters allowed"
				},
				"maxCheckbox": {
					"regex": "none",
					"alertText": "* Checks allowed exceeded"
				},
				"minCheckbox": {
					"regex": "none",
					"alertText": "* Please select ",
					"alertText2": " options"
				},
				"confirm": {
					"regex": "none",
					"alertText": "* Your field does not match"
				},
				"telephone": {
					"regex": "/^[0-9\\-\\(\\)\\ ]+$/",
					"alertText": "* Invalid phone number"
				},
				"phone_mobile": {
					"nname": "phone_mobile",
					"alertText": "* Please enter a valid mobile phone number"
				},
				"email": {
					"regex": "/^[a-zA-Z0-9_\\.\\-]+\\@([a-zA-Z0-9\\-]+\\.)+[a-zA-Z0-9]{2,4}$/",
					"alertText": "* Invalid email address"
				},
				"date": {
					"regex": "/^[0-9]{4}\\-[0-9]{1,2}\\-[0-9]{1,2}$/",
					"alertText": "* Invalid date, must be in YYYY-MM-DD format"
				},
				"length_fixed": {
					"regex": "/^[0-9]{10}$/",
					"alertText": "* Invalid length, 10 digits required"
				},
				"onlyNumber": {
					"regex": "/^[0-9\\ ]+$/",
					"alertText": "* Numbers only"
				},
				"currency": {
					"regex": "/^[0-9\\ \\.\\,]+$/",
					"alertText": "* Numbers only"
				},
				"noSpecialCharacters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* No special characters allowed"
				},
				"noSpecialCaracters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* No special characters allowed"
				},
				"onlyLetter": {
					"regex": "/^[a-zA-Z\\ \\']+$/",
					"alertText": "* Letters only"
				},
				"onlyAlphanumeric": {
					"regex": "/^[0-9a-zA-Z\\ \\']+$/",
					"alertText": "* Only letters and numbers allowed"
				},
				"password_match": {
					"nname": "password_match",
					"alertText": "* Password and password confirmation must match"
				},
				"validate2fields": {
					"nname": "validate2fields",
					"alertText": "* You must have a firstname and a lastname"
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
				"username_check": {
					"file": "mod_user/check_username",
					"alertText": "* This username is already registered",
					"alertTextOk": "* Available",
					"extraData": "",
					"alertTextLoad": "* Loading, please wait"
				},
				"check_user_exists": {
					"file": "mod_user/check_user_exists",
					"alertText": "* The specified username was not found",
					"alertTextOk": "* Success",
					"extraData": "",
					"alertTextLoad": "* Loading, please wait"
				},
				"email_check": {
					"file": "mod_user/check_email",
					"alertText": "* This email address is already registered",
					"alertTextOk": "* Available",
					"extraData": "",
					"alertTextLoad": "* Loading, please wait"
				}
			};
		}
	};
})(jQuery);

$(document).ready(function () {
	$.validationEngineLanguage.newLang();
});