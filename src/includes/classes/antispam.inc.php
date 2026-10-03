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
        /** @var array Request-local anti-spam pass record. */
        protected static $request_pass = array();
        /** @var bool Whether this request is resuming already-verified server-side checkout state. */
        protected static $request_trusted = false;
        /** @var string Trusted-state source for diagnostics and replacement-checkout handling. */
        protected static $request_trusted_source = '';

        /**
         * Determines whether the existing Pro-Form `captcha` attribute enables anti-spam for this form.
         *
         * @since 261003.0229
         *
         * @param mixed $captcha Existing Pro-Form `captcha` attribute value.
         *
         * @return bool True for the same truthy values historically used by the legacy CAPTCHA implementation.
         */
        public static function captcha_enabled($captcha = '')
        {
            //261003.0229 Preserve the established shortcode contract exactly: `captcha="0"`/empty disables protection; any existing truthy theme/value enables it.
            return (bool) $captcha;
        }

        /**
         * Determines whether the legacy Google reCAPTCHA renderer/validator should handle an enabled `captcha` attribute.
         *
         * @since 261003.0229
         *
         * @param mixed $captcha Existing Pro-Form `captcha` attribute value.
         *
         * @return bool True when legacy reCAPTCHA remains the selected verification implementation.
         */
        public static function legacy_captcha_enabled($captcha = '')
        {
            if (!self::captcha_enabled($captcha)) {
                return false;
            }
            $provider = self::verification_provider();
            if (!$provider || $provider === 'recaptcha_v2') {
                return true;
            }
            if ($provider === 'none') {
                return false;
            }
            //261003.0407 Keep the proven legacy CAPTCHA active until a selected modern provider has passed its configuration test; a typo or stale key must never silently lower protection.
            return !self::verification_adapter_class();
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
                //261003.0120 Keep Pro-Forms intentionally focused; the broader adapter catalog belongs to the separate forms add-on instead of turning this compatibility upgrade into a provider marketplace.
                'turnstile'    => 'c_ws_plugin__s2member_pro_antispam_turnstile',
                'recaptcha_v3' => 'c_ws_plugin__s2member_pro_antispam_recaptcha_v3',
                'recaptcha_v2' => 'c_ws_plugin__s2member_pro_antispam_recaptcha_v2',
                'akismet'      => 'c_ws_plugin__s2member_pro_antispam_akismet',
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
         * Returns the selected bot-verification adapter ID.
         *
         * @since 261003.0120
         *
         * @return string Adapter ID, or an empty string for no external verification.
         */
        public static function verification_provider()
        {
            return strtolower(trim((string) @$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_verification_provider']));
        }

        /**
         * Resolves the selected bot-verification adapter only when it is available and tested/configured.
         *
         * @since 261003.0120
         *
         * @return string Adapter class, or an empty string while inactive/unhealthy.
         */
        public static function verification_adapter_class()
        {
            $adapter = self::adapter_class(self::verification_provider());
            if (!$adapter || call_user_func(array($adapter, 'kind')) !== 'verification' || !call_user_func(array($adapter, 'available')) || !call_user_func(array($adapter, 'configured'))) {
                return '';
            }
            return $adapter;
        }

        /**
         * Returns the selected spam-intelligence adapter ID.
         *
         * @since 261003.0347
         *
         * @return string Adapter ID, or an empty string for no spam-intelligence service.
         */
        public static function spam_provider()
        {
            return strtolower(trim((string) @$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_spam_provider']));
        }

        /**
         * Resolves the selected spam-intelligence adapter only when it is available and configured.
         *
         * @since 261003.0347
         *
         * @return string Adapter class, or an empty string while inactive/unhealthy.
         */
        public static function spam_adapter_class()
        {
            $adapter = self::adapter_class(self::spam_provider());
            if (!$adapter || call_user_func(array($adapter, 'kind')) !== 'spam' || !call_user_func(array($adapter, 'available')) || !call_user_func(array($adapter, 'configured'))) {
                return '';
            }
            return $adapter;
        }

        /**
         * Tests one saved anti-spam adapter configuration from the General Options screen.
         *
         * @since 261003.0407
         *
         * @return void Terminates through wp_send_json_*().
         */
        public static function ajax_test_configuration()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have permission to test this configuration.', 's2member')), 403);
            }
            check_ajax_referer('ws-plugin--s2member-pro-antispam-test', 'nonce');

            $provider = !empty($_POST['provider']) ? sanitize_key((string) wp_unslash($_POST['provider'])) : '';
            $token = isset($_POST['token']) ? trim((string) wp_unslash($_POST['token'])) : '';
            $adapter = self::adapter_class($provider);
            if (!$adapter) {
                wp_send_json_error(array('message' => __('Select a configured anti-spam service first.', 's2member')), 400);
            }
            $result = call_user_func(array($adapter, 'test_configuration'), $token);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()), 400);
            }

            //261003.0407 Persist only derived test metadata here; user-entered keys continue through s2Member's normal Save All Changes path and existing multisite option handling.
            if ($provider === 'turnstile' && is_callable(array($adapter, 'configuration_fingerprint'))) {
                self::persist_tested_fingerprint('pro_antispam_turnstile_tested_fingerprint', call_user_func(array($adapter, 'configuration_fingerprint')));
            } elseif ($provider === 'recaptcha_v3' && is_callable(array($adapter, 'configuration_fingerprint'))) {
                self::persist_tested_fingerprint('pro_antispam_recaptcha_v3_tested_fingerprint', call_user_func(array($adapter, 'configuration_fingerprint')));
            }
            $health = call_user_func(array($adapter, 'health'));
            wp_send_json_success(array(
                'message' => __('Configuration test passed.', 's2member'),
                'status'  => !empty($health['status']) ? (string) $health['status'] : 'healthy',
                'label'   => !empty($health['label']) ? (string) $health['label'] : __('Healthy', 's2member'),
            ));
        }

        /**
         * Saves derived provider-test metadata without bypassing s2Member's normal storage format.
         *
         * @since 261003.0407
         *
         * @param string $option Option key inside the s2Member options array.
         * @param string $value  Tested key fingerprint.
         *
         * @return void
         */
        protected static function persist_tested_fingerprint($option, $value)
        {
            $GLOBALS['WS_PLUGIN__']['s2member']['o'][(string) $option] = (string) $value;
            update_option('ws_plugin__s2member_options', $GLOBALS['WS_PLUGIN__']['s2member']['o']);
            if (is_multisite() && is_main_site()) {
                update_site_option('ws_plugin__s2member_options', $GLOBALS['WS_PLUGIN__']['s2member']['o']);
            }
        }

        /**
         * Determines whether signup-oriented spam intelligence applies to this submission.
         *
         * @since 261003.0347
         *
         * @param string $surface    Pro-Form surface identifier.
         * @param array  $submission Normalized Pro-Form submission array.
         *
         * @return bool True only when this submission creates a new user account.
         */
        public static function spam_check_applies($surface = '', $submission = array())
        {
            if ((string) $surface === 'registration') {
                return true;
            }
            if ((string) $surface !== 'checkout' || is_user_logged_in()) {
                return false;
            }
            $attr = !empty($submission['attr']) && is_array($submission['attr']) ? $submission['attr'] : array();

            //261003.0347 Akismet's `signup` type is for account creation only; never send modification, member-only (`level="*"`), SP, update, or cancellation traffic as signups.
            return empty($attr['modify']) && (!isset($attr['level']) || (string) $attr['level'] !== '*');
        }

        /**
         * Returns the request-local anti-spam pass record, without any provider token.
         *
         * @since 261003.0120
         *
         * @return array Pass record, or an empty array when this request has not passed anti-spam.
         */
        public static function current_pass_record()
        {
            return self::$request_pass;
        }

        /**
         * Returns whether this request is using already-verified server-side checkout state.
         *
         * @since 261003.0120
         *
         * @return bool True when provider/local checks must not be consumed again.
         */
        public static function request_is_trusted()
        {
            return self::$request_trusted;
        }

        /**
         * Clears request-local verification state.
         *
         * @since 261003.0120
         *
         * @return void
         */
        public static function reset_request_state()
        {
            self::$request_pass = array();
            self::$request_trusted = false;
            self::$request_trusted_source = '';
        }

        /**
         * Trusts a server-side continuation that exists only because its original form submission already passed validation.
         *
         * @since 261003.0120
         *
         * @param string $source Short diagnostic source, e.g. `paypal_express`.
         *
         * @return bool Always true.
         */
        public static function trust_request($source = '')
        {
            self::$request_trusted = true;
            self::$request_trusted_source = (string) $source;
            return true;
        }

        /**
         * Trusts a delayed Stripe checkout only when its durable state is bound to a pending user and contains a prior anti-spam pass.
         *
         * @since 261003.0120
         *
         * @param array  $state   Gateway Checkout state.
         * @param string $surface Expected Pro-Form surface for the persisted pass.
         *
         * @return bool True only for a valid trusted continuation.
         */
        public static function trust_gateway_checkout_state($state = array(), $surface = 'checkout')
        {
            $record = !empty($state['context']['antispam_pass']) && is_array($state['context']['antispam_pass']) ? $state['context']['antispam_pass'] : array();
            if (!self::valid_pass_record($record, $surface)) {
                return false;
            }
            self::$request_pass = $record;
            self::$request_trusted = true;
            self::$request_trusted_source = 'gateway_checkout';
            return true;
        }

        /**
         * Renders enabled anti-spam fields for a protected form surface.
         *
         * @since 261002.1449
         *
         * @param string $surface    Pro-Form surface identifier.
         * @param string $input_name Root form input name, e.g. `s2member_pro_stripe_registration`.
         * @param mixed  $captcha    Existing Pro-Form `captcha` attribute value.
         *
         * @return string HTML fields, or an empty string when anti-spam is disabled/not configured.
         */
        public static function render_fields($surface = '', $input_name = '', $captcha = '')
        {
            $verification_adapter = self::verification_adapter_class();
            if (!self::captcha_enabled($captcha) || (!self::honeypot_enabled() && !self::form_age_enabled() && !$verification_adapter) || !$input_name) {
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
            if ($verification_adapter) {
                $html .= (string) call_user_func(array($verification_adapter, 'render'), $surface, $input_name, $captcha);
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
            if (!preg_match('/^ws_plugin__s2member_pro_during_sc_(stripe|paypal|authnet)(?:_(cancellation|registration|update|sp|modification))?_form$/', $hook, $matches)) {
                return;
            }
            $gateway = (string) $matches[1];
            $type    = !empty($matches[2]) ? (string) $matches[2] : '';
            $captcha = isset($vars['attr']['captcha']) ? $vars['attr']['captcha'] : '';
            $surface = $type === 'sp' ? 'sp-checkout' : ($type && $type !== 'modification' ? $type : 'checkout');
            $suffix  = $type === 'sp' ? 'sp_checkout' : ($type && $type !== 'modification' ? $type : 'checkout');
            $fields  = self::render_fields($surface, 's2member_pro_'.$gateway.'_'.$suffix, $captcha);
            if (!$fields || empty($vars['__refs']['code']) || !is_string($vars['__refs']['code'])) {
                return;
            }
            $closing_form_pos = strripos($vars['__refs']['code'], '</form>');
            if ($closing_form_pos === false) {
                return;
            }
            //261003.0229 Use the same existing Pro-Form render points and `captcha` switch for every legacy CAPTCHA-capable surface; only the anti-spam implementation changes.
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
            $captcha = isset($submission['attr']['captcha']) ? $submission['attr']['captcha'] : '';
            $result  = self::validate_submission($form, $submission, $captcha);
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
         * @param string   $surface    Pro-Form surface identifier.
         * @param array    $submission Normalized Pro-Form submission array.
         * @param mixed    $captcha    Existing Pro-Form `captcha` attribute value.
         * @param int|null $now        Unix timestamp used by deterministic tests; defaults to the current time.
         *
         * @return true|\WP_Error True on success/not-applicable, otherwise a stable diagnostic error.
         */
        public static function validate_submission($surface = '', $submission = array(), $captcha = '', $now = null)
        {
            if (!self::captcha_enabled($captcha)) {
                return true;
            }
            //261003.0120 Delayed gateway continuations reuse server-side trust; never submit an expired/single-use provider token a second time.
            if (self::$request_trusted) {
                return true;
            }
            self::$request_pass = array();
            $verification_provider = self::verification_provider();
            $verification_adapter = self::verification_adapter_class();
            $spam_provider = self::spam_provider();
            $spam_adapter = self::spam_check_applies($surface, $submission) ? self::spam_adapter_class() : '';
            if (!self::honeypot_enabled() && !self::form_age_enabled() && !$verification_adapter && !$spam_adapter) {
                return true;
            }
            $antispam = !empty($submission['antispam']) && is_array($submission['antispam']) ? $submission['antispam'] : array();
            $honeypot = isset($antispam['website']) ? (string) $antispam['website'] : '';
            $form_age_token = isset($antispam['form_age']) ? (string) $antispam['form_age'] : '';
            $checks = array();

            if (self::honeypot_enabled()) {
                if ($honeypot !== '') {
                    return new WP_Error('s2member_antispam_honeypot', __('Security verification failed. Please try again.', 's2member'));
                }
                $checks[] = 'honeypot';
            }
            if (self::form_age_enabled()) {
                //261002.1449 Reuse the signed-age verifier with an empty honeypot because honeypot enablement is independently configurable.
                $local_result = self::validate_local('', $form_age_token, $now);
                if (is_wp_error($local_result)) {
                    return $local_result;
                }
                $checks[] = 'form_age';
            }
            if ($verification_adapter) {
                $verification_result = call_user_func(array($verification_adapter, 'verify'), $surface, $submission);
                if (is_wp_error($verification_result)) {
                    return $verification_result;
                }
                $checks[] = $verification_provider;
            }
            if ($spam_adapter) {
                $spam_result = call_user_func(array($spam_adapter, 'verify'), $surface, $submission);
                if (is_wp_error($spam_result)) {
                    return $spam_result;
                }
                $checks[] = $spam_provider;
            }
            self::$request_pass = array(
                'version'               => 1,
                'surface'               => (string) $surface,
                'passed_at'             => time(),
                'verification_provider' => $verification_adapter ? (string) $verification_provider : '',
                'checks'                => $checks,
            );
            return true;
        }

        /**
         * Validates a persisted anti-spam pass record without relying on its age; checkout-state expiry remains the outer lifetime boundary.
         *
         * @since 261003.0120
         *
         * @param array  $record  Persisted anti-spam pass record.
         * @param string $surface Expected protected surface.
         *
         * @return bool True only for a structurally valid record.
         */
        protected static function valid_pass_record($record = array(), $surface = '')
        {
            return is_array($record)
                && isset($record['version'], $record['surface'], $record['passed_at'], $record['checks'])
                && (int) $record['version'] === 1
                && (string) $record['surface'] === (string) $surface
                && (int) $record['passed_at'] > 0
                && is_array($record['checks'])
                && !empty($record['checks']);
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
