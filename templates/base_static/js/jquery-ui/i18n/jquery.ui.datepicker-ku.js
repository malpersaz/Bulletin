/* Kurdish initialisation for the jQuery UI date picker plugin. */
/* Translator by Rêzan Tovjîn (rezantovjin@hotmail.com). */
jQuery(function($){
	$.datepicker.regional['ku'] = {
		closeText: 'bigire',
		prevText: '&#x3c;paşve',
		nextText: 'pêşve&#x3e',
		currentText: 'îro',
		monthNames: ['Rêbendan','Reşemî','Adar','Avrêl','Gulan','Pûşper',
		'Tîrmeh','Gelawêj','Rezber','Kewçêr','Sermawez','Berfanbar'],
		monthNamesShort: ['Rêb','Reş','Adr','Avr','Gul','Pûş',
		'Tîr','Gel','Rez','Kew','Ser','Ber'],
		dayNames: ['Yekşem','Duşem','Sêşem','Çarşem','Pêncşem','În','Şemî'],
		dayNamesShort: ['Yş','Dş','Sş','Çş','Pş','În','Şm'],
		dayNamesMin: ['Yş','Dş','Sş','Çş','Pş','În','Şm'],
		weekHeader: 'Hf',
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['ku']);
});
