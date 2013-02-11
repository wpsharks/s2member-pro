<?php
/**
* Captcha utilities (introduced by s2Member Pro).
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
* @package s2Member\Utilities
* @since 111203
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_utils_captchas"))
	{
		/**
		* Captcha utilities (introduced by s2Member Pro).
		*
		* @package s2Member\Utilities
		* @since 111203
		*/
		class c_ws_plugin__s2member_pro_utils_captchas
			{
				/**
				* Public/private keys to use for reCAPTCHA™.
				*
				* @package s2Member\Utilities
				* @since 111203
				*
				* @attaches-to ``add_filter("ws_plugin__s2member_recaptcha_keys");``
				*
				* @param array $keys An array with elements: `public` and `private`; passed through by the Filter.
				* @param array $vars An array of defined variables, passed through by the Filter.
				* @return array The array of ``$keys``, after possible modification.
				*/
				public static function recaptcha_keys ($keys = FALSE, $vars = FALSE)
					{
						if (($public = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_recaptcha_public_key"]) && ($private = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_recaptcha_private_key"]))
							$keys = array_merge ((array)$keys, array ("public" => $public, "private" => $private));

						return /* Array of ``$keys``, after possible modification. */ $keys;
					}
			}
	}
?>