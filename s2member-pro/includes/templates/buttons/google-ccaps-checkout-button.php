<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
?>

<a href="#" onclick="google.payments.inapp.buy({jwt: '%%jwt%%', success: function(){ location.href = '%%success%%'; }, failure: function(){ location.href = '%%failure%%'; }}); return false;">
 <img src="%%images%%/google-wallet-co.png" style="width:auto; height:auto; border:0;" alt="Google Wallet (Checkout Now)" />
</a>