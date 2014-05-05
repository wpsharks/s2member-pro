<?php
/**
 * [s2Member-List /] Shortcode.
 *
 * Copyright: © 2009-2011
 * {@link http://www.websharks-inc.com/ WebSharks, Inc.}
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
 *   See: {@link http://www.s2member.com/prices/}
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
 * @package s2Member\Shortcodes
 * @since 140504
 */
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_sc_member_list_in"))
	{
		/**
		 * Shortcode for `[s2Member-List /]`.
		 *
		 * @package s2Member\Shortcodes
		 * @since 140504
		 */
		class c_ws_plugin__s2member_pro_sc_member_list_in
		{
			/**
			 * `[s2Member-List /]` Shortcode.
			 *
			 * @package s2Member\Shortcodes
			 * @since 140504
			 *
			 * @attaches-to ``add_shortcode("s2Member-List");``
			 *
			 * @param array  $attr An array of Attributes.
			 * @param string $content Content inside the Shortcode.
			 * @param string $shortcode The actual Shortcode name itself.
			 *
			 * @return mixed Template file output for this shortcode.
			 */
			public static function shortcode($attr = array(), $content = "", $shortcode = "")
				{
					$wpdb = $GLOBALS["wpdb"];
					/** @var $wpdb \wpdb For IDEs. */

					$defaults        = array(
						"args"              => "",

						"blog"              => $GLOBALS["blog_id"],

						"satisfy"           => "ALL", // `ALL` or `ANY`
						"role"              => "", "level" => "", "ccaps" => "",
						"search"            => "", "search_columns" => "",
						"include"           => "", "exclude" => "",

						"order"             => "DESC",
						"orderby"           => "registered",
						"limit"             => 25,

						"template"          => "",
						"avatar_size"       => 96,
						"show_avatar"       => "yes",
						"show_display_name" => "yes",
						"show_fields"       => ""
					);
					$attr            = shortcode_atts($defaults, $attr);
					$attr["satisfy"] = strtoupper($attr["satisfy"]);
					$attr["order"]   = strtoupper($attr["order"]);

					if($attr["args"]) // Custom args?
						$args = wp_parse_args($attr["args"]);

					else // Convert shortcode attributes to args.
						{
							$args = array(
								"blog_id"        => (integer)$attr["blog"],

								"meta_query"     => array(),
								"role"           => $attr["role"],
								"search"         => $attr["search"],
								"search_columns" => preg_split('/[;,\s]+/', $attr["search_columns"], NULL, PREG_SPLIT_NO_EMPTY),
								"include"        => preg_split('/[;,\s]+/', $attr["include"], NULL, PREG_SPLIT_NO_EMPTY),
								"exclude"        => preg_split('/[;,\s]+/', $attr["exclude"], NULL, PREG_SPLIT_NO_EMPTY),

								"order"          => $attr["order"],
								"orderby"        => $attr["orderby"],
								"number"         => (integer)$attr["limit"],
							);
							if(is_numeric($attr["level"]))
								{
									$args["meta_query"][] = array(
										"key"     => $wpdb->get_blog_prefix()."capabilities",
										"value"   => '"s2member_level'.(integer)$attr['level'].'"',
										"compare" => "LIKE"
									);
									if($attr["satisfy"] === "ANY") // Default is `ALL` (i.e. `AND`).
										$args["meta_query"]["relation"] = "OR";
								}
							if($attr["ccaps"]) // Must satisfy all CCAPs in the list...
								{
									foreach(preg_split('/[;,\s]+/', $attr["ccaps"], NULL, PREG_SPLIT_NO_EMPTY) as $_ccap)
										$args["meta_query"][] = array(
											"key"     => $wpdb->get_blog_prefix()."capabilities",
											"value"   => '"access_s2member_ccap_'.$_ccap.'"',
											"compare" => "LIKE"
										);
									if($attr["satisfy"] === "ANY") // Default is `ALL` (i.e. `AND`).
										$args["meta_query"]["relation"] = "OR";

									unset($_ccap); // Housekeeping.
								}
						}
					$member_list_query = c_ws_plugin__s2member_pro_member_list::query($args);

					$custom_template = (file_exists(TEMPLATEPATH."/member-list.php")) ? TEMPLATEPATH."/member-list.php" : FALSE;
					$custom_template = ($attr["template"] && file_exists(TEMPLATEPATH."/".$attr["template"])) ? TEMPLATEPATH."/".$attr["template"] : $custom_template;
					$custom_template = ($attr["template"] && file_exists(WP_CONTENT_DIR."/".$attr["template"])) ? WP_CONTENT_DIR."/".$attr["template"] : $custom_template;

					$code = trim(file_get_contents((($custom_template) ? $custom_template : dirname(dirname(__FILE__))."/templates/members/member-list.php")));
					$code = trim(((!$custom_template || !is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) ? c_ws_plugin__s2member_utilities::evl($code, get_defined_vars()) : $code));

					return apply_filters("ws_plugin__s2member_pro_sc_member_list", $code, get_defined_vars());
				}
		}
	}