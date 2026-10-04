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
			$form_age_seconds = c_ws_plugin__s2member_pro_antispam::form_age_minimum_seconds();
			$admin_diagnostics_enabled = !empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_admin_diagnostics']);
			$stats = class_exists('c_ws_plugin__s2member_pro_antispam_stats') ? c_ws_plugin__s2member_pro_antispam_stats::summary(30) : array('daily' => array(), 'totals' => array('allowed' => 0, 'blocked' => 0, 'blocked_local' => 0, 'blocked_verification' => 0, 'blocked_spam' => 0, 'blocked_service' => 0));

			$timing = !empty($stats['timing']) && is_array($stats['timing']) ? $stats['timing'] : array(
				'bucket_labels' => array(),
				'metrics' => array(),
			);

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

			echo '<div class="ws-menu-page-group" title="Pro-Form Anti-Spam & Bot Protection (Beta)">'."\n";

			echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-captchas-section">'."\n";
			echo '<h3>Pro-Form Anti-Spam & Bot Protection (Beta)</h3>'."\n";
			echo '<p>Protects s2Member Pro-Forms when their <code>captcha</code> Shortcode attribute is enabled. Protection can combine built-in checks with one external bot check (Google reCAPTCHA or Cloudflare Turnstile). For Pro-Forms that create a new WordPress user, optional signup screening can add another layer.</p>'."\n";
			echo '<p>These are independent pass/fail checks, not a weighted score: a blocking result from any enabled check rejects the submission. <code>captcha="0"</code> disables s2Member anti-spam for that Pro-Form; enabled values such as <code>clean</code>, <code>light</code>, or <code>dark</code> enable it.</p>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Anti-Spam Health: <span class="ws-plugin--s2member-status-light ws-plugin--s2member-status-light-'.esc_attr($overall_status).'" aria-hidden="true"></span>'.esc_html($overall_label).'</h3>'."\n";
			echo '<table class="ws-plugin--s2member-status-table"><tbody>'."\n";
			echo '<tr><th scope="row">Built-in checks:</th><td>'.esc_html(($honeypot_enabled || $form_age_enabled) ? implode(' + ', array_filter(array($honeypot_enabled ? 'Honeypot' : '', $form_age_enabled ? 'Speedtrap ('.$form_age_seconds.'s)' : ''))) : 'Disabled').'</td></tr>'."\n";
			echo '<tr><th scope="row">Bot check:</th><td id="ws-plugin--s2member-pro-antispam-verification-health">'.esc_html($verification_health['label']).'</td></tr>'."\n";
			echo '<tr><th scope="row">Signup screening:</th><td id="ws-plugin--s2member-pro-antispam-spam-health">'.esc_html($spam_health['label']).'</td></tr>'."\n";
			echo '</tbody></table>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Recent Activity (30 days)</h3>'."\n";
			echo '<table class="ws-plugin--s2member-status-table"><tbody>'."\n";
			echo '<tr><th scope="row">Verified/allowed:</th><td>'.esc_html(number_format_i18n((int)$stats['totals']['allowed'])).'</td></tr>'."\n";
			echo '<tr><th scope="row">Blocked:</th><td>'.esc_html(number_format_i18n((int)$stats['totals']['blocked'])).'</td></tr>'."\n";
			echo '<tr><th scope="row">Blocked by layer:</th><td>'.esc_html(sprintf('Built-in %s · Bot check %s · Signup screening %s · Service/configuration %s', number_format_i18n((int)$stats['totals']['blocked_local']), number_format_i18n((int)$stats['totals']['blocked_verification']), number_format_i18n((int)$stats['totals']['blocked_spam']), number_format_i18n((int)$stats['totals']['blocked_service']))).'</td></tr>'."\n";
			echo '</tbody></table>'."\n";
			$_max_daily = 1;
			foreach((array)$stats['daily'] as $_day_stats)
				$_max_daily = max($_max_daily, (int)$_day_stats['allowed'] + (int)$_day_stats['blocked_local'] + (int)$_day_stats['blocked_verification'] + (int)$_day_stats['blocked_spam'] + (int)$_day_stats['blocked_service']);
			//261003.0533 Render a dependency-free aggregate chart; title text keeps exact daily values available without retaining or exposing visitor-level records.
			$_recorded_activity = (int)$stats['totals']['allowed'] + (int)$stats['totals']['blocked'];
			echo '<div class="ws-plugin--s2member-pro-antispam-stats-chart" style="position:relative;display:flex;align-items:flex-end;gap:2px;height:90px;margin:12px 0 6px;border-bottom:1px solid #c3c4c7;background:repeating-linear-gradient(to top,transparent 0,transparent 21px,#dcdcde 22px);">'."\n";
			if(!$_recorded_activity)
				echo '<span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#646970;font-style:italic;">No recorded activity in the last 30 days.</span>'."\n";
			foreach((array)$stats['daily'] as $_day => $_day_stats)
				{
					$_allowed = (int)$_day_stats['allowed'];
					$_blocked = (int)$_day_stats['blocked_local'] + (int)$_day_stats['blocked_verification'] + (int)$_day_stats['blocked_spam'] + (int)$_day_stats['blocked_service'];
					$_allowed_height = (int)round(78 * ($_allowed / $_max_daily));
					$_blocked_height = (int)round(78 * ($_blocked / $_max_daily));
					echo '<span title="'.esc_attr($_day.' — Allowed: '.$_allowed.', Blocked: '.$_blocked).'" style="position:relative;z-index:1;display:flex;flex:1;min-width:2px;height:78px;align-items:flex-end;gap:1px;"><i aria-hidden="true" style="display:block;flex:1;height:'.esc_attr($_allowed_height).'px;background:#2271b1;"></i><i aria-hidden="true" style="display:block;flex:1;height:'.esc_attr($_blocked_height).'px;background:#d63638;"></i></span>'."\n";
				}
			echo '</div><p><small><span style="color:#2271b1">■</span> Allowed &nbsp; <span style="color:#d63638">■</span> Blocked. Aggregate counts only; timing is stored only as aggregate buckets. s2Member does not store IP addresses, emails, usernames, tokens, individual durations, or submitted form data in these statistics.</small></p>'."\n";

			echo '<h4>Submission Timing</h4>'."\n";
			echo '<p>Current Speedtrap threshold: <strong>'.esc_html(number_format_i18n($form_age_seconds)).' seconds</strong>. Timing samples are collected only while Speedtrap is enabled on an applicable Pro-Form.</p>'."\n";
			echo '<table class="ws-plugin--s2member-status-table"><tbody>'."\n";
			$_timing_rows = array(
				'allowed_form_age' => 'Allowed — form age',
				'allowed_interaction' => 'Allowed — active entry',
				'blocked_form_age' => 'Blocked by anti-spam — form age',
				'blocked_speedtrap_form_age' => 'Speedtrap blocks — form age',
			);
			foreach($_timing_rows as $_timing_metric => $_timing_label)
				{
					$_timing_data = !empty($timing['metrics'][$_timing_metric]) && is_array($timing['metrics'][$_timing_metric]) ? $timing['metrics'][$_timing_metric] : array('total' => 0, 'p10' => '', 'median' => '', 'buckets' => array());
					$_timing_total = isset($_timing_data['total']) ? (int)$_timing_data['total'] : 0;
					if($_timing_total)
						{
							$_timing_p10 = !empty($_timing_data['p10']) && isset($timing['bucket_labels'][$_timing_data['p10']]) ? $timing['bucket_labels'][$_timing_data['p10']] : '—';
							$_timing_median = !empty($_timing_data['median']) && isset($timing['bucket_labels'][$_timing_data['median']]) ? $timing['bucket_labels'][$_timing_data['median']] : '—';
							$_timing_value = sprintf('%s samples · Fast-end (10th percentile): %s · Median: %s', number_format_i18n($_timing_total), $_timing_p10, $_timing_median);
						}
					else
						$_timing_value = 'No samples yet · Fast-end (10th percentile): — · Median: —';
					echo '<tr><th scope="row">'.esc_html($_timing_label).':</th><td>'.esc_html($_timing_value).'</td></tr>'."\n";
				}
			echo '</tbody></table>'."\n";
			echo '<p><small>Form age is trusted server time from form generation to submission. Active entry is browser-observed from the visitor\'s first field edit/change to submission and is advisory only; it never affects blocking.</small></p>'."\n";
			if(!empty($timing['bucket_labels']))
				{
					echo '<details><summary>Timing distribution</summary><div style="overflow-x:auto;margin-top:8px;"><table class="widefat striped" style="width:auto;min-width:760px;"><thead><tr><th>Metric</th>'."\n";
					foreach((array)$timing['bucket_labels'] as $_timing_bucket_label)
						echo '<th>'.esc_html($_timing_bucket_label).'</th>'."\n";
					echo '</tr></thead><tbody>'."\n";
					foreach($_timing_rows as $_timing_metric => $_timing_label)
						{
							$_timing_data = !empty($timing['metrics'][$_timing_metric]) && is_array($timing['metrics'][$_timing_metric]) ? $timing['metrics'][$_timing_metric] : array('buckets' => array());
							echo '<tr><th scope="row">'.esc_html($_timing_label).'</th>'."\n";
							foreach((array)$timing['bucket_labels'] as $_timing_bucket => $_timing_bucket_label)
								echo '<td>'.esc_html(number_format_i18n(isset($_timing_data['buckets'][$_timing_bucket]) ? (int)$_timing_data['buckets'][$_timing_bucket] : 0)).'</td>'."\n";
							echo '</tr>'."\n";
						}
					echo '</tbody></table></div></details>'."\n";
				}

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Diagnostics</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-admin-diagnostics">Show Admin Diagnostics on Pro-Forms</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_admin_diagnostics" id="ws-plugin--s2member-pro-antispam-admin-diagnostics">'."\n";
			echo '<option value="0"'.(!$admin_diagnostics_enabled ? ' selected="selected"' : '').'>Disabled</option>'."\n";
			echo '<option value="1"'.($admin_diagnostics_enabled ? ' selected="selected"' : '').'>Enabled</option>'."\n";
			echo '</select><br /><em>When enabled, administrators see a credential-free status panel beneath each Pro-Form plus a dry-run Simulate Bot button. Visitors never see the panel.</em></td></tr>'."\n";
			echo '</tbody></table>'."\n";
			echo '<p style="display:flex;align-items:flex-start;gap:8px;"><button type="button" class="button" style="flex:none;" id="ws-plugin--s2member-pro-antispam-self-test" onclick="return window.ws_plugin__s2member_pro_antispam_self_test ? window.ws_plugin__s2member_pro_antispam_self_test() : false;">Simulate Bot</button><span id="ws-plugin--s2member-pro-antispam-self-test-result" style="flex:1;min-width:0;"></span></p>'."\n";
			echo '<p><em>This dry run does not submit a form, create an account, or contact a payment gateway.</em></p>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Built-in Protection</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-honeypot">Honeypot</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_honeypot" id="ws-plugin--s2member-pro-antispam-honeypot">'."\n";
			echo '<option value="1"'.($honeypot_enabled ? ' selected="selected"' : '').'>Enabled</option>'."\n";
			echo '<option value="0"'.(!$honeypot_enabled ? ' selected="selected"' : '').'>Disabled</option>'."\n";
			echo '</select><br /><em>Invisible decoy field. Legitimate visitors never need to interact with it.</em></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-form-age">Speedtrap</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_form_age" id="ws-plugin--s2member-pro-antispam-form-age">'."\n";
			echo '<option value="1"'.($form_age_enabled ? ' selected="selected"' : '').'>Enabled</option>'."\n";
			echo '<option value="0"'.(!$form_age_enabled ? ' selected="selected"' : '').'>Disabled</option>'."\n";
			echo '</select><br /><em>Blocks submissions made unusually soon after the server generated the form. Cancellation forms are exempt because they have no meaningful form-fill time.</em></td></tr>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-form-age-seconds">Speedtrap Threshold</label></th></tr>'."\n";
			echo '<tr><td><input type="number" min="1" max="60" step="1" name="ws_plugin__s2member_pro_antispam_form_age_seconds" id="ws-plugin--s2member-pro-antispam-form-age-seconds" value="'.esc_attr($form_age_seconds).'" /> seconds<br /><em>Default: <code>5</code> seconds. Slow-loading or cached forms only make this check more permissive; there is no maximum form age.</em></td></tr>'."\n";
			echo '</tbody></table>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Bot Check</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-verification-provider">Provider</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_verification_provider" id="ws-plugin--s2member-pro-antispam-verification-provider">'."\n";
			echo '<option value="none"'.($verification_provider === 'none' ? ' selected="selected"' : '').'>None (built-in checks only)</option>'."\n";
			echo '<option value="recaptcha_v2"'.(($verification_provider === '' || $verification_provider === 'recaptcha_v2') ? ' selected="selected"' : '').'>Google reCAPTCHA v2 (Legacy)</option>'."\n";
			echo '<option value="recaptcha_v3"'.($verification_provider === 'recaptcha_v3' ? ' selected="selected"' : '').'>Google reCAPTCHA v3</option>'."\n";
			echo '<option value="turnstile"'.($verification_provider === 'turnstile' ? ' selected="selected"' : '').'>Cloudflare Turnstile</option>'."\n";
			echo '</select><br /><em>Choose one external bot-check provider. Save provider/key changes before running a configuration test. Untested modern credentials do not replace an existing legacy CAPTCHA.</em></td></tr>'."\n";
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

			echo '<p><button type="button" class="button" id="ws-plugin--s2member-pro-antispam-test-verification">Test Bot Check</button> <span id="ws-plugin--s2member-pro-antispam-test-verification-result"></span></p>'."\n";

			echo '<div class="ws-menu-page-hr"></div>'."\n";
			echo '<h3>Signup Screening</h3>'."\n";
			echo '<table class="form-table"><tbody>'."\n";
			echo '<tr><th><label for="ws-plugin--s2member-pro-antispam-spam-provider">Service</label></th></tr>'."\n";
			echo '<tr><td><select name="ws_plugin__s2member_pro_antispam_spam_provider" id="ws-plugin--s2member-pro-antispam-spam-provider">'."\n";
			echo '<option value=""'.($spam_provider === '' ? ' selected="selected"' : '').'>None</option>'."\n";
			echo '<option value="akismet"'.($spam_provider === 'akismet' ? ' selected="selected"' : '').'>Akismet</option>'."\n";
			echo '</select><br /><em>Checks new-account submissions for spam. Applies only when a Pro-Form creates a new WordPress user; payment-only and account-maintenance forms are not submitted to Akismet as signups.</em></td></tr>'."\n";
			echo '<tr id="ws-plugin--s2member-pro-antispam-akismet-key-row"><th><label for="ws-plugin--s2member-pro-antispam-akismet-api-key">Akismet API Key</label></th></tr>'."\n";
			echo '<tr id="ws-plugin--s2member-pro-antispam-akismet-key-value-row"><td><input type="password" autocomplete="off" name="ws_plugin__s2member_pro_antispam_akismet_api_key" id="ws-plugin--s2member-pro-antispam-akismet-api-key" value="'.esc_attr($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_akismet_api_key']).'" /><br /><em>Leave blank to reuse the API key from the Akismet plugin when available.</em></td></tr>'."\n";
			echo '</tbody></table>'."\n";
			echo '<p><button type="button" class="button" id="ws-plugin--s2member-pro-antispam-test-spam">Test Signup Screening</button> <span id="ws-plugin--s2member-pro-antispam-test-spam-result"></span></p>'."\n";

			$_ajax_nonce = wp_create_nonce('ws-plugin--s2member-pro-antispam-test');
			$_self_test_nonce = wp_create_nonce('ws-plugin--s2member-pro-antispam-self-test');
			$_saved_turnstile_site_key = (string)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_turnstile_site_key'];
			$_saved_recaptcha_v3_site_key = (string)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_antispam_recaptcha_v3_site_key'];

			//261003.0407 Keep diagnostics admin-only and test saved credentials in place; provider test metadata is persisted only after server-side verification succeeds.
			echo '<script>(function($){'."\n";
			echo 'var nonce='.wp_json_encode($_ajax_nonce).',selfTestNonce='.wp_json_encode($_self_test_nonce).',turnstileSiteKey='.wp_json_encode($_saved_turnstile_site_key).',recaptchaV3SiteKey='.wp_json_encode($_saved_recaptcha_v3_site_key).';'."\n";
			echo 'function sync(){var p=$("#ws-plugin--s2member-pro-antispam-verification-provider").val();$(".ws-plugin--s2member-pro-antispam-provider-settings").hide();if(p==="turnstile")$("#ws-plugin--s2member-pro-antispam-turnstile-settings").show();else if(p==="recaptcha_v3")$("#ws-plugin--s2member-pro-antispam-recaptcha-v3-settings").show();else if(p==="recaptcha_v2"||p==="")$("#ws-plugin--s2member-pro-antispam-recaptcha-v2-settings").show();var s=$("#ws-plugin--s2member-pro-antispam-spam-provider").val();$("#ws-plugin--s2member-pro-antispam-akismet-key-row,#ws-plugin--s2member-pro-antispam-akismet-key-value-row").toggle(s==="akismet");}'."\n";
			echo 'function result($el,message,ok){$el.text(message).css("font-weight","bold").css("color",ok?"#2271b1":"#b32d2e");}'."\n";
			echo 'function postTest(provider,token,$out){$out.text("Testing...");$.post(ajaxurl,{action:"ws_plugin__s2member_pro_antispam_test_configuration",nonce:nonce,provider:provider,token:token||""}).done(function(r){if(r&&r.success){result($out,r.data.message+" ("+r.data.label+")",true);if(provider==="akismet")$("#ws-plugin--s2member-pro-antispam-spam-health").text(r.data.label);else $("#ws-plugin--s2member-pro-antispam-verification-health").text(r.data.label);}else result($out,r&&r.data&&r.data.message?r.data.message:"Configuration test failed.",false);}).fail(function(xhr){var r=xhr.responseJSON;result($out,r&&r.data&&r.data.message?r.data.message:"Configuration test failed.",false);});}'."\n";
			echo '$("#ws-plugin--s2member-pro-antispam-verification-provider,#ws-plugin--s2member-pro-antispam-spam-provider").on("change",sync);sync();'."\n";
			echo '$("#ws-plugin--s2member-pro-antispam-test-verification").on("click",function(){var p=$("#ws-plugin--s2member-pro-antispam-verification-provider").val(),$out=$("#ws-plugin--s2member-pro-antispam-test-verification-result");if(p==="")p="recaptcha_v2";if(p==="none"){result($out,"No external bot-check provider is selected.",false);return;}if(p==="turnstile"){if(!turnstileSiteKey||!window.turnstile){result($out,"Save the Turnstile keys first, then reload this page to test them.",false);return;}var $w=$("#ws-plugin--s2member-pro-antispam-turnstile-test-widget").empty();window.turnstile.render($w[0],{sitekey:turnstileSiteKey,action:"s2member_pro_form",callback:function(t){postTest("turnstile",t,$out);},"error-callback":function(){result($out,"Turnstile could not complete the browser test.",false);}});return;}if(p==="recaptcha_v3"){if(!recaptchaV3SiteKey||!window.grecaptcha){result($out,"Save the reCAPTCHA v3 keys first, then reload this page to test them.",false);return;}grecaptcha.ready(function(){grecaptcha.execute(recaptchaV3SiteKey,{action:"s2member_config_test"}).then(function(t){postTest("recaptcha_v3",t,$out);},function(){result($out,"reCAPTCHA v3 could not complete the browser test.",false);});});return;}postTest(p,"",$out);});'."\n";
			echo '$("#ws-plugin--s2member-pro-antispam-test-spam").on("click",function(){var p=$("#ws-plugin--s2member-pro-antispam-spam-provider").val(),$out=$("#ws-plugin--s2member-pro-antispam-test-spam-result");if(!p){result($out,"No signup-screening service is selected.",false);return;}postTest(p,"",$out);});'."\n";
			//261003.2111 Keep Simulate Bot independent of admin-page event propagation quirks; the server-rendered button calls this namespaced function directly.
			echo 'window.ws_plugin__s2member_pro_antispam_self_test=function(){var $out=$("#ws-plugin--s2member-pro-antispam-self-test-result");$out.text("Testing...");$.post(ajaxurl,{action:"ws_plugin__s2member_pro_antispam_self_test",nonce:selfTestNonce,surface:"registration",captcha:"clean"}).done(function(r){result($out,r&&r.data&&r.data.message?r.data.message:"Self-test completed.",true);}).fail(function(xhr){var r=xhr.responseJSON;result($out,r&&r.data&&r.data.message?r.data.message:"Self-test failed.",false);});return false;};'."\n";
			echo '})(jQuery);</script>'."\n";
			echo '</div>'."\n";
			echo '</div>'."\n";

			unset($_verification_adapter, $_spam_adapter, $_verification_active, $_active_component, $_attention_statuses, $_ajax_nonce, $_self_test_nonce, $_saved_turnstile_site_key, $_saved_recaptcha_v3_site_key, $_max_daily, $_recorded_activity, $_day_stats, $_day, $_allowed, $_blocked, $_allowed_height, $_blocked_height, $_timing_rows, $_timing_metric, $_timing_label, $_timing_data, $_timing_total, $_timing_p10, $_timing_median, $_timing_value, $_timing_bucket, $_timing_bucket_label);
		}
	}
}

new c_ws_plugin__s2member_pro_menu_page_gen_ops_captcha_ops();
