<?php

/**
 * Amed Framework
 * User: a.k.mutlu
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
Registry::add('session_db', FALSE); // Store session in DB
Registry::add('session_check_ip', FALSE); // Check IP address : TRUE
Registry::add('session_check_agent', FALSE); // Check USER AGENT : FALSE
// Session lifetime in minutes if idle.
Registry::add('session_life_time', 60); // In minutes
// Registry::add('session_time_out',10); // Inactivity timeout before session deletion
Registry::add('session_active_check', TRUE);
// Kill session if user is inactive for 60 minutes.
// Registry::add('session_active_time',60);
