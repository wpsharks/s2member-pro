<?php
// @codingStandardsIgnoreFile
/**
 * Anti-spam statistics.
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
 * @since 261003.0533
 */
if (!defined('WPINC')) { // MUST have WordPress.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_antispam_stats')) {
    /**
     * Privacy-minimal aggregate anti-spam statistics.
     *
     * @package s2Member\Utilities
     * @since 261003.0533
     */
    class c_ws_plugin__s2member_pro_antispam_stats
    {
        /**
         * Returns the supported aggregate event names.
         *
         * @since 261003.0533
         *
         * @return string[] Event names.
         */
        public static function events()
        {
            return array('allowed', 'blocked_local', 'blocked_verification', 'blocked_spam', 'blocked_service');
        }

        /**
         * Returns supported aggregate timing metrics.
         *
         * @since 261004.0528
         *
         * @return string[] Timing metric names.
         */
        public static function timing_metrics()
        {
            return array('allowed_form_age', 'allowed_interaction', 'blocked_form_age', 'blocked_speedtrap_form_age');
        }

        /**
         * Returns fixed timing buckets in display/order sequence.
         *
         * @since 261004.0528
         *
         * @return array Bucket key => human-readable label.
         */
        public static function timing_buckets()
        {
            return array(
                'under_one'          => '< 1 sec',
                'one_to_two'         => '1–2 sec',
                'two_to_five'        => '2–5 sec',
                'five_to_ten'        => '5–10 sec',
                'ten_to_thirty'      => '10–30 sec',
                'thirty_to_sixty'    => '30–60 sec',
                'one_to_two_minutes' => '1–2 min',
                'two_minutes_plus'   => '2 min+',
            );
        }

        /**
         * Records one aggregate event without storing request/user identifiers.
         *
         * @since 261003.0533
         *
         * @param string   $event     Supported event name.
         * @param int|null $timestamp Optional WordPress-local timestamp for deterministic tests.
         *
         * @return bool True when recorded.
         */
        public static function record($event = '', $timestamp = null)
        {
            global $wpdb;

            $event = strtolower(trim((string) $event));
            if (!in_array($event, self::events(), true)) {
                return false;
            }
            $timestamp = isset($timestamp) ? (int) $timestamp : (int) current_time('timestamp');
            $option_name = self::option_prefix().date('Ymd', $timestamp).'_'.$event;

            //261003.0533 Use a tiny non-autoloaded numeric row per day/event so spam bursts do not rewrite s2Member's main options array or retain visitor data.
            $sql = "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no') ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + 1";
            $result = $wpdb->query($wpdb->prepare($sql, $option_name));
            return $result !== false;
        }

        /**
         * Records one timing sample into a fixed aggregate bucket.
         *
         * @since 261004.0528
         *
         * @param string    $metric    Supported timing metric.
         * @param int|float $seconds   Elapsed seconds. The exact duration is never persisted.
         * @param int|null  $timestamp Optional WordPress-local timestamp for deterministic tests.
         *
         * @return bool True when recorded.
         */
        public static function record_timing($metric = '', $seconds = null, $timestamp = null)
        {
            global $wpdb;

            $metric = strtolower(trim((string) $metric));
            if (!in_array($metric, self::timing_metrics(), true) || !is_numeric($seconds) || (float) $seconds < 0) {
                return false;
            }
            $bucket = self::timing_bucket((float) $seconds);
            if (!$bucket) {
                return false;
            }
            $timestamp = isset($timestamp) ? (int) $timestamp : (int) current_time('timestamp');
            $option_name = self::timing_prefix().date('Ymd', $timestamp).'__'.$metric.'__'.$bucket;

            //261004.0528 Persist only an atomic day/metric/bucket counter. Exact form ages, interaction durations, request data, and visitor identifiers never enter wp_options.
            $sql = "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no') ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + 1";
            $result = $wpdb->query($wpdb->prepare($sql, $option_name));
            return $result !== false;
        }

        /**
         * Maps a terminal anti-spam error to one aggregate blocked category.
         *
         * @since 261003.0533
         *
         * @param \WP_Error $error Anti-spam error.
         *
         * @return string Supported blocked event name.
         */
        public static function event_for_error($error)
        {
            $code = is_wp_error($error) ? (string) $error->get_error_code() : '';
            if (in_array($code, array('s2member_antispam_honeypot', 's2member_antispam_form_age', 's2member_antispam_form_too_fast'), true)) {
                return 'blocked_local';
            }
            if ($code === 's2member_antispam_akismet_spam') {
                return 'blocked_spam';
            }
            if (preg_match('/_(?:unavailable|configuration|quota)$/', $code)) {
                return 'blocked_service';
            }
            return 'blocked_verification';
        }

        /**
         * Returns aggregate daily/totals data for the recent reporting window.
         *
         * @since 261003.0533
         *
         * @param int      $days Number of days, including today.
         * @param int|null $now  Optional WordPress-local timestamp for deterministic tests.
         *
         * @return array Reporting window, per-day values, and totals.
         */
        public static function summary($days = 30, $now = null)
        {
            global $wpdb;

            $days = max(1, min(90, (int) $days));
            $now = isset($now) ? (int) $now : (int) current_time('timestamp');
            $events = self::events();
            $daily = array();
            $totals = array_fill_keys($events, 0);

            for ($offset = $days - 1; $offset >= 0; --$offset) {
                $timestamp = $now - ($offset * DAY_IN_SECONDS);
                $day = date('Ymd', $timestamp);
                $daily[$day] = array_fill_keys($events, 0);
            }

            $like = $wpdb->esc_like(self::option_prefix()).'%';
            $rows = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like), ARRAY_A);
            foreach ((array) $rows as $row) {
                if (!preg_match('/^(\d{8})_([a-z_]+)$/', substr((string) $row['option_name'], strlen(self::option_prefix())), $matches)) {
                    continue;
                }
                $day = $matches[1];
                $event = $matches[2];
                if (!isset($daily[$day][$event])) {
                    continue;
                }
                $value = max(0, (int) $row['option_value']);
                $daily[$day][$event] = $value;
                $totals[$event] += $value;
            }
            $totals['blocked'] = $totals['blocked_local'] + $totals['blocked_verification'] + $totals['blocked_spam'] + $totals['blocked_service'];
            return array('days' => $days, 'daily' => $daily, 'totals' => $totals, 'timing' => self::timing_summary($days, $now));
        }

        /**
         * Returns aggregate timing distributions and percentile bands.
         *
         * @since 261004.0528
         *
         * @param int      $days Number of days, including today.
         * @param int|null $now  Optional WordPress-local timestamp for deterministic tests.
         *
         * @return array Timing metric totals, bucket counts, and percentile bucket keys.
         */
        public static function timing_summary($days = 30, $now = null)
        {
            global $wpdb;

            $days = max(1, min(90, (int) $days));
            $now = isset($now) ? (int) $now : (int) current_time('timestamp');
            $bucket_labels = self::timing_buckets();
            $bucket_keys = array_keys($bucket_labels);
            $metrics = array();
            $included_days = array();

            foreach (self::timing_metrics() as $metric) {
                $metrics[$metric] = array(
                    'total'   => 0,
                    'buckets' => array_fill_keys($bucket_keys, 0),
                    'p10'     => '',
                    'median'  => '',
                );
            }
            for ($offset = $days - 1; $offset >= 0; --$offset) {
                $included_days[date('Ymd', $now - ($offset * DAY_IN_SECONDS))] = true;
            }

            $like = $wpdb->esc_like(self::timing_prefix()).'%';
            $rows = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like), ARRAY_A);
            foreach ((array) $rows as $row) {
                $suffix = substr((string) $row['option_name'], strlen(self::timing_prefix()));
                $parts = explode('__', $suffix, 3);
                if (count($parts) !== 3) {
                    continue;
                }
                list($day, $metric, $bucket) = $parts;
                if (!isset($included_days[$day], $metrics[$metric]['buckets'][$bucket])) {
                    continue;
                }
                $value = max(0, (int) $row['option_value']);
                $metrics[$metric]['buckets'][$bucket] += $value;
                $metrics[$metric]['total'] += $value;
            }
            foreach ($metrics as $metric => $data) {
                $metrics[$metric]['p10'] = self::percentile_bucket($data['buckets'], 0.10);
                $metrics[$metric]['median'] = self::percentile_bucket($data['buckets'], 0.50);
            }
            return array(
                'days'          => $days,
                'bucket_labels' => $bucket_labels,
                'metrics'       => $metrics,
            );
        }

        /**
         * Resolves one elapsed duration to a fixed privacy-preserving bucket.
         *
         * @since 261004.0528
         *
         * @param float $seconds Elapsed seconds.
         *
         * @return string Bucket key.
         */
        protected static function timing_bucket($seconds)
        {
            if ($seconds < 1) {
                return 'under_one';
            }
            if ($seconds < 2) {
                return 'one_to_two';
            }
            if ($seconds < 5) {
                return 'two_to_five';
            }
            if ($seconds < 10) {
                return 'five_to_ten';
            }
            if ($seconds < 30) {
                return 'ten_to_thirty';
            }
            if ($seconds < 60) {
                return 'thirty_to_sixty';
            }
            if ($seconds < 120) {
                return 'one_to_two_minutes';
            }
            return 'two_minutes_plus';
        }

        /**
         * Returns the bucket containing a requested percentile.
         *
         * @since 261004.0528
         *
         * @param array $buckets Ordered bucket counts.
         * @param float $percentile Fraction from 0 through 1.
         *
         * @return string Bucket key, or an empty string with no samples.
         */
        protected static function percentile_bucket($buckets, $percentile)
        {
            $total = array_sum((array) $buckets);
            if ($total < 1) {
                return '';
            }
            $target = max(1, (int) ceil($total * max(0, min(1, (float) $percentile))));
            $seen = 0;
            foreach ((array) $buckets as $bucket => $count) {
                $seen += max(0, (int) $count);
                if ($seen >= $target) {
                    return (string) $bucket;
                }
            }
            return '';
        }

        /**
         * Returns the private option-name prefix used for aggregate counters.
         *
         * @since 261003.0533
         *
         * @return string Option-name prefix.
         */
        public static function option_prefix()
        {
            return 'ws_plugin__s2member_pro_antispam_stat_';
        }

        /**
         * Returns the private option-name prefix used for aggregate timing buckets.
         *
         * @since 261004.0528
         *
         * @return string Option-name prefix.
         */
        public static function timing_prefix()
        {
            return 'ws_plugin__s2member_pro_antispam_timing_';
        }
    }
}
