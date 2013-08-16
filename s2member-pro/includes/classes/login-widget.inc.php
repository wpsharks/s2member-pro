<?php
/**
* Pro Login Widget.
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
* @package s2Member\Widgets
* @since 1.5
*/
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_login_widget"))
	{
		/**
		* Pro Login Widget.
		*
		* @package s2Member\Widgets
		* @since 1.5
		*
		* @todo Optimize these methods with ``empty()`` and ``isset()``.
		*/
		class c_ws_plugin__s2member_pro_login_widget // << Register widget class.
			extends WP_Widget // See: /wp-includes/widgets.php for further details.
			{
				/**
				* Constructor.
				*
				* @package s2Member\Widgets
				* @since 1.5
				*
				* @return null
				*/
				public function c_ws_plugin__s2member_pro_login_widget() // Builds the classname, id_base, description, etc.
					{
						$widget_ops = array("classname" => "colors", "description" => "Displays a Login Form if NOT logged in. Or a Profile Summary when a User/Member is logged in.");
						$control_ops = array("width" => 400, "id_base" => "ws_plugin__s2member_pro_login_widget");

						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action("ws_plugin__s2member_pro_login_widget_before_construction", get_defined_vars(), $this);
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);

						$this->WP_Widget($control_ops["id_base"], "s2Member Pro (Login Widget)", $widget_ops, $control_ops);

						do_action("ws_plugin__s2member_pro_login_widget_after_construction", get_defined_vars(), $this);

						return /* Return for uniformity. */;
					}
				/**
				* Widget display.
				*
				* @package s2Member\Widgets
				* @since 1.5
				*
				* @param array $args Optional. An array of basic settings.
				* @param array $instance Optional. An array of options for this instance.
				* @return null
				*/
				public function widget($args = FALSE, $instance = FALSE)
					{
						$options = c_ws_plugin__s2member_pro_login_widget::configure_options_and_their_defaults((array)$instance);

						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action("ws_plugin__s2member_pro_login_widget_before_display", get_defined_vars(), $this);
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);

						echo $args["before_widget"]; // OK, here we go into this widget.

						if((is_user_logged_in() && strlen($options["profile_title"])) || (!is_user_logged_in() && strlen($options["title"])))
							echo $args["before_title"].apply_filters("widget_title", $options[((is_user_logged_in()) ? "profile_title" : "title")]).$args["after_title"];

						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action("ws_plugin__s2member_pro_login_widget_during_display_before", get_defined_vars(), $this);
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);

						if(!is_user_logged_in()) // The User/Member is NOT logged in.
							{
								$links = c_ws_plugin__s2member_cache::cached_page_links();

								$ops_page = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"];

								if($ops_page && is_page($ops_page) && /* Seeking something specific? */ !empty($_GET["_s2member_seeking"]["_uri"]))
									$seeking = trim(base64_decode(trim(stripslashes((string)$_GET["_s2member_seeking"]["_uri"]))));

								$options["login_redirect"] = ($options["login_redirect"] === "%%previous%%" && empty($seeking) && $ops_page && is_page($ops_page)) ? "" : $options["login_redirect"];
								$options["login_redirect"] = ($options["login_redirect"] === "%%previous%%" && empty($seeking) && is_front_page()) ? "" : $options["login_redirect"];
								$options["login_redirect"] = ($options["login_redirect"] === "%%previous%%" && !empty($seeking) && $seeking === "/") ? "" : $options["login_redirect"];

								$redirect_to = $options["login_redirect"];
								$redirect_to = preg_replace("/%%previous%%/i", ((!empty($seeking)) ? $seeking : $_SERVER["REQUEST_URI"]), $redirect_to);
								$redirect_to = preg_replace("/%%home%%/i", home_url("/"), $redirect_to);

								echo '<div class="ws-plugin--s2member-pro-login-widget">'."\n";

								echo '<form method="post" action="'.esc_attr(site_url("wp-login.php", "login_post")).'" class="ws-plugin--s2member-pro-login-widget-form">'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-username">'."\n";
								echo '<label for="ws-plugin--s2member-pro-login-widget-username">'._x("Username", "s2member-front", "s2member").':</label><br />'."\n";
								echo '<input type="text" name="log" id="ws-plugin--s2member-pro-login-widget-username" title="Username" />'."\n";
								echo '</div>'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-password">'."\n";
								echo '<label for="ws-plugin--s2member-pro-login-widget-password">'._x("Password", "s2member-front", "s2member").':</label><br />'."\n";
								echo '<input type="password" name="pwd" id="ws-plugin--s2member-pro-login-widget-password" title="Password" />'."\n";
								echo '</div>'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-lost-password">'."\n";
								$reg_cookies_ok_url = (is_multisite() && c_ws_plugin__s2member_utils_conds::is_multisite_farm() && is_main_site()) ? c_ws_plugin__s2member_utils_urls::wp_signup_url() : c_ws_plugin__s2member_utils_urls::wp_register_url();
								echo ($options["signup_url"]) ? '<a href="'.esc_attr(($options["signup_url"] !== "%%automatic%%") ? $options["signup_url"] : ((c_ws_plugin__s2member_register_access::reg_cookies_ok()) ? $reg_cookies_ok_url : $links["membership_options_page"])).'" tabindex="-1">'._x("signup now", "s2member-front", "s2member").'</a> | ' : '';
								echo '<a href="'.esc_attr(wp_lostpassword_url()).'" tabindex="-1">'._x("forgot password?", "s2member-front", "s2member").'</a>'."\n";
								echo '</div>'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-remember-me">'."\n";
								echo '<label><input type="checkbox" name="rememberme" value="forever" />'._x("Remember Me", "s2member-front", "s2member").'</label>'."\n";
								echo '</div>'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-submit">'."\n";
								echo ($redirect_to) ? '<input type="hidden" name="redirect_to" value="'.esc_attr($redirect_to).'" />'."\n" : '';
								echo '<input type="submit" value="'.esc_attr(_x("Log Me In", "s2member-front", "s2member")).'" />'."\n";
								echo '</div>'."\n";

								echo '</form>'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-code">'."\n";

								if(is_multisite() && c_ws_plugin__s2member_utils_conds::is_multisite_farm() && !is_main_site())
									echo do_shortcode(trim($options["logged_out_code"])); // No PHP code.

								else // Otherwise, it's OK to execute PHP code.
									echo do_shortcode(c_ws_plugin__s2member_utilities::evl(trim($options["logged_out_code"])));

								echo '</div>'."\n";

								echo '<div style="clear:both;"></div>'."\n";

								echo '</div>'."\n";
							}

						else if(is_user_logged_in() && is_object($user = wp_get_current_user()) && !empty($user->ID) && ($user_id = $user->ID))
							{
								$links = c_ws_plugin__s2member_cache::cached_page_links();

								$ops_page = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"];
								$welcome_page = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["login_welcome_page"];

								$options["logout_redirect"] = ($options["logout_redirect"] === "%%previous%%" && $ops_page && is_page($ops_page)) ? "" : $options["logout_redirect"];
								$options["logout_redirect"] = ($options["logout_redirect"] === "%%previous%%" && $welcome_page && is_page($welcome_page)) ? "" : $options["logout_redirect"];

								$redirect_to = preg_replace("/%%previous%%/i", $_SERVER["REQUEST_URI"], ($redirect_to = $options["logout_redirect"]));
								$redirect_to = preg_replace("/%%home%%/i", home_url("/"), $redirect_to);

								echo '<div id="ws-plugin--s2member-pro-login-widget" class="ws-plugin--s2member-pro-login-widget">'."\n";

								echo '<div class="ws-plugin--s2member-pro-login-widget-profile-summary">'."\n";

								echo ($options["display_gravatar"]) ? (($options["link_gravatar"]) ? '<a href="http://www.gravatar.com/" target="_blank">' : '').get_avatar($user_id, 48).(($options["link_gravatar"]) ? '</a>' : '')."\n" : '';

								echo ($options["display_name"]) ? '<div class="ws-plugin--s2member-pro-login-widget-profile-summary-name">'.esc_html($user->display_name).'</div>'."\n" : '';

								echo '<div class="ws-plugin--s2member-pro-login-widget-profile-summary-code">'."\n";

								if(is_multisite() && c_ws_plugin__s2member_utils_conds::is_multisite_farm() && !is_main_site())
									echo do_shortcode(trim($options["logged_in_code"])); // No PHP code.

								else // Otherwise, it's OK to execute PHP code.
									echo do_shortcode(c_ws_plugin__s2member_utilities::evl(trim($options["logged_in_code"])));

								echo '</div>'."\n";

								echo ($options["my_account_url"]) ? '<div class="ws-plugin--s2member-pro-login-widget-profile-summary-my-account"><a href="'.esc_attr(($options["my_account_url"] !== "%%automatic%%") ? c_ws_plugin__s2member_login_redirects::fill_login_redirect_rc_vars($options["my_account_url"]) : (($login_redirection_url = c_ws_plugin__s2member_login_redirects::login_redirection_url($user)) ? $login_redirection_url : $links["login_welcome_page"])).'">'._x("My Account", "s2member-front", "s2member").'</a></div>'."\n" : '';
								echo ($options["my_profile_url"]) ? '<div class="ws-plugin--s2member-pro-login-widget-profile-summary-edit-profile"><a href="'.(($options["my_profile_url"] !== "%%automatic%%") ? esc_attr(c_ws_plugin__s2member_login_redirects::fill_login_redirect_rc_vars($options["my_profile_url"])) : esc_attr(site_url("/?s2member_profile=1")).'" onclick="if(!window.open(\''.c_ws_plugin__s2member_utils_strings::esc_js_sq(esc_attr(site_url("/?s2member_profile=1"))).'\',\'_profile\', \'width=600,height=400,left=\'+((screen.width/2)-(600/2))+\',screenX=\'+((screen.width/2)-(600/2))+\',top=\'+((screen.height/2)-(400/2))+\',screenY=\'+((screen.height/2)-(400/2))+\',location=0,menubar=0,toolbar=0,status=0,scrollbars=1,resizable=1\')) alert(\''.c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Please disable popup blockers and try again!", "s2member-front", "s2member")).'\'); return false;').'">'._x("Edit My Profile", "s2member-front", "s2member").'</a></div>'."\n" : '';
								echo '<div class="ws-plugin--s2member-pro-login-widget-profile-summary-logout"><a href="'.esc_attr(wp_logout_url($redirect_to)).'">'._x("Logout", "s2member-front", "s2member").'</a></div>'."\n";

								echo '<div style="clear:both;"></div>'."\n";

								echo '</div>'."\n";

								echo '</div>'."\n";
							}

						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action("ws_plugin__s2member_pro_login_widget_during_display_after", get_defined_vars(), $this);
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);

						echo $args["after_widget"];

						do_action("ws_plugin__s2member_pro_login_widget_after_display", get_defined_vars(), $this);

						return /* Return for uniformity. */;
					}
				/**
				* Widget form control.
				*
				* @package s2Member\Widgets
				* @since 1.5
				*
				* @param array $instance Optional. An array of options for this instance.
				* @return null
				*/
				public function form($instance = FALSE)
					{
						$options = c_ws_plugin__s2member_pro_login_widget::configure_options_and_their_defaults((array)$instance);

						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action("ws_plugin__s2member_pro_login_widget_before_form", get_defined_vars(), $this);
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);
						/*
						Ok, here is where we need to handle the widget control form. This allows a user to further customize the widget.
						*/
						echo '<label for="'.esc_attr($this->get_field_id("title")).'"><strong>Public Title</strong> (when NOT logged in)</label><br />'."\n";
						echo '<input type="text" autocomplete="off" id="'.esc_attr($this->get_field_id("title")).'" name="'.esc_attr($this->get_field_name("title")).'" value="'.format_to_edit($options["title"]).'" class="widefat" /><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("login_redirect")).'">Redirection After Login</label><br />'."\n";
						echo '<select id="'.esc_attr($this->get_field_id("login_redirect")).'" name="'.esc_attr($this->get_field_name("login_redirect")).'" class="widefat"><option value=""'.((!$options["login_redirect"]) ? ' selected="selected"' : '').'>Login Welcome Page</option><option value="%%previous%%"'.(($options["login_redirect"] === "%%previous%%") ? ' selected="selected"' : '').'>Previous page</option><option value="%%home%%"'.(($options["login_redirect"] === "%%home%%") ? ' selected="selected"' : '').'>Home Page</option></select><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("signup_url")).'">Signup Now (enter URL, or just use <code>%%automatic%%</code>)</label><br />'."\n";
						echo '<input type="text" autocomplete="off" id="'.esc_attr($this->get_field_id("signup_url")).'" name="'.esc_attr($this->get_field_name("signup_url")).'" value="'.format_to_edit($options["signup_url"]).'" class="widefat" /><br />'."\n";
						echo '<small>(leave blank to exclude this link)</small><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("code")).'">Additional XHTML'.((!is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) ? '/PHP' : '').' Code?</label><br />'."\n";
						echo '<textarea id="'.esc_attr($this->get_field_id("logged_out_code")).'" name="'.esc_attr($this->get_field_name("logged_out_code")).'" rows="1" cols="1" class="widefat" style="height:50px;">'.format_to_edit($options["logged_out_code"]).'</textarea>'."\n";

						echo '<div style="margin:15px 0 15px 0; height:1px; line-height:1px; background:#CCCCCC;"></div>'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("profile_title")).'"><strong>Profile Title</strong> (when logged-in)</label><br />'."\n";
						echo '<input type="text" autocomplete="off" id="'.esc_attr($this->get_field_id("profile_title")).'" name="'.esc_attr($this->get_field_name("profile_title")).'" value="'.format_to_edit($options["profile_title"]).'" class="widefat" /><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("display_gravatar")).'">Display Gravatar Image?</label><br />'."\n";
						echo '<select id="'.esc_attr($this->get_field_id("display_gravatar")).'" name="'.esc_attr($this->get_field_name("display_gravatar")).'" class="widefat"><option value="1"'.(($options["display_gravatar"]) ? ' selected="selected"' : '').'>Yes, display Gravatar</option><option value="0"'.((!$options["display_gravatar"]) ? ' selected="selected"' : '').'>No, do NOT display</option></select><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("link_gravatar")).'">Link To Gravatar.com?</label><br />'."\n";
						echo '<select id="'.esc_attr($this->get_field_id("link_gravatar")).'" name="'.esc_attr($this->get_field_name("link_gravatar")).'" class="widefat"><option value="1"'.(($options["link_gravatar"]) ? ' selected="selected"' : '').'>Yes, apply link</option><option value="0"'.((!$options["link_gravatar"]) ? ' selected="selected"' : '').'>No, do NOT apply</option></select><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("display_name")).'">Display User\'s Name?</label><br />'."\n";
						echo '<select id="'.esc_attr($this->get_field_id("display_name")).'" name="'.esc_attr($this->get_field_name("display_name")).'" class="widefat"><option value="1"'.(($options["display_name"]) ? ' selected="selected"' : '').'>Yes, display User\'s name</option><option value="0"'.((!$options["display_name"]) ? ' selected="selected"' : '').'>No, do NOT display</option></select><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("my_account_url")).'">My Account (enter URL, or just use <code>%%automatic%%</code>)</label><br />'."\n";
						echo '<input type="text" autocomplete="off" id="'.esc_attr($this->get_field_id("my_account_url")).'" name="'.esc_attr($this->get_field_name("my_account_url")).'" value="'.format_to_edit($options["my_account_url"]).'" class="widefat" /><br />'."\n";
						echo '<small>(leave blank to exclude this link)</small><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("my_profile_url")).'">Edit Profile (enter URL, or use <code>%%automatic%%</code>)</label><br />'."\n";
						echo '<input type="text" autocomplete="off" id="'.esc_attr($this->get_field_id("my_profile_url")).'" name="'.esc_attr($this->get_field_name("my_profile_url")).'" value="'.format_to_edit($options["my_profile_url"]).'" class="widefat" /><br />'."\n";
						echo '<small>(leave blank to exclude this link)</small><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("logout_redirect")).'">Redirection After Logout</label><br />'."\n";
						echo '<select id="'.esc_attr($this->get_field_id("logout_redirect")).'" name="'.esc_attr($this->get_field_name("logout_redirect")).'" class="widefat"><option value="%%home%%"'.(($options["logout_redirect"] === "%%home%%") ? ' selected="selected"' : '').'>Home Page</option><option value="%%previous%%"'.(($options["logout_redirect"] === "%%previous%%") ? ' selected="selected"' : '').'>Previous page</option><option value=""'.((!$options["logout_redirect"]) ? ' selected="selected"' : '').'>Login screen</option></select><br /><br />'."\n";

						echo '<label for="'.esc_attr($this->get_field_id("code")).'">Additional XHTML'.((!is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) ? '/PHP' : '').' Code?</label><br />'."\n";
						echo '<textarea id="'.esc_attr($this->get_field_id("logged_in_code")).'" name="'.esc_attr($this->get_field_name("logged_in_code")).'" rows="1" cols="1" class="widefat" style="height:50px;">'.format_to_edit($options["logged_in_code"]).'</textarea>'."\n";

						echo (!is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) ? '<div style="margin:15px 0 15px 0; height:1px; line-height:1px; background:#CCCCCC;"></div>'."\n" : '';
						echo (!is_multisite() || !c_ws_plugin__s2member_utils_conds::is_multisite_farm() || is_main_site()) ? '<em>Or include this widget dynamically via PHP:<br />'.c_ws_plugin__s2member_utils_strings::highlight_php('<?php echo s2member_pro_login_widget(); ?>').'<br /><small>See: <code>s2Member -› API Scripting -› Pro Login Widget</code></small></em>'."\n" : '';

						do_action("ws_plugin__s2member_pro_login_widget_after_form", get_defined_vars(), $this);

						echo '<br />'."\n";

						return /* Return for uniformity. */;
					}
				/**
				* Widget updates.
				*
				* @package s2Member\Widgets
				* @since 1.5
				*
				* @param array $instance Optional. An array of options for this instance.
				* @param array $old Optional. An old array of options for this instance.
				* @return array New array of options for this instance.
				*/
				public function update($instance = FALSE, $old = FALSE)
					{
						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action("ws_plugin__s2member_pro_login_widget_before_update", get_defined_vars(), $this);
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);

						$instance = (array)c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($instance));

						return c_ws_plugin__s2member_pro_login_widget::configure_options_and_their_defaults($instance);
					}
				/**
				* Configure/validate all widget options; and set their defaults.
				*
				* @package s2Member\Widgets
				* @since 1.5
				*
				* @param array $options Optional. An array of options for a particular instance.
				* @return array Array of options, after having been validated and merged with defaults.
				*/
				public function configure_options_and_their_defaults($options = FALSE)
					{
						$default_options = apply_filters("ws_plugin__s2member_pro_login_widget_default_options", array("title" => _x("Membership Login", "s2member-front", "s2member"), "profile_title" => _x("My Profile Summary", "s2member-front", "s2member"), "signup_url" => "%%automatic%%", "my_account_url" => "%%automatic%%", "my_profile_url" => "%%automatic%%", "login_redirect" => "", "logout_redirect" => "%%home%%", "logged_in_code" => "", "logged_out_code" => "", "display_gravatar" => "1", "link_gravatar" => "1", "display_name" => "1"));

						$options = array_merge($default_options, (array)$options); // Merge options with defaults.

						foreach($options as $key => /* By reference. */ &$value)
							{
								if(!isset($default_options[$key]))
									unset($options[$key]);

								else if(($key === "title" || $key === "profile_title") && !is_string($value))
									$value = $default_options[$key];

								else if(preg_match("/^(signup|my_account|my_profile)_url$/", $key) && !is_string($value))
									$value = $default_options[$key];

								else if(preg_match("/^(login|logout)_redirect$/", $key) && !is_string($value))
									$value = $default_options[$key];

								else if(preg_match("/^logged_(in|out)_code$/", $key) && !is_string($value))
									$value = $default_options[$key];

								else if(preg_match("/^(display|link)_(name|gravatar)$/", $key) && (!is_string($value) || !is_numeric($value)))
									$value = $default_options[$key];
							}
						return apply_filters("ws_plugin__s2member_pro_login_widget_options", $options);
					}
			}
	}
?>