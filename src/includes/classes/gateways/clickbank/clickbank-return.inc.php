<?php
// @codingStandardsIgnoreFile
/**
* ClickBank Return URL handler.
*
* Copyright: © 2009-2011
* {@link http://websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* This WordPress plugin (s2Member Pro) is comprised of two parts:
*
* o (1) Its PHP code is licensed under the GPL license, as is WordPress.
* 	You should have received a copy of the GNU General Public License,
* 	along with this software. In the main directory, see: /licensing/
* 	If not, see: {@link http://www.gnu.org/licenses/}.
*
* o (2) All other parts of (s2Member Pro); including, but not limited to:
* 	the CSS code, some JavaScript code, images, and design;
* 	are licensed according to the license purchased.
* 	See: {@link http://s2member.com/prices/}
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
* @package s2Member\ClickBank
* @since 1.5
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_clickbank_return"))
	{
		/**
		* ClickBank Return URL handler.
		*
		* @package s2Member\ClickBank
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_clickbank_return
			{
				/**
				* Handles ClickBank Return URL processing.
				*
				* @package s2Member\ClickBank
				* @since 1.5
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null|inner Return-value of inner routine.
				*/
				public static function clickbank_return ()
					{
						if (!empty($_GET['s2member_pro_cb_return'])) {
							$_GET['s2member_pro_clickbank_return']     = $_GET['s2member_pro_cb_return'];
							$_REQUEST['s2member_pro_clickbank_return'] = $_GET['s2member_pro_cb_return'];
						}
							
						if (!empty($_GET['s2member_pro_cb_return_success'])) {
							$_GET['s2member_pro_clickbank_return_success']     = $_GET['s2member_pro_cb_return_success'];
							$_REQUEST['s2member_pro_clickbank_return_success'] = $_GET['s2member_pro_cb_return_success'];
						}
							
						if (!empty($_GET["s2member_pro_clickbank_return"]))
							{
								return c_ws_plugin__s2member_pro_clickbank_return_in::clickbank_return ();
							}
					}
			}
	}
