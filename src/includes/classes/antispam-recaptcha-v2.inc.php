<?php
// @codingStandardsIgnoreFile
/**
 * Google reCAPTCHA v2 anti-spam adapter.
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
 * @since 261003.0340
 */
if (!defined('WPINC')) { // MUST have WordPress.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_antispam_recaptcha_v2')) {
    /**
     * Google reCAPTCHA v2 compatibility adapter.
     *
     * @package s2Member\Utilities
     * @since 261003.0340
     */
    class c_ws_plugin__s2member_pro_antispam_recaptcha_v2
    {
        /**
         * Returns the provider label.
         *
         * @since 261003.0340
         *
         * @return string Provider label.
         */
        public static function label()
        {
            return __('Google reCAPTCHA v2', 's2member');
        }

        /**
         * Returns the adapter kind.
         *
         * @since 261003.0340
         *
         * @return string Legacy verification adapter kind.
         */
        public static function kind()
        {
            //261003.0340 Keep v2 on the existing Pro-Form renderer/validator instead of routing it through the modern single-use-token adapter path.
            return 'legacy_verification';
        }

        /**
         * Returns whether the legacy v2 implementation is available.
         *
         * @since 261003.0340
         *
         * @return bool Always true while the existing CAPTCHA utilities are available.
         */
        public static function available()
        {
            return class_exists('c_ws_plugin__s2member_utils_captchas') && class_exists('c_ws_plugin__s2member_pro_utils_captchas');
        }

        /**
         * Returns whether both existing v2 keys are configured.
         *
         * @since 261003.0340
         *
         * @return bool True when the existing Pro reCAPTCHA v2 key pair is present.
         */
        public static function configured()
        {
            return self::site_key() !== '' && self::secret_key() !== '';
        }

        /**
         * Leaves rendering to the existing CAPTCHA implementation.
         *
         * @since 261003.0340
         *
         * @return string Always empty; existing gateway code renders v2.
         */
        public static function render($surface = '', $input_name = '', $captcha = '')
        {
            return '';
        }

        /**
         * Leaves submission verification to the existing CAPTCHA implementation.
         *
         * @since 261003.0340
         *
         * @return bool Always true here; existing gateway validation owns v2 verification.
         */
        public static function verify($surface = '', $submission = array())
        {
            return true;
        }

        /**
         * Tests whether the existing v2 configuration is usable by s2Member.
         *
         * @since 261003.0340
         *
         * @return true|\WP_Error True when keys are present and v2 is selected by the existing utility.
         */
        public static function test_configuration()
        {
            if (!self::available()) {
                return new WP_Error('s2member_antispam_recaptcha_v2_unavailable', __('Google reCAPTCHA v2 is unavailable on this site.', 's2member'));
            }
            if (!self::configured()) {
                return new WP_Error('s2member_antispam_recaptcha_v2_configuration', __('Enter both the reCAPTCHA v2 site key and secret key first.', 's2member'));
            }
            if (c_ws_plugin__s2member_utils_captchas::recaptcha_version() !== '2') {
                return new WP_Error('s2member_antispam_recaptcha_v2_configuration', __('Google reCAPTCHA v2 is not active with the configured keys.', 's2member'));
            }
            return true;
        }

        /**
         * Reports legacy v2 configuration health without changing existing behavior.
         *
         * @since 261003.0340
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
         * Returns the existing Pro reCAPTCHA v2 site key.
         *
         * @since 261003.0340
         *
         * @return string Site key.
         */
        protected static function site_key()
        {
            return trim((string) @$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_recaptcha2_public_key']);
        }

        /**
         * Returns the existing Pro reCAPTCHA v2 secret key.
         *
         * @since 261003.0340
         *
         * @return string Secret key.
         */
        protected static function secret_key()
        {
            return trim((string) @$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_recaptcha2_private_key']);
        }
    }
}
