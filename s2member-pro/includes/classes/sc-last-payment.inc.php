<?php
/**
 * Shortcode `[s2LastPayment /]`.
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
    exit ('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_sc_last_payment'))
{
    /**
     * Shortcode `[s2LastPayment /]`.
     *
     * @package s2Member\s2LastPayment
     * @since 160328
     */
    class c_ws_plugin__s2member_sc_last_payment
    {
        /**
         * Handles the Shortcode for: `[s2LastPayment /]`.
         *
         * @package s2Member\s2LastPayment
         * @since 160120
         *
         * @attaches-to ``add_shortcode('s2LastPayment');``
         *
         * @param array  $attr An array of Attributes.
         * @param string $content Content inside the Shortcode.
         * @param string $shortcode The actual Shortcode name itself.
         *
         * @return string Return-value of inner routine.
         */
        public static function sc_last_payment_details($attr = array(), $content = '', $shortcode = '')
        {
            return c_ws_plugin__s2member_sc_last_payment_in::sc_last_payment_details($attr, $content, $shortcode);
        }
    }
}
