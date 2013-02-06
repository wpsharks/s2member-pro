<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
?>

<a href="%%wpurl%%/?s2member_pro_google_co=1&amp;co[level]=%%level%%&amp;co[ccaps]=%%ccaps%%&amp;co[desc]=%%desc%%&amp;co[cc]=%%cc%%&amp;co[custom]=%%custom%%&amp;co[ra]=%%ra%%&amp;co[rp]=%%rp%%&amp;co[rt]=%%rt%%&amp;co[rr]=%%rr%%&amp;co[image]=%%image%%&amp;co[output]=%%output%%">
 <img src="https://checkout.google.com/buttons/checkout.gif?w=180&amp;h=46&amp;style=trans&amp;variant=text&amp;loc=<?php echo urlencode (_x ("en_US", "s2member-front google-button-lang-code", "s2member")); ?>" style="width:auto; height:auto; border:0;" alt="Google®" />
</a>