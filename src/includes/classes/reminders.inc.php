<?php
// @codingStandardsIgnoreFile
/**
 * Reminders.
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
 * @since 151202 Reminders.
 * @since 260914.0409 Lightweight gate for reminder hooks; heavy processing lives in `reminders-in.inc.php`.
 */
if (!defined('WPINC')) { // MUST have.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_reminders')) {
    /**
     * Reminders.
     *
     * @since 151202 Reminders.
     * @since 260914.0409 Lightweight gate for reminder hooks.
     */
    class c_ws_plugin__s2member_pro_reminders
    {
        /**
         * Ensures that fixed-EOT reminders have their recurring schedule when applicable.
         *
         * @since 260914.0409 Lightweight reminder gate.
         *
         * @param array|null $vars             Optional `ws_plugin__s2member_after_update_all_options` hook context.
         * @param bool       $cleanup_disabled Internal. Allow a disabled reminder cron callback to remove stale scheduled work.
         *
         * @return bool Reminder schedule result.
         */
        public static function ensure_fixed_eot_reminder_schedule($vars = null, $cleanup_disabled = false)
        {
            $after_options_update = is_array($vars) && array_key_exists('updated_all_options', $vars);

            //260914.0409 Keep the normal disabled-request path in this small gate so every request does not load the full reminder worker/health/mail implementation.
            if ($after_options_update && empty($vars['updated_all_options'])) {
                return true;
            }
            $options = $after_options_update && isset($vars['options']) && is_array($vars['options']) ? $vars['options'] : $GLOBALS['WS_PLUGIN__']['s2member']['o'];
            if (empty($options['pro_eot_reminder_email_enable']) && !$after_options_update && !$cleanup_disabled) {
                return true;
            }
            return c_ws_plugin__s2member_pro_reminders_in::ensure_fixed_eot_reminder_schedule($vars, $cleanup_disabled);
        }

        /**
         * Returns reminder health information for diagnostics/admin UI.
         *
         * @since 260914.0409 Lightweight reminder gate.
         *
         * @param bool $force_refresh Force a fresh schedule/window check.
         *
         * @return array Reminder health information.
         */
        public static function fixed_eot_reminder_health($force_refresh = false)
        {
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_reminder_health($force_refresh);
        }

        /**
         * Handles dismissal of the reminder admin notice.
         *
         * @since 260914.0409 Lightweight reminder gate.
         */
        public static function fixed_eot_reminder_admin_notice_dismiss()
        {
            //260914.0409 `admin_init` runs on every admin request; avoid loading reminder internals unless this request is actually dismissing the notice.
            if (empty($_GET['s2member-dismiss-eot-reminder-notice'])) {
                return;
            }
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_reminder_admin_notice_dismiss();
        }

        /**
         * Formats the representative unresolved reminder recipient.
         *
         * @since 260914.0409 Lightweight reminder gate.
         *
         * @param array $health Reminder health snapshot.
         *
         * @return string Safe HTML.
         */
        public static function fixed_eot_reminder_failure_recipient_html($health)
        {
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_reminder_failure_recipient_html($health);
        }

        /**
         * Displays the reminder admin notice when applicable.
         *
         * @since 260914.0409 Lightweight reminder gate.
         */
        public static function fixed_eot_reminder_admin_notice()
        {
            //260914.0409 `admin_notices` runs broadly; disabled reminders cannot have an active reminder warning and do not need the heavy inner class.
            if (empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_enable'])) {
                return;
            }
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_reminder_admin_notice();
        }

        /**
         * Runs a fixed-EOT reminder pass.
         *
         * @since 260914.0409 Lightweight reminder gate.
         *
         * @param bool $is_continuation Internal one-off continuation of a runtime-limited pass.
         */
        public static function fixed_eot_remind($is_continuation = false)
        {
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_remind($is_continuation);
        }

        /**
         * Runs a one-off continuation for a runtime-limited fixed-EOT reminder pass.
         *
         * @since 260914.0409 Lightweight reminder gate.
         */
        public static function fixed_eot_remind_continuation()
        {
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_remind_continuation();
        }

        /**
         * Runs stored-EOT reminders before an explicitly requested external Auto-EOT pass.
         *
         * @since 260914.0409 Lightweight reminder gate.
         */
        public static function fixed_eot_remind_via_cron()
        {
            //260914.0409 The Framework hook fires on every request; reject ordinary requests here before autoloading reminder internals.
            if (empty($_GET['s2member_auto_eot_system_via_cron'])) {
                return;
            }
            return c_ws_plugin__s2member_pro_reminders_in::fixed_eot_remind_via_cron();
        }

        /**
         * Handles the optional legacy Next Payment Time reminder path.
         *
         * @since 260914.0409 Lightweight reminder gate.
         *
         * @param array $vars Defined variables from the Auto-EOT pass.
         */
        public static function remind($vars = array())
        {
            $options = &$GLOBALS['WS_PLUGIN__']['s2member']['o'];

            //260914.0409 Keep the disabled/NPT-disabled legacy path in the small gate; only actual NPT reminder work needs the full reminder implementation.
            if (empty($options['pro_eot_reminder_email_enable']) || empty($options['pro_eot_reminder_email_on_npt_also'])) {
                return;
            }
            return c_ws_plugin__s2member_pro_reminders_in::remind($vars);
        }
    }
}
