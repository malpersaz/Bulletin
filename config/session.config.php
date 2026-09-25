<?php

/**
 * Amed Framework
 * User: AKM
 * Date: 4/26/11 - 1:58 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * File: session.config.php
 * Description: Session Settings
 */
if (defined('root_dir') === False)    exit(':)');
Registry::add('session_name', 'AKMCMS'); // Session Name
#Registry::add('session_path',root_dir.'session'); // Session Directory
Registry::add('session_db', FALSE); // Should Session be kept in DB?
Registry::add('session_check_ip', FALSE); // Should IP control be performed? : TRUE
Registry::add('session_check_agent', FALSE); // Should USER AGENT control be performed? : FALSE
// Session lifetime; drops if inactive for 60 minutes.
Registry::add('session_life_time', 60); // In Minutes
// Registry::add('session_time_out',10); // How many minutes of inactivity before session is deleted
Registry::add('session_active_check', TRUE);
// If User is inactive for 60 Minutes, kills session.
// Registry::add('session_active_time',60);
