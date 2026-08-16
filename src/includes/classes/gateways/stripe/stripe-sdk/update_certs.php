#!/usr/bin/env php
<?php
// @codingStandardsIgnoreFile
chdir(dirname(__FILE__));

set_time_limit(0); // unlimited max execution time

$fp = fopen(dirname(__FILE__) . '/data/ca-certificates.crt', 'w+');

$options = array(
  CURLOPT_FILE    => $fp,
  CURLOPT_TIMEOUT =>  3600,
  CURLOPT_URL     => 'https://curl.haxx.se/ca/cacert.pem',
);

$ch = curl_init();
curl_setopt_array($ch, $options);
curl_exec($ch);
//260816 curl_close() is a no-op on PHP 8+ and deprecated in PHP 8.5; PHP 5-7 still need it.
if (PHP_VERSION_ID < 80000) {
    curl_close($ch);
}
fclose($fp);
