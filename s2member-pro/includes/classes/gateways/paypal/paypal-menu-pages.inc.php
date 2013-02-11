<?php
/**
* PayPal® Menu Pages.
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
* @package s2Member\Menu_Pages
* @since 1.5
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_paypal_menu_pages"))
	{
		/**
		* PayPal® Menu Pages.
		*
		* @package s2Member\Menu_Pages
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_paypal_menu_pages
			{
				/**
				* Add the pages for this Payment Gateway.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_filter("ws_plugin__s2member_during_add_admin_options_add_paypal_buttons_page");``
				*
				* @param bool $add_paypal_buttons_page Expects a boolean value, passed through by the Filter.
				* @param array $vars Expects an array of defined variables passed through by the Filter.
				* @return bool Passes back the original value of ``$add_paypal_buttons_page``.
				*/
				public static function paypal_admin_options ($add_paypal_buttons_page = TRUE, $vars = FALSE)
					{
						add_submenu_page ($vars["menu"], "s2Member Pro / PayPal® Pro Forms", "PayPal® Pro-Forms", "create_users", "ws-plugin--s2member-pro-paypal-forms", "c_ws_plugin__s2member_pro_paypal_menu_pages::paypal_forms_page");

						return $add_paypal_buttons_page; // Now add the PayPal® Buttons.
					}
				/**
				* Builds PayPal® Pro option details into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_during_paypal_account_details");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_details ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-details.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds PayPal® Pro option detail rows into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_during_paypal_account_detail_rows");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_detail_rows ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-detail-rows.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds PayPal® Pro Sandbox tip into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_during_paypal_account_details_after_sandbox_tip");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_sandbox_tip ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-sandbox-tip.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds PayPal® Pro IPN tip into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_during_paypal_ipn_after_quick_tip");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_ipn_tip ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-ipn-tip.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds PayPal® Pro PDT tip into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_during_paypal_pdt_after_quick_tip");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_pdt_tip ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-pdt-tip.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds the PayPal® Pro Signup Confirmation Email into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_after_signup_confirmation_email");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_signup_email ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-signup-email.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds the PayPal® Pro Specific Post/Page Confirmation Email into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_after_sp_confirmation_email");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_sp_email ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-sp-email.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds the PayPal® Pro Tax Configuration into s2Member.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_paypal_ops_page_during_left_sections_after_sp_confirmation_email");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_ops_page_tax_rates ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-ops-tax-rates.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds the documentation for Scripting / API Constants related to this Payment Gateway.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_scripting_page_during_left_sections_during_list_of_api_constants");``
				*
				* @param array $vars Expects an array of defined variables passed through by the Action Hook.
				* @return null
				*/
				public static function paypal_scripting_page_api_constants ($vars = FALSE)
					{
						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-s-api-c.inc.php";

						return /* Return for uniformity. */;
					}
				/**
				* Builds the Pro Forms page for this Payment Gateway.
				*
				* @package s2Member\Menu_Pages
				* @since 1.5
				*
				* @return null
				*/
				public static function paypal_forms_page ()
					{
						if (c_ws_plugin__s2member_pro_paypal_responses::paypal_form_api_validation_errors ()) // Report error if PayPal® Options are not yet configured.
							c_ws_plugin__s2member_admin_notices::display_admin_notice ('Please configure <code>s2Member -› PayPal® Options</code> first. Once all of your PayPal® Options are configured; including your Email Address, API Username, Password, and Signature; return to this page &amp; generate your PayPal® Pro Form(s).<br /><br />*NOTE: If you\'re ONLY planning to use Free Registration Forms (e.g. you\'re NOT going to accept payments), you can safely ignore this warning and continue to use the Free Registration Form shortcode provided below.', true);

						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/menu-pages/paypal-forms.inc.php";

						return /* Return for uniformity. */;
					}
			}
	}
?>