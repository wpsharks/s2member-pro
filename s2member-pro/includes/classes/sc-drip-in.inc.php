<?php
/**
 * [s2Drip] Shortcode.
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
 * @since 140328
 */
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_sc_drip_in"))
	{
		/**
		 * [s2Drip] Shortcode.
		 *
		 * @package s2Member\Shortcodes
		 * @since 140328
		 */
		class c_ws_plugin__s2member_pro_sc_drip_in
		{
			/**
			 * [s2Drip] Shortcode.
			 *
			 * @package s2Member\Shortcodes
			 * @since 140328
			 *
			 * @attaches-to ``add_shortcode("s2Drip");``
			 *
			 * @param array $attr An array of Attributes.
			 * @param str   $content Content inside the Shortcode.
			 * @param str   $shortcode The actual Shortcode name itself.
			 *
			 * @return inner Return-value of inner routine.
			 */
			public static function shortcode($attr = FALSE, $content = FALSE, $shortcode = FALSE)
				{
					foreach(array_keys(get_defined_vars()) as $__v) $__refs[$__v] =& $$__v;
					do_action("ws_plugin__s2member_pro_before_sc_drip", get_defined_vars());
					unset /* Unset defined __refs, __v. */
					($__refs, $__v);

					if(current_user_can("administrator"))
						$drip = TRUE;
					else
						{
							$drip          = FALSE;
							$attr          = shortcode_atts(array("level" => "0", "from_day" => "0", "to_day" => ""), $attr, $shortcode);
							$attr["level"] = abs((integer)$attr["level"]);

							if(current_user_can("access_s2member_level".$attr["level"]))
								{
									$level_time = 0;

									if($attr["level"] === 0)
										$level_time = c_ws_plugin__s2member_registration_times::registration_time();
									else
										{
											$paid_times = // Index include a `level` prefix.
												get_user_option("s2member_paid_registration_times");

											if(is_array($paid_times))
												{
													foreach($paid_times as $_level => $_time)
														{
															$_level = (integer)str_ireplace("level", "", $_level);
															// The `level` index becomes `0` here ^; all others become integers >= 1.
															if($_level && $_level >= $attr["level"] && (!$level_time || $_time < $level_time))
																$level_time = $_time;
														}
													unset($_level, $_time);
												}
										}
									if($level_time)
										{
											$time = time();
											if($time > ($level_time + (max(0, ($attr["from_day"] - 1)) * 86400)))
												{
													$drip = TRUE;
													if(!empty($attr["to_day"]) && $attr["to_day"] > 1)
														if($time > ($level_time + ($attr["to_day"] * 86400)))
															$drip = FALSE;
												}
										}
								}
						}
					return apply_filters("ws_plugin__s2member_pro_sc_drip_content", $drip ? $content : "", get_defined_vars());
				}
		}
	}
?>