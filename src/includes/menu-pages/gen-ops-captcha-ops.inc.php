<?php
// @codingStandardsIgnoreFile
/**
 * Menu page for s2Member Pro (Login Welcome Page / One-Time-Offers).
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
 * @since 111203
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit ("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops"))
{
	/**
	 * Menu page for s2Member Pro (Captcha Anti-Spam Preferences).
	 *
	 * @package s2Member\Menu_Pages
	 * @since 111203
	 */
	class c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops
	{
		public function __construct()
		{
			$verification_provider = c_ws_plugin__s2member_pro_antispam::verification_provider();
			$spam_provider = c_ws_plugin__s2member_pro_antispam::spam_provider();
			$honeypot_enabled = c_ws_plugin__s2member_pro_antispam::honeypot_enabled();
			$form_age_enabled = c_ws_plugin__s2member_pro_antispam::form_age_enabled();

			$effective_verification_provider = $verification_provider ? $verification_provider : 'recaptcha_v2';
			$verification_health = array('status' => 'disabled', 'label' => 'Disabled');
			if($verification_provider === '')
				{
					$_verification_adapter = c_ws_plugin__s2member_pro_antispam::adapter_class('recaptcha_v2');
					$verification_health = ($_verification_adapter && call_user_func(array($_verification_adapter, 'configured'))) ? call_user_func(array($_verification_adapter, 'health')) : array('status' => 'legacy', 'label' => 'Legacy/default behavior');
				}
			elseif($verification_provider !== 'none')
				{
					$_verification_adapter = c_ws_plugin__s2member_pro_antispam::adapter_class($effective_verification_provider);
					if($_verification_adapter)
						$verification_health = call_user_func(array($_verification_adapter, 'health'));
				}
			$spam_health = array('status' => 'disabled', 'label' => 'Disabled');
			if($spam_provider)
				{
					$_spam_adapter = c_ws_plugin__s2member_pro_antispam::adapter_class($spam_provider);
					if($_spam_adapter)
						$spam_health = call_user_func(array($_spam_adapter, 'health'));
				}

			$overall_status = 'disabled';
			$overall_label = 'Disabled';
			$_verification_active = ($verification_provider === '') ? ($verification_health['status'] === 'healthy') : ($verification_provider !== 'none' && $verification_health['status'] !== 'disabled');
			$_active_component = $honeypot_enabled || $form_age_enabled || $_verification_active || $spam_health['status'] !== 'disabled';
			$_attention_statuses = array('unavailable', 'needs_configuration', 'needs_test');
			if($_active_component)
				{
					$overall_status = (in_array($verification_health['status'], $_attention_statuses, TRUE) || in_array($spam_health['status'], $_attention_statuses, TRUE)) ? 'attention' : 'healthy';
					$overall_label = ($overall_status === 'healthy') ? 'Healthy' : 'Needs attention';
				}

			//261003.0407 Load provider scripts only on this admin screen and only for the saved provider being tested; normal Pro-Form frontend loading remains adapter-owned.
			if($effective_verification_provider === 'turnstile' && c_ws_plugin__s2member_pro_antispam_turnstile::site_key())
				wp_enqueue_script('ws-plugin--s2member-pro-antispam-turnstile-admin-test', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', array(), NULL, TRUE);
			elseif($effective_verification_provider === 'recaptcha_v3' && c_ws_plugin__s2member_pro_antispam_recaptcha_v3::site_key())
				wp_enqueue_script('ws-plugin--s2member-pro-antispam-recaptcha-v3-admin-test', 'https://www.google.com/recaptcha/api.js?render='.rawurlencode(c_ws_plugin__s2member_pro_antispam_recaptcha_v3::site_key()), array(), NULL, TRUE);

			echo '<div class="ws-menu-page-group" title="Anti-Spam & Bot Protection">'."\n";

			echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-captchas-section">'."\n";
			echo '<h3>Anti-Spam & Bot Protection (for s2Member Pro-Forms)</h3>'."\n";
			echo '<p>s2Member Pro-Forms can layer quiet built-in bot checks with a bot-verification service and optional signup-spam detection. The existing <code>captcha</code> Shortcode attribute remains the per-form switch: <code>captcha="0"</code> disables s2Member anti-spam on that form; any existing enabled value such as <code>clean</code>, <code>light</code>, or <code>dark</code> enables it.</p>'."\n";
			echo '<p>Existing reCAPTCHA v2 sites remain compatible. A modern provider does not replace legacy reCAPTCHA until its saved configuration passes the test below.</p>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Anti-Spam Health: <span class="ws-plugin--s2member-status-light ws-plugin--s2member-status-light-'.esc_attr($overall_status).'" aria-hidden="true"></span>'.esc_html($overall_label).'</h3>'."\n";
			echo '<table class="ws-plugin--s2member-status-table"><tbody>'."\n";
			echo '<tr><th scope="row">Built-in checks:</th><td>'.esc_html(($honeypot_enabled || $form_age_enabled) ? implode(' + ', array_filter(array($honeypot_enabled ? 'Honeypot' : '', $form_age_enabled ? 'Minimum form age' : ''))) : 'Disabled').'</td></tr>'."\n";
			echo '<tr><th scope="row">Bot verification:</th><td id="ws-plugin--s2member-pro-antispam-verification-health">'.esc_html($verification_health['label']).'</td></tr>'."\n";
			echo '<tr><th scope="row">Signup spam detection:</th><td id="ws-plugin--s2member-pro-antispam-spam-health">'.esc_html($spam_health['label']).'</td></tr>'."\n";
			echo '</tbody></table>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Built-in Protection</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-honeypot">Honeypot</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_honeypot" id="ws-plugin--s2member-pro-antispam-honeypot">'."\n";
			echo '<option value="1"'.($honeypot_enabled ? ' selected="selected"' : '').'>Enabled</option>'."\n";
			echo '<option value="0"'.(!$honeypot_enabled ? ' selected="selected"' : '').'>Disabled</option>'."\n";
			echo '</select><br /><em>Invisible decoy field. Legitimate visitors never need to interact with it.</em></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-form-age">Minimum Form Age</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_form_age" id="ws-plugin--s2member-pro-antispam-form-age">'."\n";
			echo '<option value="1"'.($form_age_enabled ? ' selected="selected"' : '').'>Enabled</option>'."\n";
			echo '<option value="0"'.(!$form_age_enabled ? ' selected="selected"' : '').'>Disabled</option>'."\n";
			echo '</select><br /><em>Rejects only impossibly fast submissions. There is no maximum age, so cached forms remain valid.</em></td></tr>'."\n";
			echo '</tbody></table>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Bot Verification</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-verification-provider">Provider</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_verification_provider" id="ws-plugin--s2member-pro-antispam-verification-provider">'."\n";
			echo '<option value=""'.($verification_provider === '' ? ' selected="selected"' : '').'>Legacy/default reCAPTCHA behavior</option>'."\n";
			echo '<option value="none"'.($verification_provider === 'none' ? ' selected="selected"' : '').'>None (built-in checks only)</option>'."\n";
			echo '<option value="turnstile"'.($verification_provider === 'turnstile' ? ' selected="selected"' : '').'>Cloudflare Turnstile</option>'."\n";
			echo '<option value="recaptcha_v3"'.($verification_provider === 'recaptcha_v3' ? ' selected="selected"' : '').'>Google reCAPTCHA v3</option>'."\n";
			echo '<option value="recaptcha_v2"'.($verification_provider === 'recaptcha_v2' ? ' selected="selected"' : '').'>Google reCAPTCHA v2 (Legacy)</option>'."\n";
			echo '</select><br /><em>Save provider/key changes before running a configuration test. Untested modern credentials do not replace an existing legacy CAPTCHA.</em></td></tr>'."\n";
			echo '</tbody></table>'."\n";

			echo '<div id="ws-plugin--s2member-pro-antispam-turnstile-settings" class="ws-plugin--s2member-pro-antispam-provider-settings">'."\n";
			echo '<h4>Cloudflare Turnstile</h4><table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-turnstile-site-key">Site Key</label></th></tr>'."\n";
			echo '<tr><td><input type="text" autocomplete="off" name="ws_plugin__s2member_pro_antispam_turnstile_site_key" id="ws-plugin--s2member-pro-antispam-turnstile-site-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_site_key']).'" /></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-turnstile-secret-key">Secret Key</label></th></tr>'."\n";
			echo '<tr><td><input type="password" autocomplete="off" name="ws_plugin__s2member_pro_antispam_turnstile_secret_key" id="ws-plugin--s2member-pro-antispam-turnstile-secret-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_secret_key']).'" /></td></tr>'."\n";
			echo '</tbody></table><div id="ws-plugin--s2member-pro-antispam-turnstile-test-widget"></div>'."\n";
			echo '</div>'."\n";

			echo '<div id="ws-plugin--s2member-pro-antispam-recaptcha-v3-settings" class="ws-plugin--s2member-pro-antispam-provider-settings">'."\n";
			echo '<h4>Google reCAPTCHA v3</h4><table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-recaptcha-v3-site-key">Site Key</label></th></tr>'."\n";
			echo '<tr><td><input type="text" autocomplete="off" name="ws_plugin__s2member_pro_antispam_recaptcha_v3_site_key" id="ws-plugin--s2member-pro-antispam-recaptcha-v3-site-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_site_key']).'" /></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-recaptcha-v3-secret-key">Secret Key</label></th></tr>'."\n";
			echo '<tr><td><input type="password" autocomplete="off" name="ws_plugin__s2member_pro_antispam_recaptcha_v3_secret_key" id="ws-plugin--s2member-pro-antispam-recaptcha-v3-secret-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_secret_key']).'" /></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-recaptcha-v3-minimum-score">Minimum Score</label></th></tr>'."\n";
			echo '<tr><td><input type="number" min="0" max="1" step="0.1" name="ws_plugin__s2member_pro_antispam_recaptcha_v3_minimum_score" id="ws-plugin--s2member-pro-antispam-recaptcha-v3-minimum-score" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_minimum_score']).'" /><br /><em>Google commonly suggests starting around <code>0.5</code>. Higher values are stricter.</em></td></tr>'."\n";
			echo '</tbody></table></div>'."\n";

			echo '<div id="ws-plugin--s2member-pro-antispam-recaptcha-v2-settings" class="ws-plugin--s2member-pro-antispam-provider-settings">'."\n";
			echo '<h4>Google reCAPTCHA v2 (Legacy)</h4><table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-recaptcha2-public-key">Site Key</label></th></tr>'."\n";
			echo '<tr><td><input type="text" autocomplete="off" name="ws_plugin__s2member_pro_recaptcha2_public_key" id="ws-plugin--s2member-pro-recaptcha2-public-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_recaptcha2_public_key']).'" /></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-recaptcha2-private-key">Secret Key</label></th></tr>'."\n";
			echo '<tr><td><input type="password" autocomplete="off" name="ws_plugin__s2member_pro_recaptcha2_private_key" id="ws-plugin--s2member-pro-recaptcha2-private-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_recaptcha2_private_key']).'" /></td></tr>'."\n";
			echo '</tbody></table></div>'."\n";

			echo '<p><button type="button" class="button" id="ws-plugin--s2member-pro-antispam-test-verification">Test Bot Verification</button> <span id="ws-plugin--s2member-pro-antispam-test-verification-result"></span></p>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Signup Spam Detection</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-spam-provider">Service</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_spam_provider" id="ws-plugin--s2member-pro-antispam-spam-provider">'."\n";
			echo '<option value=""'.($spam_provider === '' ? ' selected="selected"' : '').'>None</option>'."\n";
			echo '<option value="akismet"'.($spam_provider === 'akismet' ? ' selected="selected"' : '').'>Akismet</option>'."\n";
			echo '</select><br /><em>Signup spam detection applies only when a Pro-Form creates a new WordPress user account; payment-only and account-maintenance forms are not submitted as Akismet signups.</em></td></tr>'."\n";
			echo '<tr id="ws-plugin--s2member-pro-antispam-akismet-key-row"><th><label for="ws-plugin--s2member-pro-antispam-akismet-api-key">Akismet API Key</label></th></tr>'."\n";
			echo '<tr id="ws-plugin--s2member-pro-antispam-akismet-key-value-row"><td><input type="password" autocomplete="off" name="ws_plugin__s2member_pro_antispam_akismet_api_key" id="ws-plugin--s2member-pro-antispam-akismet-api-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_akismet_api_key']).'" /><br /><em>Leave blank to reuse the API key from the Akismet plugin when available.</em></td></tr>'."\n";
			echo '</tbody></table>'."\n";
			echo '<p><button type="button" class="button" id="ws-plugin--s2member-pro-antispam-test-spam">Test Spam Detection</button> <span id="ws-plugin--s2member-pro-antispam-test-spam-result"></span></p>'."\n";

			$_ajax_nonce = wp_create_nonce('ws-plugin--s2member-pro-antispam-test');
			$_saved_turnstile_site_key = (string)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_site_key'];
			$_saved_recaptcha_v3_site_key = (string)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_site_key'];

			//261003.0407 Keep diagnostics admin-only and test saved credentials in place; provider test metadata is persisted only after server-side verification succeeds.
			echo '<script>(function($){'."\n";
			echo 'var nonce='.wp_json_encode($_ajax_nonce).',turnstileSiteKey='.wp_json_encode($_saved_turnstile_site_key).',recaptchaV3SiteKey='.wp_json_encode($_saved_recaptcha_v3_site_key).';'."\n";
			echo 'function sync(){var p=$("#ws-plugin--s2member-pro-antispam-verification-provider").val();$(".ws-plugin--s2member-pro-antispam-provider-settings").hide();if(p==="turnstile")$("#ws-plugin--s2member-pro-antispam-turnstile-settings").show();else if(p==="recaptcha_v3")$("#ws-plugin--s2member-pro-antispam-recaptcha-v3-settings").show();else if(p==="recaptcha_v2"||p==="")$("#ws-plugin--s2member-pro-antispam-recaptcha-v2-settings").show();var s=$("#ws-plugin--s2member-pro-antispam-spam-provider").val();$("#ws-plugin--s2member-pro-antispam-akismet-key-row,#ws-plugin--s2member-pro-antispam-akismet-key-value-row").toggle(s==="akismet");}'."\n";
			echo 'function result($el,message,ok){$el.text(message).css("font-weight","bold").css("color",ok?"#2271b1":"#b32d2e");}'."\n";
			echo 'function postTest(provider,token,$out){$out.text("Testing...");$.post(ajaxurl,{action:"ws_plugin__s2member_pro_antispam_test_configuration",nonce:nonce,provider:provider,token:token||""}).done(function(r){if(r&&r.success){result($out,r.data.message+" ("+r.data.label+")",true);if(provider==="akismet")$("#ws-plugin--s2member-pro-antispam-spam-health").text(r.data.label);else $("#ws-plugin--s2member-pro-antispam-verification-health").text(r.data.label);}else result($out,r&&r.data&&r.data.message?r.data.message:"Configuration test failed.",false);}).fail(function(xhr){var r=xhr.responseJSON;result($out,r&&r.data&&r.data.message?r.data.message:"Configuration test failed.",false);});}'."\n";
			echo '$("#ws-plugin--s2member-pro-antispam-verification-provider,#ws-plugin--s2member-pro-antispam-spam-provider").on("change",sync);sync();'."\n";
			echo '$("#ws-plugin--s2member-pro-antispam-test-verification").on("click",function(){var p=$("#ws-plugin--s2member-pro-antispam-verification-provider").val(),$out=$("#ws-plugin--s2member-pro-antispam-test-verification-result");if(p==="")p="recaptcha_v2";if(p==="none"){result($out,"No external bot-verification provider is selected.",false);return;}if(p==="turnstile"){if(!turnstileSiteKey||!window.turnstile){result($out,"Save the Turnstile keys first, then reload this page to test them.",false);return;}var $w=$("#ws-plugin--s2member-pro-antispam-turnstile-test-widget").empty();window.turnstile.render($w[0],{sitekey:turnstileSiteKey,action:"s2member_pro_form",callback:function(t){postTest("turnstile",t,$out);},"error-callback":function(){result($out,"Turnstile could not complete the browser test.",false);}});return;}if(p==="recaptcha_v3"){if(!recaptchaV3SiteKey||!window.grecaptcha){result($out,"Save the reCAPTCHA v3 keys first, then reload this page to test them.",false);return;}grecaptcha.ready(function(){grecaptcha.execute(recaptchaV3SiteKey,{action:"s2member_config_test"}).then(function(t){postTest("recaptcha_v3",t,$out);},function(){result($out,"reCAPTCHA v3 could not complete the browser test.",false);});});return;}postTest(p,"",$out);});'."\n";
			echo '$("#ws-plugin--s2member-pro-antispam-test-spam").on("click",function(){var p=$("#ws-plugin--s2member-pro-antispam-spam-provider").val(),$out=$("#ws-plugin--s2member-pro-antispam-test-spam-result");if(!p){result($out,"No signup-spam service is selected.",false);return;}postTest(p,"",$out);});'."\n";
			echo '})(jQuery);</script>'."\n";
			echo '</div>'."\n";
			echo '</div>'."\n";

			unset($_verification_adapter, $_spam_adapter, $_verification_active, $_active_component, $_attention_statuses, $_ajax_nonce, $_saved_turnstile_site_key, $_saved_recaptcha_v3_site_key);
		}
	}
}

new c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops();
