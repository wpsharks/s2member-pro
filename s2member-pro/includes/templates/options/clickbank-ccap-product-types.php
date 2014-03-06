<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
?>

<option value="standard" selected="selected"><?php echo esc_html (_x ("Standard", "s2member-admin", "s2member")); ?></option>