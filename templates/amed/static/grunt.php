<?php
set_time_limit(10);
ini_set('max_execution_time', 10);
$p = system("pidof grunt");
system("kill $p");
//echo system("/bin/sh " . dirname(__FILE__) . "/grunt.sh");
echo passthru("cd " . dirname(__FILE__) . ";/usr/bin/nohup /usr/local/bin/grunt &");