<?php
// @codingStandardsIgnoreFile
/**
 * Menu page for s2Member Pro (PayPal options, Payflow).
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
 * @package s2Member\Menu_Pages
 * @since 1.5
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit ("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_menu_page_paypal_ops_payflow"))
{
	/**
	 * Menu page for s2Member Pro (PayPal options, Tax Rates).
	 *
	 * @package s2Member\Menu_Pages
	 * @since 110531
	 */
	class c_ws_plugin__s2member_pro_menu_page_paypal_ops_payflow
	{
		/**
		 * @attaches-to ``add_action('ws_plugin__s2member_during_paypal_ops_page_during_left_sections_after_paypal_account_details');``
		 * @also-attaches-to ``add_action('s2x_during_payment_gateways_options_page_during_left_sections_after_paypal_account_details');``
		 */
		static public function render()
		{
			echo '<div class="ws-menu-page-group" title="Payflow Account Details">'."\n";

			echo '<div class="ws-menu-page-section ws-plugin--s2member-paypal-payflow-account-details-section">'."\n";
			echo '<a href="https://s2member.com/r/paypal/" target="_blank"><img src="'.esc_attr($GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["dir_url"]).'/src/images/paypal-logo.png" class="ws-menu-page-right" style="width:125px; height:125px; border:0;" alt="." /></a>'."\n";
			echo '<h3>Payflow Account Details (required, if using Payflow)</h3>'."\n";
			echo '<p>Newer PayPal Pro accounts (i.e., PayPal Pro w/ Payflow Edition), come with the Payflow API for Recurring Billing service. If you have a newer PayPal Pro account, and you wish to integrate PayPal\'s Recurring Billing service with s2Member Pro-Forms, you will need to fill in the details here. Providing Payflow API Credentials below, will automatically put s2Member\'s Recurring Billing integration through Pro-Forms into Payflow mode. Just fill in the details below, and you\'re ready to generate Pro-Forms that charge customers on a recurring basis. s2Member will use the Payflow Edition API instead of the older PayPal Pro DPRP service; which is being slowly phased out in favor of Payflow Edition APIs.</p>'."\n";
			echo '<p><em><strong>Payflow API Credentials:</strong> Once you have a PayPal Pro account, you\'ll need access to your <a href="http://s2member.com/r/paypal-profile-api-access/" target="_blank" rel="external">Payflow API Credentials</a>. Log into your PayPal account, and navigate to <strong>Profile → API Access (or → Request API Credentials)</strong>. From the available options, please choose "Payflow / API Access". You will need the following credentials: Username, Password, Partner, and Vendor.</em></p>'."\n";
			echo '<p><em><strong>Important Note:</strong> Supplying Payflow API Credentials here does not mean you can bypass other areas of s2Member\'s configuration; i.e., please supply s2Member with all of your PayPal account details. Your PayPal Pro (Payflow Edition) account is configured here, but up above you will need to configure other Account Details also.</em></p>'."\n";
			echo '<p><strong>See also:</strong> This KB article: <a href="http://s2member.com/kb-article/supported-paypal-account-types/" target="_blank" rel="external">PayPal Account Types</a>.</p>'."\n";
			do_action("s2x_during_payment_gateways_options_page_during_left_sections_during_paypal_payflow_account_details", get_defined_vars());

			echo '<table class="form-table">'."\n";
			echo '<tbody>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-paypal-payflow-api-username">'."\n";
			echo 'Your Payflow API Username:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_paypal_payflow_api_username" id="ws-plugin--s2member-paypal-payflow-api-username" value="'.format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_username"]).'" /><br />'."\n";
			echo 'At PayPal, see: <strong>Profile → API Access (or → Request API Credentials) → Payflow API Access</strong>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-paypal-payflow-api-password">'."\n";
			echo 'Your Payflow API Password:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="password" autocomplete="off" name="ws_plugin__s2member_paypal_payflow_api_password" id="ws-plugin--s2member-paypal-payflow-api-password" value="'.format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_password"]).'" /><br />'."\n";
			echo 'At PayPal, see: <strong>Profile → API Access (or → Request API Credentials) → Payflow API Access</strong>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-paypal-payflow-api-partner">'."\n";
			echo 'Your Payflow API Partner:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="text" name="ws_plugin__s2member_paypal_payflow_api_partner" id="ws-plugin--s2member-paypal-payflow-api-partner" value="'.format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_partner"]).'" /><br />'."\n";
			echo 'At PayPal, see: <strong>Profile → API Access (or → Request API Credentials) → Payflow API Access</strong>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-paypal-payflow-api-vendor">'."\n";
			echo 'Your Payflow API Vendor:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="text" name="ws_plugin__s2member_paypal_payflow_api_vendor" id="ws-plugin--s2member-paypal-payflow-api-vendor" value="'.format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_vendor"]).'" /><br />'."\n";
			echo 'At PayPal, see: <strong>Profile → API Access (or → Request API Credentials) → Payflow API Access</strong>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			do_action("s2x_during_payment_gateways_options_page_during_left_sections_during_paypal_payflow_account_detail_rows", get_defined_vars());
			echo '</tbody>'."\n";
			echo '</table>'."\n";
			echo '</div>'."\n";

			echo '</div>'."\n";
		}
	}
}
