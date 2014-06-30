<?php
/**
 * Stripe Silent Post *(aka: IPN)* (inner processing routines).
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
 * sub-license, sell, resell, or provide for free; part (2) of the s2Member Pro Module;
 * or make an offer to do any of these things. All of these things are strictly
 * prohibited with part (2) of the s2Member Pro Module.
 *
 * Your purchase of s2Member Pro includes free lifetime upgrades via s2Member.com
 * (i.e. new features, bug fixes, updates, improvements); along with full access
 * to our video tutorial library: {@link http://www.s2member.com/videos/}
 *
 * @package s2Member\Stripe
 * @since 140617
 */
if(realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']))
	exit ('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_stripe_notify_in'))
{
	/**
	 * Stripe Silent Post *(aka: IPN)* (inner processing routines).
	 *
	 * @package s2Member\Stripe
	 * @since 140617
	 */
	class c_ws_plugin__s2member_pro_stripe_notify_in
	{
		/**
		 * Handles Stripe Webhook/IPN event processing.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @attaches-to ``add_action('init');``
		 */
		public static function stripe_notify()
		{
			global $current_site, $current_blog;

			if(!empty($_GET['s2member_pro_stripe_notify']) && $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key'])
			{
				$stripe = array(); // Initialize array of Webhook/IPN event data and s2Member log details.
				@ignore_user_abort(TRUE); // Continue processing even if/when connection is broken by the sender.

				if(is_object($event = c_ws_plugin__s2member_pro_stripe_utilities::get_event()) && ($stripe['event'] = $event))
				{
					if(empty($processed)) $stripe['s2member_log'][] = 'Ignoring this Webhook/IPN. The event does NOT require any action on the part of s2Member.';
				}
				else // Extensive log reporting here. This is an area where many site owners find trouble. Depending on server configuration; remote HTTPS connections may fail.
				{
					$stripe['s2member_log'][] = 'Unable to verify Webhook/IPN event ID. This is most likely related to an invalid Stripe configuration. Please check: s2Member -› Stripe Options.';
					$stripe['s2member_log'][] = 'If you\'re absolutely SURE that your Stripe configuration is valid, you may want to run some tests on your server, just to be sure \$_POST variables (and php://input) are populated; and that your server is able to connect to Stripe over an HTTPS connection.';
					$stripe['s2member_log'][] = 's2Member uses the Stripe SDK for remote connections; which relies upon the cURL extension for PHP. Please make sure that your installation of PHP has the cURL extension; and that it\'s configured together with OpenSSL for HTTPS communication.';
					$stripe['s2member_log'][] = var_export($_REQUEST, TRUE)."\n".var_export(json_decode(@file_get_contents('php://input')), TRUE);
				}
				$logt = c_ws_plugin__s2member_utilities::time_details();
				$logv = c_ws_plugin__s2member_utilities::ver_details();
				$logm = c_ws_plugin__s2member_utilities::mem_details();
				$log4 = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n".'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
				$log4 = (is_multisite() && !is_main_site()) ? ($_log4 = $current_blog->domain.$current_blog->path)."\n".$log4 : $log4;
				$log2 = (is_multisite() && !is_main_site()) ? 'stripe-ipn-4-'.trim(preg_replace('/[^a-z0-9]/i', '-', (!empty($_log4) ? $_log4 : '')), '-').'.log' : 'stripe-ipn.log';

				if($GLOBALS['WS_PLUGIN__']['s2member']['o']['gateway_debug_logs'])
					if(is_dir($logs_dir = $GLOBALS['WS_PLUGIN__']['s2member']['c']['logs_dir']))
						if(is_writable($logs_dir) && c_ws_plugin__s2member_utils_logs::archive_oversize_log_files())
							file_put_contents($logs_dir.'/'.$log2,
							                  'LOG ENTRY: '.$logt."\n".$logv."\n".$logm."\n".$log4."\n".
							                  c_ws_plugin__s2member_utils_logs::conceal_private_info(var_export($stripe, TRUE))."\n\n",
							                  FILE_APPEND);

				status_header(200); // Send a 200 OK status header.
				header('Content-Type: text/plain; charset=UTF-8'); // Content-Type text/plain with UTF-8.
				while(@ob_end_clean()) ; // Clean any existing output buffers.

				exit(); // Exit now.
			}
		}
	}
}