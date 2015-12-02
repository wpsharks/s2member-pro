<?php
/**
 * Reminders.
 *
 * Copyright: © 2009-2011
 * {@link http://www.websharks-inc.com/ WebSharks, Inc.}
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
 *   See: {@link http://www.s2member.com/prices/}
 *
 * Unless you have our prior written consent, you must NOT directly or indirectly license,
 * sub-license, sell, resell, or provide for free; part (2) of the s2Member Pro Add-on;
 * or make an offer to do any of these things. All of these things are strictly
 * prohibited with part (2) of the s2Member Pro Add-on.
 *
 * Your purchase of s2Member Pro includes free lifetime upgrades via s2Member.com
 * (i.e., new features, bug fixes, updates, improvements); along with full access
 * to our video tutorial library: {@link http://www.s2member.com/videos/}
 *
 * @since 151202 Reminders.
 */
if (!defined('WPINC')) { // MUST have.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_reminders')) {
    /**
     * Reminders.
     *
     * @since 151202 Reminders.
     */
    class c_ws_plugin__s2member_pro_reminders
    {
        /**
         * Remind.
         *
         * @since 151202 Reminders.
         *
         * @attaches-to ``add_action('ws_plugin__s2member_after_auto_eot_system');``
         *
         * @param array $vars Expects an array of defined variables.
         */
        public static function remind($vars = array())
        {
            $options = &$GLOBALS['WS_PLUGIN__']['s2member']['o'];

            if (!$options['pro_eot_reminder_email_enable']) {
                return; // Nothing to do here.
            }
            if (!$options['pro_eot_reminder_email_days']) {
                return; // Nothing to do here.
            }
            // Will use `c_ws_plugin__s2member_utils_users::get_user_eot($user_id = 0, $check_gateway = TRUE, $favor = 'fixed')`
        }
    }
}
