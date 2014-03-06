<?php
error_reporting (E_ALL);
@ini_set ("display_errors", true);
/*
Here we perform the PHP test routine.
*/
if (file_get_contents ("https://www.paypal.com/"))
	echo '<div>Test succeeded :-) Lookin\' good here.</div>';

else // Otherwise, the test failed.
	echo '<div>Sorry, this test failed!</div>';
?>