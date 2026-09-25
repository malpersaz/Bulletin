(function ($) {
	$.fn.validationEngineLanguage = function () { };
	$.validationEngineLanguage = {
		newLang: function () {
			$.validationEngineLanguage.allRules = {
				"required": {
					"regex": "none",
					"alertText": "* Ce champ est requis",
					"alertTextCheckboxMultiple": "* Veuillez choisir une option",
					"alertTextCheckboxe": "* Cette case est requise"
				},
				"length": {
					"regex": "none",
					"alertText": "* Entre ",
					"alertText2": " et ",
					"alertText3": " caractères requis"
				},
				"maxCheckbox": {
					"regex": "none",
					"alertText": "* Nombre maximum d'options dépassé"
				},
				"minCheckbox": {
					"regex": "none",
					"alertText": "* Veuillez choisir au moins ",
					"alertText2": " options"
				},
				"confirm": {
					"regex": "none",
					"alertText": "* Vos champs ne correspondent pas"
				},
				"telephone": {
					"regex": "/^[0-9\\-\\(\\)\\ ]+$/",
					"alertText": "* Numéro de téléphone invalide"
				},
				"phone_mobile": {
					"nname": "phone_mobile",
					"alertText": "* Numéro de téléphone mobile invalide"
				},
				"email": {
					"regex": "/^[a-zA-Z0-9_\\.\\-]+\\@([a-zA-Z0-9\\-]+\\.)+[a-zA-Z0-9]{2,4}$/",
					"alertText": "* Adresse email invalide"
				},
				"date": {
					"regex": "/^[0-9]{4}\\-[0-9]{1,2}\\-[0-9]{1,2}$/",
					"alertText": "* Date invalide, format YYYY-MM-DD requis"
				},
				"length_fixed": {
					"regex": "/^[0-9]{10}$/",
					"alertText": "* Longueur invalide, 10 chiffres requis"
				},
				"onlyNumber": {
					"regex": "/^[0-9\\ ]+$/",
					"alertText": "* Chiffres seulement acceptés"
				},
				"currency": {
					"regex": "/^[0-9\\ \\.\\,]+$/",
					"alertText": "* Chiffres seulement acceptés"
				},
				"noSpecialCharacters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Aucun caractère spécial accepté"
				},
				"noSpecialCaracters": {
					"regex": "/^[0-9a-zA-Z]+$/",
					"alertText": "* Aucun caractère spécial accepté"
				},
				"onlyLetter": {
					"regex": "/^[a-zA-Z\\ \\']+$/",
					"alertText": "* Lettres seulement acceptées"
				},
				"onlyAlphanumeric": {
					"regex": "/^[0-9a-zA-Z\\ \\']+$/",
					"alertText": "* Lettres et chiffres seulement acceptés"
				},
				"password_match": {
					"nname": "password_match",
					"alertText": "* Le mot de passe et sa confirmation doivent être identiques"
				},
				"validate2fields": {
					"nname": "validate2fields",
					"alertText": "* Vous devez renseigner un prénom et un nom"
				},
				"ajaxUser": {
					"file": "validateUser.php",
					"extraData": "name=eric",
					"alertTextOk": "* Ce nom d'utilisateur est disponible",
					"alertTextLoad": "* Chargement, veuillez patienter...",
					"alertText": "* Ce nom d'utilisateur est déjà pris"
				},
				"ajaxName": {
					"file": "validateUser.php",
					"alertText": "* Ce nom est déjà pris",
					"alertTextOk": "* Ce nom est disponible",
					"alertTextLoad": "* Chargement, veuillez patienter..."
				},
				"username_check": {
					"file": "mod_user/check_username",
					"alertText": "* Ce nom d'utilisateur est déjà utilisé",
					"alertTextOk": "* Disponible",
					"extraData": "",
					"alertTextLoad": "* Vérification en cours..."
				},
				"check_user_exists": {
					"file": "mod_user/check_user_exists",
					"alertText": "* Utilisateur introuvable",
					"alertTextOk": "* Succès",
					"extraData": "",
					"alertTextLoad": "* Vérification en cours..."
				},
				"email_check": {
					"file": "mod_user/check_email",
					"alertText": "* Cette adresse email est déjà enregistrée",
					"alertTextOk": "* Disponible",
					"extraData": "",
					"alertTextLoad": "* Vérification en cours..."
				}
			};
		}
	};
})(jQuery);

$(document).ready(function () {
	$.validationEngineLanguage.newLang();
});