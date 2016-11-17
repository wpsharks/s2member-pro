<?php
// @codingStandardsIgnoreFile
/**
 * s2Member Pro Remote Operations API.
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
 * @since 110713
 */
if (!defined('WPINC')) { // MUST have WordPress.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_remote_ops')) {
    /**
     * s2Member Pro Remote Operations API.
     *
     * @since 110713
     */
    class c_ws_plugin__s2member_pro_remote_ops
    {
        /**
         * Handles Remote Operation communications.
         *
         * @since 110713 Adding remote OPs.
         *
         * @attaches-to ``add_action('init');``
         */
        public static function remote_ops()
        {
            if (($op = self::get_remote_op())) {
                status_header(200); // Always.
                c_ws_plugin__s2member_no_cache::no_cache_constants(true);

                extract($op); // `[format, data]` elements.

                if ($format === 'serialized') {
                    header('Content-Type: text/plain; charset=utf-8');
                } elseif ($format === 'json') {
                    header('Content-Type: application/json; charset=utf-8');
                }
                while (@ob_end_clean()); // Clean output buffers.

                if (empty($data['api_key']) || $data['api_key'] !== self::remote_ops_key_gen()) {
                    if ($format === 'serialized') {
                        exit('Error: Invalid API key.');
                    } elseif ($format === 'json') {
                        exit(json_encode(array('error' => 'Invalid API key.')));
                    }
                } elseif (empty($data['op']) || !is_string($data['op'])) {
                    if ($format === 'serialized') {
                        exit('Error: Invalid operation.');
                    } elseif ($format === 'json') {
                        exit(json_encode(array('error' => 'Invalid operation.')));
                    }
                } elseif (is_callable('c_ws_plugin__s2member_pro_remote_ops_in::'.$data['op'])) {
                    $response = call_user_func('c_ws_plugin__s2member_pro_remote_ops_in::'.$data['op'], $data);

                    if ($format === 'serialized') { // Array indicates success.
                        exit(is_array($response) ? serialize($response) : 'Error: '.$response);
                    } elseif ($format === 'json') { // Array indicates success.
                        exit(is_array($response) ? json_encode($response) : json_encode(array('error' => $response)));
                    }
                }
            }
        }

        /**
         * Is remote OP?
         *
         * @since 110713 Adding remote OPs.
         *
         * @param string $check_op OP to check.
         *
         * @return bool True if remote OP.
         */
        public static function is_remote_op($check_op = '')
        {
            if (!($op = self::get_remote_op())) {
                return false; // Nope.
            }
            extract($op); // `[format, data]` elements.

            if (!empty($data['api_key']) && $data['api_key'] === self::remote_ops_key_gen()) {
                if (!empty($data['op']) && (!$check_op || $data['op'] === $check_op)) {
                    return true; // Is remote OP.
                }
            } // Otherwise return default.
            return false; // Default return value.
        }

        /**
         * Get Remote OP.
         *
         * @since 161110 Adding support for JSON I/O.
         *
         * @return array Remote OP `[format, data]`.
         */
        public static function get_remote_op()
        {
            static $remote_op; // Static cache.

            if ($remote_op !== null) {
                return $remote_op;
            }
            if (empty($_GET['s2member_pro_remote_op'])) {
                return $remote_op = array();
            } elseif (empty($_POST['s2member_pro_remote_op'])) {
                return $remote_op = array();
            }
            $op = trim(stripslashes((string) $_POST['s2member_pro_remote_op']));

            if (is_array($serialized_op = maybe_unserialize($op))) {
                $serialized_op    = c_ws_plugin__s2member_utils_strings::trim_deep($serialized_op);
                return $remote_op = array('format' => 'serialized', 'data' => $serialized_op);
                //
            } elseif (is_array($json_op = json_decode($op, true))) {
                $json_op          = c_ws_plugin__s2member_utils_strings::trim_deep($json_op);
                return $remote_op = array('format' => 'json', 'data' => $json_op);
            }
            return $remote_op = array('format' => 'serialized', 'data' => array());
        }

        /**
         * Generates an API Key, for Remote Operations.
         *
         * @since 110713 Adding remote OPs.
         *
         * @return string An API Key. It's an MD5 Hash, 32 chars, URL-safe.
         */
        public static function remote_ops_key_gen()
        {
            global $current_site, $current_blog;

            if ($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_remote_ops_key']) {
                $key = $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_remote_ops_key'];
                //
            } elseif (is_multisite() && !is_main_site()) { // Child blogs in a MS network get their own key.
                $key = md5(c_ws_plugin__s2member_utils_encryption::xencrypt($current_blog->domain.$current_blog->path, false, false));
            } else {
                $key = md5(c_ws_plugin__s2member_utils_encryption::xencrypt(preg_replace('/\:[0-9]+$/', '', $_SERVER['HTTP_HOST']), false, false));
            }
            return apply_filters('ws_plugin__s2member_pro_remote_ops_key', !empty($key) ? $key : '');
        }
    }
}
