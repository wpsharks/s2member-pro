<?php
/**
* Core API Functions *(for site owners)*.
*
* Copyright: © 2009-2011
* {@link http://www.websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* This WordPress® plugin (s2Member Pro) is comprised of two parts:
*
* o (1) Its PHP code is licensed under the GPL license, as is WordPress®.
* 	You should have received a copy of the GNU General Public License,
* 	along with this software. In the main directory, see: /licensing/
* 	If not, see: {@link http://www.gnu.org/licenses/}.
*
* o (2) All other parts of (s2Member Pro); including, but not limited to:
* 	the CSS code, some JavaScript code, images, and design;
* 	are licensed according to the license purchased.
* 	See: {@link http://www.s2member.com/prices/}
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
* @package s2Member\API_Functions
* @since 1.0
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
/**
* Allows for the inclusion of the Pro Login Widget directly into a theme/plugin file.
*
* This function will return the HTML output from the widget function call.
* 	Example usage: ``<!php echo s2member_pro_login_widget(); !>``
*
* The ``$options`` parameter (array) is completely optional *(i.e. NOT required)*.
* It can be passed in as an array of options; overriding some or all of these defaults:
*
* 	o ``"title" => "Membership Login"``
* 	Title when NOT logged in, or leave this blank if you'd prefer not to show a title.
*
* 	o ``"signup_url" => "%%automatic%%"``
* 	Full Signup URL, or use `%%automatic%%` for the Membership Options Page. If you leave this blank, it will not be shown.
*
* 	o ``"login_redirect" => ""``
* 	Empty ( i.e. `""` ) = Login Welcome Page, `%%previous%%` = Previous Page, `%%home%%` = Home Page, or use a full URL of your own.
*
* 	o ``"logged_out_code" => ""``
* 	HTML/PHP code to display when logged out. May also contain WP Shortcodes if you like.
*
* 	o ``"profile_title" => "My Profile Summary"``
* 	Title when a User is logged in. Or you can leave this blank if you'd prefer not to show a title.
*
* 	o ``"display_gravatar" => "1"``
* 	Display Gravatar image when logged in? `1` = yes, `0` = no. Gravatars are based on email address.
*
* 	o ``"link_gravatar" => "1"``
* 	Link Gravatar image to Gravatar.com? `1` = yes, `0` = no. Allows Users to setup a Gravatar.
*
* 	o ``"display_name" => "1"``
* 	Display the current User's WordPress® "Display Name" when logged in? `1` = yes, `0` = no.
*
* 	o ``"logged_in_code" => ""``
* 	HTML/PHP code to display when logged in. May also contain WP Shortcodes if you like.
*
* 	o ``"logout_redirect" => "%%home%%"``
* 	Empty ( i.e. `""` ) = Login Screen, `%%previous%%` = Previous Page, `%%home%%` = Home Page, or use a full URL of your own.
*
* 	o ``"my_account_url" => "%%automatic%%"``
* 	Full URL of your own, or use `%%automatic%%` for the Login Welcome Page. Leave empty to not show this at all.
*
* 	o ``"my_profile_url" => "%%automatic%%"``
* 	Full URL of your own, or use `%%automatic%%` for a JavaScript popup. Leave empty to not show this at all.
*
* The ``$args`` parameter (array) is also completely optional *(i.e. NOT required)*.
* It can be passed in as an array of options: overriding some or all of these defaults:
*
* 	o ``"before_widget" => ""``
* 	HTML code to display before the widget.
*
* 	o ``"before_title" => "<h3>"``
* 	HTML code to display before the title.
*
* 	o ``"after_title" => "</h3>"``
* 	HTML code to display after the title.
*
* 	o ``"after_widget" => ""``
* 	HTML code to display after the widget.
*
* @package s2Member\API_Functions
* @since 1.5
*
* @param array $options Optional. See function description for details.
* @param array $args Optional. See function description for details.
* @return str The Pro Login Widget, HTML markup.
*/
if (!function_exists ("s2member_pro_login_widget"))
	{
		function s2member_pro_login_widget ($options = FALSE, $args = FALSE)
			{
				$args = (is_array ($args)) ? $args : array ("before_widget" => "", "before_title" => "<h3>", "after_title" => "</h3>", "after_widget" => "");

				ob_start(); // Begin output buffering.

				c_ws_plugin__s2member_pro_login_widget::widget($args, $options);

				return ob_get_clean();
			}
	}
