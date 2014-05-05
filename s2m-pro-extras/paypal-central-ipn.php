<?php /* ---- Central IPN Processing: ---------------------------------------------------------------------------------------

With PayPal Pro integration you absolutely MUST set an IPN URL inside your PayPal account.
PayPal Pro integration does NOT allow the IPN location to be overridden on a per-transaction basis.

So, if you're using a single PayPal Pro account for multiple cross-domain installations,
and you need to receive IPN notifications for each of your domains; you'll want to create
a central IPN processing script that scans variables in each IPN response,
forking itself out to each of your individual domains.

In rare cases when this is necessary, you'll find two variables in all IPN responses for s2Member.
The originating domain name will always be included somewhere within, either:
`custom` and/or `rp_invoice_id`; depending on the type of transaction.

These variables can be used to test incoming IPNs, and fork to the proper installation.

---- Instructions: ----------------------------------------------------------------------------------------------------------

1. Save this PHP file to your website.

2. Set the IPN URL (in your PayPal account) to the location of this script on your server.
	This central processor forks IPNs out to the proper installation domain.

3. Configuration (below).

---- Configuration: --------------------------------------------------------------------------------------------------------*/
$config = /* One line for each domain (follow the examples here please). */ array
	(
		"[YOUR DOMAIN]" => "[FULL URL TO AN IPN HANDLER FOR YOUR DOMAIN]",
		"www.site1.com" => "http://www.site1.com/?s2member_paypal_notify=1",
		"www.site2.com" => "http://www.site2.com/?s2member_paypal_notify=1",
	);
/*
---- Do NOT edit anything below, unless you know what you're doing. --------------------------------------------------------*/
@ignore_user_abort(true);

header("HTTP/1.0 200 OK");
header("Content-Type: text/plain; charset=UTF-8");
while (@ob_end_clean ()); // Clean any existing output buffers.

if ( /* No ``$_POST`` vars? */empty($_POST) || !is_array($_POST))
	exit /* Exit now. There is nothing to process. */();

foreach ($config as $_key => $_value)
	$config[strtolower($_key)] = $_value;
unset($_key, $_value);

$_p = (get_magic_quotes_gpc()) ? stripslashes_deep($_POST) : $_POST;
$_p = trim_deep /* Now trim this array deeply. */($_p);

if ((preg_match("/^(.+?)(?:\||$)/i", (string)@$_p["custom"], $_m) || preg_match("/~(.+?)~/i", (string)@$_p["rp_invoice_id"], $_m)) && !empty($config[$_m[1]]))
	{
		$_paypal_ipn_server_ip = $_ip = /* Forge IP address to match the PayPal IPN server here. */ "173.0.81.1";
		// See list of IPs here: <https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_api_golivechecklist>.

		echo (trim(curlpsr(($_url = $config[$_m[1]]), http_build_query($_p, null, "&"), 20, 20, array
			(
				"REMOTE_ADDR: " . $_ip,
				"HTTP_X_FORWARDED_FOR: " . $_ip
			))));
	}
unset($_paypal_ipn_server_ip, $_ip, $_url, $_m);
/*
---- Do NOT edit anything below, unless you know what you're doing. --------------------------------------------------------*/

function trim_deep ($value = FALSE) { return is_array($value) ? array_map("trim_deep", $value) : trim((string)$value); }

function stripslashes_deep ($value = FALSE) { return is_array($value) ? array_map("stripslashes_deep", $value) : stripslashes((string)$value); }

function curlpsr ($url = FALSE, $post_vars = array (), $max_con_secs = 20, $max_stream_secs = 20, $headers = array ())
	{
		if (($url = trim($url)) && ($curl = curl_init()))
			{
				if /* Because cURL can't deal with complex arrays. */ (is_array($post_vars))
					$post_vars = http_build_query($post_vars);

				$follow = (!ini_get("safe_mode") && !ini_get("open_basedir"));

				curl_setopt_array($curl, array
					(
						CURLOPT_URL => $url,
						CURLOPT_POST => true,
						CURLOPT_CONNECTTIMEOUT => $max_con_secs,
						CURLOPT_TIMEOUT => $max_stream_secs,
						CURLOPT_HEADER => false,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => $headers,
						CURLOPT_POSTFIELDS => $post_vars,
						CURLOPT_FOLLOWLOCATION => $follow,
						CURLOPT_MAXREDIRS => (($follow) ? 5 : 0),
						CURLOPT_ENCODING => "",
						CURLOPT_VERBOSE => false,
						CURLOPT_FAILONERROR => true,
						CURLOPT_FORBID_REUSE => true,
						CURLOPT_SSL_VERIFYPEER => false
					));

				$o = trim(curl_exec($curl));

				curl_close($curl);
			}

		return (!empty($o)) ? $o : false;
	}
?>