<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
?>

<optgroup label="<?php echo esc_attr (_x ("Currency", "s2member-admin", "s2member")); ?>">
<option value="USD" selected="selected">USD</option>
</optgroup>