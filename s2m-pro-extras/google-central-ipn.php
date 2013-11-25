<?php /* ---- Central IPN Processing (aka: Postback URL): -------------------------------------------------------------------

With Google Wallet you absolutely MUST set a Postback (aka: IPN) URL inside your Google Wallet account.
Google Wallet integration does NOT allow the Postback location to be overridden on a per-transaction basis.

So, if you're using one Google Wallet account for multiple cross-domain installations,
and you need to receive IPN notifications for each of your domains; you'll want to create
a central IPN processing script that scans variables in each IPN response,
forking itself out to each of your individual domains.

---- Instructions: ----------------------------------------------------------------------------------------------------------

1. Save this PHP file to your website.

2. Set the Postback URL (in your Google Wallet account) to the location of this script on your server.
	This central processor forks IPNs out to the proper installation domain.

3. Configuration (below).

---- Configuration: --------------------------------------------------------------------------------------------------------*/
$key = ""; // Your Google Wallet Merchant "Key" (a secret key).
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

if (is_object($_jwt = JWT::decode((string)@$_p["jwt"], $key)) && !empty($_jwt->request->sellerData->cs) && preg_match("/^(.+?)(?:\||$)/i", (string)$_jwt->request->sellerData->cs, $_m) && !empty($config[$_m[1]]))
	echo (trim(curlpsr(($_url = $config[$_m[1]]), http_build_query($_p, null, "&"))));
unset($_jwt, $_url, $_m);
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
/**
 * JSON Web Token implementation
 *
 * Minimum implementation used by Realtime auth, based on this spec:
 * http://self-issued.info/docs/draft-jones-json-web-token-01.html.
 *
 * @author Neuman Vong <neuman@twilio.com>
 */
class JWT
{
    /**
     * @param string      $jwt    The JWT
     * @param string|null $key    The secret key
     * @param bool        $verify Don't skip verification process
     *
     * @return object The JWT's payload as a PHP object
     */
    public static function decode($jwt, $key = null, $verify = true)
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $payloadb64, $cryptob64) = $tks;
        if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))
        ) {
            throw new UnexpectedValueException('Invalid segment encoding');
        }
        if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payloadb64))
        ) {
            throw new UnexpectedValueException('Invalid segment encoding');
        }
        $sig = JWT::urlsafeB64Decode($cryptob64);
        if ($verify) {
            if (empty($header->alg)) {
                throw new DomainException('Empty algorithm');
            }
            if ($sig != JWT::sign("$headb64.$payloadb64", $key, $header->alg)) {
                throw new UnexpectedValueException('Signature verification failed');
            }
        }
        return $payload;
    }

    /**
     * @param object|array $payload PHP object or array
     * @param string       $key     The secret key
     * @param string       $algo    The signing algorithm
     *
     * @return string A JWT
     */
    public static function encode($payload, $key, $algo = 'HS256')
    {
        $header = array('typ' => 'JWT', 'alg' => $algo);

        $segments = array();
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($header));
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($payload));
        $signing_input = implode('.', $segments);

        $signature = JWT::sign($signing_input, $key, $algo);
        $segments[] = JWT::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * @param string $msg    The message to sign
     * @param string $key    The secret key
     * @param string $method The signing algorithm
     *
     * @return string An encrypted message
     */
    public static function sign($msg, $key, $method = 'HS256')
    {
        $methods = array(
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        );
        if (empty($methods[$method])) {
            throw new DomainException('Algorithm not supported');
        }
        return hash_hmac($methods[$method], $msg, $key, true);
    }

    /**
     * @param string $input JSON string
     *
     * @return object Object representation of JSON string
     */
    public static function jsonDecode($input)
    {
        $obj = json_decode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            JWT::handleJsonError($errno);
        }
        else if ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }
        return $obj;
    }

    /**
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            JWT::handleJsonError($errno);
        }
        else if ($json === 'null' && $input !== null) {
            throw new DomainException('Null result with non-null input');
        }
        return $json;
    }

    /**
     * @param string $input A base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * @param string $input Anything really
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError($errno)
    {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
        );
        throw new DomainException(isset($messages[$errno])
            ? $messages[$errno]
            : 'Unknown JSON error: ' . $errno
        );
    }
}
?>