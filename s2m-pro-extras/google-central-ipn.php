<?php /* ---- Central IPN Processing (aka: Callback URL): -------------------------------------------------------------------

With Google Wallet you absolutely MUST set a Callback (aka: IPN) URL inside your Google Wallet account.
Google Wallet integration does NOT allow the Callback location to be overridden on a per-transaction basis.

So, if you're using one Google Wallet account for multiple cross-domain installations,
and you need to receive IPN notifications for each of your domains; you'll want to create
a central IPN processing script that scans variables in each IPN response,
forking itself out to each of your individual domains.

In rare cases when this is necessary, you'll find an XML component in all IPN responses for s2Member.
The originating domain name will always be included as the first value in:
	$_POST["order-summary_shopping-cart_items_item-1_merchant-private-item-data"]
		Inside: `<s2_custom></s2_custom>`

This XML component can be used to test incoming IPNs, and fork to the proper installation.

---- Instructions: ----------------------------------------------------------------------------------------------------------

1. Save this PHP file to your website.

2. Set the Callback URL (in your Google Wallet account) to the location of this script on your server.
	This central processor forks IPNs out to the proper installation domain.

3. Configuration (below).

---- Configuration: --------------------------------------------------------------------------------------------------------*/
$config = /* One line for each domain (follow the examples here please). */ array
	(
		"[YOUR DOMAIN]" => "[FULL URL TO AN IPN HANDLER FOR YOUR DOMAIN]",
		"www.site1.com" => "http://www.site1.com/?s2member_pro_google_notify=1",
		"www.site2.com" => "http://www.site2.com/?s2member_pro_google_notify=1",
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

if (preg_match("/\<s2_custom\>\s*(.+?)(?:\|.*?)?\s*\<\/s2_custom\>/i", (string)@$_p["order-summary_shopping-cart_items_item-1_merchant-private-item-data"], $_m) && !empty($config[$_m[1]]))
	echo (trim(curlpsr(($_url = $config[$_m[1]]), http_build_query($_p, null, "&"))));
unset($_url, $_m);
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