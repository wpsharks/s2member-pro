<?php
/**
 * Shortcode `[s2LastPayment /]` (inner processing routines).
 *
 * Copyright: © 2009-2011
 * {@link http://websharks-inc.com/ WebSharks, Inc.}
 * (coded in the USA)
 *
 * Released under the terms of the GNU General Public License.
 * You should have received a copy of the GNU General Public License,
 * along with this software. In the main directory, see: /licensing/
 * If not, see: {@link http://www.gnu.org/licenses/}.
 *
 * @package s2Member\s2LastPayment
 * @since 160328
 */
if(!defined('WPINC')) // MUST have WordPress.
    exit('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_sc_last_payment_in'))
{
    /**
     * Shortcode `[s2LastPayment /]` (inner processing routines).
     *
     * @package s2Member\s2LastPayment
     * @since 160328
     */
    class c_ws_plugin__s2member_sc_last_payment_in
    {
        /**
         * Handles the Shortcode for: `[s2LastPayment /]`.
         *
         * @package s2Member\s2LastPayment
         * @since 160328
         *
         * @attaches-to ``add_shortcode('s2LastPayment');``
         *
         * @param array  $attr An array of Attributes.
         * @param string $content Content inside the Shortcode.
         * @param string $shortcode The actual Shortcode name itself.
         *
         * @return string Value of the requested data.
         */
        public static function sc_last_payment_details($attr = array(), $content = '', $shortcode = '')
        {
            if(empty($attr['user_id']) || !(integer)$attr['user_id'])
                $attr['user_id'] = $user_id = get_current_user_id();
            else $user_id = (integer)$attr['user_id'];

            $last_payment_time = get_user_option('s2member_last_payment_time', $user_id);
            // This returns a Unix Timestamp (UTC).

            $attr = shortcode_atts( // Attributes.
                array(
                    'user_id'              => '0', // Current.
                    'show'                 => 'time', // Current.
                    'format'               => 'M jS, Y, g:i a T',
                    'timezone'             => '', // Default timezone; i.e., GMT/UTC.
                ),
                c_ws_plugin__s2member_utils_strings::trim_qts_deep((array)$attr)
            );

            // Initialize Last Payment details/output date format.

            $time = null; // Initialize the time calculation.
            if($last_payment_time) // // Do we have a time to work with?
                {
                    $time = new DateTime(date('Y-m-d H:i:s', $last_payment['time']));
                    if($attr['timezone'] && strtoupper($attr['timezone']) !== 'UTC')
                        $time->setTimezone(new DateTimeZone($attr['timezone']));
                }
            if($time && $attr['format'] === 'timestamp')
                $date = (string)$time->getTimestamp();

            else if($time && $attr['format'] === 'default')
                $date = $time->format(get_option('format'));

            else if($time && $attr['format'])
                $date = $time->format($attr['format']);

            else if($time) // Default date/time format.
                $date = $time->format('M jS, Y, g:i a T');

            else $date = ''; // Default date; i.e., nothing.

            $details = str_ireplace('%%date%%', esc_html($date), $details);

            // Return the details/output from this shortcode.

            return apply_filters('ws_plugin__s2member_sc_last_payment_details', $details, get_defined_vars());
        }
    }
}
