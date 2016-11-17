<?php
// @codingStandardsIgnoreFile
/**
 * Menu page for s2Member Pro (PayPal options, Reminder Email).
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
 * @since 151203
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_menu_page_paypal_ops_reminder_email"))
{
	/**
	 * Menu page for s2Member Pro (PayPal options, Reminder Email).
	 *
	 * @package s2Member\Menu_Pages
	 * @since 151203
	 */
	class c_ws_plugin__s2member_pro_menu_page_paypal_ops_reminder_email
	{
		public function __construct()
		{
			echo '<div class="ws-menu-page-group" title="EOT Renewal/Reminder Email(s)">'."\n";

			echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-eot-reminder-email-section">'."\n";
			echo '<h3>EOT Renewal/Reminder Emails (optional, for reminding customers who have an EOT Time)</h3>'."\n";
			echo '<p>The <strong>primary</strong> purpose of this email is to remind a customer that they will soon lose access to what they paid for. You may customize this further by providing details that are specifically geared to your site. Keep in mind that some of your customers may not have an EOT Time; i.e., if you don\'t require a recurring payment or you\'re not selling fixed-term access, then a customer\'s account never expires. Thus, they will have no EOT Time. This email is not going to be sent to those customers. See also: <a href="https://s2member.com/kb-article/when-is-an-eot-time-set-for-each-user/" target="_blank">When is an EOT Time set for each user?</a></p>'."\n";

			//echo '<p><em class="ws-menu-page-bright-hilite">* The email configuration below is universally applied to all Payment Gateway integrations. [ <a href="#" onclick="alert(\'This configuration panel may ALSO appear under (s2Member → PayPal Options). Feel free to configure this email here; but please remember that this configuration is applied universally (i.e., SHARED) among all Payment Gateways integrated with s2Member.\'); return false;">?</a> ]</em></p>'."\n";

			echo '<table class="form-table">'."\n";
			echo '<tbody>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-pro-eot-reminder-email-enable">'."\n";
			echo 'EOT Renewal/Reminder Enabled?'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<select name="ws_plugin__s2member_pro_eot_reminder_email_enable" id="ws-plugin--s2member-pro-eot-reminder-email-enable">'."\n";
			echo '<option value="0"'.(($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_enable'] === "0") ? ' selected="selected"' : '').'>No (disabled)</option>'."\n";
			echo '<option value="1"'.(($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_enable'] === "1") ? ' selected="selected"' : '').'>Yes (enable)</option>'."\n";
			echo '</select>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '</tbody>'."\n";
			echo '</table>'."\n";

			echo '<div class="ws-menu-page-pro-eot-reminder-email-ops" style="opacity:0.5;">'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";

			echo '<table class="form-table">'."\n";
			echo '<tbody>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-pro-eot-reminder-email-days">'."\n";
			echo 'Remind X Days Before EOT Occurs:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_pro_eot_reminder_email_days" id="ws-plugin--s2member-pro-eot-reminder-email-days" value="'.format_to_edit($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_days']).'" /><br />'."\n";
			echo 'This can be a comma-delimited list of days on which to send the reminder email; e.g., <code>-5,-1</code> sends a reminder email 5 days before the EOT will occur, and then again (if the EOT still exists; i.e., the customer has not yet renewed) 1 day before the EOT occurs. Negative numbers indicate days <em>before</em> the EOT occurs, positive numbers <em>after</em> the EOT has already occurred; <code>0</code> being the day the EOT occurs. If you set this to <code>-5</code> (one value only) the reminder is sent only one time. If you set this to <code>-10,-5,-2,-1,2,5</code> there is the potential for a reminder to be sent up to six times. Four times before the EOT occurs. Two times after the EOT occurs.'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '</tbody>'."\n";
			echo '</table>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";

			echo '<div id="ws-plugin--s2member-pro-eot-reminder-email-day">'."\n";
			echo '	<h3 style="display:inline-block;">Customize Email for Day:</h3>'."\n";
			echo '	<div class="-tabs ws-menu-page-number-button-tabs">'."\n";
			echo '	</div>'."\n";
			echo '</div>'."\n";

			echo '<table class="form-table">'."\n";
			echo '<tbody>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-pro-eot-reminder-email-recipients-for-day">'."\n";
			echo 'EOT Renewal/Reminder Recipients:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="hidden" name="ws_plugin__s2member_pro_eot_reminder_email_recipients" id="ws-plugin--s2member-pro-eot-reminder-email-recipients" value="'.format_to_edit($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_recipients']).'" />'."\n";
			echo '<input type="text" autocomplete="off" id="ws-plugin--s2member-pro-eot-reminder-email-recipients-for-day" value="" /><br />'."\n";
			echo 'This is a semicolon <code>;</code> delimited list of recipients <em>(listed together here, but emailed separately)</em>.<br />'."\n";
			echo '<small>Example: <code>"%%user_full_name%%" &lt;%%user_email%%&gt;; admin@example.com; "Webmaster" &lt;webmaster@example.com&gt;</code></small>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-pro-eot-reminder-email-subject-for-day">'."\n";
			echo 'EOT Renewal/Reminder Subject:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="hidden" name="ws_plugin__s2member_pro_eot_reminder_email_subject" id="ws-plugin--s2member-pro-eot-reminder-email-subject" value="'.format_to_edit($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_subject']).'" />'."\n";
			echo '<input type="text" autocomplete="off" id="ws-plugin--s2member-pro-eot-reminder-email-subject-for-day" value="" /><br />'."\n";
			echo 'Subject line used in the email reminder that is sent to a Customer.'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-pro-eot-reminder-email-message-for-day">'."\n";
			echo 'EOT Renewal/Reminder Message:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<input type="hidden" name="ws_plugin__s2member_pro_eot_reminder_email_message" id="ws-plugin--s2member-pro-eot-reminder-email-message" value="'.format_to_edit($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_reminder_email_message']).'" />'."\n";
			echo '<textarea id="ws-plugin--s2member-pro-eot-reminder-email-message-for-day" rows="10"></textarea><br />'."\n";
			echo 'Message Body (plain text; i.e., not HTML) used in the email reminder that is sent to a Customer.<br /><br />'."\n";

			echo '<strong>You can also use these special Replacement Codes if you need them:</strong><br /><br />'."\n";

			echo '<strong>EOT Date/Time Formats:</strong>'."\n";
			echo '<ul class="ws-menu-page-li-margins">'."\n";
			echo '<li><code>%%eot_date%%</code> = The EOT date; e.g., <code>'.esc_html(date_i18n(get_option('date_format'), strtotime('+30 days'))).'</code> (based on date format in your WordPress General Settings).</li>'."\n";
			echo '<li><code>%%eot_time%%</code> = The EOT time; e.g., <code>'.esc_html(date_i18n(get_option('time_format'), strtotime('+30 days'))).'</code> (based on time format in your WordPress General Settings).</li>'."\n";
			echo '<li><code>%%eot_tz%%</code> = The EOT timezone code; e.g., <code>'.esc_html(date_i18n('T', strtotime('+30 days'))).'</code> (based on timezone in your WordPress General Settings).</li>'."\n";
			echo '<li><code>%%eot_date_time_tz%%</code> = A full concatenation of <code>%%eot_date%% %%eot_time%% %%eot_tz%%</code>; e.g., <code>'.esc_html(date_i18n(get_option('date_format').' '.get_option('time_format').' T', strtotime('+30 days'))).'</code></li>'."\n";
			echo '<li><code>%%eot_descriptive_time%%</code> = An human readable description of the EOT time difference, between now and the EOT. e.g., <code>30 days</code>, <code>2 hours</code>, <code>1 month</code>. For example, "<strong>expires in <code>%%eot_descriptive_time%%</code></strong>". Or "<strong><code>%%eot_descriptive_time%%</code> from now</strong>". If the EOT has already occurred; e.g., if you have non-negative days listed above, so that reminders are sent even <em>after</em> the EOT has occurred, all of the dates (including this description) will reflect that. In the case of this descriptive variation, you might alter your usage to, "<strong><code>%%eot_descriptive_time%%</code> ago</strong>".</li>'."\n";
			echo '</ul>'."\n";

			echo '<strong>Account Details:</strong>'."\n";
			echo '<ul class="ws-menu-page-li-margins">'."\n";
			echo '<li><code>%%user_first_name%%</code> = The First Name in their WordPress account profile.</li>'."\n";
			echo '<li><code>%%user_last_name%%</code> = The Last Name in their WordPress account profile.</li>'."\n";
			echo '<li><code>%%user_full_name%%</code> = The First/Last Name in their WordPress account profile.</li>'."\n";
			echo '<li><code>%%user_email%%</code> = The Email Address in their WordPress account profile.</li>'."\n";
			echo '<li><code>%%user_login%%</code> = The Username associated with their account in WordPress.</li>'."\n";
			echo '<li><code>%%user_ip%%</code> = The Customer\'s original IP Address, during checkout/registration via <code>$_SERVER["REMOTE_ADDR"]</code>.</li>'."\n";
			echo '<li><code>%%user_id%%</code> = A unique WordPress User ID that references this account in the WordPress database.</li>'."\n";
			echo '<li><code>%%user_role%%</code> = The Role that this user has on your site; e.g., <code>s2member_level1</code>, <code>s2member_level2</code>, etc.</li>'."\n";
			echo '<li><code>%%user_level%%</code> = The Level this user has on your site; e.g., <code>1</code>, <code>2</code>, <code>3</code>, etc.</li>'."\n";
			echo '<li><code>%%user_level_label%%</code> = The Level Label that this user has; e.g., <code>Bronze</code>, <code>Platimun</code>, etc.</li>'."\n";
			echo '<li><code>%%user_ccaps%%</code> = A comma-delimited list of any Custom Capabilities they have; e.g., <code>pro,unlimited</code></li>'."\n";
			echo '</ul>'."\n";

			echo '<strong>Customer Subscription Data:</strong>'."\n";
			echo '<ul class="ws-menu-page-li-margins">'."\n";
			echo '<li><code>%%subscr_id%%</code> = The customer\'s Paid Subscr. ID, which remains constant throughout any &amp; all future payments.</li>'."\n";
			// echo '<li><code>%%subscr_cid%%</code> = This is the Customer\'s ID in Stripe, which remains constant throughout any &amp; all future payments.</li>'."\n";
			echo '<li><code>%%currency%%</code> = Three-character currency code (uppercase); e.g., <code>USD</code></li>'."\n";
			echo '<li><code>%%currency_symbol%%</code> = Currency code symbol; e.g., <code>$</code></li>'."\n";
			echo '<li><code>%%initial%%</code> = The Initial Fee. If you offered a 100% Free Trial, this will be <code>0</code>. [ <a href="#" onclick="alert(\'This will always represent the amount of money the Customer spent when they initially completed checkout, no matter what. Even if that amount was 0. If a Customer upgraded/downgraded under the terms of a 100% Free Trial Period, this will be 0.\'); return false;">?</a> ]</li>'."\n";
			echo '<li><code>%%regular%%</code> = The Regular Amount of the Subscription. If you offered something 100% free, this will be <code>0</code>. [ <a href="#" onclick="alert(\'This is how much the Subscription costs after an Initial Period expires. If you did NOT offer an Initial Period at a different price, %%initial%% and %%regular%% will be equal to the same thing.\'); return false;">?</a> ]</li>'."\n";
			echo '<li><code>%%recurring%%</code> = This is the amount that will be charged on a recurring basis, or <code>0</code> if non-recurring. [ <a href="#" onclick="alert(\'If Recurring Payments have not been required, this will be equal to 0. That being said, %%regular%% &amp; %%recurring%% are usually the same value.\'); return false;">?</a> ]</li>'."\n";
			echo '<li><code>%%first_name%%</code> = The First Name of the Customer who purchased the Membership Subscription.</li>'."\n";
			echo '<li><code>%%last_name%%</code> = The Last Name of the Customer who purchased the Membership Subscription.</li>'."\n";
			echo '<li><code>%%full_name%%</code> = The Full Name (First &amp; Last) of the Customer who purchased the Membership Subscription.</li>'."\n";
			echo '<li><code>%%payer_email%%</code> = The Email Address of the Customer who purchased the Membership Subscription.</li>'."\n";
			echo '<li><code>%%item_number%%</code> = The Item Number (colon separated <code><em>level:custom_capabilities:fixed term</em></code>) that the Subscription is for.</li>'."\n";
			echo '<li><code>%%item_name%%</code> = The Item Name (as provided by the <code>desc=""</code> attribute in your Shortcode, which briefly describes the Item Number).</li>'."\n";
			echo '<li><code>%%initial_term%%</code> = This is the term length of the Initial Period. This will be a numeric value, followed by a space, then a single letter. [ <a href="#" onclick="alert(\'Here are some examples:\\n\\n%%initial_term%% = 1 D (this means 1 Day)\\n%%initial_term%% = 1 W (this means 1 Week)\\n%%initial_term%% = 1 M (this means 1 Month)\\n%%initial_term%% = 1 Y (this means 1 Year)\\n\\nThe Initial Period never recurs, so this only lasts for the term length specified, then it is over.\'); return false;">?</a> ]</li>'."\n";
			echo '<li><code>%%initial_cycle%%</code> = This is the <code>%%initial_term%%</code> from above, converted to a cycle representation of: <code><em>X days/weeks/months/years</em></code>.</li>'."\n";
			echo '<li><code>%%regular_term%%</code> = This is the term length of the Regular Period. This will be a numeric value, followed by a space, then a single letter. [ <a href="#" onclick="alert(\'Here are some examples:\\n\\n%%regular_term%% = 1 D (this means 1 Day)\\n%%regular_term%% = 1 W (this means 1 Week)\\n%%regular_term%% = 1 M (this means 1 Month)\\n%%regular_term%% = 1 Y (this means 1 Year)\\n%%regular_term%% = 1 L (this means 1 Lifetime)\\n\\nThe Regular Term is usually recurring. So the Regular Term value represents the period (or duration) of each recurring period. If %%recurring%% = 0, then the Regular Term only applies once, because it is not recurring. So if it is not recurring, the value of %%regular_term%% simply represents how long their Membership privileges are going to last after the %%initial_term%% has expired, if there was an Initial Term. The value of this variable ( %%regular_term%% ) will never be empty, it will always be at least: 1 D, meaning 1 day. No exceptions.\'); return false;">?</a> ]</li>'."\n";
			echo '<li><code>%%regular_cycle%%</code> = This is the <code>%%regular_term%%</code> from above, converted to a cycle representation of: <code><em>[every] X days/weeks/months/years</em></code>. Or, if applicable, it may simply read <em><code>daily, weekly, bi-weekly, monthly, bi-monthly, quarterly, yearly, or lifetime</em></code>. This is a very useful Replacment Code. Its value is dynamic; depending on term length, recurring status, and period/term lengths configured.</li>'."\n";
			echo '<li><code>%%recurring/regular_cycle%%</code> = Example (<code>14.95 / Monthly</code>), or ... (<code>0 / non-recurring</code>); depending on the value of <code>%%recurring%%</code>.</li>'."\n";
			echo '</ul>'."\n";

			echo '<strong>Custom Registration/Profile Fields are also supported in this email:</strong>'."\n";
			echo '<ul class="ws-menu-page-li-margins">'."\n";
			echo '<li><code>%%date_of_birth%%</code> would be valid; if you have a Custom Registration/Profile Field with the ID <code>date_of_birth</code>.</li>'."\n";
			echo '<li><code>%%street_address%%</code> would be valid; if you have a Custom Registration/Profile Field with the ID <code>street_address</code>.</li>'."\n";
			echo '<li><code>%%country%%</code> would be valid; if you have a Custom Registration/Profile Field with the ID <code>country</code>.</li>'."\n";
			echo '<li><em><code>%%etc, etc...%%</code> <strong>see:</strong> s2Member → General Options → Registration/Profile Fields</em>.</li>'."\n";
			echo '</ul>'."\n";

			echo '<strong>Custom Replacement Codes can also be inserted using these instructions:</strong>'."\n";
			echo '<ul class="ws-menu-page-li-margins">'."\n";
			echo '<li><code>%%cv0%%</code> = The domain of your site, which is passed through the <code>custom</code> attribute in your Shortcode.</li>'."\n";
			echo '<li><code>%%cv1%%</code> = If you need to track additional custom variables, you can pipe delimit them into the `custom` attribute; inside your Shortcode, like this: <code>custom="'.esc_html($_SERVER['HTTP_HOST']).'|cv1|cv2|cv3"</code>. You can have an unlimited number of custom variables. Obviously, this is for advanced webmasters; but the functionality has been made available for those who need it.</li>'."\n";
			echo '</ul>'."\n";
			echo '<strong>This example uses cv1 to record a special marketing campaign:</strong><br />'."\n";
			echo '<em>The campaign (i.e., christmas-promo) could be referenced using <code>%%cv1%%</code>.</em><br />'."\n";
			echo '<code>custom="'.esc_html($_SERVER['HTTP_HOST']).'|christmas-promo"</code>'."\n";

			echo (!is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) ?
				'<div class="ws-menu-page-hr"></div>'."\n".
				'<p style="margin:0;"><strong>PHP Code:</strong> It is also possible to use PHP tags (optional, for developers). Please note that all Replacement Codes will be parsed first, and then any PHP tags that you\'ve included. Also, please remember that emails are sent in plain text format.</p>'."\n"
				: '';
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '</tbody>'."\n";
			echo '</table>'."\n";
			echo '</div>'."\n";
			echo '</div>'."\n";

			echo '</div>'."\n";
		}
	}
}

new c_ws_plugin__s2member_pro_menu_page_paypal_ops_reminder_email ();
