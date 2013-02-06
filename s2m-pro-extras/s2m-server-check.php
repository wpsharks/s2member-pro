<?php
/*
This PHP file can be uploaded to your server and opened in a web browser.
The routines below will check your server for compatibility with s2Member® Pro.
*/
if(version_compare(PHP_VERSION, "5.2.3", ">="))
	{
		echo '<p><strong>Congratulations, you have PHP v'.PHP_VERSION.' installed<em>!</em></p>';
		echo '<p>Your server IS fully compatible with <a href="http://www.s2member.com/prices/">s2Member® Pro</a>.</p>';
		echo '&mdash; Good to go! ( visit <a href="http://www.s2member.com/prices/">s2Member.com</a> )</p>';
	}
else /* PHP version is very outdated. A PHP uggrade will be required. */
	{
		echo '<p><strong>Sorry. PHP v'.PHP_VERSION.' is NOT compatible.</strong></p>';
		echo '<p>You need <a href="http://www.php.net/downloads.php" target="_blank">PHP v5.2.3+</a> to run s2Member® Pro.</p>';
	}
?>