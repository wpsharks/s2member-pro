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
        protected static $now; // Timestamp.
        protected static $recipients;
        protected static $subject;
        protected static $message;

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
            global $wpdb; // WP database class.

            $options = &$GLOBALS['WS_PLUGIN__']['s2member']['o'];

            if (!$options['pro_eot_reminder_email_enable']) {
                return; // Nothing to do here.
            }
            if (!isset($options['pro_eot_reminder_email_days'][0])) {
                return; // Nothing to do here.
            }
            self::$now        = time(); // Current UTC timestamp.
            self::$recipients = json_decode($options['pro_eot_reminder_email_recipients']);
            self::$subject    = json_decode($options['pro_eot_reminder_email_subject']);
            self::$message    = json_decode($options['pro_eot_reminder_email_message']);

            if (!is_object(self::$recipients) || !is_object(self::$subject) || !is_object(self::$message)) {
                return; // Not possible. Possible corruption in the DB.
            }
            $days        = preg_split('/[;,\s]+/', trim($options['pro_eot_reminder_email_days']), -1, PREG_SPLIT_NO_EMPTY);
            $scan_time   = apply_filters('ws_plugin__s2member_pro_reminders_scan_time', strtotime('-1 day', self::$now), get_defined_vars());
            $per_process = apply_filters('ws_plugin__s2member_pro_reminders_per_process', $vars['per_process'], get_defined_vars());

            $sql_already_scanned_recently = '
                SELECT DISTINCT `user_id` AS `ID` FROM `'.$wpdb->usermeta.'`
                    WHERE `meta_key` = \''.$wpdb->prefix.'s2member_last_reminder_scan\'
                        AND `meta_value` >= \''.esc_sql($scan_time).'\'
            ';
            $sql = '
                SELECT DISTINCT `user_id` AS `ID` FROM `'.$wpdb->usermeta.'`
                    WHERE (
                              (`meta_key` = \''.$wpdb->prefix.'s2member_subscr_gateway\' AND `meta_value` != \'\')
                              OR (`meta_key` = \''.$wpdb->prefix.'s2member_auto_eot_time\' AND `meta_value` != \'\')
                              OR (`meta_key` = \''.$wpdb->prefix.'s2member_last_auto_eot_time\' AND `meta_value` != \'\')
                          )
                          AND `user_id` NOT IN('.$sql_already_scanned_recently.')
                    LIMIT '.esc_sql($per_process).'
            ';
            if (!($user_ids = $wpdb->get_col($sql))) {
                return; // Nothing to do here.
            }
            $email_configs_were_on = // Was enabled already?
                c_ws_plugin__s2member_email_configs::email_config_status();
            c_ws_plugin__s2member_email_configs::email_config();

            foreach ($user_ids as $_user_id) {
                $_day = $_recipients = $_subject = $_message = null;

                if (!($_user = new WP_User($_user_id)) || !$_user->ID) {
                    continue; // Possible DB corruption.
                }
                update_user_option($_user->ID, 's2member_last_reminder_scan', self::$now);

                $_eot = c_ws_plugin__s2member_utils_users::get_user_eot($_user->ID);

                if (!$_eot || !$_eot['type'] || !$_eot['time'] || !$_eot['tense']) {
                    continue; // Nothing to do; i.e., no EOT or NPT time.
                } elseif ($_eot['type'] === 'next' && !$options['pro_eot_reminder_email_on_npt_also']) {
                    continue; // Nothing to do; i.e., not an EOT time and NPTs are off.
                } elseif (!($_day = self::calculate_day($_eot['time'])) && $_day !== '0') {
                    continue; // Unable to calculate day.
                } elseif (!in_array($_day, $days, true)) {
                    continue; // Nothing on this day.
                } elseif (!($_recipients = self::get_recipients_for_day($_day))) {
                    continue; // No recipients.
                } elseif (!($_subject = self::get_subject_for_day($_day))) {
                    continue; // No subject.
                } elseif (!($_message = self::get_message_for_day($_day))) {
                    continue; // No message.
                }
                foreach (array(
                    'subscr_gateway',
                    'subscr_id',
                    'subscr_cid',
                    'subscr_cid',
                ) as $_s2_meta_key) {
                    $_subject = str_ireplace('%%'.$_s2_meta_key.'%%', get_user_option('s2member_'.$_s2_meta_key, $_user->ID), $_subject);
                    $_message = str_ireplace('%%'.$_s2_meta_key.'%%', get_user_option('s2member_'.$_s2_meta_key, $_user->ID), $_message);
                } // unset($_s2_meta_key); // Housekeeping.

                if (!$_subject || !$_message) {
                    continue; // Empty as replacement(s).
                }
            } // unset($_user_id, $_user, $_eot, $_day, $_recipients, $_subject, $_message); // Housekeeping

            if (!$email_configs_were_on) {
                c_ws_plugin__s2member_email_configs::email_config_release();
            }
        }

        protected static function calculate_day($time)
        {
            // -1 = 1 day before.
            //  0 = the day of.
            //  1 = 1 day after.

            if (!($time = (int) $time)) {
                return ''; // Not possible.
                //
            } elseif ($time >= self::$now) {
                // Now, or in the future?
                $diff = $time - self::$now;
                $diff = floor($diff / DAY_IN_SECONDS);
                return (string) -max(0, $diff);
                //
            } else { // Past tense.
                $diff = self::$now - $time;
                $diff = floor($diff / DAY_IN_SECONDS);
                return (string) max(0, $diff);
            }
        }

        protected static function get_recipients_for_day($day)
        {
            $day = (string) $day; // Force string.

            if (!isset($day[0])) {
                return ''; // Day is empty.
            }
            if (!empty(self::$recipients->{$day}) && is_string(self::$recipients->{$day})) {
                return (string) self::$recipients->{$day};
            }
            return ''; // Nothing.
        }

        protected static function get_subject_for_day($day)
        {
            $day = (string) $day; // Force string.

            if (!isset($day[0])) {
                return ''; // Day is empty.
            }
            if (!empty(self::$subject->{$day}) && is_string(self::$subject->{$day})) {
                return (string) self::$subject->{$day};
            }
            return ''; // Nothing.
        }

        protected static function get_message_for_day($day)
        {
            $day = (string) $day; // Force string.

            if (!isset($day[0])) {
                return ''; // Day is empty.
            }
            if (!empty(self::$message->{$day}) && is_string(self::$message->{$day})) {
                return (string) self::$message->{$day};
            }
            return ''; // Nothing.
        }
    }
}
