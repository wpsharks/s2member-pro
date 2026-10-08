<?php
// @codingStandardsIgnoreFile
/**
 * Cloudflare Turnstile anti-spam adapter.
 *
 * @package s2Member\Utilities
 * @since 261002.2358
 */
if (!defined('WPINC')) {
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_antispam_turnstile')) {
    /**
     * Cloudflare Turnstile anti-spam adapter.
     *
     * @package s2Member\Utilities
     * @since 261002.2358
     */
    class c_ws_plugin__s2member_pro_antispam_turnstile
    {
        /** @since 261002.2358 */
        public static function label()
        {
            return __('Cloudflare Turnstile', 's2member');
        }

        /** @since 261002.2358 */
        public static function kind()
        {
            return 'verification';
        }

        /** @since 261002.2358 */
        public static function available()
        {
            return function_exists('wp_remote_post');
        }

        /** @since 261002.2358 */
        public static function site_key()
        {
            return trim((string)@$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_site_key']);
        }

        /** @since 261002.2358 */
        public static function secret_key()
        {
            return trim((string)@$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_secret_key']);
        }

        /**
         * Fingerprints the current key pair so editing either key automatically invalidates an earlier successful configuration test.
         *
         * @since 261002.2358
         *
         * @return string Empty until both keys are present.
         */
        public static function configuration_fingerprint()
        {
            $site_key = self::site_key();
            $secret_key = self::secret_key();

            return ($site_key && $secret_key) ? hash('sha256', $site_key."\0".$secret_key) : '';
        }

        /** @since 261002.2358 */
        public static function configured()
        {
            $fingerprint = self::configuration_fingerprint();
            $tested = trim((string)@$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_tested_fingerprint']);

            return $fingerprint !== '' && $tested !== '' && hash_equals($fingerprint, $tested);
        }

        /**
         * Renders one Turnstile widget using a Pro-Form-scoped response field.
         *
         * @since 261002.2358
         *
         * @param string $surface    Form surface identifier.
         * @param string $input_name Root Pro-Form input name.
         * @param mixed  $captcha    Existing Pro-Form `captcha` attribute value; light/dark are appearance hints.
         *
         * @return string Widget HTML, or an empty string until configuration has been tested successfully.
         */
        public static function render($surface = '', $input_name = '', $captcha = '')
        {
            if (!self::available() || !self::configured() || !$input_name) {
                return '';
            }
            //261003.0229 Keep the script cacheable and let the existing `captcha` theme act only as an appearance hint for providers that support one.
            wp_enqueue_script('ws-plugin--s2member-pro-antispam-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true);
            $theme = in_array((string) $captcha, array('light', 'dark'), true) ? ' data-theme="'.esc_attr((string) $captcha).'"' : '';

            return '<div class="cf-turnstile s2member-pro-antispam-turnstile" data-sitekey="'.esc_attr(self::site_key()).'" data-action="s2member_pro_form"'.$theme.' data-response-field-name="'.esc_attr($input_name.'[antispam][turnstile]').'"></div>'."\n";
        }

        /**
         * Verifies a submitted Turnstile token with Cloudflare.
         *
         * @since 261002.2358
         *
         * @param string $surface    Form surface identifier.
         * @param array  $submission Normalized Pro-Form submission.
         *
         * @return true|\WP_Error True on success, otherwise a stable diagnostic error.
         */
        public static function verify($surface = '', $submission = array())
        {
            if (!self::configured()) {
                return new WP_Error('s2member_antispam_turnstile_configuration', __('Security verification is not configured correctly. Please contact the site owner.', 's2member'));
            }
            $antispam = !empty($submission['antispam']) && is_array($submission['antispam']) ? $submission['antispam'] : array();
            $token = isset($antispam['turnstile']) ? trim((string)$antispam['turnstile']) : '';
            if (!$token) {
                return new WP_Error('s2member_antispam_turnstile_missing', __('Security verification is required. Please try again.', 's2member'));
            }
            return self::verify_token($token);
        }

        /**
         * Tests the current key pair with a browser-generated token before the configuration can become active.
         *
         * @since 261002.2358
         *
         * @param string $token Browser-generated Turnstile token.
         *
         * @return true|\WP_Error True on success.
         */
        public static function test_configuration($token = '')
        {
            if (!self::site_key() || !self::secret_key()) {
                return new WP_Error('s2member_antispam_turnstile_configuration', __('Enter both the Turnstile site key and secret key first.', 's2member'));
            }
            //261003.0801 Cloudflare publishes dummy credentials for automated browser testing; refuse them on public hosts so a site owner cannot accidentally activate an always-pass test widget in production.
            if (self::uses_official_test_credential() && !self::official_test_credentials_allowed()) {
                return new WP_Error('s2member_antispam_turnstile_test_credentials', __('Cloudflare Turnstile test keys may only be used on localhost or another loopback development host.', 's2member'));
            }
            if (!(string)$token) {
                return new WP_Error('s2member_antispam_turnstile_test_token', __('Complete the Turnstile test before saving this configuration.', 's2member'));
            }
            return self::verify_token((string)$token);
        }

        /**
         * Reports configuration health without making a remote request.
         *
         * @since 261002.2358
         *
         * @return array Health status and label.
         */
        public static function health()
        {
            if (!self::available()) {
                return array('status' => 'unavailable', 'label' => __('Unavailable', 's2member'));
            }
            if (!self::site_key() || !self::secret_key()) {
                return array('status' => 'needs_configuration', 'label' => __('Needs configuration', 's2member'));
            }
            if (!self::configured()) {
                return array('status' => 'needs_test', 'label' => __('Test required', 's2member'));
            }
            return array('status' => 'healthy', 'label' => __('Healthy', 's2member'));
        }

        /**
         * Performs server-side Siteverify and validates the expected action and hostname.
         *
         * @since 261002.2358
         *
         * @param string $token Browser-generated Turnstile token.
         *
         * @return true|\WP_Error True on success.
         */
        protected static function verify_token($token)
        {
            $body = array('secret' => self::secret_key(), 'response' => (string)$token);
            if (defined('S2MEMBER_CURRENT_USER_IP') && S2MEMBER_CURRENT_USER_IP) {
                $body['remoteip'] = (string)S2MEMBER_CURRENT_USER_IP;
            }
            $url = apply_filters('ws_plugin__s2member_pro_antispam_turnstile_siteverify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
            $response = wp_remote_post($url, array('timeout' => 8, 'body' => $body));
            if (is_wp_error($response)) {
                return new WP_Error('s2member_antispam_turnstile_unavailable', __('Security verification is temporarily unavailable. Please try again.', 's2member'));
            }
            if ((int)wp_remote_retrieve_response_code($response) !== 200) {
                return new WP_Error('s2member_antispam_turnstile_unavailable', __('Security verification is temporarily unavailable. Please try again.', 's2member'));
            }
            $result = json_decode((string)wp_remote_retrieve_body($response), true);
            if (!is_array($result)) {
                return new WP_Error('s2member_antispam_turnstile_unavailable', __('Security verification is temporarily unavailable. Please try again.', 's2member'));
            }
            if (empty($result['success'])) {
                return new WP_Error('s2member_antispam_turnstile_failed', __('Security verification failed. Please try again.', 's2member'));
            }
            //261003.0801 Official Cloudflare dummy keys return fixed test metadata (`action=test`, `hostname=localhost`); accept that metadata only on loopback development hosts while keeping strict action/hostname checks for every real credential.
            $official_local_test = self::uses_official_test_credential() && self::official_test_credentials_allowed();
            if (!$official_local_test && (!isset($result['action']) || (string)$result['action'] !== 's2member_pro_form')) {
                return new WP_Error('s2member_antispam_turnstile_action', __('Security verification failed. Please try again.', 's2member'));
            }
            $expected_host = self::expected_hostname();
            if (!$official_local_test && $expected_host && !empty($result['hostname']) && strcasecmp($expected_host, (string)$result['hostname']) !== 0) {
                return new WP_Error('s2member_antispam_turnstile_hostname', __('Security verification failed. Please try again.', 's2member'));
            }
            return true;
        }

        /**
         * Returns whether either configured credential is one of Cloudflare's published Turnstile test keys.
         *
         * @since 261003.0801
         *
         * @return bool True when a published test site key or secret key is configured.
         */
        protected static function uses_official_test_credential()
        {
            $site_keys = array(
                '1x00000000000000000000AA',
                '2x00000000000000000000AB',
                '1x00000000000000000000BB',
                '2x00000000000000000000BB',
                '3x00000000000000000000FF',
            );
            $secret_keys = array(
                '1x0000000000000000000000000000000AA',
                '2x0000000000000000000000000000000AA',
                '3x0000000000000000000000000000000AA',
            );

            return in_array(self::site_key(), $site_keys, true) || in_array(self::secret_key(), $secret_keys, true);
        }

        /**
         * Allows Cloudflare test credentials only on loopback development hosts.
         *
         * @since 261003.0801
         *
         * @return bool True only for localhost/loopback hosts.
         */
        protected static function official_test_credentials_allowed()
        {
            return in_array(self::expected_hostname(), array('localhost', '127.0.0.1', '0.0.0.0', '::1'), true);
        }

        /**
         * Determines the hostname expected in a successful Siteverify response.
         *
         * @since 261002.2358
         *
         * @return string Hostname without a port.
         */
        protected static function expected_hostname()
        {
            //261008.0325 Use s2Member's compatibility URL parser on WordPress versions older than 4.4.
            $host = !empty($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : (string)c_ws_plugin__s2member_utils_urls::parse_url(home_url('/'), PHP_URL_HOST);
            if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
                $host = preg_replace('/:\\d+$/', '', $host);
            }
            return strtolower(trim($host, '[] '));
        }
    }
}
