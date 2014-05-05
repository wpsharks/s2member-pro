<?php
error_reporting(E_ALL);
@ini_set ("display_errors", true);
/*
Here we perform the PHP test routine.
*/
if (curlpsr ("https://www.paypal.com/", array ("x_test" => 1)))
	echo '<div>Test succeeded :-) Lookin\' good here. Your cURL extension is functioning properly, with SSL enabled too.</div>';

else if (ini_get ("safe_mode"))
	echo '<div>Test failed, but your server is running with <code>safe_mode = on</code>.<br />Please check with your hosting company to be sure.</div>';

else if (ini_get ("open_basedir"))
	echo '<div>Test failed, but your server is running with <code>open_basedir</code>.<br />Please check with your hosting company to be sure.</div>';

else // Otherwise, the test failed.
	echo '<div>Sorry, this test failed!</div>';
/*
Curl operation for posting data and reading response.
*/
function curlpsr ($url = FALSE, $postvars = array (), $max_con_secs = 20, $max_stream_secs = 20, $headers = array ())
	{
		if (($url = trim ($url)) && ($c = curl_init ()))
			{
				if (is_array ($postvars)) // Because cURL can't deal with complex arrays.
					// Since cURL can't deal with complex arrays, we force this to a query string.
					$postvars = http_build_query ($postvars, null, "&");

				curl_setopt_array ($c, // Configure options.
					array (CURLOPT_URL => $url, CURLOPT_POST => true,
					CURLOPT_CONNECTTIMEOUT => $max_con_secs, CURLOPT_TIMEOUT => $max_stream_secs, // Initial connection & stream seconds.
					CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $postvars,
					CURLOPT_FOLLOWLOCATION => ($follow = (!ini_get ("safe_mode") && !ini_get ("open_basedir"))), CURLOPT_MAXREDIRS => (($follow) ? 5 : 0),
					CURLOPT_ENCODING => "", CURLOPT_VERBOSE => false, CURLOPT_FAILONERROR => true, CURLOPT_FORBID_REUSE => true, CURLOPT_SSL_VERIFYPEER => false));

				$o = trim (curl_exec ($c));

				curl_close($c);
			}
		return (!empty ($o)) ? $o : false;
	}
?>