<?php
/**
 * Amed Framework
 * User: AKM
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: email.config.php
 * Description: E-Mail Settings
 */
if(defined('root_dir')===False)	exit(':)');
$email['smtp']=array(
					'host'=>'******.kasserver.com',
					'user'=>'username',
					'pass'=>'************',
					'port'=>'587',
					'mail'=>'info@sitename.com',
					'name'=>'Your Site Name'
					);
Registry::add('email_config',$email);
unset($email);