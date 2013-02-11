<?php
/**
* Menu page for s2Member Pro (Login Welcome Page / One-Time-Offers).
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
* @since 111203
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops"))
	{
		/**
		* Menu page for s2Member Pro (Captcha Anti-Spam Preferences).
		*
		* @package s2Member\Menu_Pages
		* @since 111203
		*/
		class c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops
			{
				public function __construct ()
					{
						echo '<div class="ws-menu-page-group" title="CAPTCHA Anti-Spam Security">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-captchas-section">' . "\n";
						echo '<h3>CAPTCHA Anti-Spam Security (for s2Member Pro Forms)</h3>' . "\n";
						echo '<p>Please note. s2Member does NOT introduce a <a href="http://www.s2member.com/captcha-definition" target="_blank" rel="external">CAPTCHA</a> <em>(i.e. a challenge-response)</em>, into any core feature for WordPress®. We\'ve <strong>excluded</strong> this functionality on purpose, because many site owners prefer to use a more comprehensive CAPTCHA plugin that encompasses all aspects of their site. We recommend <a href="http://wordpress.org/extend/plugins/si-captcha-for-wordpress/" target="_blank" rel="external">this one</a>. <strong>However</strong>, s2Member Pro Forms for PayPal® Pro and Authorize.Net® <em>(including Free Registration Forms)</em> CAN be configured with s2Member, to use Google\'s reCAPTCHA™ service <em>(which is free)</em>. Just add this Attribute to your Pro Form Shortcode: <code>captcha="clean"</code>.</p>' . "\n";
						echo '<p><strong>Regarding s2Member Pro Forms.</strong> s2Member comes with a default set of reCAPTCHA™ Keys, both Public and Private. If you leave the fields below empty, s2Member will simply use its own default set of Keys for reCAPTCHA™. However, if you\'re using s2Member Pro Forms, we DO suggest that you acquire your own set of reCAPTCHA™ Keys <em>(it\'s free)</em>. It\'s better to have your own set of Keys, specifically for your domain. Please follow <a href="http://www.s2member.com/recaptcha-create-keys" target="_blank" rel="external">this link</a> to obtain Public/Private Keys for the reCAPTCHA™ service.</p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-pro-recaptcha-public-key">' . "\n";
						echo 'reCAPTCHA™ Public Key:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_pro_recaptcha_public_key" id="ws-plugin--s2member-pro-recaptcha-public-key" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_recaptcha_public_key"]) . '" />' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-pro-recaptcha-private-key">' . "\n";
						echo 'reCAPTCHA™ Private Key:' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="password" autocomplete="off" name="ws_plugin__s2member_pro_recaptcha_private_key" id="ws-plugin--s2member-pro-recaptcha-private-key" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_recaptcha_private_key"]) . '" />' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";
					}
			}
	}

new c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops ();
?>