<?php
// @codingStandardsIgnoreFile
/**
 * Human verification.
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
if (!class_exists('c_ws_plugin__s2member_pro_human_verification')) {
    /**
     * Provider-neutral human-verification policy and local checks.
     *
     * @package s2Member\Utilities
     * @since 261002.0750
     */
    class c_ws_plugin__s2member_pro_human_verification
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
            //261002.0750 Keep human verification scoped to anonymous account creation; modifications and Specific Post/Page checkout must remain unaffected.
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
            $signature = hash_hmac('sha256', 's2member-human-verification-form-age|'.$issued_at, wp_salt('nonce'));

            return $issued_at.'.'.$signature;
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
                return new WP_Error('s2member_human_verification_honeypot', __('Security verification failed. Please try again.', 's2member'));
            }
            $parts = explode('.', (string) $form_age_token, 2);
            if (count($parts) !== 2 || $parts[0] === '' || !ctype_digit($parts[0])) {
                return new WP_Error('s2member_human_verification_form_age', __('Security verification is invalid. Please reload the page and try again.', 's2member'));
            }
            $issued_at = (int) $parts[0];
            $expected  = hash_hmac('sha256', 's2member-human-verification-form-age|'.$issued_at, wp_salt('nonce'));
            if (!hash_equals($expected, $parts[1])) {
                return new WP_Error('s2member_human_verification_form_age', __('Security verification is invalid. Please reload the page and try again.', 's2member'));
            }
            $now = isset($now) ? (int) $now : time();
            if ($issued_at > $now) {
                return new WP_Error('s2member_human_verification_form_age', __('Security verification is invalid. Please reload the page and try again.', 's2member'));
            }
            //261002.0750 Enforce only a conservative one-second minimum; deliberately omit a maximum age so cached forms do not become false positives.
            if (($now - $issued_at) < 1) {
                return new WP_Error('s2member_human_verification_form_too_fast', __('The form was submitted too quickly. Please try again.', 's2member'));
            }
            return true;
        }
    }
}
