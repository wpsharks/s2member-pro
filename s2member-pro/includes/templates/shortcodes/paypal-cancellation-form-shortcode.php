<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
?>

[s2Member-Pro-PayPal-Form cancel="1" desc="<?php echo esc_attr (_x ("This will cancel your account. Are you sure?", "s2member-front", "s2member")); ?>" captcha="0" /]