<?php
/**
* Menu page for s2Member Pro (Google® Options page).
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

if (!class_exists ("c_ws_plugin__s2member_pro_menu_page_google_ops"))
	{
		/**
		* Menu page for s2Member Pro (Google® Options page).
		*
		* @package s2Member\Menu_Pages
		* @since 110531
		*/
		class c_ws_plugin__s2member_pro_menu_page_google_ops
			{
				public function __construct ()
					{
						echo '<div class="wrap ws-menu-page">' . "\n";

						echo '<div id="icon-plugins" class="icon32"><br /></div>' . "\n";
						echo '<h2>s2Member® Pro / Google® Options</h2>' . "\n";

						echo '<table class="ws-menu-page-table">' . "\n";
						echo '<tbody class="ws-menu-page-table-tbody">' . "\n";
						echo '<tr class="ws-menu-page-table-tr">' . "\n";
						echo '<td class="ws-menu-page-table-l">' . "\n";

						echo '<form method="post" name="ws_plugin__s2member_pro_options_form" id="ws-plugin--s2member-pro-options-form">' . "\n";
						echo '<input type="hidden" name="ws_plugin__s2member_options_save" id="ws-plugin--s2member-options-save" value="' . esc_attr (wp_create_nonce ("ws-plugin--s2member-options-save")) . '" />' . "\n";

						echo '<div class="ws-menu-page-group" title="Google® Account Details">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-google-account-details-section">' . "\n";
						echo '<h3>Google® Account Details (required)</h3>' . "\n";
						echo '<p><a href="http://www.s2member.com/google-checkout" target="_blank" rel="external">Google® Checkout</a> is a fast, secure checkout process that helps increase sales by bringing you more customers and allowing them to buy from you quickly and easily with a single login. Google\'s Payment Guarantee protects 98% of Checkout orders on average. When an order is guaranteed, you get paid even if it results in a chargeback.</p>' . "\n";
						echo '<p>s2Member has been integrated with Google® for Direct Payments and also for Recurring Billing. In order to take advantage of this integration, you will need to have a Google® Checkout Account. Once you have an account, all of the details below can be obtained from inside of your Google® Merchant account. If you need assistance, please check their <a href="http://www.s2member.com/google-checkout-help" target="_blank" rel="external">help section</a>.</p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-pro-google-merchant-id">' . "\n";
						echo 'Google® Merchant ID:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_pro_google_merchant_id" id="ws-plugin--s2member-pro-google-merchant-id" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"]) . '" /><br />' . "\n";
						echo 'You\'ll find this in your Google® Checkout account, under: <code>Settings -› Integration</code>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-pro-google-merchant-key">' . "\n";
						echo 'Google® Merchant Key:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="password" autocomplete="off" name="ws_plugin__s2member_pro_google_merchant_key" id="ws-plugin--s2member-pro-google-merchant-key" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"]) . '" /><br />' . "\n";
						echo 'You\'ll find this in your Google® Checkout account, under: <code>Settings -› Integration</code>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th style="padding-top:0;">' . "\n";
						echo '<label for="ws-plugin--s2member-pro-google-sandbox">' . "\n";
						echo 'Developer/Sandbox Testing?' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="radio" name="ws_plugin__s2member_pro_google_sandbox" id="ws-plugin--s2member-pro-google-sandbox-0" value="0"' . ((!$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? ' checked="checked"' : '') . ' /> <label for="ws-plugin--s2member-pro-google-sandbox-0">No</label> &nbsp;&nbsp;&nbsp; <input type="radio" name="ws_plugin__s2member_pro_google_sandbox" id="ws-plugin--s2member-pro-google-sandbox-1" value="1"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? ' checked="checked"' : '') . ' /> <label for="ws-plugin--s2member-pro-google-sandbox-1">Yes, enable support for Sandbox testing.</label><br />' . "\n";
						echo '<em>Only enable this if you\'ve provided Sandbox credentials above.<br />This puts s2Member\'s Google® integration into Sandbox mode.<br />See: <a href="http://www.s2member.com/google-checkout-sandbox-accounts" target="_blank" rel="external">Google® Sandbox Accounts</a></em>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";

						if (!is_multisite () || !c_ws_plugin__s2member_utils_conds::is_multisite_farm () || is_main_site ())
							{
								echo '<tr>' . "\n";

								echo '<th>' . "\n";
								echo '<label for="ws-plugin--s2member-gateway-debug-logs">' . "\n";
								echo 'Enable Logging Routines?<br />' . "\n";
								echo '<small><em class="ws-menu-page-hilite">* This setting applies universally. [ <a href="#" onclick="alert(\'This configuration option may ALSO appear under (s2Member -› PayPal® Options). Feel free to configure it here; but please remember that this setting is applied universally (i.e. SHARED) among all Payment Gateways integrated with s2Member.\'); return false;">?</a> ]</em></small>' . "\n";
								echo '</label>' . "\n";
								echo '</th>' . "\n";

								echo '</tr>' . "\n";
								echo '<tr>' . "\n";

								echo '<td>' . "\n";
								echo '<input type="radio" name="ws_plugin__s2member_gateway_debug_logs" id="ws-plugin--s2member-gateway-debug-logs-0" value="0"' . ((!$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["gateway_debug_logs"]) ? ' checked="checked"' : '') . ' /> <label for="ws-plugin--s2member-gateway-debug-logs-0">No</label> &nbsp;&nbsp;&nbsp; <input type="radio" name="ws_plugin__s2member_gateway_debug_logs" id="ws-plugin--s2member-gateway-debug-logs-1" value="1"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["gateway_debug_logs"]) ? ' checked="checked"' : '') . ' /> <label for="ws-plugin--s2member-gateway-debug-logs-1">Yes, enable debugging, with API, IPN &amp; Return Page logging.</label><br />' . "\n";
								echo '<em>This enables API, IPN and Return Page logging. The log files are stored here: <code>' . esc_html (c_ws_plugin__s2member_utils_dirs::doc_root_path ($GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["logs_dir"])) . '</code></em><br />' . "\n";
								echo '<em class="ws-menu-page-hilite">If you have any trouble, please review your s2Member® log files for problems. See: <a href="'.esc_attr(admin_url("/admin.php?page=ws-plugin--s2member-logs")).'">Log Viewer</a></em>'."\n";
								echo '</td>' . "\n";

								echo '</tr>' . "\n";
								echo '<tr>'."\n";

								echo '<td>'."\n";
								echo '<div class="ws-menu-page-hilite" style="border-radius:3px; padding:5px;">'."\n";
								echo '<p style="font-size:110%; margin-top:0;"><span>We HIGHLY recommend that you enable logging during your initial testing phase. Logs produce lots of useful details that can help in debugging. Logs can help you find issues in your configuration and/or problems during payment processing. See: <a href="'.esc_attr(admin_url("/admin.php?page=ws-plugin--s2member-logs")).'">Log Files (Debug)</a>.</span></p>'."\n";
								echo '<p style="font-size:110%; margin-bottom:0;"><span class="ws-menu-page-error">However, it is VERY IMPORTANT to disable logging once you go live. Log files may contain personally identifiable information, credit card numbers, secret API credentials, passwords and/or other sensitive information. We STRONGLY suggest that logging be disabled on a live site (for security reasons).</span></p>'."\n";
								echo '</div>'."\n";
								echo '</td>'."\n";

								echo '</tr>'."\n";
							}

						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-group" title="Google® API v2.5 Integration">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-google-api-section">' . "\n";
						echo '<h3>Google® API Callback v2.5 Integration (required)<br />aka: Google® IPN (Instant Payment Notifications)</h3>' . "\n";
						echo '<p>Log into your Google® Checkout account and navigate to this section:<br /><code>Settings -› Integration</code></p>' . "\n";
						echo '<p>Your Google® API v2.5 (Callback URL) is:<br /><code>' . esc_html (site_url ("/?s2member_pro_google_notify=1")) . '</code></p>' . "\n";
						echo '<p>Set your API (Callback Content) to:<br /><code>Notification Serial Number</code>.</strong></p>' . "\n";
						echo '<p>Only Post Digitally Signed Carts: <code>On</code></p>' . "\n";
						echo '<p>Notification Filtering: <code>Off (important)</code></p>' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo '<p>Now navigate to this section:<br /><code>Settings -› Preferences</code></p>' . "\n";
						echo '<p>Set Order Processing to: <code>Authorize And Charge</code></p>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-group" title="Signup Confirmation Email (Standard)">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-signup-confirmation-email-section">' . "\n";
						echo '<h3>Signup Confirmation Email (required, but the default works fine)</h3>' . "\n";
						echo '<p>This email is sent to new Customers after they return from a successful signup at Google®. The <strong>primary</strong> purpose of this email, is to provide the Customer with instructions, along with a link to register a Username for their Membership. You may also customize this further, by providing details that are specifically geared to your site.</p>' . "\n";

						echo '<p><em class="ws-menu-page-hilite">* This email configuration is universally applied to all Payment Gateway integrations. [ <a href="#" onclick="alert(\'This configuration panel may ALSO appear under (s2Member -› PayPal® Options). Feel free to configure this email here; but please remember that this configuration is applied universally (i.e. SHARED) among all Payment Gateways integrated with s2Member.\'); return false;">?</a> ]</em></p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-signup-email-recipients">' . "\n";
						echo 'Signup Confirmation Recipients:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_signup_email_recipients" id="ws-plugin--s2member-signup-email-recipients" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["signup_email_recipients"]) . '" /><br />' . "\n";
						echo 'This is a semicolon ( ; ) delimited list of Recipients. Here is an example:<br />' . "\n";
						echo '<code>"%%full_name%%" &lt;%%payer_email%%&gt;; admin@example.com; "Webmaster" &lt;webmaster@example.com&gt;</code>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-signup-email-subject">' . "\n";
						echo 'Signup Confirmation Email Subject:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_signup_email_subject" id="ws-plugin--s2member-signup-email-subject" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["signup_email_subject"]) . '" /><br />' . "\n";
						echo 'Subject Line used in the email sent to a Customer after a successful signup has occurred through Google®.' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-signup-email-message">' . "\n";
						echo 'Signup Confirmation Email Message:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<textarea name="ws_plugin__s2member_signup_email_message" id="ws-plugin--s2member-signup-email-message" rows="10">' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["signup_email_message"]) . '</textarea><br />' . "\n";
						echo 'Message Body used in the email sent to a Customer after a successful signup has occurred through Google®.<br /><br />' . "\n";
						echo '<strong>You can also use these special Replacement Codes if you need them:</strong>' . "\n";
						echo '<ul>' . "\n";
						echo '<li><code>%%registration_url%%</code> = The full URL (generated by s2Member) where the Customer can get registered.</li>' . "\n";
						echo '<li><code>%%subscr_id%%</code> = A unique Subscription ID (i.e. the s2Member TID/SID #; always with an `s2-` prefix). [ <a href="#" onclick="alert(\'The reason s2Member generates a unique TID/SID # (always with an `s2-` prefix), is because the built-in Order # Google® generates is NOT dynamic enough to handle everything s2Member makes possible with Membership Access. Google® Orders (by themselves) do NOT allow site owners to track multiple payments with a common Subscription ID. So s2Member makes up for this minor deficiency.\\n\\nFor instance, with Recurring Google® Subscriptions, s2Member\\\'s %%subscr_id%% will remain constant throughout all future payments; making things easier to keep track of (on the back-end management side of things).\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%initial%%</code> = The Initial Fee charged during signup. If you offered a 100% Free Trial, this will be <code>0</code>. [ <a href="#" onclick="alert(\'This will always represent the amount of money the Customer spent, whenever they initially signed up, no matter what. If a Customer signs up, under the terms of a 100% Free Trial Period, this will be 0.\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%regular%%</code> = The Regular Amount of the Subscription. This value is <code>always > 0</code>, no matter what. [ <a href="#" onclick="alert(\'This is how much the Subscription costs after an Initial Period expires. The %%regular%% rate is always > 0. If you did NOT offer an Initial Period at a different price, %%initial%% and %%regular%% will be equal to the same thing.\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%recurring%%</code> = This is the amount that will be charged on a recurring basis, or <code>0</code> if non-recurring. [ <a href="#" onclick="alert(\'If Recurring Payments have not been required, this will be equal to 0. That being said, %%regular%% &amp; %%recurring%% are usually the same value. This variable can be used in two different ways. You can use it to determine what the Regular Recurring Rate is, or to determine whether the Subscription will recur or not. If it is going to recur, %%recurring%% will be > 0.\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%first_name%%</code> = The First Name of the Customer who purchased the Membership Subscription.</li>' . "\n";
						echo '<li><code>%%last_name%%</code> = The Last Name of the Customer who purchased the Membership Subscription.</li>' . "\n";
						echo '<li><code>%%full_name%%</code> = The Full Name (First &amp; Last) of the Customer who purchased the Membership Subscription.</li>' . "\n";
						echo '<li><code>%%payer_email%%</code> = The Email Address of the Customer who purchased the Membership Subscription.</li>' . "\n";
						echo '<li><code>%%user_ip%%</code> = The Customer\'s IP Address, detected during checkout via <code>$_SERVER["REMOTE_ADDR"]</code>.</li>' . "\n";
						echo '<li><code>%%item_number%%</code> = The Item Number (colon separated <code><em>level:custom_capabilities:fixed term</em></code>) that the Subscription is for.</li>' . "\n";
						echo '<li><code>%%item_name%%</code> = The Item Name (as provided by the <code>desc=""</code> attribute in your Shortcode, which briefly describes the Item Number).</li>' . "\n";
						echo '<li><code>%%initial_term%%</code> = This is the term length of the Initial Period. This will be a numeric value, followed by a space, then a single letter. [ <a href="#" onclick="alert(\'Here are some examples:\\n\\n%%initial_term%% = 1 D (this means 1 Day)\\n%%initial_term%% = 1 W (this means 1 Week)\\n%%initial_term%% = 1 M (this means 1 Month)\\n%%initial_term%% = 1 Y (this means 1 Year)\\n\\nThe Initial Period never recurs, so this only lasts for the term length specified, then it is over.\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%initial_cycle%%</code> = This is the <code>%%initial_term%%</code> from above, converted to a cycle representation of: <code><em>X days/weeks/months/years</em></code>.</li>' . "\n";
						echo '<li><code>%%regular_term%%</code> = This is the term length of the Regular Period. This will be a numeric value, followed by a space, then a single letter. [ <a href="#" onclick="alert(\'Here are some examples:\\n\\n%%regular_term%% = 1 D (this means 1 Day)\\n%%regular_term%% = 1 W (this means 1 Week)\\n%%regular_term%% = 1 M (this means 1 Month)\\n%%regular_term%% = 1 Y (this means 1 Year)\\n%%regular_term%% = 1 L (this means 1 Lifetime)\\n\\nThe Regular Term is usually recurring. So the Regular Term value represents the period (or duration) of each recurring period. If %%recurring%% = 0, then the Regular Term only applies once, because it is not recurring. So if it is not recurring, the value of %%regular_term%% simply represents how long their Membership privileges are going to last after the %%initial_term%% has expired, if there was an Initial Term. The value of this variable ( %%regular_term%% ) will never be empty, it will always be at least: 1 D, meaning 1 day. No exceptions.\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%regular_cycle%%</code> = This is the <code>%%regular_term%%</code> from above, converted to a cycle representation of: <code><em>[every] X days/weeks/months/years — OR daily, weekly, bi-weekly, monthly, bi-monthly, quarterly, yearly, or lifetime</em></code>. This is a very useful Replacment Code. Its value is dynamic; depending on term length, recurring status, and period/term lengths configured.</li>' . "\n";
						echo '<li><code>%%recurring/regular_cycle%%</code> = Example (<code>14.95 / Monthly</code>), or ... (<code>0 / non-recurring</code>); depending on the value of <code>%%recurring%%</code>.</li>' . "\n";
						echo '</ul>' . "\n";

						echo '<strong>Custom Replacement Codes can also be inserted using these instructions:</strong>' . "\n";
						echo '<ul>' . "\n";
						echo '<li><code>%%cv0%%</code> = The domain of your site, which is passed through the `custom` attribute in your Shortcode.</li>' . "\n";
						echo '<li><code>%%cv1%%</code> = If you need to track additional custom variables, you can pipe delimit them into the `custom` attribute; inside your Shortcode, like this: <code>custom="' . esc_html ($_SERVER["HTTP_HOST"]) . '|cv1|cv2|cv3"</code>. You can have an unlimited number of custom variables. Obviously, this is for advanced webmasters; but the functionality has been made available for those who need it.</li>' . "\n";
						echo '</ul>' . "\n";
						echo '<strong>This example uses cv1 to record a special marketing campaign:</strong><br />' . "\n";
						echo '<em>(The campaign (i.e. christmas-promo) could be referenced using <code>%%cv1%%</code>)</em><br />' . "\n";
						echo '<code>custom="' . esc_html ($_SERVER["HTTP_HOST"]) . '|christmas-promo"</code>' . "\n";

						echo (!is_multisite () || !c_ws_plugin__s2member_utils_conds::is_multisite_farm () || is_main_site ()) ?
							'<div class="ws-menu-page-hr"></div>' . "\n".
							'<p style="margin:0;"><strong>PHP Code:</strong> It is also possible to use PHP tags — optional (for developers). If you use PHP tags, please run a test email with <code>&lt;?php print_r(get_defined_vars()); ?&gt;</code>. This will give you a full list of all PHP variables available to you in this email. The <code>$paypal</code> variable is the most important one. It contains all of the <code>$_POST</code> variables received from Google\'s Callback/IPN service, which are then translated into a format that s2Member\'s Core PayPal® Processor can understand (e.g. <code>$paypal["item_number"]</code>, <code>$paypal["item_name"]</code>, etc). Please note that all Replacement Codes will be parsed first, and then any PHP tags that you\'ve included. Also, please remember that emails are sent in plain text format.</p>'."\n"
							: '';
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-group" title="Specific Post/Page Confirmation Email (Standard)">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-sp-confirmation-email-section">' . "\n";
						echo '<h3>Specific Post/Page Confirmation Email (required, but the default works fine)</h3>' . "\n";
						echo '<p>This email is sent to new Customers after they return from a successful purchase at Google®, for Specific Post/Page Access. (see: <code>s2Member -› Restriction Options -› Specific Post/Page Access</code>). This is NOT used for Membership sales, only for Specific Post/Page Access. The <strong>primary</strong> purpose of this email, is to provide the Customer with instructions, along with a link to access the Specific Post/Page they\'ve purchased access to. If you\'ve created a Specific Post/Page Package (with multiple Posts/Pages bundled together into one transaction), this ONE link (<code>%%sp_access_url%%</code>) will automatically authenticate them for access to ALL of the Posts/Pages included in their transaction. You may customize this email further, by providing details that are specifically geared to your site.</p>' . "\n";

						echo '<p><em class="ws-menu-page-hilite">* This email configuration is universally applied to all Payment Gateway integrations. [ <a href="#" onclick="alert(\'This configuration panel may ALSO appear under (s2Member -› PayPal® Options). Feel free to configure this email here; but please remember that this configuration is applied universally (i.e. SHARED) among all Payment Gateways integrated with s2Member.\'); return false;">?</a> ]</em></p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-sp-email-recipients">' . "\n";
						echo 'Specific Post/Page Confirmation Recipients:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_sp_email_recipients" id="ws-plugin--s2member-sp-email-recipients" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["sp_email_recipients"]) . '" /><br />' . "\n";
						echo 'This is a semicolon ( ; ) delimited list of Recipients. Here is an example:<br />' . "\n";
						echo '<code>"%%full_name%%" &lt;%%payer_email%%&gt;; admin@example.com; "Webmaster" &lt;webmaster@example.com&gt;</code>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-sp-email-subject">' . "\n";
						echo 'Specific Post/Page Confirmation Email Subject:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_sp_email_subject" id="ws-plugin--s2member-sp-email-subject" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["sp_email_subject"]) . '" /><br />' . "\n";
						echo 'Subject Line used in the email sent to a Customer after a successful purchase has occurred through Google®, for Specific Post/Page Access.' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-sp-email-message">' . "\n";
						echo 'Specific Post/Page Confirmation Email Message:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<textarea name="ws_plugin__s2member_sp_email_message" id="ws-plugin--s2member-sp-email-message" rows="10">' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["sp_email_message"]) . '</textarea><br />' . "\n";
						echo 'Message Body used in the email sent to a Customer after a successful purchase has occurred through Google®, for Specific Post/Page Access.<br /><br />' . "\n";
						echo '<strong>You can also use these special Replacement Codes if you need them:</strong>' . "\n";
						echo '<ul>' . "\n";
						echo '<li><code>%%sp_access_url%%</code> = The full URL (generated by s2Member) where the Customer can gain access.</li>' . "\n";
						echo '<li><code>%%sp_access_exp%%</code> = Human readable expiration for <code>%%sp_access_url%%</code>. Ex: <em>(link expires in <code>%%sp_access_exp%%</code>)</em>.</li>' . "\n";
						echo '<li><code>%%txn_id%%</code> = A unique Transaction ID for this purchase (always generated by Google®). [ <a href="#" onclick="alert(\'This is always the built-in Order # generated by Google® Checkout.\'); return false;">?</a> ]</li>' . "\n";
						echo '<li><code>%%amount%%</code> = The full Amount that you charged for Specific Post/Page Access. This value will <code>always be > 0</code>.</li>' . "\n";
						echo '<li><code>%%first_name%%</code> = The First Name of the Customer who purchased Specific Post/Page Access.</li>' . "\n";
						echo '<li><code>%%last_name%%</code> = The Last Name of the Customer who purchased Specific Post/Page Access.</li>' . "\n";
						echo '<li><code>%%full_name%%</code> = The Full Name (First &amp; Last) of the Customer who purchased Specific Post/Page Access.</li>' . "\n";
						echo '<li><code>%%payer_email%%</code> = The Email Address of the Customer who purchased Specific Post/Page Access.</li>' . "\n";
						echo '<li><code>%%user_ip%%</code> = The Customer\'s IP Address, detected during checkout via <code>$_SERVER["REMOTE_ADDR"]</code>.</li>' . "\n";
						echo '<li><code>%%item_number%%</code> = The Item Number. Ex: <code><em>sp:13,24,36:72</em></code> (translates to: <code><em>sp:comma-delimited IDs:expiration hours</em></code>).</li>' . "\n";
						echo '<li><code>%%item_name%%</code> = The Item Name (as provided by the <code>desc=""</code> attribute in your Shortcode, which briefly describes the Item Number).</li>' . "\n";
						echo '</ul>' . "\n";

						echo '<strong>Custom Replacement Codes can also be inserted using these instructions:</strong>' . "\n";
						echo '<ul>' . "\n";
						echo '<li><code>%%cv0%%</code> = The domain of your site, which is passed through the `custom` attribute in your Shortcode.</li>' . "\n";
						echo '<li><code>%%cv1%%</code> = If you need to track additional custom variables, you can pipe delimit them into the `custom` attribute; inside your Shortcode, like this: <code>custom="' . esc_html ($_SERVER["HTTP_HOST"]) . '|cv1|cv2|cv3"</code>. You can have an unlimited number of custom variables. Obviously, this is for advanced webmasters; but the functionality has been made available for those who need it.</li>' . "\n";
						echo '</ul>' . "\n";
						echo '<strong>This example uses cv1 to record a special marketing campaign:</strong><br />' . "\n";
						echo '<em>(The campaign (i.e. christmas-promo) could be referenced using <code>%%cv1%%</code>)</em><br />' . "\n";
						echo '<code>custom="' . esc_html ($_SERVER["HTTP_HOST"]) . '|christmas-promo"</code>' . "\n";

						echo (!is_multisite () || !c_ws_plugin__s2member_utils_conds::is_multisite_farm () || is_main_site ()) ?
							'<div class="ws-menu-page-hr"></div>' . "\n".
							'<p style="margin:0;"><strong>PHP Code:</strong> It is also possible to use PHP tags — optional (for developers). If you use PHP tags, please run a test email with <code>&lt;?php print_r(get_defined_vars()); ?&gt;</code>. This will give you a full list of all PHP variables available to you in this email. The <code>$paypal</code> variable is the most important one. It contains all of the <code>$_POST</code> variables received from Google\'s Callback/IPN service, which are then translated into a format that s2Member\'s Core PayPal® Processor can understand (e.g. <code>$paypal["item_number"]</code>, <code>$paypal["item_name"]</code>, etc). Please note that all Replacement Codes will be parsed first, and then any PHP tags that you\'ve included. Also, please remember that emails are sent in plain text format.</p>'."\n"
							: '';
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-group" title="Automatic EOT Behavior">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-eot-behavior-section">' . "\n";
						echo '<h3>Google® EOT Behavior (required, please choose)</h3>' . "\n";
						echo '<p>EOT = End Of Term. By default, s2Member will demote a paid Member to a Free Subscriber whenever their Subscription term has ended (i.e. expired), been cancelled, refunded, charged back to you, etc. s2Member demotes them to a Free Subscriber, so they will no longer have Member Level Access to your site. However, in some cases, you may prefer to have Customer accounts deleted completely, instead of just being demoted. This is where you choose which method works best for your site. If you don\'t want s2Member to take ANY action at all, you can disable s2Member\'s EOT System temporarily, or even completely. There are also a few other configurable options here, so please read carefully. These options are all very important.</p>' . "\n";
						echo '<p>The Google® IPN service will notify s2Member whenever a refund or chargeback occurs. For example, if you issue a refund to an unhappy Customer through Google®, s2Member will eventually be notified, and the account for that Customer will either be demoted to a Free Subscriber, or deleted automatically (based on your configuration). ~ Otherwise, under normal circumstances, s2Member will not process an EOT until the User has completely used up the time they paid for.</em></p>' . "\n";

						echo '<p id="ws-plugin--s2member-auto-eot-system-enabled-via-cron"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] == 2 && (!function_exists ("wp_cron") || !wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule"))) ? '' : ' style="display:none;"') . '>If you\'d like to run s2Member\'s Auto-EOT System through a more traditional Cron Job; instead of through <code>WP-Cron</code>, you will need to configure a Cron Job through your server control panel; provided by your hosting company. Set the Cron Job to run <code>once about every 10 minutes to an hour</code>. You\'ll want to configure an HTTP Cron Job that loads this URL:<br /><code>' . esc_html (site_url ("/?s2member_auto_eot_system_via_cron=1")) . '</code></p>' . "\n";

						echo '<p><em class="ws-menu-page-hilite">* These options are universally applied to all Payment Gateway integrations. [ <a href="#" onclick="alert(\'These settings may ALSO appear under (s2Member -› PayPal® Options). Feel free to configure them here; but please remember that these configuration options are applied universally (i.e. they\\\'re SHARED) among all Payment Gateways integrated with s2Member.\'); return false;">?</a> ]</em></p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-auto-eot-system-enabled">' . "\n";
						echo 'Enable s2Member\'s Auto-EOT System?' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<select name="ws_plugin__s2member_auto_eot_system_enabled" id="ws-plugin--s2member-auto-eot-system-enabled">' . "\n";
						// Very advanced conditionals here. If the Auto-EOT System is NOT running, or NOT fully configured, this will indicate that no option is set - as sort of a built-in acknowledgment/warning in the UI panel.
						echo (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] == 1 && (!function_exists ("wp_cron") || !wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule"))) || ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] == 2 && (function_exists ("wp_cron") && wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule"))) || (!$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] && (function_exists ("wp_cron") && wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule")))) ? '<option value=""></option>' . "\n" : '';
						echo '<option value="1"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] == 1 && function_exists ("wp_cron") && wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule")) ? ' selected="selected"' : '') . '>Yes (enable the Auto-EOT System through WP-Cron)</option>' . "\n";
						echo (!is_multisite () || !c_ws_plugin__s2member_utils_conds::is_multisite_farm () || is_main_site ()) ? '<option value="2"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] == 2 && (!function_exists ("wp_cron") || !wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule"))) ? ' selected="selected"' : '') . '>Yes (but, I\'ll run it with my own Cron Job)</option>' . "\n" : '';
						echo '<option value="0"' . ((!$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["auto_eot_system_enabled"] && (!function_exists ("wp_cron") || !wp_get_schedule ("ws_plugin__s2member_auto_eot_system__schedule"))) ? ' selected="selected"' : '') . '>No (disable the Auto-EOT System)</option>' . "\n";
						echo '</select><br />' . "\n";
						echo 'Recommended setting: (<code>Yes / enable via WP-Cron</code>)' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-membership-eot-behavior">' . "\n";
						echo 'Membership EOT Behavior (Demote or Delete)?' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<select name="ws_plugin__s2member_membership_eot_behavior" id="ws-plugin--s2member-membership-eot-behavior">' . "\n";
						echo '<option value="demote"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_eot_behavior"] === "demote") ? ' selected="selected"' : '') . '>Demote (convert them to a Free Subscriber)</option>' . "\n";
						echo '<option value="delete"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_eot_behavior"] === "delete") ? ' selected="selected"' : '') . '>Delete (erase their account completely)</option>' . "\n";
						echo '</select>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>'."\n";

						echo '<th>'."\n";
						echo '<label for="ws-plugin--s2member-eots-remove-ccaps">'."\n";
						echo 'Membership EOTs also Remove all Custom Capabilities?'."\n";
						echo '</label>'."\n";
						echo '</th>'."\n";

						echo '</tr>'."\n";
						echo '<tr>'."\n";

						echo '<td>'."\n";
						echo '<select name="ws_plugin__s2member_eots_remove_ccaps" id="ws-plugin--s2member-eots-remove-ccaps">'."\n";
						echo '<option value="1"'.(($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["eots_remove_ccaps"]) ? ' selected="selected"' : '').'>Yes (an EOT also results in the loss of any Custom Capabilities a User/Member may have)</option>'."\n";
						echo '<option value="0"'.((!$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["eots_remove_ccaps"]) ? ' selected="selected"' : '').'>No (an EOT has no impact on any Custom Capabilities a User/Member may have)</option>'."\n";
						echo '</select><br />'."\n";
						echo '<em>NOTE: If Refunds/Reversals trigger an Immediate EOT (see setting below); Custom Capabilities will always be removed when/if a Refund or Reversal occurs. In other words, this setting is ignored for Refunds/Reversals (IF they trigger an Immediate EOT — based on your configuration below). If you prefer to review all Refunds/Reversals for yourself, please choose that option below.</em>'."\n";
						echo '</td>'."\n";

						echo '</tr>'."\n";
						echo '<tr>'."\n";

						echo '<th>'."\n";
						echo '<label for="ws-plugin--s2member-eot-grace-time">'."\n";
						echo 'EOT Grace Time (in seconds):'."\n";
						echo '</label>'."\n";
						echo '</th>'."\n";

						echo '</tr>'."\n";
						echo '<tr>'."\n";

						echo '<td>'."\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_eot_grace_time" id="ws-plugin--s2member-eot-grace-time" value="'.format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["eot_grace_time"]).'" /><br />'."\n";
						echo '<em>This is represented in seconds. For example, a value of: <code>86400</code> = 1 day. Your EOT Grace Time; is the amount of time you will offer as a grace period (if any). Most site owners will give customers an additional 24 hours of access; just to help avoid any negativity that may result from a customer losing access sooner than they might expect. You can disable EOT Grace Time by setting this to: <code>0</code>. Note: there is NO Grace Time applied when/if a Refund or Reversal occurs. If Refunds/Reversals trigger an Immediate EOT (see setting below); there is never any Grace Time applied in that scenario.</em>'."\n";
						echo '</td>'."\n";

						echo '</tr>'."\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-triggers-immediate-eot">' . "\n";
						echo 'Refunds/Reversals (trigger Immediate EOT)?' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<select name="ws_plugin__s2member_triggers_immediate_eot" id="ws-plugin--s2member-triggers-immediate-eot">' . "\n";
						echo '<option value="none"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["triggers_immediate_eot"] === "none") ? ' selected="selected"' : '') . '>Neither (I\'ll review these two events manually)</option>' . "\n";
						echo '<option value="refunds"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["triggers_immediate_eot"] === "refunds") ? ' selected="selected"' : '') . '>Refunds (refunds ALWAYS trigger an Immediate EOT action)</option>' . "\n";
						echo '<option value="reversals"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["triggers_immediate_eot"] === "reversals") ? ' selected="selected"' : '') . '>Reversals (chargebacks ALWAYS trigger an Immediate EOT action)</option>' . "\n";
						echo '<option value="refunds,reversals"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["triggers_immediate_eot"] === "refunds,reversals") ? ' selected="selected"' : '') . '>Refunds/Reversals (ALWAYS trigger an Immediate EOT action)</option>' . "\n";
						echo '</select><br />' . "\n";
						echo '<em>This setting will <a href="#" onclick="alert(\'A Refund/Reversal Notification will ALWAYS be processed internally by s2Member, even if no action is taken by s2Member. This way you\\\'ll have the full ability to listen for these two events on your own; if you prefer (optional). For more information, check your Dashboard under: `s2Member -› API Notifications -› Refunds/Reversals`.\'); return false;">NOT impact</a> s2Member\'s internal API Notifications for Refund/Reversal events.</em>'."\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-eot-time-ext-behavior">' . "\n";
						echo 'Fixed-Term Extensions (Auto-Extend)?' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<select name="ws_plugin__s2member_eot_time_ext_behavior" id="ws-plugin--s2member-eot-time-ext-behavior">' . "\n";
						echo '<option value="extend"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["eot_time_ext_behavior"] === "extend") ? ' selected="selected"' : '') . '>Yes (default, automatically extend any existing EOT Time)</option>' . "\n";
						echo '<option value="reset"' . (($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["eot_time_ext_behavior"] === "reset") ? ' selected="selected"' : '') . '>No (do NOT extend; s2Member should reset EOT Time completely)</option>' . "\n";
						echo '</select><br />' . "\n";
						echo '<em>This setting will only affect Buy Now transactions for fixed-term lengths. By default, s2Member will automatically extend any existing EOT Time that a Customer may have. For example, if I buy one year of access, and then I buy another year of access (before my first year is totally used up); I end up with everything I paid you for (now over 1 year of access) if this is set to <code>Yes</code>. If this was set to <code>No</code>, the EOT Time would be reset when I make the second purchase; leaving me with only 1 year of access, starting the date of my second purchase.</em>'."\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo '<p class="submit"><input type="submit" class="button-primary" value="Save All Changes" /></p>' . "\n";

						echo '</form>' . "\n";

						echo '</td>' . "\n";

						echo '<td class="ws-menu-page-table-r">' . "\n";
						c_ws_plugin__s2member_menu_pages_rs::display ();
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";

						echo '</div>' . "\n";
					}
			}
	}

new c_ws_plugin__s2member_pro_menu_page_google_ops ();
?>