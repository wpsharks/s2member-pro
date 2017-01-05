<?php
// @codingStandardsIgnoreFile
/**
 * s2Member Pro upgrader.
 *
 * Copyright: © 2009-2011
 * {@link http://websharks-inc.com/ WebSharks, Inc.}
 * (coded in the USA)
 *
 * This WordPress plugin (s2Member Pro) is comprised of two parts:
 *
 * o (1) Its PHP code is licensed under the GPL license, as is WordPress.
 * 	You should have received a copy of the GNU General Public License,
 * 	along with this software. In the main directory, see: /licensing/
 * 	If not, see: {@link http://www.gnu.org/licenses/}.
 *
 * o (2) All other parts of (s2Member Pro); including, but not limited to:
 * 	the CSS code, some JavaScript code, images, and design;
 * 	are licensed according to the license purchased.
 * 	See: {@link http://s2member.com/prices/}
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
 * @since 1.5
 */
if (!defined('WPINC')) { // MUST have.
    exit('Do not access this file directly.');
}
if (!class_exists('c_ws_plugin__s2member_pro_upgrader')) {
    /**
     * s2Member Pro upgrader.
     *
     * @since 1.5
     */
    class c_ws_plugin__s2member_pro_upgrader
    {
        /**
         * Upgrade error.
         *
         * @since 111027
         *
         * @type string
         */
        public static $error = '';

        /**
         * Filesystem credentials.
         *
         * @since 111027
         *
         * @type array
         */
        public static $credentials = array();

        /**
         * Upgrade wizard markup.
         *
         * @since 1.5 Adding pro upgrader.
         * @since 170105 Enhancing pro upgrader.
         *
         * @return string Wizard HTML markup.
         */
        public static function wizard()
        {
            if (!current_user_can('update_plugins')) {
                return ''; // Not applicable.
            }
            $error    = !empty(self::$error) ? (string) self::$error : '';
            $wp_error = $error ? new WP_Error('s2member_pro_upgrade_error', $error) : false;

            $stored = (array) get_transient(md5('ws_plugin__s2member_pro_upgrade_credentials'));
            $_p     = !empty($_POST) ? c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep((array) $_POST)) : array();

            $username = !empty($_p['ws_plugin__s2member_pro_upgrade_username']) ? (string) $_p['ws_plugin__s2member_pro_upgrade_username'] : '';
            $username = !$username && !empty($stored['username']) ? (string) $stored['username'] : $username;

            $password = !empty($_p['ws_plugin__s2member_pro_upgrade_password']) ? (string) $_p['ws_plugin__s2member_pro_upgrade_password'] : '';
            $password = !$password && !empty($stored['password']) ? (string) $stored['password'] : $password;

            $plugin_dir  = dirname(dirname(dirname(dirname(__FILE__)))); // Pro.
            $plugins_dir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));

            $credentials             = self::$credentials             = array(); // Reset & collect below.
            $credential_extra_fields = array('ws_plugin__s2member_pro_upgrade', 'ws_plugin__s2member_pro_upgrade_username', 'ws_plugin__s2member_pro_upgrade_password');

            ob_start(); // Buffer output, this generates a form for credentials, when necessary.
            if (is_array($credentials = request_filesystem_credentials($_SERVER['REQUEST_URI'], false, $wp_error, $plugins_dir, $credential_extra_fields))) {
                self::$credentials = $credentials; // Pass to `WP_Filesystem()`.
            } // Collect the form in case it is needed below.
            $credentials_form = ob_get_clean();

            if (!empty($_p['ws_plugin__s2member_pro_upgrade']) && $error && strpos($error, '#0004') !== false && $credentials_form) {
                $wizard = '<div class="error fade">'."\n";
                $wizard .= '<p>Your <a href="http://s2member.com/" target="_blank">s2Member Pro Add-on</a> must be updated to v'.esc_html(WS_PLUGIN__S2MEMBER_MIN_PRO_VERSION).'.<br />Please log in at <a href="http://s2member.com/" target="_blank" rel="external">s2Member.com</a> for access to the latest version.</p>'."\n";
                $wizard .= '</div>'."\n";
                $wizard .= $credentials_form."\n";
                //
            } else { // Otherwise, default handling.
                $wizard = '<div class="error fade">'."\n";
                $wizard .= '<p>Your <a href="http://s2member.com/" target="_blank">s2Member Pro Add-on</a> must be updated to v'.esc_html(WS_PLUGIN__S2MEMBER_MIN_PRO_VERSION).'.<br />Please log in at <a href="http://s2member.com/" target="_blank" rel="external">s2Member.com</a> for access to the latest version.</p>'."\n";

                $wizard .= '<form method="post" action="'.esc_attr($_SERVER['REQUEST_URI']).'" style="margin: 5px 0 5px 0;" autocomplete="off">'."\n";
                $wizard .= '<p><strong>Or upgrade automatically using your s2Member.com username &amp; license key.</strong>.</p>'."\n";
                $wizard .= '<input type="hidden" name="ws_plugin__s2member_pro_upgrade" id="ws-plugin--s2member-pro-upgrade" value="'.esc_attr(wp_create_nonce('ws-plugin--s2member-pro-upgrade')).'" />'."\n";
                $wizard .= '<input type="text" placeholder="Username" autocomplete="new-password" name="ws_plugin__s2member_pro_upgrade_username" id="ws-plugin--s2member-pro-upgrade-username" value="'.esc_attr($username).'" />'."\n";
                $wizard .= '<input type="password" placeholder="License Key" autocomplete="new-password" name="ws_plugin__s2member_pro_upgrade_password" id="ws-plugin--s2member-pro-upgrade-password" value="'.esc_attr($password).'" />'."\n";
                $wizard .= '<input type="submit" class="button" id="ws-plugin--s2member-pro-upgrade-submit" value="Upgrade s2Member Pro Automatically" />'."\n";
                $wizard .= $error ? '<p><em>'.$error.'</em></p>'."\n" : '';
                $wizard .= '</form>'."\n";

                $wizard .= '</div>';
            }
            return $wizard;
        }

        /**
         * Upgrade processor.
         *
         * @since 1.5 Adding pro upgrader.
         * @since 170105 Enhancing pro upgrader.
         *
         * @attaches-to `add_action('admin_init');`
         */
        public static function upgrade()
        {
            global $wp_filesystem;

            if (!current_user_can('update_plugins')) {
                return; // Not applicable.
            } elseif (empty($_POST['ws_plugin__s2member_pro_upgrade'])) {
                return; // Not applicable.
            } elseif (!($nonce = (string) $_POST['ws_plugin__s2member_pro_upgrade'])) {
                return; // Not applicable.
            } elseif (!wp_verify_nonce($nonce, 'ws-plugin--s2member-pro-upgrade')) {
                return; // Not applicable.
            }
            @set_time_limit(0); // No time limit during upgrade.
            $admin_memory_limit = apply_filters('admin_memory_limit', WP_MAX_MEMORY_LIMIT);
            @ini_set('memory_limit', $admin_memory_limit); // Attempt to maximize.

            $memory_limit             = @ini_get('memory_limit');
            $memory_limit_bytes       = self::abbr_bytes($memory_limit);
            $admin_memory_limit_bytes = self::abbr_bytes($admin_memory_limit);

            if ($memory_limit_bytes && $admin_memory_limit_bytes && $memory_limit_bytes < $admin_memory_limit_bytes) {
                self::$error = 'Upgrade failed. Error #0001. Not enough memory. Unzipping s2Member Pro via WordPress requires plenty of RAM. Please upgrade via FTP instead.';
                return; // Nothing more we can do here.
            }
            $stored = (array) get_transient(md5('ws_plugin__s2member_pro_upgrade_credentials'));
            $_p     = !empty($_POST) ? c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep((array) $_POST)) : array();

            $username = !empty($_p['ws_plugin__s2member_pro_upgrade_username']) ? (string) $_p['ws_plugin__s2member_pro_upgrade_username'] : '';
            $username = !$username && !empty($stored['username']) ? (string) $stored['username'] : $username;

            $password = !empty($_p['ws_plugin__s2member_pro_upgrade_password']) ? (string) $_p['ws_plugin__s2member_pro_upgrade_password'] : '';
            $password = !$password && !empty($stored['password']) ? (string) $stored['password'] : $password;

            if (!$username || !$password) { // Both of these are absolutely required to complete the upgrade.
                self::$error = 'Upgrade failed. Error #0002. Empty username or license key. Please try again.';
                return; // Nothing more we can do here.
            }
            $latest_query_vars = array('product_api' => array(
                'action'   => 'latest_pro_update',
                'username' => $username, 'password' => $password,
            ));
            $latest_request_url = 'https://s2member.com/'; // Always over `https://` at main site.
            $latest_request_url = add_query_arg(urlencode_deep($latest_query_vars), $latest_request_url);
            $latest             = json_decode(c_ws_plugin__s2member_utils_urls::remote($latest_request_url), true);

            if (empty($latest['pro_zip']) || empty($latest['pro_version'])) {
                self::$error = 'Upgrade failed. Error #0003. Invalid username or license key. Please try again.';
                return; // Nothing more we can do here.
            }
            set_transient(md5('ws_plugin__s2member_pro_upgrade_credentials'), compact('username', 'password'), 5184000);

            $plugin_dir  = dirname(dirname(dirname(dirname(__FILE__)))); // Pro.
            $plugins_dir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));

            ob_start(); // Buffer output, this generates a form that is not necessary here.
            if (is_array($credentials = request_filesystem_credentials($_SERVER['REQUEST_URI'], false, false, $plugins_dir))) {
                self::$credentials = $credentials; // Pass to `WP_Filesystem()`.
            } // The output from this call is not needed here.
            ob_end_clean(); // End & clean only.

            self::maintenance_mode(true); // Enter maintenance mode.

            if (!WP_Filesystem(self::$credentials, $plugins_dir)
                    || !($fs_plugins_dir = rtrim($wp_filesystem->find_folder($plugins_dir), '/'))
                    || !($fs_plugin_dir = rtrim($wp_filesystem->find_folder($plugin_dir), '/'))) {
                self::$error = 'Upgrade failed. Error #0004. Please upgrade via FTP or supply valid filesystem credentials.';
                self::maintenance_mode(false); // Exit maintenance mode.
                return; // Nothing more we can do here.
            }
            $tmp_zip              = wp_unique_filename($plugins_dir, basename($plugin_dir).'.zip');
            $fs_tmp_zip           = $fs_plugins_dir.'/'.$tmp_zip;
            $tmp_zip              = $plugins_dir.'/'.$tmp_zip;

            $zip_file_contents = c_ws_plugin__s2member_utils_urls::remote($latest['pro_zip'], false, array('timeout' => 120));

            if (!$zip_file_contents || !$wp_filesystem->put_contents($fs_tmp_zip, $zip_file_contents, FS_CHMOD_FILE)) {
                self::$error = 'Upgrade failed. Error #0005. Unable to acquire latest pro version via https://. Please upgrade via FTP.';
                $wp_filesystem->delete($fs_tmp_zip);
                $wp_filesystem->delete($fs_plugin_dir.'-new', true);
                self::maintenance_mode(false);
                return;
            }
            if ($wp_filesystem->is_dir($fs_plugin_dir.'-new') && !$wp_filesystem->delete($fs_plugin_dir.'-new', true)) {
                self::$error = 'Upgrade failed. Error #0006. Unable to delete old temp plugin directory. Please upgrade via FTP. ';
                $wp_filesystem->delete($fs_tmp_zip);
                $wp_filesystem->delete($fs_plugin_dir.'-new', true);
                self::maintenance_mode(false);
                return;
            }
            if (!$wp_filesystem->mkdir($fs_plugin_dir.'-new', FS_CHMOD_DIR)) {
                self::$error = 'Upgrade failed. Error #0007. Unable to create temporary plugin directory. Please upgrade via FTP. ';
                $wp_filesystem->delete($fs_tmp_zip);
                $wp_filesystem->delete($fs_plugin_dir.'-new', true);
                self::maintenance_mode(false);
                return;
            }
            if (is_wp_error($unzip = unzip_file($tmp_zip, $plugin_dir.'-new'))) {
                $wp_filesystem->delete($fs_tmp_zip);
                $wp_filesystem->delete($fs_plugin_dir.'-new', true);
                self::$error = 'Upgrade failed. Error #0008. Failed to unzip new version. '.esc_html($unzip->get_error_message());
                self::maintenance_mode(false);
                return;
            }
            if ($wp_filesystem->is_dir($fs_plugin_dir) && !$wp_filesystem->delete($fs_plugin_dir, true)) {
                $wp_filesystem->delete($fs_tmp_zip);
                $wp_filesystem->delete($fs_plugin_dir.'-new', true);
                self::$error = 'Upgrade failed. Error #0009. Cannot delete existing pro add-on directory. Please upgrade via FTP.';
                self::maintenance_mode(false); // Stay in maintenance mode.
                return;
            }
            if (!$wp_filesystem->move($fs_plugin_dir.'-new/s2member-pro', $fs_plugin_dir)) {
                $wp_filesystem->delete($fs_tmp_zip);
                $wp_filesystem->delete($plugin_dir.'-new', true);
                self::$error = 'Upgrade failed. Unable to extract. Error #0010. Please upgrade via FTP.';
                self::maintenance_mode(false); // Stay in maintenance mode.
                return;
            }
            $wp_filesystem->delete($fs_tmp_zip);
            $wp_filesystem->delete($fs_plugin_dir.'-new', true);

            $notice = 's2Member Pro successfully updated to v'.esc_html($latest['pro_version']).'.';
            c_ws_plugin__s2member_admin_notices::enqueue_admin_notice($notice, 'blog|network:*');
            self::maintenance_mode(false); // Exit maintenance mode now.

            do_action('ws_plugin__s2member_pro_during_successfull_upgrade', get_defined_vars());

            wp_redirect(self_admin_url('/plugins.php')).exit();
        }

        /**
         * Maintenance mode.
         *
         * @since 170105 Enhancing pro upgrader.
         *
         * @param bool $enable Enable or disable; i.e., true|false.
         */
        public static function maintenance_mode($enable = true)
        {
            global $wp_filesystem; // Need this below.

            if (apply_filters('ws_plugin__s2member_pro_upgrade_maintenance', true) && WP_Filesystem(self::$credentials, ABSPATH)) {
                $dot_maintenance = $wp_filesystem->abspath().'.maintenance'; // Remote filesystem path.

                $wp_filesystem->delete($dot_maintenance); // In case it exists.
                if ($enable) { // If enabling maintenance mode, update file with a fresh timestamp.
                    $wp_filesystem->put_contents($dot_maintenance, '<?php $upgrading = '.time().'; ?>', FS_CHMOD_FILE);
                }
            }
        }

        /**
         * Converts an abbreviated byte notation into bytes.
         *
         * @since 130819 Enhancing pro upgrader.
         *
         * @param string $string A string value in byte notation.
         *
         * @return float A float indicating the number of bytes.
         */
        public static function abbr_bytes($string)
        {
            $string = (string) $string;

            $notation = '/^(?P<value>[0-9\.]+)\s*(?P<modifier>bytes|byte|kbs|kb|k|mb|m|gb|g|tb|t)$/i';

            if (!preg_match($notation, $string, $_op)) {
                return (float) 0;
            }

            $value    = (float) $_op['value'];
            $modifier = strtolower($_op['modifier']);
            unset($_op); // Housekeeping.

                switch ($modifier) { // Fall throughs.
                    case 't': // Multiplied four times.
                    case 'tb':
                            $value *= 1024;
                    case 'g': // Multiplied three times.
                    case 'gb':
                            $value *= 1024;
                    case 'm': // Multiple two times.
                    case 'mb':
                            $value *= 1024;
                    case 'k': // One time only.
                    case 'kb':
                    case 'kbs':
                            $value *= 1024;
                }
            return (float) $value;
        }
    }
}
