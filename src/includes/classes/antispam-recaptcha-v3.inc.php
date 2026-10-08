<?php
// @codingStandardsIgnoreFile
/**
 * Google reCAPTCHA v3 anti-spam adapter.
 *
 * @package s2Member\Utilities
 * @since 261003.0156
 */
if (!defined('WPINC')) {
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_antispam_recaptcha_v3')) {
    /**
     * Google reCAPTCHA v3 anti-spam adapter.
     *
     * @package s2Member\Utilities
     * @since 261003.0156
     */
    class c_ws_plugin__s2member_pro_antispam_recaptcha_v3
    {
        /** @var int Render counter used only to keep multiple Pro-Forms distinct on one page. */
        protected static $render_index = 0;

        /** @since 261003.0156 */
        public static function label()
        {
            return __('Google reCAPTCHA v3', 's2member');
        }

        /** @since 261003.0156 */
        public static function kind()
        {
            return 'verification';
        }

        /** @since 261003.0156 */
        public static function available()
        {
            return function_exists('wp_remote_post');
        }

        /** @since 261003.0156 */
        public static function site_key()
        {
            return trim((string)@$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_site_key']);
        }

        /** @since 261003.0156 */
        public static function secret_key()
        {
            return trim((string)@$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_secret_key']);
        }

        /**
         * Returns the configured minimum v3 score, clamped to Google's 0.0-1.0 range.
         *
         * @since 261003.0156
         *
         * @return float Minimum accepted score.
         */
        public static function minimum_score()
        {
            $raw_score = isset($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_minimum_score']) ? $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_minimum_score'] : '0.5';
            $score = is_numeric($raw_score) ? (float)$raw_score : 0.5;
            if ($score < 0) {
                $score = 0.0;
            } elseif ($score > 1) {
                $score = 1.0;
            }
            return $score;
        }

        /**
         * Fingerprints the current key pair so editing either key invalidates an earlier successful configuration test.
         *
         * @since 261003.0156
         *
         * @return string Empty until both keys are present.
         */
        public static function configuration_fingerprint()
        {
            $site_key = self::site_key();
            $secret_key = self::secret_key();

            return ($site_key && $secret_key) ? hash('sha256', $site_key."\0".$secret_key) : '';
        }

        /** @since 261003.0156 */
        public static function configured()
        {
            $fingerprint = self::configuration_fingerprint();
            $tested = trim((string)@$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_tested_fingerprint']);

            return $fingerprint !== '' && $tested !== '' && hash_equals($fingerprint, $tested);
        }

        /**
         * Renders a namespaced v3 token field and submission-time token acquisition.
         *
         * @since 261003.0156
         *
         * @param string $surface    Form surface identifier.
         * @param string $input_name Root Pro-Form input name.
         *
         * @return string Client integration HTML, or an empty string until configuration has been tested successfully.
         */
        public static function render($surface = '', $input_name = '', $captcha = '')
        {
            if (!self::available() || !self::configured() || !$input_name) {
                return '';
            }
            $site_key = self::site_key();
            $script_url = apply_filters('ws_plugin__s2member_pro_antispam_recaptcha_v3_script_url', 'https://www.google.com/recaptcha/api.js?render='.rawurlencode($site_key), $site_key);
            wp_enqueue_script('ws-plugin--s2member-pro-antispam-recaptcha-v3', $script_url, array(), null, true);

            $field_id = 's2member-pro-antispam-recaptcha-v3-'.md5($input_name.'|'.(++self::$render_index));
            $field_name = $input_name.'[antispam][recaptcha_v3]';

            //261003.0156 Generate a fresh score token at submit time; v3 tokens expire after two minutes and are single-use, so page-load tokens are intentionally avoided.
            $script = '(function(){var f=document.getElementById('.wp_json_encode($field_id).');if(!f||!f.form){return;}var form=f.form,busy=false,bypass=false;form.addEventListener("submit",function(e){if(bypass){bypass=false;return;}e.preventDefault();e.stopImmediatePropagation();if(busy){return;}busy=true;f.value="";var submitter=e.submitter&&e.submitter.form===form?e.submitter:null;var resume=function(){bypass=true;busy=false;if(form.requestSubmit){if(submitter){form.requestSubmit(submitter);}else{form.requestSubmit();}}else if(submitter&&submitter.click){submitter.click();}else{var b=form.querySelector("input[type=submit],button[type=submit],button:not([type])");if(b&&b.click){b.click();}else{form.submit();}}};if(!window.grecaptcha||!grecaptcha.ready||!grecaptcha.execute){resume();return;}grecaptcha.ready(function(){grecaptcha.execute('.wp_json_encode($site_key).',{action:"s2member_pro_form"}).then(function(token){f.value=token||"";resume();},function(){resume();});});},true);})();';

            return '<input type="hidden" class="s2member-pro-antispam-recaptcha-v3" id="'.esc_attr($field_id).'" name="'.esc_attr($field_name).'" value="" />' . "\n" . '<script>'.$script.'</script>' . "\n";
        }

        /**
         * Verifies a submitted reCAPTCHA v3 token and its Pro-Form action/score.
         *
         * @since 261003.0156
         *
         * @param string $surface    Form surface identifier.
         * @param array  $submission Normalized Pro-Form submission.
         *
         * @return true|\WP_Error True on success, otherwise a stable diagnostic error.
         */
        public static function verify($surface = '', $submission = array())
        {
            if (!self::configured()) {
                return new WP_Error('s2member_antispam_recaptcha_v3_configuration', __('Security verification is not configured correctly. Please contact the site owner.', 's2member'));
            }
            $antispam = !empty($submission['antispam']) && is_array($submission['antispam']) ? $submission['antispam'] : array();
            $token = isset($antispam['recaptcha_v3']) ? trim((string)$antispam['recaptcha_v3']) : '';
            if (!$token) {
                return new WP_Error('s2member_antispam_recaptcha_v3_missing', __('Security verification is required. Please try again.', 's2member'));
            }
            return self::verify_token($token, 's2member_pro_form', true);
        }

        /**
         * Tests the current key pair with a browser-generated v3 token before configuration can become active.
         *
         * @since 261003.0156
         *
         * @param string $token Browser-generated reCAPTCHA v3 token using action `s2member_config_test`.
         *
         * @return true|\WP_Error True on success.
         */
        public static function test_configuration($token = '')
        {
            if (!self::site_key() || !self::secret_key()) {
                return new WP_Error('s2member_antispam_recaptcha_v3_configuration', __('Enter both the reCAPTCHA v3 site key and secret key first.', 's2member'));
            }
            if (!(string)$token) {
                return new WP_Error('s2member_antispam_recaptcha_v3_test_token', __('Complete the reCAPTCHA v3 test before saving this configuration.', 's2member'));
            }
            return self::verify_token((string)$token, 's2member_config_test', false);
        }

        /**
         * Reports configuration health without making a remote request.
         *
         * @since 261003.0156
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
         * Performs legacy SiteVerify for a third-party plugin integration and rejects Google's documented over-quota fail-open response.
         *
         * @since 261003.0156
         *
         * @param string $token          Browser-generated reCAPTCHA token.
         * @param string $expected_action Expected action name.
         * @param bool   $enforce_score  Whether to enforce the configured minimum score.
         *
         * @return true|\WP_Error True on success.
         */
        protected static function verify_token($token, $expected_action, $enforce_score)
        {
            $body = array('secret' => self::secret_key(), 'response' => (string)$token);
            if (defined('S2MEMBER_CURRENT_USER_IP') && S2MEMBER_CURRENT_USER_IP) {
                $body['remoteip'] = (string)S2MEMBER_CURRENT_USER_IP;
            }
            $url = apply_filters('ws_plugin__s2member_pro_antispam_recaptcha_v3_siteverify_url', 'https://www.google.com/recaptcha/api/siteverify');
            $response = wp_remote_post($url, array('timeout' => 8, 'body' => $body));
            if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 200) {
                return new WP_Error('s2member_antispam_recaptcha_v3_unavailable', __('Security verification is temporarily unavailable. Please try again.', 's2member'));
            }
            $result = json_decode((string)wp_remote_retrieve_body($response), true);
            if (!is_array($result)) {
                return new WP_Error('s2member_antispam_recaptcha_v3_unavailable', __('Security verification is temporarily unavailable. Please try again.', 's2member'));
            }
            //261003.0156 Google documents SiteVerify failing open above the free quota with success=true/score=0.9; never accept that synthetic score as a human-verification pass.
            if (stripos((string)wp_json_encode($result), 'over free quota') !== false) {
                return new WP_Error('s2member_antispam_recaptcha_v3_quota', __('Security verification is temporarily unavailable. Please try again.', 's2member'));
            }
            $error_codes = !empty($result['error-codes']) && is_array($result['error-codes']) ? $result['error-codes'] : array();
            if (in_array('invalid-input-secret', $error_codes, true) || in_array('missing-input-secret', $error_codes, true)) {
                return new WP_Error('s2member_antispam_recaptcha_v3_configuration', __('Security verification is not configured correctly. Please contact the site owner.', 's2member'));
            }
            if (empty($result['success'])) {
                return new WP_Error('s2member_antispam_recaptcha_v3_failed', __('Security verification failed. Please try again.', 's2member'));
            }
            if (!isset($result['action']) || (string)$result['action'] !== (string)$expected_action) {
                return new WP_Error('s2member_antispam_recaptcha_v3_action', __('Security verification failed. Please try again.', 's2member'));
            }
            $expected_host = self::expected_hostname();
            if ($expected_host && !empty($result['hostname']) && strcasecmp($expected_host, (string)$result['hostname']) !== 0) {
                return new WP_Error('s2member_antispam_recaptcha_v3_hostname', __('Security verification failed. Please try again.', 's2member'));
            }
            if ($enforce_score) {
                if (!isset($result['score']) || !is_numeric($result['score']) || (float)$result['score'] < self::minimum_score()) {
                    return new WP_Error('s2member_antispam_recaptcha_v3_score', __('Security verification failed. Please try again.', 's2member'));
                }
            }
            return true;
        }

        /**
         * Determines the hostname expected in a successful SiteVerify response.
         *
         * @since 261003.0156
         *
         * @return string Hostname without a port.
         */
        protected static function expected_hostname()
        {
            //261008.0325 Use s2Member's compatibility URL parser on WordPress versions older than 4.4.
            $host = !empty($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : (string)c_ws_plugin__s2member_utils_urls::parse_url(home_url('/'), PHP_URL_HOST);
            if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
                $host = preg_replace('/:\d+$/', '', $host);
            }
            return strtolower(trim($host, '[] '));
        }
    }
}
