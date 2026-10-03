<?php
// @codingStandardsIgnoreFile
/**
 * Akismet anti-spam adapter.
 *
 * Copyright: © 2009-2011
 * {@link http://websharks-inc.com/ WebSharks, Inc.}
 * (coded in the USA)
 *
 * This WordPress plugin (s2Member Pro) is comprised of two parts:
 *
 * o (1) Its PHP code is licensed under the GPL license, as is WordPress.
 *   You should have received a copy of the GNU General Public License,
 *   along with this software. In the main directory, see: /licensing/
 *   If not, see: {@link http://www.gnu.org/licenses/}.
 *
 * o (2) All other parts of (s2Member Pro); including, but not limited to:
 *   the CSS code, some JavaScript code, images, and design;
 *   are licensed according to the license purchased.
 *   See: {@link http://s2member.com/prices/}
 *
 * Unless you have our prior written consent, you must NOT directly or indirectly license,
 * sub-license, sell, resell, or provide for free; part (2) of the s2Member Pro Add-on;
 * or make an offer to do any of these things. All of these things are strictly
 * prohibited with part (2) of the s2Member Pro Add-on.
 *
 * Your purchase of s2Member Pro includes free lifetime upgrades via s2Member.com
 * (i.e., new features, bug fixes, updates, improvements); along with full access
 * to our video tutorial library: {@link http://s2member.com/videos/}
 *
 * @package s2Member\Utilities
 * @since 261003.0347
 */
if (!defined('WPINC')) { // MUST have WordPress.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_antispam_akismet')) {
    /**
     * Akismet signup-spam adapter.
     *
     * @package s2Member\Utilities
     * @since 261003.0347
     */
    class c_ws_plugin__s2member_pro_antispam_akismet
    {
        /** @since 261003.0347 */
        public static function label()
        {
            return __('Akismet', 's2member');
        }

        /** @since 261003.0347 */
        public static function kind()
        {
            return 'spam';
        }

        /** @since 261003.0347 */
        public static function available()
        {
            return function_exists('wp_remote_post');
        }

        /** @since 261003.0347 */
        public static function configured()
        {
            return self::api_key() !== '';
        }

        /**
         * Akismet does not render any visitor-facing field.
         *
         * @since 261003.0347
         *
         * @return string Always empty.
         */
        public static function render($surface = '', $input_name = '', $captcha = '')
        {
            return '';
        }

        /**
         * Checks a new-account submission with Akismet's documented `signup` content type.
         *
         * @since 261003.0347
         *
         * @param string $surface    Pro-Form surface identifier.
         * @param array  $submission Normalized Pro-Form submission.
         *
         * @return true|\WP_Error True for ham/provider outage, otherwise a spam error.
         */
        public static function verify($surface = '', $submission = array())
        {
            if (!self::configured()) {
                return true;
            }
            $response = self::request('comment-check', self::signup_data($submission));
            if (is_wp_error($response)) {
                //261003.0347 Akismet is supplemental spam intelligence, so a provider/network outage must not lock legitimate registrations; surface diagnostics through a hook instead.
                do_action('ws_plugin__s2member_pro_antispam_akismet_unavailable', $response, $surface, $submission);
                return true;
            }
            if (trim(strtolower((string) $response)) === 'true') {
                return new WP_Error('s2member_antispam_akismet_spam', __('Registration could not be completed. Please try again or contact the site owner.', 's2member'));
            }
            return true;
        }

        /**
         * Verifies the currently resolved Akismet API key.
         *
         * @since 261003.0347
         *
         * @return true|\WP_Error True for a valid key.
         */
        public static function test_configuration()
        {
            if (!self::api_key()) {
                return new WP_Error('s2member_antispam_akismet_configuration', __('Enter an Akismet API key or configure the Akismet plugin first.', 's2member'));
            }
            $response = self::request('verify-key', array('blog' => home_url('/')));
            if (is_wp_error($response)) {
                return $response;
            }
            if (trim(strtolower((string) $response)) !== 'valid') {
                return new WP_Error('s2member_antispam_akismet_configuration', __('The Akismet API key could not be verified.', 's2member'));
            }
            return true;
        }

        /**
         * Reports configuration health without making a remote request.
         *
         * @since 261003.0347
         *
         * @return array Health status and label.
         */
        public static function health()
        {
            if (!self::available()) {
                return array('status' => 'unavailable', 'label' => __('Unavailable', 's2member'));
            }
            if (!self::configured()) {
                return array('status' => 'needs_configuration', 'label' => __('Needs configuration', 's2member'));
            }
            return array('status' => 'healthy', 'label' => __('Healthy', 's2member'));
        }

        /**
         * Resolves an explicit s2Member key first, then reuses the site's existing Akismet key when available.
         *
         * @since 261003.0347
         *
         * @return string API key.
         */
        public static function api_key()
        {
            $key = trim((string) @$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_akismet_api_key']);
            if ($key) {
                return $key;
            }
            //261003.0347 Reuse Akismet's established WordPress key when possible so site owners do not have to configure the same service twice.
            if (class_exists('Akismet') && is_callable(array('Akismet', 'get_api_key'))) {
                $key = trim((string) Akismet::get_api_key());
            } elseif (function_exists('get_option')) {
                $key = trim((string) get_option('wordpress_api_key'));
            }
            return $key;
        }

        /**
         * Builds the deliberately limited registration data sent to Akismet.
         *
         * @since 261003.0347
         *
         * @param array $submission Normalized Pro-Form submission.
         *
         * @return array Akismet request data without passwords, payment data, or custom fields.
         */
        protected static function signup_data($submission = array())
        {
            $first_name = isset($submission['first_name']) ? trim((string) $submission['first_name']) : '';
            $last_name = isset($submission['last_name']) ? trim((string) $submission['last_name']) : '';
            $username = isset($submission['username']) ? trim((string) $submission['username']) : '';
            $author = trim($first_name.' '.$last_name);
            if (!$author) {
                $author = $username;
            }
            return array(
                'blog'                 => home_url('/'),
                'user_ip'              => c_ws_plugin__s2member_utils_ip::current(),
                'user_agent'           => isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '',
                'referrer'             => isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '',
                'permalink'            => home_url('/'),
                'comment_type'         => 'signup',
                'comment_author'       => $author,
                'comment_author_email' => isset($submission['email']) ? trim((string) $submission['email']) : '',
                'blog_lang'            => str_replace('_', ',', strtolower((string) get_locale())),
                'blog_charset'         => get_option('blog_charset'),
            );
        }

        /**
         * Calls one Akismet REST endpoint.
         *
         * @since 261003.0347
         *
         * @param string $method Endpoint method.
         * @param array  $data   Request data excluding the API key.
         *
         * @return string|\WP_Error Response body or an availability/configuration error.
         */
        protected static function request($method, $data = array())
        {
            $key = self::api_key();
            if (!$key) {
                return new WP_Error('s2member_antispam_akismet_configuration', __('Akismet is not configured.', 's2member'));
            }
            $data['api_key'] = $key;
            $url = apply_filters('ws_plugin__s2member_pro_antispam_akismet_url', 'https://rest.akismet.com/1.1/'.rawurlencode((string) $method), $method);
            $response = wp_remote_post($url, array(
                'timeout' => 8,
                'headers' => array('User-Agent' => 'WordPress/'.get_bloginfo('version').' | s2Member Pro'),
                'body'    => $data,
            ));
            if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
                return new WP_Error('s2member_antispam_akismet_unavailable', __('Spam detection is temporarily unavailable.', 's2member'));
            }
            return trim((string) wp_remote_retrieve_body($response));
        }
    }
}
