<?php
// @codingStandardsIgnoreFile
/**
 * Menu page for s2Member Pro shortcode template security.
 *
 * @package s2Member\Menu_Pages
 * @since 260812
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_menu_page_gen_ops_shortcode_templates"))
{
	/**
	 * Menu page for s2Member Pro shortcode template security.
	 *
	 * @package s2Member\Menu_Pages
	 * @since 260812
	 */
	class c_ws_plugin__s2member_pro_menu_page_gen_ops_shortcode_templates
	{
		public function __construct()
		{
			echo '<div id="ws-plugin--s2member-pro-shortcode-templates-whitelist"></div>'."\n";
			echo '<div class="ws-menu-page-group" title="Pro Shortcode Templates Whitelist"'.((!empty($_GET['s2member-open-panel']) && $_GET['s2member-open-panel'] === 'pro-shortcode-templates-whitelist') ? ' default-state="open"' : '').'>'."\n";

			echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-sc-templates-whitelist-section">'."\n";
			echo '<h3>Pro Shortcode Templates Whitelist (optional)</h3>'."\n";
			echo '<p>Custom files specified with a <code>template=""</code> attribute in a Pro shortcode are evaluated as template code. A user who can edit site content should not be able to make a shortcode use an unintended file under <code>wp-content</code>. This whitelist lets you explicitly approve the custom template files you trust.</p>'."\n";
			echo '<table class="form-table">'."\n";
			echo '<tbody>'."\n";
			echo '<tr>'."\n";

			echo '<th>'."\n";
			echo '<label for="ws-plugin--s2member-pro-sc-templates-whitelist">'."\n";
			echo 'Allowed custom template files:'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";

			echo '</tr>'."\n";
			echo '<tr>'."\n";

			echo '<td>'."\n";
			echo '<textarea name="ws_plugin__s2member_pro_sc_templates_whitelist" id="ws-plugin--s2member-pro-sc-templates-whitelist" rows="5" style="width:100%;">'.format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_sc_templates_whitelist"]).'</textarea><br />'."\n";
			echo 'Enter one custom template file per line. Each line starts after wp-content/. This is the same path used in the shortcode\'s <code>template</code> attribute. So if your template\'s location is <em>/wp-content/my-member-list.php</em>, you\'d enter <em>my-member-list.php</em> here.<br /><br />'."\n";
			echo '<strong>Examples:</strong><br />'."\n";
			echo '<code>member-list-custom.php</code><br />'."\n";
			echo '<code>s2-templates/member-list-custom.php</code><br />'."\n";
			echo '<code>themes/my-child-theme/stripe-checkout-custom.php</code><br /><br />'."\n";
			echo '<em class="ws-menu-page-caution-hilite"><strong>Note:</strong> Paths outside wp-content or inside WordPress uploads are blocked and can\'t be whitelisted; the shortcode defaults to its standard template instead.</em>'."\n";
			echo '</td>'."\n";

			echo '</tr>'."\n";
			echo '</tbody>'."\n";
			echo '</table>'."\n";
			echo '</div>'."\n";

			echo '</div>'."\n";
		}
	}
}

new c_ws_plugin__s2member_pro_menu_page_gen_ops_shortcode_templates();
