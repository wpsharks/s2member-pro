<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
?>

[s2Member-Pro-PayPal-Form register="1" level="%%level%%" ccaps="" desc="<?php echo esc_attr (_x ("Signup now, it's Free!", "s2member-front", "s2member")); ?>" custom="%%custom%%" tp="0" tt="D" captcha="clean" /]