<?php
$lang['mod_user_group']=[
	'add'=>'Lägg till användargrupp',
	'management_title'=>'Användargrupps-hantering',
	'user_groups'=>'Användargrupper',
	'select_optional'=>'---Vänligen välj--- ',
	'confirm_delete'=>'Är du säker på att du vill ta bort denna Användargrupp?<br/> Om du tar bort denna grupp kommer även alla dess undergrupper att tas bort!',
	'group_deleted'=>'Användargrupp Borttagen!',
	'order_changed'=>'Användargruppsordning Ändrad!',
	'delete_group'=>'Ta Bort Användargrupp',
	'edit_group'=>'Redigera Användargrupp',
	'edit'=>'Redigera',
	'yes'=>'Ja',
	'cancel'=>'Avbryt',
	'group_list'=>'Användargruppslista',
	'double_click_instruction'=>'Dubbelklicka på användargruppsnamnet du vill ta bort eller redigera',
	'drag_drop_instruction'=>'Dra och släpp användargrupper du vill omordna',
	'add_new_group'=>'Lägg till Ny Användargrupp'
];

$lang['mod_user_group']['admin']=[
	'add_area'=>'Användargrupps-tilläggningsområde',
	'edit_area'=>'Användargrupps-redigeringsområde',
	'management'=>'Användargrupps-hantering',
	'add_form'=>'Användargrupps-tilläggningsformulär',
	'edit_form'=>'Användargrupps-redigeringsformulär',
	'group_name'=>'Användargruppsnamn',
	'add'=>'Lägg till',
	'edit'=>'Redigera',
	'group_list'=>'Användargruppslista',
	'list'=>'Användargruppslista',
	'group_added'=>'Användargrupp Tillagd!',
	'group_edited'=>'Användargrupp Uppdaterad',
	'sub_group'=>'Underanvändargrupp'
];

$lang['mod_user_group']['hata']=[
	'name_required'=>'Ange ett användargruppsnamn',
	'link_required'=>'Ange en användargruppslänk.',
	'link_unique'=>'Ange en annan användargruppslänk <br/>Eftersom den angivna användargruppslänken redan är registrerad'
];

// Modern language keys (not numbered)
$lang['mod_user_group']['buttons'] = [
    'edit' => 'Redigera',
    'cancel' => 'Avbryt',
    'add' => 'Lägg till',
    'delete' => 'Ta bort',
    'save' => 'Spara'
];

$lang['mod_user_group']['role']=[
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
	'management_title'=>'Du har inte behörighet att redigera användargrupper som tillhör andra.',
	'user_groups'=>'Du har inte behörighet att ta bort användargrupper som tillhör andra.',
	'move'=>'Du har inte behörighet att ändra användargruppsordning.',
	'add' =>'Du har inte behörighet att lägga till användargrupper.',
	'delete'=>'Du har inte behörighet att ta bort användargrupper.',
	'edit' =>'Du har inte behörighet att redigera användargrupper.',
	'list' =>'Du har inte behörighet att visa användargrupps-hantering.'
];
?>
