<?php
$lang['mod_user_group']=[
	'1'=>'Användargrupps-hantering',
	'2'=>'Användargrupper',
	'5'=>'---Vänligen välj--- ',
	'8'=>'Är du säker på att du vill ta bort denna Användargrupp?<br/> Om du tar bort denna grupp kommer även alla dess undergrupper att tas bort!',
	'9'=>'Användargrupp Borttagen!',
	'10'=>'Användargruppsordning Ändrad!',
	'11'=>'Ta Bort Användargrupp',
	'12'=>'Redigera Användargrupp',
	'13'=>'Redigera',
	'15'=>'Ja',
	'16'=>'Avbryt',
	'17'=>'Användargruppslista',
	'18'=>'Dubbelklicka på användargruppsnamnet du vill ta bort eller redigera',
	'19'=>'Dra och släpp användargrupper du vill omordna',
	'20'=>'Lägg till Ny Användargrupp'
];

$lang['mod_user_group']['admin']=[
	'1'=>'Användargrupps-tilläggningsområde',
	'2'=>'Användargrupps-redigeringsområde',
	'3'=>'Användargrupps-hantering',
	'4'=>'Användargrupps-tilläggningsformulär',
	'5'=>'Användargrupps-redigeringsformulär',
	'7'=>'Användargruppsnamn',
	'8'=>'Lägg till',
	'9'=>'Redigera',
	'10'=>'Användargruppslista',
	'12'=>'Användargrupp Tillagd!',
	'13'=>'Användargrupp Uppdaterad',
	'22'=>'Underanvändargrupp'
];

$lang['mod_user_group']['hata']=[
	'1'=>'Ange ett användargruppsnamn',
	'2'=>'Ange en användargruppslänk.',
	'3'=>'Ange en annan användargruppslänk <br/>Eftersom den angivna användargruppslänken redan är registrerad'
];

// Modern language keys (not numbered)
$lang['mod_user_group']['buttons'] = [
    'edit' => 'Redigera',
    'cancel' => 'Avbryt',
    'add' => 'Lägg till',
    'delete' => 'Ta bort',
    'save' => 'Spara'
];

$lang['mod_user_group']['roll']=[
	'name'=>'Användargruppsroller',
	'add' =>'Kan Lägga till Användargrupp.',
	'edit' =>'Kan Redigera Tillagd Användargrupp.',
	'delete'=>'Kan Ta Bort Tillagd Användargrupp.',
	'list' =>'Kan Visa Användargrupps-hantering.',
	'moderate'=>'Kan Hantera Användargrupper Tillagda av Andra.',
	'move'=>'Kan Ändra Användargruppsordning'
];

// === AJAX Response Messages ===
$lang['mod_user_group']['ajax'] = [
    'success' => 'Lyckades',
    'error' => 'Fel',
];

$lang['mod_user_group']['role_errors']=[
	'1'=>'Du har inte behörighet att redigera användargrupper som tillhör andra.',
	'2'=>'Du har inte behörighet att ta bort användargrupper som tillhör andra.',
	'move'=>'Du har inte behörighet att ändra användargruppsordning.',
	'add' =>'Du har inte behörighet att lägga till användargrupper.',
	'delete'=>'Du har inte behörighet att ta bort användargrupper.',
	'edit' =>'Du har inte behörighet att redigera användargrupper.',
	'list' =>'Du har inte behörighet att visa användargrupps-hantering.'
];
?>