/**
* Pulls an array of details from the PayPal® Pro API; related to a customer's Recurring Billing Profile.
*
* This function will return an array of data (as described below); else an empty array if no Recurring Billing Profile exists.
* 	Example usage: ``<!php print_r(s2member_pro_paypal_rbp_for_user(123)); !>``
*
* Array elements returned by this function correlate with the PayPal® Pro API call method: `GetRecurringPaymentsProfileDetails`.
* 	Please see {@link https://www.x.com/developers/paypal/documentation-tools/api/getrecurringpaymentsprofiledetails-api-operation-nvp}.
*
* @package s2Member\API_Functions
* @since 130405
*
* @param str|int $user_id Optional. A specific User ID. Defaults to the current User ID that is logged into the site.
* @return array An array of data (from the PayPal® Pro API); else an empty array if no Recurring Billing Profile exists.
*
* 	Array elements returned by this function correlate with the PayPal® Pro API call method: `GetRecurringPaymentsProfileDetails`.
* 	Please see {@link https://www.x.com/developers/paypal/documentation-tools/api/getrecurringpaymentsprofiledetails-api-operation-nvp}.
*
* @note If your PayPal® Pro account uses the Payflow™ Edition API, please use {@link s2member_pro_payflow_rbp_for_user()} instead.
*/
if (!function_exists ("s2member_pro_paypal_rbp_for_user"))
	{
		function s2member_pro_paypal_rbp_for_user ($user_id = FALSE)
			{
				$user_id = (integer)$user_id;
				$user_id = ($user_id) ? $user_id : get_current_user_id();
				if(!$user_id) return array();
				
				$user_subscr_id = get_user_option ("s2member_subscr_id", $user_id);
				if(!$user_subscr_id) return array();
				
				$paypal["METHOD"] = "GetRecurringPaymentsProfileDetails";
				$paypal["PROFILEID"] = $user_subscr_id;
				
				if (is_array($paypal = c_ws_plugin__s2member_paypal_utilities::paypal_api_response ($paypal)) && empty($paypal["__error"]))
					return $paypal;
				
				return array();
			}
	}
/**
* Pulls last/next billing times from the PayPal® Pro API; associated with a customer's Recurring Billing Profile.
*
* This function will return an array of data (as described below); else an empty array if no Recurring Billing Profile exists.
* 	Example usage: ``<!php print_r(s2member_pro_paypal_rbp_times_for_user(123)); !>``
*
* Array elements returned by this function include: `last_billing_time`, `next_billing_time` (both as UTC Unix timestamps).
*
* @package s2Member\API_Functions
* @since 130405
*
* @param str|int $user_id Optional. A specific User ID. Defaults to the current User ID that is logged into the site.
* @return array Array elements: `last_billing_time`, `next_billing_time` (both as UTC Unix timestamps);
* 	else an empty array if no Recurring Billing Profile exists.
*
* If one or more times (e.g. `last_billing_time`, `next_billing_time`) are irrelevant (i.e. there was no payment received yet; or there are no future payments to receive);
* 	that time will default to a value of `0` indicating it's irrelevant and/or not applicable.
*
* @note If your PayPal® Pro account uses the Payflow™ Edition API, please use {@link s2member_pro_payflow_rbp_times_for_user()} instead.
*/
if (!function_exists ("s2member_pro_paypal_rbp_times_for_user"))
	{
		function s2member_pro_paypal_rbp_times_for_user ($user_id = FALSE)
			{
				if(!($paypal = s2member_pro_paypal_rbp_for_user($user_id)))
					return array();
					
				$array = array("last_billing_time" => 0, "next_billing_time" => 0);
				
				if(preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}/", $paypal["LASTPAYMENTDATE"]))
					$array["last_billing_time"] = strtotime($paypal["LASTPAYMENTDATE"]);
				
				if(($paypal["TOTALBILLINGCYCLES"] === "0" || $paypal["NUMCYCLESREMAINING"] > 0) && preg_match ("/^(Active|ActiveProfile)$/i", $paypal["STATUS"]))
					if(preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}/", $paypal["NEXTBILLINGDATE"]))
						$array["next_billing_time"] = strtotime($paypal["NEXTBILLINGDATE"]);

				return $array;
			}
	}
