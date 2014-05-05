<?php
// ---- Configuration. -------------------------------------------------------------------------------------------------------

// s2Member installation domain.
$config["custom"] = "www.mydomain.com";

// s2Member Membership Level#.
$config["item_number"] = "1";

// s2Member Proxy IPN URL handler.
$config["proxy_ipn_handler_url"] = // You'll get this from Dashboard: `s2Member -› PayPal Options -› IPN Integration -› Proxy IPN URL`.
"http://www.mydomain.com/?s2member_paypal_notify=1&s2member_paypal_proxy=proxy&s2member_paypal_proxy_verification=c28831a2ddfdeexXX2f8b722efa0";

// ---- Do NOT edit anything below, unless you know what you're doing. --------------------------------------------------------
@ignore_user_abort(true);
header("HTTP/1.0 200 OK");
header("Content-Type: text/plain; charset=UTF-8");
while (@ob_end_clean ()); // Clean any existing output buffers.

if(/* No ``$_POST`` vars? */empty($_POST) || !is_array($_POST))
	exit /* Exit now. There is nothing to process. */();

$_p = (get_magic_quotes_gpc()) ? stripslashes_deep($_POST) : $_POST;
$_p = trim_deep /* Now trim this array deeply. */($_p);

$_paypal_ipn_server_ip = $_ip = /* Forge IP address to match the PayPal IPN server here. */ "216.113.188.202";
// See list of IPs here: <https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_api_golivechecklist>.

$_p["custom"] = $config["custom"]; $_p["item_number"] = $config["item_number"];
echo (trim(curlpsr($config["proxy_ipn_handler_url"], http_build_query($_p, null, "&"), 20, 20, array("REMOTE_ADDR: ".$_ip, "HTTP_X_FORWARDED_FOR: ".$_ip))));

unset($_paypal_ipn_server_ip, $_ip);

// ---- Do NOT edit anything below, unless you know what you're doing. --------------------------------------------------------
function trim_deep($value = FALSE)
	{
		return is_array($value) ? array_map("trim_deep", $value) : trim((string)$value);
	}
function stripslashes_deep($value = FALSE)
	{
		return is_array($value) ? array_map("stripslashes_deep", $value) : stripslashes((string)$value);
	}
function curlpsr($url = FALSE, $post_vars = array(), $max_con_secs = 20, $max_stream_secs = 20, $headers = array())
	{
		if(($url = trim($url)) && ($curl = curl_init()))
			{
				if /* Because cURL can't deal with complex arrays. */(is_array($post_vars))
					$post_vars = http_build_query($post_vars);
				$follow = (!ini_get("safe_mode") && !ini_get("open_basedir"));
				curl_setopt_array($curl, array(CURLOPT_URL => $url, CURLOPT_POST => true, CURLOPT_CONNECTTIMEOUT => $max_con_secs, CURLOPT_TIMEOUT => $max_stream_secs, CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $post_vars, CURLOPT_FOLLOWLOCATION => $follow, CURLOPT_MAXREDIRS => (($follow) ? 5 : 0), CURLOPT_ENCODING => "", CURLOPT_VERBOSE => false, CURLOPT_FAILONERROR => true, CURLOPT_FORBID_REUSE => true, CURLOPT_SSL_VERIFYPEER => false));
				$o = trim(curl_exec($curl));
				curl_close($curl);
			}
		return (!empty($o)) ? $o : false;
	}
?>