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
        protected static $now; // `time()`
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
            if (!$GLOBALS['WS_PLUGIN__']['s2member']['o']['reg_email_from_name']
                || !$GLOBALS['WS_PLUGIN__']['s2member']['o']['reg_email_from_email']) {
                return; // Not possible. Email configuration is incomplete.
            }
            $days                 = preg_split('/[;,\s]+/', trim($options['pro_eot_reminder_email_days']), -1, PREG_SPLIT_NO_EMPTY);
            $scan_time            = apply_filters('ws_plugin__s2member_pro_eot_reminders_scan_time', strtotime('-1 day', self::$now), get_defined_vars());
            $per_process          = apply_filters('ws_plugin__s2member_pro_eot_reminders_per_process', $vars['per_process'], get_defined_vars());
            $message_bytes_in_log = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_message_bytes_in_log', 100);

            $mail_from = '"'.str_replace('"', "'", $GLOBALS['WS_PLUGIN__']['s2member']['o']['reg_email_from_name']).'"'.
                               ' <'.$GLOBALS['WS_PLUGIN__']['s2member']['o']['reg_email_from_email'].'>';

            $user_ids_to_exclude = '
                SELECT DISTINCT `user_id` AS `ID` FROM `'.$wpdb->usermeta.'`
                    WHERE
                        (`meta_key` = \''.$wpdb->prefix.'s2member_last_reminder_scan\' AND `meta_value` >= \''.esc_sql($scan_time).'\')
                        OR (`meta_key` = \''.$wpdb->prefix.'s2member_reminders_enable\' AND `meta_value` = \'0\')
            ';
            $additional_user_ids_to_exclude = apply_filters('ws_plugin__s2member_pro_eot_reminders_exclude_user_ids', array(), get_defined_vars());

            $sql = '
                SELECT DISTINCT `user_id` AS `ID` FROM `'.$wpdb->usermeta.'`
                    WHERE `user_id` NOT IN('.$user_ids_to_exclude.')

                        '.($additional_user_ids_to_exclude // See filter above.
                            ? 'AND `user_id` NOT IN(\''.implode("','", $additional_user_ids_to_exclude).'\')'
                            : '').'
                        AND (
                              (`meta_key` = \''.$wpdb->prefix.'s2member_subscr_gateway\' AND `meta_value` != \'\')
                              OR (`meta_key` = \''.$wpdb->prefix.'s2member_auto_eot_time\' AND `meta_value` != \'\')
                              OR (`meta_key` = \''.$wpdb->prefix.'s2member_last_auto_eot_time\' AND `meta_value` != \'\')
                            )
                    LIMIT '.esc_sql($per_process).'
            ';
            if (!($user_ids = $wpdb->get_col($sql))) {
                return; // Nothing to do here.
            }
            $email_configs_were_on = // Was enabled already?
                c_ws_plugin__s2member_email_configs::email_config_status();
            c_ws_plugin__s2member_email_configs::email_config();

            foreach ($user_ids as $_user_id) {
                $_eot = $_day = $_recipients = $_subject = $_message = null;

                if (!($_user = new WP_User($_user_id)) || !$_user->ID) {
                    continue; // Possible DB corruption.
                }
                update_user_option($_user->ID, 's2member_last_reminder_scan', self::$now);

                $_eot = c_ws_plugin__s2member_utils_users::get_user_eot($_user->ID);

                if (!$_eot || !$_eot['type'] || !$_eot['time'] || !$_eot['tense']) {
                    continue; // Nothing to do; i.e., no EOT or NPT time.
                } elseif ($_eot['type'] === 'next' // Disabled by default!
                        && !$options['pro_eot_reminder_email_on_npt_also']) {
                    continue; // Nothing to do; i.e., not an EOT time and no NPTs.
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
                } //
                self::fill_replacement_codes($_user, $_eot, $_recipients, $_subject, $_message);

                $_mail_from  = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_from', $mail_from, get_defined_vars());
                $_recipients = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_recipients', $_recipients, get_defined_vars());
                $_subject    = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_subject', $_subject, get_defined_vars());
                $_message    = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_message', $_message, get_defined_vars());

                if (!$_recipients || !$_subject || !$_message || !$_mail_from) {
                    continue; // Final validation must not fail.
                }
                foreach (c_ws_plugin__s2member_utils_strings::parse_emails($_recipients) as $_recipient) {
                    wp_mail($_recipient, $_subject, $_message, // `text/plain` emails.
                        'From: '.$_mail_from."\r\n".'Content-Type: text/plain; charset=utf-8');

                    $_log_entry = array(
                        'eot'        => $_eot,
                        'eot_rfc822' => date(DATE_RFC822, $_eot['time']),
                        'day'        => $_day, // Reminder day.
                        'now'        => self::$now,

                        'user_id'         => $_user->ID,
                        'user_login'      => $_user->user_login,
                        'user_email'      => $_user->user_email,
                        'user_first_name' => $_user->first_name,
                        'user_last_name'  => $_user->last_name,

                        'mail_from' => $_mail_from,
                        'recipient' => $_recipient,
                        'subject'   => $_subject,
                    );
                    if (strlen($_message) > $message_bytes_in_log) {
                        $_log_entry['message_clip'] = substr($_message, 0, $message_bytes_in_log).'...';
                    } else {
                        $_log_entry['message'] = $_message; // Full message.
                    }
                    c_ws_plugin__s2member_utils_logs::log_entry('eot-reminders', $_log_entry);
                }
            }
            unset($_user_id, $_user, $_eot, $_day, $_mail_from, $_recipients, $_recipient, $_subject, $_message, $_log_entry);

            if (!$email_configs_were_on) {
                c_ws_plugin__s2member_email_configs::email_config_release();
            }
        }

        protected static function fill_replacement_codes($user, $eot, &$recipients, &$subject, &$message)
        {
            $ipn_signup_vars = // If available, these take precedence.
                (array) c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars($user->ID);

            foreach (array(
                'payer_email',
                'first_name',
                'last_name',

                'subscr_id',
                'subscr_cid',
                'subscr_baid',
                'subscr_gateway',

                'currency',
                'currency_symbol',

                'initial',
                'initial_term',

                'regular',
                'regular_term',

                'recurring',

                'item_name',
                'item_number',
            ) as $_key) {
                if (isset($ipn_signup_vars[$_key])) {
                    $_value     = (string) $ipn_signup_vars[$_key];
                    $recipients = str_ireplace('%%'.$_key.'%%', $_value, $recipients);
                    $subject    = str_ireplace('%%'.$_key.'%%', $_value, $subject);
                    $message    = str_ireplace('%%'.$_key.'%%', $_value, $message);
                }
            }
            unset($_key, $_value); // Housekeeping.

            if (!empty($ipn_signup_vars['initial_term'])) {
                $initial_cycle = c_ws_plugin__s2member_utils_time::period_term($ipn_signup_vars['initial_term']);
                $recipients    = str_ireplace('%%initial_cycle%%', $initial_cycle, $recipients);
                $subject       = str_ireplace('%%initial_cycle%%', $initial_cycle, $subject);
                $message       = str_ireplace('%%initial_cycle%%', $initial_cycle, $message);
            }
            if (!empty($ipn_signup_vars['regular_term'])) {
                if (!empty($ipn_signup_vars['recurring'])) {
                    $regular_cycle           = c_ws_plugin__s2member_utils_time::period_term($ipn_signup_vars['regular_term'], true);
                    $recurring_regular_cycle = $ipn_signup_vars['recurring'].' / '.c_ws_plugin__s2member_utils_time::period_term($ipn_signup_vars['regular_term'], true);
                } else {
                    $regular_cycle           = c_ws_plugin__s2member_utils_time::period_term($ipn_signup_vars['regular_term'], false);
                    $recurring_regular_cycle = __('0 / non-recurring', 's2member-front', 's2member');
                }
                $recipients = str_ireplace('%%regular_cycle%%', $regular_cycle, $recipients);
                $subject    = str_ireplace('%%regular_cycle%%', $regular_cycle, $subject);
                $message    = str_ireplace('%%regular_cycle%%', $regular_cycle, $message);

                $recipients = str_ireplace('%%recurring/regular_cycle%%', $recurring_regular_cycle, $recipients);
                $subject    = str_ireplace('%%recurring/regular_cycle%%', $recurring_regular_cycle, $subject);
                $message    = str_ireplace('%%recurring/regular_cycle%%', $recurring_regular_cycle, $message);
            }
            if (isset($ipn_signup_vars['first_name'], $ipn_signup_vars['last_name'])) {
                $full_name  = trim($ipn_signup_vars['first_name'].' '.$ipn_signup_vars['last_name']);
                $recipients = str_ireplace('%%full_name%%', $full_name, $recipients);
                $subject    = str_ireplace('%%full_name%%', $full_name, $subject);
                $message    = str_ireplace('%%full_name%%', $full_name, $message);
            }
            foreach (array( // In case IPN Signup Vars are unavailable.
                'subscr_id', // e.g., imported/migrated by site owner.
                'subscr_cid',
                'subscr_baid',
                'subscr_gateway',
            ) as $_key) {
                $_value     = (string) get_user_option('s2member_'.$_key, $user->ID);
                $recipients = str_ireplace('%%'.$_key.'%%', $_value, $recipients);
                $subject    = str_ireplace('%%'.$_key.'%%', $_value, $subject);
                $message    = str_ireplace('%%'.$_key.'%%', $_value, $message);
            }
            unset($_key, $_value); // Housekeeping.

            foreach (array( // WP account properties.
                'ID',
                'first_name',
                'last_name',
                'user_email',
                'user_login',
            ) as $_property) {
                $_property_value             = (string) $user->{$_property};
                $_lc_property_wo_user_prefix = preg_replace('/^user_/i', '', strtolower($_property));
                $recipients                  = str_ireplace('%%user_'.$_lc_property_wo_user_prefix.'%%', $_property_value, $recipients);
                $subject                     = str_ireplace('%%user_'.$_lc_property_wo_user_prefix.'%%', $_property_value, $subject);
                $message                     = str_ireplace('%%user_'.$_lc_property_wo_user_prefix.'%%', $_property_value, $message);
            }
            unset($_property, $_property_value, $_lc_property_wo_user_prefix); // Housekeeping.

            $first_name = $user->first_name; // If not yet filled above.
            $recipients = str_ireplace('%%first_name%%', $first_name, $recipients);
            $subject    = str_ireplace('%%first_name%%', $first_name, $subject);
            $message    = str_ireplace('%%first_name%%', $first_name, $message);

            $last_name  = $user->last_name; // If not yet filled above.
            $recipients = str_ireplace('%%last_name%%', $last_name, $recipients);
            $subject    = str_ireplace('%%last_name%%', $last_name, $subject);
            $message    = str_ireplace('%%last_name%%', $last_name, $message);

            $full_name  = trim($first_name.' '.$last_name); // Same here.
            $recipients = str_ireplace('%%full_name%%', $full_name, $recipients);
            $subject    = str_ireplace('%%full_name%%', $full_name, $subject);
            $message    = str_ireplace('%%full_name%%', $full_name, $message);

            $user_full_name = trim($user->first_name.' '.$user->last_name);
            $recipients     = str_ireplace('%%user_full_name%%', $user_full_name, $recipients);
            $subject        = str_ireplace('%%user_full_name%%', $user_full_name, $subject);
            $message        = str_ireplace('%%user_full_name%%', $user_full_name, $message);

            $user_ip    = get_user_option('s2member_registration_ip', $user->ID);
            $recipients = str_ireplace('%%user_ip%%', $user_ip, $recipients);
            $subject    = str_ireplace('%%user_ip%%', $user_ip, $subject);
            $message    = str_ireplace('%%user_ip%%', $user_ip, $message);

            $user_role  = c_ws_plugin__s2member_user_access::user_access_role($user);
            $recipients = str_ireplace('%%user_role%%', $user_role, $recipients);
            $subject    = str_ireplace('%%user_role%%', $user_role, $subject);
            $message    = str_ireplace('%%user_role%%', $user_role, $message);

            $user_level = c_ws_plugin__s2member_user_access::user_access_level($user);
            $recipients = str_ireplace('%%user_level%%', $user_level, $recipients);
            $subject    = str_ireplace('%%user_level%%', $user_level, $subject);
            $message    = str_ireplace('%%user_level%%', $user_level, $message);

            $user_level_label = c_ws_plugin__s2member_user_access::user_access_label($user);
            $recipients       = str_ireplace('%%user_level_label%%', $user_level_label, $recipients);
            $subject          = str_ireplace('%%user_level_label%%', $user_level_label, $subject);
            $message          = str_ireplace('%%user_level_label%%', $user_level_label, $message);

            $user_ccaps = implode(',', c_ws_plugin__s2member_user_access::user_access_ccaps($user));
            $recipients = str_ireplace('%%user_ccaps%%', $user_ccaps, $recipients);
            $subject    = str_ireplace('%%user_ccaps%%', $user_ccaps, $subject);
            $message    = str_ireplace('%%user_ccaps%%', $user_ccaps, $message);

            if (is_array($fields = get_user_option('s2member_custom_fields', $user->ID))) {
                foreach ($fields as $_key => $_value) {
                    $_serialized_value = maybe_serialize($_value);
                    $recipients        = str_ireplace('%%'.$_key.'%%', $_serialized_value, $recipients);
                    $subject           = str_ireplace('%%'.$_key.'%%', $_serialized_value, $subject);
                    $message           = str_ireplace('%%'.$_key.'%%', $_serialized_value, $message);
                }
                unset($_key, $_value, $_serialized_value); // Housekeeping.
            }
            foreach (preg_split('/\|/', get_user_option('s2member_custom', $user->ID)) as $_key => $_value) {
                $recipients = str_ireplace('%%cv'.$_key.'%%', $_value, $recipients);
                $subject    = str_ireplace('%%cv'.$_key.'%%', $_value, $subject);
                $message    = str_ireplace('%%cv'.$_key.'%%', $_value, $message);
            }
            unset($_key, $_value); // Housekeeping.

            $eot_offset     = (get_option('gmt_offset') * HOUR_IN_SECONDS);
            $eot_local_time = $eot['time'] + $eot_offset; // `date_i18n()`

            $eot_date   = date_i18n(get_option('date_format'), $eot_local_time);
            $recipients = str_ireplace('%%eot_date%%', $eot_date, $recipients);
            $subject    = str_ireplace('%%eot_date%%', $eot_date, $subject);
            $message    = str_ireplace('%%eot_date%%', $eot_date, $message);

            $eot_time   = date_i18n(get_option('time_format'), $eot_local_time);
            $recipients = str_ireplace('%%eot_time%%', $eot_time, $recipients);
            $subject    = str_ireplace('%%eot_time%%', $eot_time, $subject);
            $message    = str_ireplace('%%eot_time%%', $eot_time, $message);

            $eot_tz     = date_i18n('T', $eot_local_time);
            $recipients = str_ireplace('%%eot_tz%%', $eot_tz, $recipients);
            $subject    = str_ireplace('%%eot_tz%%', $eot_tz, $subject);
            $message    = str_ireplace('%%eot_tz%%', $eot_tz, $message);

            $eot_date_time_tz = $eot_date.' '.$eot_time.' '.$eot_tz;
            $recipients       = str_ireplace('%%eot_date_time_tz%%', $eot_date_time_tz, $recipients);
            $subject          = str_ireplace('%%eot_date_time_tz%%', $eot_date_time_tz, $subject);
            $message          = str_ireplace('%%eot_date_time_tz%%', $eot_date_time_tz, $message);

            $eot_descriptive_time = c_ws_plugin__s2member_utils_time::approx_time_difference(self::$now, $eot['time'], 'floor');
            $recipients           = str_ireplace('%%eot_descriptive_time%%', $eot_descriptive_time, $recipients);
            $subject              = str_ireplace('%%eot_descriptive_time%%', $eot_descriptive_time, $subject);
            $message              = str_ireplace('%%eot_descriptive_time%%', $eot_descriptive_time, $message);

            // This allows developers to build a list of custom replacement codes if they'd like; using a WP filter.
            foreach (apply_filters('ws_plugin__s2member_pro_eot_reminder_email_custom_rcs', array(), get_defined_vars()) as $_custom_rc_key => $_custom_rc_value) {
                if (!is_string($_custom_rc_key) || !is_scalar($_custom_rc_value)) {
                    continue; // Requires string key and scalar value.
                }
                $recipients = str_ireplace('%%'.$_custom_rc_key.'%%', (string) $_custom_rc_value, $recipients);
                $subject    = str_ireplace('%%'.$_custom_rc_key.'%%', (string) $_custom_rc_value, $subject);
                $message    = str_ireplace('%%'.$_custom_rc_key.'%%', (string) $_custom_rc_value, $message);
            }
            unset($_custom_rc_key, $_custom_rc_value); // Housekeeping.

            $recipients = trim(preg_replace('/%%(.+?)%%/i', '', $recipients)); // Remove remaining.
            $subject    = trim(preg_replace('/%%(.+?)%%/i', '', $subject)); // Remove any remaining.
            $message    = trim(preg_replace('/%%(.+?)%%/i', '', $message)); // Remove any remaining.

            if (!is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) {
                //
                $evl_vars = get_defined_vars(); // Defined vars; minus primaries.
                unset($evl_vars['recipients'], $evl_vars['subject'], $evl_vars['message']);

                $recipients = c_ws_plugin__s2member_utilities::evl($recipients, $evl_vars);
                $subject    = c_ws_plugin__s2member_utilities::evl($subject, $evl_vars);
                $message    = c_ws_plugin__s2member_utilities::evl($message, $evl_vars);
            }
        }

        protected static function calculate_day($time)
        {
            // Note: `floor()` very important here.
            // Always round down to avoid skipping any.

            // -1 = 1 day before.
            //  0 = the day of.
            //  1 = 1 day after.

            if (!($time = (int) $time)) {
                return ''; // Not possible.
                //
            } elseif ($time >= self::$now) {
                // Now, or in the future.
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
