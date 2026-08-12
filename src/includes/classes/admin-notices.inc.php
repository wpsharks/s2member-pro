<?php
// @codingStandardsIgnoreFile
/**
 * Administrative notices for s2Member Pro.
 *
 * @package s2Member\Admin_Notices
 * @since 260812
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if(!class_exists('c_ws_plugin__s2member_pro_admin_notices'))
{
	/**
	 * Administrative notices for s2Member Pro.
	 *
	 * @package s2Member\Admin_Notices
	 * @since 260812
	 */
	class c_ws_plugin__s2member_pro_admin_notices
	{
		/**
		 * Supplies exact custom template files approved for Pro shortcodes.
		 *
		 * @package s2Member\Admin_Notices
		 * @since 260812
		 *
		 * @param array $whitelist Current shortcode templates whitelist.
		 * @return array Approved paths relative to WP_CONTENT_DIR.
		 */
		public static function shortcode_templates_whitelist($whitelist = array())
		{
			$whitelist = (is_array($whitelist)) ? $whitelist : array();
			//260813 Use the submitted whitelist on save so the notice updates immediately.
			$_using_submitted_whitelist = !empty($_POST['ws_plugin__s2member_options_save']) && is_string($_POST['ws_plugin__s2member_options_save']) && wp_verify_nonce($_POST['ws_plugin__s2member_options_save'], 'ws-plugin--s2member-options-save') && isset($_POST['ws_plugin__s2member_pro_sc_templates_whitelist']) && is_string($_POST['ws_plugin__s2member_pro_sc_templates_whitelist']);
			$_whitelist = ($_using_submitted_whitelist) ? trim((string)wp_unslash($_POST['ws_plugin__s2member_pro_sc_templates_whitelist'])) : ((!empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_sc_templates_whitelist'])) ? (string)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_sc_templates_whitelist'] : '');
			$_whitelist = ($_whitelist !== '') ? preg_split('/[\r\n]+/', $_whitelist, -1, PREG_SPLIT_NO_EMPTY) : array();

			return array_merge($whitelist, $_whitelist);
		}

		/**
		 * Clears a previous warning when an exact custom template has been approved.
		 *
		 * @package s2Member\Admin_Notices
		 * @since 260812
		 *
		 * @param string $template Template path relative to WP_CONTENT_DIR.
		 * @param string $shortcode Shortcode name.
		 * @param int $post_id Post/Page ID.
		 */
		public static function shortcode_template_approved($template = '', $shortcode = '', $post_id = 0)
		{
			$_templates = (array)get_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', array());
			if(isset($_templates[$template]))
			{
				unset($_templates[$template]);
				if($_templates)
					update_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', $_templates, FALSE);
				else delete_option('ws_plugin__s2member_pro_unapproved_shortcode_templates');
			}
		}

		/**
		 * Records an unapproved custom template and where it was encountered.
		 *
		 * @package s2Member\Admin_Notices
		 * @since 260812
		 *
		 * @param string $template Template path relative to WP_CONTENT_DIR.
		 * @param string $shortcode Shortcode name.
		 * @param int $post_id Post/Page ID.
		 */
		public static function shortcode_template_unapproved($template = '', $shortcode = '', $post_id = 0)
		{
			$template = trim((string)$template);
			$shortcode = trim((string)$shortcode);
			if(!$template || !$shortcode)
				return;

			$_templates = (array)get_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', array());
			if(!isset($_templates[$template]) && count($_templates) >= 20)
				return;
			$_old_templates = $_templates;

			if(empty($_templates[$template]) || !is_array($_templates[$template]))
				$_templates[$template] = array('shortcodes' => array(), 'post_ids' => array());
			else
			{
				$_templates[$template]['shortcodes'] = (!empty($_templates[$template]['shortcodes']) && is_array($_templates[$template]['shortcodes'])) ? $_templates[$template]['shortcodes'] : array();
				$_templates[$template]['post_ids'] = (!empty($_templates[$template]['post_ids']) && is_array($_templates[$template]['post_ids'])) ? $_templates[$template]['post_ids'] : array();
			}
			if(!in_array($shortcode, $_templates[$template]['shortcodes'], TRUE))
				$_templates[$template]['shortcodes'][] = $shortcode;

			//260812 Limit stored Post/Page references so warning data stays small even when a shortcode appears many times.
			$post_id = (int)$post_id;
			if($post_id > 0 && count($_templates[$template]['post_ids']) < 20 && !in_array($post_id, $_templates[$template]['post_ids'], TRUE))
				$_templates[$template]['post_ids'][] = $post_id;

			if($_templates !== $_old_templates)
				update_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', $_templates, FALSE);
		}

		/**
		 * Dismisses the shortcode template security notice until another unapproved template is detected.
		 *
		 * @package s2Member\Admin_Notices
		 * @since 260812
		 */
		public static function dismiss_shortcode_template_notice()
		{
			if(!is_admin() || !current_user_can('create_users') || empty($_GET['ws-plugin--s2member-dismiss-shortcode-template-notice']))
				return;

			check_admin_referer('ws-plugin--s2member-dismiss-shortcode-template-notice');
			delete_option('ws_plugin__s2member_pro_unapproved_shortcode_templates');

			wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url());
			exit;
		}

		/**
		 * Displays a security notice for custom shortcode template files that are not approved.
		 *
		 * @package s2Member\Admin_Notices
		 * @since 260812
		 */
		public static function shortcode_template_notice()
		{
			if(!current_user_can('create_users'))
				return;

			$_templates = (array)get_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', array());
			if(!$_templates)
				return;

			// Remove stale files and entries that have since been approved.
			foreach($_templates as $_template => $_details)
			{
				if(!is_string($_template) || !is_array($_details) || !is_file(WP_CONTENT_DIR.'/'.ltrim($_template, '/\\')))
					unset($_templates[$_template]);
				else c_ws_plugin__s2member_utils_dirs::shortcode_template($_template, array(), '', 0, TRUE);
			}

			// Re-read after the resolver has cleared entries that are now approved.
			$_stored_templates = (array)get_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', array());
			$_templates = array_intersect_key($_templates, $_stored_templates);
			if($_templates !== $_stored_templates)
			{
				if($_templates)
					update_option('ws_plugin__s2member_pro_unapproved_shortcode_templates', $_templates, FALSE);
				else delete_option('ws_plugin__s2member_pro_unapproved_shortcode_templates');
			}
			if(!$_templates)
				return;

			// Build a useful list with the shortcode and edit links when the originating Post/Page is known.
			$_template_items = array();
			foreach($_templates as $_template => $_details)
			{
				$_item = esc_html($_template);
				$_shortcodes = (!empty($_details['shortcodes'])) ? array_unique((array)$_details['shortcodes']) : array();
				if($_shortcodes)
					$_item .= ' — ['.esc_html(implode('], [', $_shortcodes)).']';

				$_post_links = array();
				foreach((!empty($_details['post_ids'])) ? array_unique(array_map('intval', (array)$_details['post_ids'])) : array() as $_post_id)
				{
					if($_post_id > 0 && ($_edit_link = get_edit_post_link($_post_id, '')))
					{
						$_post_title = get_the_title($_post_id);
						$_post_title = ($_post_title !== '') ? $_post_title : '(no title)';
						$_post_links[] = '<a href="'.esc_url($_edit_link).'">'.esc_html($_post_title).' (#'.$_post_id.')</a>';
					}
				}
				if($_post_links)
					$_item .= ' — '.implode(', ', $_post_links);

				$_template_items[] = $_item;
			}

			$_settings_url = add_query_arg('s2member-open-panel', 'pro-shortcode-templates-whitelist', admin_url('/admin.php?page=ws-plugin--s2member-gen-ops')).'#ws-plugin--s2member-pro-shortcode-templates-whitelist';
			$_dismiss_url = wp_nonce_url(add_query_arg('ws-plugin--s2member-dismiss-shortcode-template-notice', '1', admin_url()), 'ws-plugin--s2member-dismiss-shortcode-template-notice');
			$_message = 'Some Pro shortcodes use custom template files that are not in <em><a href="'.esc_url($_settings_url).'">s2Member → General Options → Pro Shortcode Templates Whitelist</a></em>';
			c_ws_plugin__s2member_admin_notices::display_security_notice($_message, 'Review the files below and allow the ones you trust:', $_template_items, $_dismiss_url);
		}
	}
}
