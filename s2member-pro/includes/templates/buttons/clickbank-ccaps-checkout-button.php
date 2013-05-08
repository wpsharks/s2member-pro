<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
?>

<a href="http://%%item%%.%%vendor%%.pay.clickbank.net/?cbskin=%%cbskin%%&cbfid=%%cbfid%%&cbur=%%cbur%%&cbf=%%cbf%%&s2_invoice=%%invoice%%&amp;s2_desc=%%desc%%&amp;s2_custom=%%custom%%&amp;s2_customer_ip=<?php echo "<?php echo S2MEMBER_CURRENT_USER_IP; ?>"; ?>%%referencing%%">
 <img src="%%images%%/clickbank-button.png" style="width:auto; height:auto; border:0;" alt="ClickBank®" />
</a>