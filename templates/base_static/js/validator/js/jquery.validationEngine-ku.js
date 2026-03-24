

(function($) {
	$.fn.validationEngineLanguage = function() {};
	$.validationEngineLanguage = {
		newLang: function() {
			$.validationEngineLanguage.allRules = 	{
					"required":{    			// Add your regex rules here, you can take telephone as an example
						"regex":"none",
						"alertText":"* Divê Were Nivîsîn",
						"alertTextCheckboxMultiple":"* Ji Kerema Xwe re Hilbijêre",
						"alertTextCheckboxe":"* Ji Kerema Xwe re Vebijêrkekê Hilbijêre"},
					"length":{
						"regex":"none",
						"alertText":"*Li Vê Zeviyê Herî Kêm ",
						"alertText2":" Herî Dirêj ",
						"alertText3": " Tîp Dikarin Bên Nivîsîn"},
					"maxCheckbox":{
						"regex":"none",
						"alertText":"* Checks allowed Exceeded"},	
					"minCheckbox":{
						"regex":"none",
						"alertText":"* Ji Kerema Xwe re Hilbijêre ",
						"alertText2":" options"},	
					"confirm":{
						"regex":"none",
						"alertText":"* Daneyên Te Nivîsîne Li Hev Nakin"},
					"check_tcno":{
						"nname":"check_tcno",
						"alertText":"Hejmara TC NO Ne Derbasdar e"},		
					"telephone":{
						"regex":"/^[0-9\-\(\)\ ]+$/",
						"alertText":"* Hejmara Telefonê Ne Derbasdar e"},	
					"email":{
						"regex":"/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/",
						"alertText":"* Navnîşana E-Postayê Ne Derbasdar e"},	
					"date":{
                         "regex":"/^[0-9]{4}\-\[0-9]{1,2}\-\[0-9]{1,2}$/",
                         "alertText":"* Invalid date, must be in YYYY-MM-DD format"},
         			"telii":{
             			"nname":"telii",
             			"alertText":"* Telefon Nederbasdar"},
					"uzunluk":{
	                        "regex":"/^[0-9]{10}$/",
	                        "alertText":"* Dirêjahiha Nederbasdar"},
					"onlyNumber":{
						"regex":"/^[0-9\ ]+$/",
						"alertText":"* Tenê Dikare Hejmar be"},	
						
						"para":{
							"regex":"/^[0-9\ \.\,]+$/",
							"alertText":"* Tenê Dikare Hejmar be"},
							
					"noSpecialCaracters":{
						"regex":"/^[0-9a-zA-Z]+$/",
						"alertText":"* Valahî û Tîpên Taybet Bi Kar Nîne"},	
					"ajaxUser":{
						"file":"validateUser.php",
						"extraData":"name=eric",
						"alertTextOk":"* This user is available",	
						"alertTextLoad":"* Tê Girêdan, ji kerema xwe re li hêviyê be",
						"alertText":"* This user is already taken"},	
					"ajaxName":{
						"file":"validateUser.php",
						"alertText":"* This name is already taken",
						"alertTextOk":"* This name is available",	
						"alertTextLoad":"* Loading, please wait"},		
					"onlyLetter":{
						"regex":"/^[a-zA-Z\ \']+$/",
						"alertText":"* Sadece Harflerden Oluşabilir"},
					"onlyrhb":{
						"regex":"/^[0-9a-zA-Z\ \']+$/",
						"alertText":"* Ev herêm tenê dikare ji tîp û hejmaran pêk were. Tîpên tayber bi kar nîne"},
					"validate2fields":{
    					"nname":"validate2fields",
    					"alertText":"* You must have a firstname and a lastname"}
					}	
					
		}
	}
})(jQuery);
$(document).ready(function() {	
	$.validationEngineLanguage.newLang()
});