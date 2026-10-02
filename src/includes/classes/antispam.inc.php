<?php
// @codingStandardsIgnoreFile
/**
 * Anti-spam.
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
 * @since 261002.0750
 */
if (!defined('WPINC')) { // MUST have WordPress.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_antispam')) {
    /**
     * Provider-neutral anti-spam policy and local checks.
     *
     * @package s2Member\Utilities
     * @since 261002.0750
     */
    class c_ws_plugin__s2member_pro_antispam
    {
        /**
         * Determines whether account-creation protection applies to a form surface.
         *
         * @since 261002.0750
         *
         * @param string $surface      Form surface identifier.
         * @param bool   $is_logged_in Whether the submitter is already logged in.
         *
         * @return bool True only for anonymous account-creation surfaces.
         */
        public static function protection_applies($surface = '', $is_logged_in = false)
        {
            //261002.0750 Keep anti-spam scoped to anonymous account creation; modifications and Specific Post/Page checkout must remain unaffected.
            if ($is_logged_in) {
                return false;
            }
            return in_array((string) $surface, array('registration', 'checkout'), true);
        }

        /**
         * Creates a signed form-age token without imposing a maximum age on cached forms.
         *
         * @since 261002.0750
         *
         * @param int|null $issued_at Unix timestamp; defaults to the current time.
         *
         * @return string Signed form-age token.
         */
        public static function form_age_token($issued_at = null)
        {
            $issued_at = isset($issued_at) ? (int) $issued_at : time();
            $signature = hash_hmac('sha256', 's2member-antispam-form-age|'.$issued_at, wp_salt('nonce'));

            return $issued_at.'.'.$signature;
        }

        /**
         * Returns whether the honeypot local check is enabled.
         *
         * @since 261002.1449
         *
         * @return bool True if the honeypot check is enabled.
         */
        public static function honeypot_enabled()
        {
            return !empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_honeypot']);
        }

        /**
         * Returns whether the signed minimum-form-age local check is enabled.
         *
         * @since 261002.1449
         *
         * @return bool True if the minimum-form-age check is enabled.
         */
        public static function form_age_enabled()
        {
            return !empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_form_age']);
        }

        /**
         * Returns implemented anti-spam adapters.
         *
         * @since 261002.1932
         *
         * @return string[] Adapter ID => adapter class.
         */
        public static function adapter_classes()
        {
            //261002.1932 Keep adapter discovery in one coordinator so CAPTCHA, proof-of-work, and reputation integrations can evolve without revisiting every protected Pro-Form surface.
            $adapters = apply_filters('ws_plugin__s2member_pro_antispam_adapter_classes', array(
                'altcha'           => 'c_ws_plugin__s2member_pro_antispam_altcha',
                'turnstile'        => 'c_ws_plugin__s2member_pro_antispam_turnstile',
                'recaptcha_v3'     => 'c_ws_plugin__s2member_pro_antispam_recaptcha_v3',
                'hcaptcha'         => 'c_ws_plugin__s2member_pro_antispam_hcaptcha',
                'friendly_captcha' => 'c_ws_plugin__s2member_pro_antispam_friendly_captcha',
                'recaptcha_v2'     => 'c_ws_plugin__s2member_pro_antispam_recaptcha_v2',
                'akismet'          => 'c_ws_plugin__s2member_pro_antispam_akismet',
                'cleantalk'        => 'c_ws_plugin__s2member_pro_antispam_cleantalk',
                'oopspam'          => 'c_ws_plugin__s2member_pro_antispam_oopspam',
                'proxycheck'       => 'c_ws_plugin__s2member_pro_antispam_proxycheck',
            ));
            $required_methods = array('label', 'kind', 'available', 'configured', 'render', 'verify', 'test_configuration', 'health');
            $valid            = array();

            foreach ((array) $adapters as $adapter => $class) {
                $adapter = strtolower(trim((string) $adapter));
                if (!$adapter || !is_string($class) || !$class || !class_exists($class)) {
                    continue;
                }
                foreach ($required_methods as $method) {
                    if (!is_callable(array($class, $method))) {
                        continue 2;
                    }
                }
                $valid[$adapter] = $class;
            }
            return $valid;
        }

        /**
         * Resolves one implemented anti-spam adapter.
         *
         * @since 261002.1932
         *
         * @param string $adapter Adapter identifier.
         *
         * @return string Adapter class, or an empty string when unavailable.
         */
        public static function adapter_class($adapter = '')
        {
            $adapters = self::adapter_classes();
            $adapter  = strtolower(trim((string) $adapter));

            return isset($adapters[$adapter]) ? $adapters[$adapter] : '';
        }

        /**
         * Renders enabled local anti-spam fields for a protected form surface.
         *
         * @since 261002.1449
         *
         * @param string    $surface      Form surface identifier.
         * @param string    $input_name   Root form input name, e.g. `s2member_pro_stripe_registration`.
         * @param bool|null $is_logged_in Whether the submitter is already logged in; defaults to WordPress state.
         *
         * @return string HTML fields, or an empty string when local checks do not apply.
         */
        public static function render_fields($surface = '', $input_name = '', $is_logged_in = null)
        {
            $is_logged_in = isset($is_logged_in) ? (bool) $is_logged_in : is_user_logged_in();
            if (!self::protection_applies($surface, $is_logged_in) || (!self::honeypot_enabled() && !self::form_age_enabled()) || !$input_name) {
                return '';
            }
            $html = '';
            if (self::honeypot_enabled()) {
                //261002.1449 Keep the decoy off-screen, out of tab order, and hidden from assistive technology while leaving a normal text input for unsophisticated bots to populate.
                $html .= '<div class="s2member-pro-antispam-honeypot" aria-hidden="true" style="position:absolute !important;left:-10000px !important;top:auto !important;width:1px !important;height:1px !important;overflow:hidden !important;">'."\n";
                $html .= '<input type="text" name="'.esc_attr($input_name.'[antispam][website]').'" value="" tabindex="-1" autocomplete="off" autocapitalize="none" spellcheck="false" />'."\n";
                $html .= '</div>'."\n";
            }
            if (self::form_age_enabled()) {
                $html .= '<input type="hidden" name="'.esc_attr($input_name.'[antispam][form_age]').'" value="'.esc_attr(self::form_age_token()).'" />'."\n";
            }
            return $html;
        }

        /**
         * Adds enabled anti-spam fields through the existing Pro-Form rendering hooks.
         *
         * @since 261002.1451
         *
         * @param array $vars Pro-Form rendering variables, including the referenced final form code.
         *
         * @return void
         */
        public static function render_form_hook($vars = array())
        {
            $hook = current_filter();
            if (!preg_match('/^ws_plugin__s2member_pro_during_sc_(stripe|paypal|authnet)(?:_(registration))?_form$/', $hook, $matches)) {
                return;
            }
            $surface = !empty($matches[2]) ? 'registration' : 'checkout';
            $fields  = self::render_fields($surface, 's2member_pro_'.$matches[1].'_'.$surface);
            if (!$fields || empty($vars['__refs']['code']) || !is_string($vars['__refs']['code'])) {
                return;
            }
            $closing_form_pos = strripos($vars['__refs']['code'], '</form>');
            if ($closing_form_pos === false) {
                return;
            }
            //261002.1451 Inject immediately before the form closes so standard and approved custom Pro-Form templates receive the same protection without gateway-specific markup edits.
            $vars['__refs']['code'] = substr_replace($vars['__refs']['code'], $fields, $closing_form_pos, 0);
        }

        /**
         * Applies enabled anti-spam checks after existing gateway field validation succeeds.
         *
         * @since 261002.1451
         *
         * @param null|array $response   Existing gateway validation response.
         * @param string     $form       Pro-Form surface identifier.
         * @param array      $submission Normalized Pro-Form submission.
         *
         * @return null|array Existing response, anti-spam error response, or null on success.
         */
        public static function validation_response($response = null, $form = '', $submission = array())
        {
            //261002.1451 Never replace an earlier validation error; local/provider checks belong at the end so single-use provider tokens are not consumed for unrelated input mistakes.
            if ($response !== null) {
                return $response;
            }
            $result = self::validate_submission($form, $submission);
            if (is_wp_error($result)) {
                return array('response' => $result->get_error_message(), 'error' => true);
            }
            return $response;
        }

        /**
         * Validates only the local checks enabled for a protected form submission.
         *
         * @since 261002.1449
         *
         * @param string    $surface      Form surface identifier.
         * @param array     $submission   Normalized Pro-Form submission array.
         * @param bool|null $is_logged_in Whether the submitter is already logged in; defaults to WordPress state.
         * @param int|null  $now          Unix timestamp used by deterministic tests; defaults to the current time.
         *
         * @return true|\WP_Error True on success/not-applicable, otherwise a stable diagnostic error.
         */
        public static function validate_submission($surface = '', $submission = array(), $is_logged_in = null, $now = null)
        {
            $is_logged_in = isset($is_logged_in) ? (bool) $is_logged_in : is_user_logged_in();
            if (!self::protection_applies($surface, $is_logged_in) || (!self::honeypot_enabled() && !self::form_age_enabled())) {
                return true;
            }
            $antispam = !empty($submission['antispam']) && is_array($submission['antispam']) ? $submission['antispam'] : array();
            $honeypot           = isset($antispam['website']) ? (string) $antispam['website'] : '';
            $form_age_token     = isset($antispam['form_age']) ? (string) $antispam['form_age'] : '';

            if (self::honeypot_enabled() && $honeypot !== '') {
                return new WP_Error('s2member_antispam_honeypot', __('Security verification failed. Please try again.', 's2member'));
            }
            if (self::form_age_enabled()) {
                //261002.1449 Reuse the signed-age verifier with an empty honeypot because honeypot enablement is independently configurable.
                return self::validate_local('', $form_age_token, $now);
            }
            return true;
        }

        /**
         * Validates the quiet local bot checks before any external provider is consulted.
         *
         * @since 261002.0750
         *
         * @param string   $honeypot       Populated only by bots; legitimate browsers leave this empty.
         * @param string   $form_age_token Signed token produced when the form was rendered.
         * @param int|null $now            Unix timestamp used for validation; defaults to the current time.
         *
         * @return true|\WP_Error True on success, otherwise a stable diagnostic error.
         */
        public static function validate_local($honeypot = '', $form_age_token = '', $now = null)
        {
            //261002.0750 Treat any honeypot content as automated input; the field will be hidden from legitimate users when rendering is added.
            if ((string) $honeypot !== '') {
                return new WP_Error('s2member_antispam_honeypot', __('Security verification failed. Please try again.', 's2member'));
            }
            $parts = explode('.', (string) $form_age_token, 2);
            if (count($parts) !== 2 || $parts[0] === '' || !ctype_digit($parts[0])) {
                return new WP_Error('s2member_antispam_form_age', __('Security verification is invalid. Please reload the page and try again.', 's2member'));
            }
            $issued_at = (int) $parts[0];
            $expected  = hash_hmac('sha256', 's2member-antispam-form-age|'.$issued_at, wp_salt('nonce'));
            if (!hash_equals($expected, $parts[1])) {
                return new WP_Error('s2member_antispam_form_age', __('Security verification is invalid. Please reload the page and try again.', 's2member'));
            }
            $now = isset($now) ? (int) $now : time();
            if ($issued_at > $now) {
                return new WP_Error('s2member_antispam_form_age', __('Security verification is invalid. Please reload the page and try again.', 's2member'));
            }
            //261002.0750 Enforce only a conservative one-second minimum; deliberately omit a maximum age so cached forms do not become false positives.
            if (($now - $issued_at) < 1) {
                return new WP_Error('s2member_antispam_form_too_fast', __('The form was submitted too quickly. Please try again.', 's2member'));
            }
            return true;
        }
    }
}
