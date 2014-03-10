<?php
/**
* Shortcode `[s2Member-Pro-Drip /]`.
*
* Copyright: © 2009-2011
* {@link http://www.websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* Released under the terms of the GNU General Public License.
* You should have received a copy of the GNU General Public License,
* along with this software. In the main directory, see: /licensing/
* If not, see: {@link http://www.gnu.org/licenses/}.
*
* @package s2Member\s2Member_Shortcode_Dripping
* @since 130730
*/
if (!class_exists('c_ws_plugin__s2member_sc_pro_dripping'))
	{
		/**
		* Shortcode `[s2Member-Pro-Drip /]`.
		*
		* @package s2Member\s2Member_Shortcode_Dripping
		* @since 130730
		*/
		class c_ws_plugin__s2member_pro_sc_dripping
			{
				/**
				* Handles the Shortcode for: `[s2Member-Pro-Drip /]`.
				*
				* @package s2Member\s2Member_Pro_Dripping
				* @since 130730
				*
				* @attaches-to ``add_shortcode('s2Member-Pro-Drip');``
				*
				* @param array $attr An array of Attributes.
				* @param str $content Content inside the Shortcode..
				* @return str Returns the ``$content`` if User meets criteria, otherwise ``$else`` or an empty string.
				*/
				public static function sc_drip($atts, $content = NULL)
					{
						extract(shortcode_atts(array(
							'modifier' => '>=',
							'level' => '0',
							'else' => '',
							'period' => ''
						), $atts));
						
						if(empty($content) || !is_user_logged_in() || $period === '') return $else;
						
						if($level === '0')
							$registered_days = S2MEMBER_CURRENT_USER_REGISTRATION_DAYS;
							
						elseif ($level === 'paid')
							$registered_days = S2MEMBER_CURRENT_USER_PAID_REGISTRATION_DAYS;
						else
							{
								$paid_registration_time = s2member_paid_registration_time('level' . $level);
								$current_time = time();
								
								$registered_days = floor(($current_time - $paid_registration_time) / 86400);
							}
							
						$period = (integer)$period;
						
						$modifier = str_replace(' ', '', trim($modifier)); // Makes sure that spaces aren't an issue
						$content = do_shortcode($content);
						
						switch($modifier)
							{
								case '>':
									if($registered_days > $period) return $content;
								break;
								
								case '>=':
								case '=>':
									if($registered_days >= $period) return $content;
								break;
								
								case '<':
									if($registered_days < $period) return $content;
								break;
								
								case '<=':
								case '=<':
									if($registered_days <= $period) return $content;
								break;
								
								case '=':
									if($registered_days === $period) return $content;
								break;
							}
						return $else;
					}
			}
	}
?>