/**
* Pulls an array of details from PayPal® Pro (Payflow™ Edition) API; related to a customer's Recurring Billing Profile.
*
* This function will return an array of data (as described below); else an empty array if no Recurring Billing Profile exists.
* 	Example usage: ``<!php print_r(s2member_pro_payflow_rbp_for_user(123)); !>``
*
* Array elements returned by this function correlate with the PayPal® Pro (Payflow™ Edition) API call method: `ACTION=I`.
* 	Please see {@link https://www.paypalobjects.com/webstatic/en_US/developer/docs/pdf/wpppe_rp_guide.pdf#page=54}.
*
* @package s2Member\API_Functions
* @since 130405
*
* @param str|int $user_id Optional. A specific User ID. Defaults to the current User ID that is logged into the site.
* @return array An array of data from the PayPal® Pro (Payflow™ Edition) API; else an empty array if no Recurring Billing Profile exists.
*
* 	Array elements returned by this function correlate with the PayPal® Pro (Payflow™ Edition) API call method: `ACTION=I`.
* 	Please see {@link https://www.paypalobjects.com/webstatic/en_US/developer/docs/pdf/wpppe_rp_guide.pdf#page=54}.
*/
if (!function_exists ("s2member_pro_payflow_rbp_for_user"))
	{
		function s2member_pro_payflow_rbp_for_user ($user_id = FALSE)
			{
				$user_id = (integer)$user_id;
				$user_id = ($user_id) ? $user_id : get_current_user_id();
				if(!$user_id) return array();
				
				$user_subscr_id = get_user_option ("s2member_subscr_id", $user_id);
				if(!$user_subscr_id) return array();
				
				if(!class_exists("c_ws_plugin__s2member_pro_paypal_utilities"))
					return array();
				
				if (is_array($payflow = c_ws_plugin__s2member_pro_paypal_utilities::payflow_get_profile ($user_subscr_id)))
					return $payflow;
				
				return array();
			}
	}
/**
* Pulls last/next billing times from the PayPal® Pro (Payflow™ Edition) API; associated with a customer's Recurring Billing Profile.
*
* This function will return an array of data (as described below); else an empty array if no Recurring Billing Profile exists.
* 	Example usage: ``<!php print_r(s2member_pro_payflow_rbp_times_for_user(123)); !>``
*
* Array elements returned by this function include: `last_billing_time`, `next_billing_time` (both as UTC Unix timestamps).
*
* @package s2Member\API_Functions
* @since 130405
*
* @param str|int $user_id Optional. A specific User ID. Defaults to the current User ID that is logged into the site.
* @return array Array elements: `last_billing_time`, `next_billing_time` (both as UTC Unix timestamps);
* 	else an empty array if no Recurring Billing Profile exists.
*
* If one or more times (e.g. `last_billing_time`, `next_billing_time`) are irrelevant (i.e. there was no payment received yet; or there are no future payments to receive);
* 	that time will default to a value of `0` indicating it's irrelevant and/or not applicable.
*/
if (!function_exists ("s2member_pro_payflow_rbp_times_for_user"))
	{
		function s2member_pro_payflow_rbp_times_for_user ($user_id = FALSE)
			{
				if(!($payflow = s2member_pro_payflow_rbp_for_user($user_id)))
					return array();
					
				$array = array("last_billing_time" => 0, "next_billing_time" => 0);
				
				if(($last_billing_time = get_user_option("s2member_last_payment_time", $user_id)))
					$array["last_billing_time"] = $last_billing_time; // Must use this because the PayFlow® API does not offer it up.
				
				if(($payflow["TERM"] === "0" || $payflow["PAYMENTSLEFT"] > 0) && preg_match ("/^(Active|ActiveProfile)$/i", $payflow["STATUS"]))
					if(preg_match("/^[0-9]{8}/", $payflow["NEXTPAYMENT"]))
						$array["next_billing_time"] = strtotime($payflow["NEXTPAYMENT"]);

				return $array;
			}
	}
?>