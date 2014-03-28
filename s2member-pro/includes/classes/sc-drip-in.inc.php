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
					$drip = FALSE;
					shortcode_atts(array("level" => "0", "after_day" => "0", "until_day" => ""), $attr, $shortcode);
					$attr["level"] = (integer)$attr["level"]; // Non-integers become `0` here.

					if(is_super_admin() || current_user_can("administrator")) $drip = TRUE;
					// This is a bit confusing even still; we need to note this behavior in the docs.
					// Particularly in the case of `until_day`; which is completely ignored here.

					else if(current_user_can("access_s2member_level".$attr["level"]))
						{
							$level_time = 0; // Initialize as `0` (not paid).

							if($attr["level"] === 0) // Zero indicates registration time (paid or not).
								$level_time = c_ws_plugin__s2member_registration_times::registration_time();

							// Here we"re looking at the paid registration time.
							// We need to look at Levels >= the Level requirement passed to the shortcode.
							else if(is_array($pr_times = get_user_option("s2member_paid_registration_times")))
								foreach($pr_times as $_pr_level => $_pr_level_time)
									{
										if(is_numeric($_pr_level)) // Considers `level` index.
											if($_pr_level >= $attr["level"] && (!$level_time || $_pr_level_time < $level_time))
												// The oldest time; at a Level >= the Level requirement.
												$level_time = $_pr_level_time;
									}
							unset($_pr_level, $_pr_level_time);

							if($level_time) // If they have a paid registration time.
								// Or, a registration time in the case of `$attr["level"] === 0`.
								{
									$time = time(); // Current UTC time.

									if($time > ($level_time + ($attr["after_day"] * 86400)))
										{
											$drip = TRUE; // It is after the required day.

											if(!empty($attr["until_day"]) && $attr["until_day"] > 1)
												if($time > ($level_time + (($attr["until_day"] - 1) * 86400)))
													// Do NOT drip, it is after the `until_day` requirement.
													$drip = FALSE; // Looks good to me also.
										}
								}
						}
					return $drip ? $content : ""; // Looks good to me also.
				}
		}
	}
?>