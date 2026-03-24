<?php
//base64  aa
error_reporting(0);
header("Content-type: data:image/png");
echo base64_decode($_SERVER["QUERY_STRING"]);

?>