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
         * Ensures that reminders based on stored End-of-Term dates have their own recurring WP-Cron event.
         *
         * These reminders no longer depend on the Auto-EOT processor completing first. This keeps reminder
         * delivery independent from demotion/backlog processing and lets a missing reminder event repair itself.
         *
         * @since 260820.1924
         *
         * @return bool True when the reminder schedule is healthy/disabled as configured, otherwise false.
         */
        public static function ensure_fixed_eot_reminder_schedule()
        {
            $hook = 'ws_plugin__s2member_pro_fixed_eot_reminders__schedule';
            $continuation_hook = 'ws_plugin__s2member_pro_fixed_eot_reminders__continuation';
            $enabled = !empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_enable']);

            if (!$enabled) {
                wp_clear_scheduled_hook($hook);
                wp_clear_scheduled_hook($continuation_hook);
                return true;
            }
            if (wp_next_scheduled($hook) && wp_get_schedule($hook) === 'every10m') {
                return true;
            }
            if (wp_next_scheduled($hook)) {
                wp_clear_scheduled_hook($hook); //260820.1924 Repair an inherited/wrong recurrence instead of leaving a slower reminder heartbeat in place indefinitely.
            }

            //260820.1924 A 10-minute heartbeat makes date-sensitive reminders prompt without coupling them back to Auto-EOT; one-off continuations drain unusually large due queues sooner.
            return (bool) wp_schedule_event(time() + MINUTE_IN_SECONDS, 'every10m', $hook);
        }

        /**
         * Returns a conservative wall-clock budget for EOT reminder processing.
         *
         * @since 260820.1924
         *
         * @return float Runtime budget in seconds.
         */
        protected static function fixed_eot_runtime_budget()
        {
            $php_max_execution_time = (int) ini_get('max_execution_time');

            //260820.1924 Mail transports can block unpredictably; use only half of a finite PHP limit (or a bounded 20 seconds when unlimited) so one reminder pass leaves substantial request headroom.
            $runtime_budget = $php_max_execution_time > 0 ? floor($php_max_execution_time * 0.50) : 20;

            return max(1, (float) apply_filters('ws_plugin__s2member_pro_fixed_eot_reminders_runtime', max(1, $runtime_budget), get_defined_vars()));
        }

        /**
         * Returns the retry delay after a failed recipient handoff.
         *
         * Early retries are intentionally aggressive for transient mail failures; longer spacing after repeated
         * failures avoids hammering a persistently broken transport while the reminder remains eligible.
         *
         * @since 260820.1924
         *
         * @param int $attempts Number of attempts already made for this recipient/offset.
         *
         * @return int Delay in seconds before another attempt.
         */
        protected static function fixed_eot_retry_delay($attempts)
        {
            $attempts = max(0, (int) $attempts);

            if ($attempts <= 0) {
                return 0;
            } elseif ($attempts === 1) {
                return 10 * MINUTE_IN_SECONDS;
            } elseif ($attempts === 2) {
                return 30 * MINUTE_IN_SECONDS;
            } elseif ($attempts === 3) {
                return HOUR_IN_SECONDS;
            }
            return 3 * HOUR_IN_SECONDS;
        }

        /**
         * Loads and validates the configured reminder templates.
         *
         * @since 260820.1924
         *
         * @return array|false Parsed reminder offsets, or false when reminders cannot run.
         */
        protected static function load_reminder_config()
        {
            $options = &$GLOBALS['WS_PLUGIN__']['s2member']['o'];

            if (empty($options['pro_eot_reminder_email_enable']) || !isset($options['pro_eot_reminder_email_days'][0])) {
                return false;
            }
            self::$now        = time();
            self::$recipients = json_decode($options['pro_eot_reminder_email_recipients']);
            self::$subject    = json_decode($options['pro_eot_reminder_email_subject']);
            self::$message    = json_decode($options['pro_eot_reminder_email_message']);

            if (!is_object(self::$recipients) || !is_object(self::$subject) || !is_object(self::$message)
                || !$options['reg_email_from_name'] || !$options['reg_email_from_email']) {
                return false;
            }

            $days = preg_split('/[;,\s]+/', trim($options['pro_eot_reminder_email_days']), -1, PREG_SPLIT_NO_EMPTY);
            $days = array_values(array_unique(array_map('intval', $days)));
            //260820.1924 Process the latest target first. If several sequence messages became due during an outage, one current message can safely supersede an older missed one for the same recipient.
            rsort($days, SORT_NUMERIC);

            return $days;
        }

        /**
         * Returns the site's timezone for calendar-day reminder calculations.
         *
         * @since 260820.1924
         *
         * @return DateTimeZone Site timezone.
         */
        protected static function site_timezone()
        {
            if (function_exists('wp_timezone')) {
                return wp_timezone();
            }
            if (($timezone_string = get_option('timezone_string'))) {
                return new DateTimeZone($timezone_string);
            }

            $offset = (float) get_option('gmt_offset');
            $hours = (int) $offset;
            $minutes = (int) round(abs($offset - $hours) * 60);
            return new DateTimeZone(sprintf('%+03d:%02d', $hours, $minutes));
        }

        /**
         * Sends one reminder and captures WordPress/PHPMailer failure details for diagnostics.
         *
         * @since 260820.1924
         *
         * @param string $recipient Recipient email address.
         * @param string $subject   Reminder subject.
         * @param string $message   Reminder body.
         * @param string $mail_from Formatted From identity.
         *
         * @return array Mail result and diagnostic details.
         */
        protected static function send_reminder_mail($recipient, $subject, $message, $mail_from)
        {
            global $phpmailer;

            $wp_error = null;
            $success = false;

            //260820.1924 Scope wp_mail_failed capture to this handoff; it exposes PHPMailer details that wp_mail()'s boolean return cannot explain on its own.
            $failed = static function ($error) use (&$wp_error) {
                if ($error instanceof WP_Error) {
                    $wp_error = $error;
                }
            };
            add_action('wp_mail_failed', $failed, PHP_INT_MAX, 1);

            $started = microtime(true);
            try {
                if (empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['html_emails_enabled'])) {
                    $success = wp_mail($recipient, $subject, $message,
                        'From: '.$mail_from."\r\n".'Content-Type: text/plain; charset=utf-8');
                } else {
                    $success = c_ws_plugin__s2member_utilities::mail($recipient, $subject, $message);
                }
            } finally {
                $duration = max(0, microtime(true) - $started);
                remove_action('wp_mail_failed', $failed, PHP_INT_MAX);
            }

            $error_data = ($wp_error instanceof WP_Error) ? $wp_error->get_error_data() : array();
            $error_data = is_array($error_data) ? $error_data : array();

            return array(
                'success'                    => !empty($success),
                'duration'                   => $duration,
                'error_code'                 => ($wp_error instanceof WP_Error) ? $wp_error->get_error_code() : '',
                'error_message'              => ($wp_error instanceof WP_Error) ? $wp_error->get_error_message() : '',
                'phpmailer_exception_code'   => isset($error_data['phpmailer_exception_code']) ? $error_data['phpmailer_exception_code'] : '',
                'phpmailer_error_info'       => is_object($phpmailer) && isset($phpmailer->ErrorInfo) ? (string) $phpmailer->ErrorInfo : '',
                'phpmailer_mailer'           => is_object($phpmailer) && isset($phpmailer->Mailer) ? (string) $phpmailer->Mailer : '',
                'phpmailer_from'             => is_object($phpmailer) && isset($phpmailer->From) ? (string) $phpmailer->From : '',
                'phpmailer_from_name'        => is_object($phpmailer) && isset($phpmailer->FromName) ? (string) $phpmailer->FromName : '',
                'phpmailer_sender'           => is_object($phpmailer) && isset($phpmailer->Sender) ? (string) $phpmailer->Sender : '',
                'phpmailer_content_type'     => is_object($phpmailer) && isset($phpmailer->ContentType) ? (string) $phpmailer->ContentType : '',
            );
        }

        /**
         * Runs the dedicated reminder processor for stored End-of-Term dates.
         *
         * Reminder candidates come directly from users' stored EOT metadata. The normal eligibility window is the
         * configured calendar day plus the following site-local calendar day, so a rare scheduler outage can
         * recover a message without turning old reminder sequences into a burst. Recipient-specific delivery
         * state prevents successful recipients from receiving duplicates while failed recipients retry with backoff.
         *
         * @since 260820.1924
         *
         * @param bool $is_continuation Internal one-off continuation of a runtime-limited pass.
         */
        public static function fixed_eot_remind($is_continuation = false)
        {
            global $wpdb;

            if (!($days = self::load_reminder_config())) {
                self::ensure_fixed_eot_reminder_schedule();
                return;
            }

            $runtime_budget = self::fixed_eot_runtime_budget();
            $request_started = isset($_SERVER['REQUEST_TIME_FLOAT']) && is_numeric($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
            $deadline = $request_started + $runtime_budget;
            $safety_buffer = min($runtime_budget * 0.25, max(0.25, (float) apply_filters('ws_plugin__s2member_pro_fixed_eot_reminders_runtime_safety_buffer', 1.0, get_defined_vars())));
            //260820.1924 This is additional calendar days after the configured target day; default 1 means target day + the following day.
            $late_days = max(0, (int) apply_filters('ws_plugin__s2member_pro_fixed_eot_reminders_late_days', 1, get_defined_vars()));
            $lock_option = 'ws_plugin__s2member_pro_fixed_eot_reminders_lock';
            $state_option = 'ws_plugin__s2member_pro_fixed_eot_reminders_state';
            $continuation_hook = 'ws_plugin__s2member_pro_fixed_eot_reminders__continuation';
            $reminder_state_option = 's2member_eot_reminder_state';
            $meta_key = $wpdb->prefix.'s2member_auto_eot_time';
            $last_meta_key = $wpdb->prefix.'s2member_last_auto_eot_time';
            $timezone = self::site_timezone();
            $today = new DateTimeImmutable('today', $timezone);
            $lock_stale_after = max(120, (int) ceil(($runtime_budget * 2) + 30));
            $lock = get_option($lock_option);

            //260820.1924 A malformed/stale lock must not suppress reminders forever after an interrupted request; a fresh lock still prevents overlapping workers.
            if ($lock !== false && (!is_array($lock) || empty($lock['heartbeat_at']))) {
                delete_option($lock_option);
                $lock = false;
            }
            if (is_array($lock) && time() - (int) $lock['heartbeat_at'] > $lock_stale_after) {
                delete_option($lock_option);
                $lock = false;
            }
            if (is_array($lock)) {
                return;
            }

            $run_token = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('s2-reminder-', true);
            $lock = array('token' => $run_token, 'heartbeat_at' => time(), 'processed' => 0);

            //260820.1924 add_option() is the atomic acquisition step; only one reminder worker may own this lock.
            if (!add_option($lock_option, $lock, '', false)) {
                return;
            }

            $state = get_option($state_option);
            $state = is_array($state) ? $state : array();
            $state['last_started_at'] = time();
            $state['active_run_token'] = $run_token;
            update_option($state_option, $state, false);

            $mail_from = '"'.str_replace('"', "'", $GLOBALS['WS_PLUGIN__']['s2member']['o']['reg_email_from_name']).'"'.
                         ' <'.$GLOBALS['WS_PLUGIN__']['s2member']['o']['reg_email_from_email'].'>';
            $message_bytes_in_log = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_message_bytes_in_log', 100);
            $additional_user_ids_to_exclude = array_map('intval', (array) apply_filters('ws_plugin__s2member_pro_eot_reminders_exclude_user_ids', array(), get_defined_vars()));
            $mail_count = 0;
            $processed_count = 0;
            $mail_total_duration = 0.0;
            $last_mail_duration = 0.0;
            $runtime_exhausted = false;
            $last_heartbeat = microtime(true);

            $email_configs_were_on = c_ws_plugin__s2member_email_configs::email_config_status();
            c_ws_plugin__s2member_email_configs::email_config();

            try {
                //260820.1924 Offsets are newest-first, implementing coalescing: after downtime, a newer sequence message can suppress an older missed one instead of sending both.
                foreach ($days as $_day) {
                    $offset = (int) $_day;
                    //260820.1924 Build eligibility from site-local calendar boundaries, not 86,400-second arithmetic, so DST changes cannot move a reminder to the wrong local day.
                    $eot_start_day = $today->modify('-'.$late_days.' days')->modify(($offset > 0 ? '-' : '+').abs($offset).' days');
                    $eot_end_day = $today->modify('+1 day')->modify(($offset > 0 ? '-' : '+').abs($offset).' days');
                    $range_start = $eot_start_day->getTimestamp();
                    $range_end = $eot_end_day->getTimestamp();
                    $cursor_time = 0;
                    $cursor_umeta_id = 0;

                    while (true) {
                        //260820.1924 Predict from this run only. A slow recent/average mail handoff must fit alongside the safety buffer before another candidate begins.
                        $remaining_runtime = $deadline - microtime(true);
                        $average_mail_duration = $mail_count ? $mail_total_duration / $mail_count : 0.0;
                        $estimated_next_duration = max($last_mail_duration, $average_mail_duration);
                        if ($remaining_runtime <= $safety_buffer + $estimated_next_duration) {
                            $runtime_exhausted = true;
                            break 2;
                        }

                        //260820.1924 Include archived EOTs only when this reminder's late-delivery window can reach the EOT date or later; Auto-EOT may already have moved the current timestamp to history before this worker runs.
                        if ($offset + $late_days >= 0) {
                            $sql = "SELECT `umeta_id`, `user_id` AS `ID`, `meta_key`, CAST(`meta_value` AS UNSIGNED) AS `eot_time` FROM `".$wpdb->usermeta."` WHERE `meta_key` IN (%s, %s) AND CAST(`meta_value` AS UNSIGNED) >= %d AND CAST(`meta_value` AS UNSIGNED) < %d";
                            $sql_args = array($meta_key, $last_meta_key, $range_start, $range_end);
                        } else {
                            $sql = "SELECT `umeta_id`, `user_id` AS `ID`, `meta_key`, CAST(`meta_value` AS UNSIGNED) AS `eot_time` FROM `".$wpdb->usermeta."` WHERE `meta_key` = %s AND CAST(`meta_value` AS UNSIGNED) >= %d AND CAST(`meta_value` AS UNSIGNED) < %d";
                            $sql_args = array($meta_key, $range_start, $range_end);
                        }
                        if ($cursor_time || $cursor_umeta_id) {
                            $sql .= " AND (CAST(`meta_value` AS UNSIGNED) > %d OR (CAST(`meta_value` AS UNSIGNED) = %d AND `umeta_id` > %d))";
                            $sql_args[] = $cursor_time;
                            $sql_args[] = $cursor_time;
                            $sql_args[] = $cursor_umeta_id;
                        }
                        //260820.1924 LIMIT 100 is only a DB cursor buffer, not a users-per-run cap; runtime and mail cost decide how much work this pass safely completes.
                        $sql .= " ORDER BY CAST(`meta_value` AS UNSIGNED) ASC, `umeta_id` ASC LIMIT 100";
                        $eots = $wpdb->get_results($wpdb->prepare($sql, $sql_args));
                        if (!is_array($eots) || !$eots) {
                            break;
                        }

                        foreach ($eots as $_eot_row) {
                            $cursor_time = (int) $_eot_row->eot_time;
                            $cursor_umeta_id = (int) $_eot_row->umeta_id;

                            if (in_array((int) $_eot_row->ID, $additional_user_ids_to_exclude, true)
                                || (string) get_user_option('s2member_reminders_enable', (int) $_eot_row->ID) === '0') {
                                continue;
                            }

                            //260820.1924 Re-read the exact usermeta row immediately before acting; a renewal/admin EOT edit selected after the query must win over this stale candidate.
                            $current_eot = $wpdb->get_row($wpdb->prepare("SELECT `user_id`, `meta_key`, `meta_value` FROM `".$wpdb->usermeta."` WHERE `umeta_id` = %d LIMIT 1", $cursor_umeta_id));
                            if (!$current_eot || (int) $current_eot->user_id !== (int) $_eot_row->ID || (string) $current_eot->meta_key !== (string) $_eot_row->meta_key || (int) $current_eot->meta_value !== $cursor_time) {
                                continue;
                            }

                            $_user = new WP_User((int) $_eot_row->ID);
                            if (!$_user->ID) {
                                continue;
                            }

                            //260821.0057 Refund/reversal EOTs terminate access for a payment exception, not a normal renewal opportunity. Match details to this exact EOT so stale provenance cannot suppress a later legitimate reminder sequence.
                            $_eot_details_option = (string) $current_eot->meta_key === $last_meta_key ? 's2member_last_auto_eot_details' : 's2member_auto_eot_details';
                            $_eot_details = get_user_option($_eot_details_option, $_user->ID);
                            if (is_array($_eot_details) && !empty($_eot_details['time']) && (int) $_eot_details['time'] === (int) $current_eot->meta_value
                                && !empty($_eot_details['source']) && (string) $_eot_details['source'] === 'refund_reversal') {
                                continue;
                            }

                            //260820.1924 Resolve locally (no gateway API call) before mailing. Archived EOT history is valid only while the account still represents that expiration; renewed/reactivated users must not receive it.
                            $_resolved_eot = c_ws_plugin__s2member_utils_users::get_user_eot($_user->ID, false);
                            if (empty($_resolved_eot['type']) || $_resolved_eot['type'] !== 'fixed' || (int) $_resolved_eot['time'] !== (int) $current_eot->meta_value) {
                                continue;
                            }

                            $_eot_time = (int) $current_eot->meta_value;

                            //260820.1924 Reconstruct the configured target from the authoritative EOT in site time, then measure lateness in whole calendar days.
                            $_eot_local = (new DateTimeImmutable('@'.$_eot_time))->setTimezone($timezone);
                            $_target_day = $_eot_local->setTime(0, 0, 0)->modify(($offset >= 0 ? '+' : '').$offset.' days');
                            $_target_timestamp = $_target_day->getTimestamp();
                            $_lateness = (int) $_target_day->diff($today)->format('%r%a');
                            if ($_lateness < 0 || $_lateness > $late_days) {
                                continue;
                            }

                            $_recipients = self::get_recipients_for_day((string) $offset);
                            $_subject = self::get_subject_for_day((string) $offset);
                            $_message = self::get_message_for_day((string) $offset);
                            if (!$_recipients || !$_subject || !$_message) {
                                continue;
                            }

                            $_eot = array('type' => 'fixed', 'time' => $_eot_time, 'tense' => $_eot_time <= self::$now ? 'past' : 'future', 'debug' => 'Fixed EOT reminder candidate.');
                            self::fill_replacement_codes($_user, $_eot, $_recipients, $_subject, $_message);
                            $_mail_from = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_from', $mail_from, get_defined_vars());
                            $_recipients = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_recipients', $_recipients, get_defined_vars());
                            $_subject = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_subject', $_subject, get_defined_vars());
                            $_message = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_message', $_message, get_defined_vars());
                            if (!$_recipients || !$_subject || !$_message || !$_mail_from) {
                                continue;
                            }

                            $_reminder_state = get_user_option($reminder_state_option, $_user->ID);

                            //260821.0458 Keep only the state for this exact EOT. The EOT timestamp is the sequence identity, so a renewal/admin edit cannot inherit delivery suppression or retries from the previous term.
                            if (!is_array($_reminder_state) || count($_reminder_state) !== 1 || !isset($_reminder_state[$_eot_time]) || !is_array($_reminder_state[$_eot_time])) {
                                $_reminder_state = array($_eot_time => array());
                            }

                            //260820.1924 Each recipient is independent. One failed committee/admin copy must not block the member or other recipients, and successful recipients must never be retried.
                            foreach (c_ws_plugin__s2member_utils_strings::parse_emails($_recipients) as $_recipient) {
                                $remaining_runtime = $deadline - microtime(true);
                                $average_mail_duration = $mail_count ? $mail_total_duration / $mail_count : 0.0;
                                $estimated_next_duration = max($last_mail_duration, $average_mail_duration);
                                if ($remaining_runtime <= $safety_buffer + $estimated_next_duration) {
                                    $runtime_exhausted = true;
                                    break 4;
                                }

                                //260821.0535 Normalize only the delivery-state identity; wp_mail() still receives the parsed address unchanged. Case-only recipient changes must not create a second reminder sequence.
                                $_recipient_key = strtolower(trim($_recipient));
                                $_recipient_state = !empty($_reminder_state[$_eot_time][$_recipient_key]) && is_array($_reminder_state[$_eot_time][$_recipient_key]) ? $_reminder_state[$_eot_time][$_recipient_key] : array();
                                $_already_sent = false;

                                //260821.0458 Offset branches are chronological relative to the same EOT. A successful equal/newer offset therefore proves this message was delivered already or was superseded by a later sequence message.
                                foreach ($_recipient_state as $_sent_offset => $_sent_attempts) {
                                    if (!is_numeric($_sent_offset) || (int) $_sent_offset < $offset || !is_array($_sent_attempts) || !$_sent_attempts) {
                                        continue;
                                    }
                                    $_last_sent_attempt = end($_sent_attempts);
                                    if (is_array($_last_sent_attempt) && array_key_exists('success', $_last_sent_attempt)) {
                                        $_already_sent = true;
                                        break;
                                    }
                                }
                                unset($_sent_offset, $_sent_attempts, $_last_sent_attempt);
                                if ($_already_sent) {
                                    continue;
                                }

                                $_offset_attempts = !empty($_recipient_state[(string) $offset]) && is_array($_recipient_state[(string) $offset]) ? $_recipient_state[(string) $offset] : array();
                                $_attempts = count($_offset_attempts);
                                $_retry_delay = self::fixed_eot_retry_delay($_attempts);
                                $_last_attempt_at = 0;
                                if ($_offset_attempts) {
                                    end($_offset_attempts);
                                    $_last_attempt_at = (int) key($_offset_attempts);
                                }
                                if ($_last_attempt_at && self::$now < $_last_attempt_at + $_retry_delay) {
                                    continue;
                                }

                                $mail_count++;
                                $_mail_result = self::send_reminder_mail($_recipient, $_subject, $_message, $_mail_from);
                                $last_mail_duration = (float) $_mail_result['duration'];
                                $mail_total_duration += $last_mail_duration;

                                $_attempts++;
                                $_attempt_time = time();
                                $_attempt_result = $_mail_result['success']
                                    ? array('success' => '')
                                    : array((string) ($_mail_result['error_code'] ?: 'failure') => (string) ($_mail_result['error_message'] ?: 'wp_mail() returned false.'));
                                $_offset_attempts[$_attempt_time] = $_attempt_result;
                                $_recipient_state[(string) $offset] = $_offset_attempts;
                                $_reminder_state[$_eot_time][$_recipient_key] = $_recipient_state;

                                //260821.0458 Persist each compact attempt immediately so a later timeout/fatal cannot duplicate a successful handoff and a failed handoff retains its retry history.
                                update_user_option($_user->ID, $reminder_state_option, $_reminder_state);

                                $_log_entry = array(
                                    'eot'                       => $_eot,
                                    'eot_rfc822'                => date(DATE_RFC822, $_eot_time),
                                    'day'                       => (string) $offset,
                                    'reminder_target_rfc822'    => date(DATE_RFC822, $_target_timestamp),
                                    'reminder_late_days'        => $_lateness,
                                    'now'                       => self::$now,
                                    'user_id'                   => $_user->ID,
                                    'user_login'                => $_user->user_login,
                                    'user_email'                => $_user->user_email,
                                    'user_first_name'           => $_user->first_name,
                                    'user_last_name'            => $_user->last_name,
                                    'mail_from'                 => $_mail_from,
                                    'recipient'                 => $_recipient,
                                    'subject'                   => $_subject,
                                    'mail_number_in_run'        => $mail_count,
                                    'mail_duration'             => $_mail_result['duration'],
                                    'retry_count'               => max(0, $_attempts - 1),
                                    'run_token'                 => $run_token,
                                    'wp_mail_success'           => $_mail_result['success'] ? 'yes' : 'no',
                                    'wp_mail_error_code'        => $_mail_result['error_code'],
                                    'wp_mail_error_message'     => $_mail_result['error_message'],
                                    'phpmailer_exception_code'  => $_mail_result['phpmailer_exception_code'],
                                    'phpmailer_error_info'      => $_mail_result['phpmailer_error_info'],
                                    'phpmailer_mailer'          => $_mail_result['phpmailer_mailer'],
                                    'phpmailer_from'            => $_mail_result['phpmailer_from'],
                                    'phpmailer_from_name'       => $_mail_result['phpmailer_from_name'],
                                    'phpmailer_sender'          => $_mail_result['phpmailer_sender'],
                                    'phpmailer_content_type'    => $_mail_result['phpmailer_content_type'],
                                );
                                if (strlen($_message) > $message_bytes_in_log) {
                                    $_log_entry['message_clip'] = substr($_message, 0, $message_bytes_in_log).'...';
                                } else {
                                    $_log_entry['message'] = $_message;
                                }
                                c_ws_plugin__s2member_utils_logs::log_entry('eot-reminders', $_log_entry);

                                //260820.1924 Keep the latest transport evidence outside debug logging too, so the admin-health layer can diagnose persistent failures even when gateway logs are disabled.
                                $state = get_option($state_option);
                                $state = is_array($state) ? $state : array();
                                if ($_mail_result['success']) {
                                    $state['last_success_at'] = time();
                                    $state['consecutive_mail_failures'] = 0;
                                } else {
                                    $state['last_failure_at'] = time();
                                    $state['last_failure_user_id'] = $_user->ID;
                                    $state['last_failure_recipient'] = $_recipient;
                                    $state['last_failure_error_code'] = $_mail_result['error_code'];
                                    $state['last_failure_error_message'] = $_mail_result['error_message'];
                                    $state['last_failure_phpmailer_exception_code'] = $_mail_result['phpmailer_exception_code'];
                                    $state['last_failure_phpmailer_error_info'] = $_mail_result['phpmailer_error_info'];
                                    $state['last_failure_phpmailer_mailer'] = $_mail_result['phpmailer_mailer'];
                                    $state['last_failure_phpmailer_from'] = $_mail_result['phpmailer_from'];
                                    $state['last_failure_phpmailer_from_name'] = $_mail_result['phpmailer_from_name'];
                                    $state['last_failure_phpmailer_sender'] = $_mail_result['phpmailer_sender'];
                                    $state['last_failure_phpmailer_content_type'] = $_mail_result['phpmailer_content_type'];
                                    $state['last_failure_mail_number_in_run'] = $mail_count;
                                    $state['last_failure_mail_duration'] = $_mail_result['duration'];
                                    $state['last_failure_run_token'] = $run_token;
                                    $state['consecutive_mail_failures'] = !empty($state['consecutive_mail_failures']) ? (int) $state['consecutive_mail_failures'] + 1 : 1;
                                }
                                update_option($state_option, $state, false);
                            }

                            $processed_count++;

                            //260820.1924 Heartbeat long passes so a second request cannot mistake an active worker for an abandoned stale lock.
                            if ($processed_count % 5 === 0 || microtime(true) - $last_heartbeat >= 5) {
                                $lock['heartbeat_at'] = time();
                                $lock['processed'] = $processed_count;
                                update_option($lock_option, $lock, false);
                                $last_heartbeat = microtime(true);
                            }
                        }

                        if (count($eots) < 100) {
                            break;
                        }
                    }
                }
            } finally {
                if (!$email_configs_were_on) {
                    c_ws_plugin__s2member_email_configs::email_config_release();
                }
            }

            $state = get_option($state_option);
            $state = is_array($state) ? $state : array();
            $state['last_completed_at'] = time();
            $state['last_processed'] = $processed_count;
            $state['last_mail_count'] = $mail_count;
            $state['last_stop_reason'] = $runtime_exhausted ? 'runtime_budget' : 'complete';
            $state['active_run_token'] = '';
            update_option($state_option, $state, false);
            delete_option($lock_option);

            if ($runtime_exhausted) {
                //260820.1924 Do not wait for the next 10-minute heartbeat when due work remains; continue soon, while the recipient/EOT state keeps the continuation idempotent.
                if (!wp_next_scheduled($continuation_hook)) {
                    wp_schedule_single_event(time() + MINUTE_IN_SECONDS, $continuation_hook);
                }
            } else {
                wp_clear_scheduled_hook($continuation_hook);
            }
        }

        /**
         * Runs a one-off continuation for a runtime-limited fixed EOT reminder pass.
         *
         * @since 260820.1924
         */
        public static function fixed_eot_remind_continuation()
        {
            self::fixed_eot_remind(true);
        }

        /**
         * Handles the legacy optional Next Payment Time (NPT) reminder path.
         *
         * Since 260820.1924, reminders based on a user's stored End-of-Term (EOT) date run independently in
         * `fixed_eot_remind()`. This method stays on the older Auto-EOT completion hook only for sites that
         * explicitly enabled reminders based on gateway-derived NPTs; that gateway-dependent path will be
         * redesigned separately.
         *
         * @since 151202 Reminders.
         * @since 260820.1924 Stored-EOT reminders moved to their own scheduler; this callback remains for optional NPT reminders and now records shared mail-transport diagnostics.
         *
         * @param array $vars Defined variables from the Auto-EOT pass.
         */
        public static function remind($vars = array())
        {
            global $wpdb;

            $options = &$GLOBALS['WS_PLUGIN__']['s2member']['o'];
            if (empty($options['pro_eot_reminder_email_enable']) || empty($options['pro_eot_reminder_email_on_npt_also'])) {
                return;
            }
            if (!($days = self::load_reminder_config())) {
                return;
            }

            //260820.1952 Keep the old daily scan throttle only on NPT lookups; stored-EOT reminders use per-EOT/per-recipient delivery state instead.
            $scan_time = apply_filters('ws_plugin__s2member_pro_eot_reminders_scan_time', strtotime('-1 day', self::$now), get_defined_vars());
            $per_process = apply_filters('ws_plugin__s2member_pro_eot_reminders_per_process', !empty($vars['per_process']) ? (int) $vars['per_process'] : 10, get_defined_vars());
            $message_bytes_in_log = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_message_bytes_in_log', 100);
            $mail_from = '"'.str_replace('"', "'", $options['reg_email_from_name']).'" <'.$options['reg_email_from_email'].'>';

            $user_ids_to_exclude = '
                SELECT DISTINCT `user_id` AS `ID` FROM `'.$wpdb->usermeta.'`
                    WHERE
                        (`meta_key` = \''.$wpdb->prefix.'s2member_last_reminder_scan\' AND `meta_value` >= \''.esc_sql($scan_time).'\')
                        OR (`meta_key` = \''.$wpdb->prefix.'s2member_reminders_enable\' AND `meta_value` = \'0\')
            ';
            $additional_user_ids_to_exclude = apply_filters('ws_plugin__s2member_pro_eot_reminders_exclude_user_ids', array(), get_defined_vars());

            //260820.1952 NPT candidates require subscription gateway metadata because this legacy path may query the gateway for a next-payment date; stored EOT dates are handled elsewhere.
            $sql = '
                SELECT DISTINCT `user_id` AS `ID` FROM `'.$wpdb->usermeta.'`
                    WHERE `user_id` NOT IN('.$user_ids_to_exclude.')
                        '.($additional_user_ids_to_exclude ? 'AND `user_id` NOT IN(\''.implode("','", $additional_user_ids_to_exclude).'\')' : '').'
                        AND (`meta_key` = \''.$wpdb->prefix.'s2member_subscr_gateway\' AND `meta_value` != \'\')
                    LIMIT '.esc_sql($per_process).'
            ';
            if (!($user_ids = $wpdb->get_col($sql))) {
                return;
            }

            $email_configs_were_on = c_ws_plugin__s2member_email_configs::email_config_status();
            c_ws_plugin__s2member_email_configs::email_config();

            foreach ($user_ids as $_user_id) {
                $_eot = $_day = $_recipients = $_subject = $_message = null;
                if (!($_user = new WP_User($_user_id)) || !$_user->ID) {
                    continue;
                }

                //260820.1952 NPT keeps its legacy scan marker for now; the dedicated stored-EOT worker no longer relies on this coarse daily marker.
                update_user_option($_user->ID, 's2member_last_reminder_scan', self::$now);

                //260820.1952 Explicitly favor the gateway-derived NPT. This is the reminder path that may perform gateway API lookups; stored-EOT reminders never need them.
                $_eot = c_ws_plugin__s2member_utils_users::get_user_eot($_user->ID, true, 'next');
                if (!$_eot || $_eot['type'] !== 'next' || !$_eot['time'] || !$_eot['tense']) {
                    continue;
                } elseif (!($_day = self::calculate_day($_eot['time'])) && $_day !== '0') {
                    continue;
                } elseif (!in_array((int) $_day, $days, true)) {
                    continue;
                } elseif (!($_recipients = self::get_recipients_for_day($_day))
                    || !($_subject = self::get_subject_for_day($_day))
                    || !($_message = self::get_message_for_day($_day))) {
                    continue;
                }

                self::fill_replacement_codes($_user, $_eot, $_recipients, $_subject, $_message);
                $_mail_from = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_from', $mail_from, get_defined_vars());
                $_recipients = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_recipients', $_recipients, get_defined_vars());
                $_subject = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_subject', $_subject, get_defined_vars());
                $_message = apply_filters('ws_plugin__s2member_pro_eot_reminder_email_message', $_message, get_defined_vars());
                if (!$_recipients || !$_subject || !$_message || !$_mail_from) {
                    continue;
                }

                //260820.1952 Reuse the shared mail wrapper so NPT reminders get the same PHPMailer failure evidence as the independent stored-EOT worker.
                $_mail_attempted = $_mail_succeeded = false;
                foreach (c_ws_plugin__s2member_utils_strings::parse_emails($_recipients) as $_recipient) {
                    $_mail_attempted = true;
                    $_mail_result = self::send_reminder_mail($_recipient, $_subject, $_message, $_mail_from);
                    $_mail_success = $_mail_result['success'];
                    if ($_mail_success) {
                        $_mail_succeeded = true;
                    }

                    //260820.1952 Record the actual handoff and transport details; older reminder logs recorded attempts without proving whether wp_mail() succeeded.
                    $_log_entry = array(
                        'eot'                       => $_eot,
                        'eot_rfc822'                => date(DATE_RFC822, $_eot['time']),
                        'day'                       => $_day,
                        'now'                       => self::$now,
                        'user_id'                   => $_user->ID,
                        'user_login'                => $_user->user_login,
                        'user_email'                => $_user->user_email,
                        'user_first_name'           => $_user->first_name,
                        'user_last_name'            => $_user->last_name,
                        'mail_from'                 => $_mail_from,
                        'recipient'                 => $_recipient,
                        'subject'                   => $_subject,
                        'wp_mail_success'           => $_mail_success ? 'yes' : 'no',
                        'wp_mail_error_code'        => $_mail_result['error_code'],
                        'wp_mail_error_message'     => $_mail_result['error_message'],
                        'phpmailer_exception_code'  => $_mail_result['phpmailer_exception_code'],
                        'phpmailer_error_info'      => $_mail_result['phpmailer_error_info'],
                        'phpmailer_mailer'           => $_mail_result['phpmailer_mailer'],
                        'phpmailer_from'             => $_mail_result['phpmailer_from'],
                        'phpmailer_from_name'        => $_mail_result['phpmailer_from_name'],
                        'phpmailer_sender'           => $_mail_result['phpmailer_sender'],
                        'phpmailer_content_type'     => $_mail_result['phpmailer_content_type'],
                        'mail_duration'             => $_mail_result['duration'],
                    );
                    if (strlen($_message) > $message_bytes_in_log) {
                        $_log_entry['message_clip'] = substr($_message, 0, $message_bytes_in_log).'...';
                    } else {
                        $_log_entry['message'] = $_message;
                    }
                    c_ws_plugin__s2member_utils_logs::log_entry('eot-reminders', $_log_entry);
                }
                if ($_mail_attempted && !$_mail_succeeded) {
                    //260820.1952 Preserve legacy duplicate safety: retry the NPT reminder only when every recipient failed; any successful handoff keeps the scan marker to avoid resending that successful copy.
                    delete_user_option($_user->ID, 's2member_last_reminder_scan');
                }
            }
            unset($_user_id, $_user, $_eot, $_day, $_mail_from, $_recipients, $_recipient, $_subject, $_message, $_mail_success, $_mail_attempted, $_mail_succeeded, $_mail_result, $_log_entry);

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
