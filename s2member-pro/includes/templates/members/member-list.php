<?php
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

/** @var $attr array */
/** @var $member_list_query array */
/** @var $query WP_User_Query */
/** @var $pagination array */
$query      = $member_list_query["query"];
$pagination = $member_list_query["pagination"];
?>
<div class="ws-plugin--s2member-list-wrapper">
	<div class="ws-plugin--s2member-list-container">
		<div class="ws-plugin--s2member-list">

			<?php if($query->get_total()): ?>
				<ul class="ws-plugin--s2member-list-users">
					<?php foreach($query->get_results() as $_user): /** @var $_user WP_User */ ?>
						<li class="ws-plugin--s2member-list-user">

							<?php if($attr["avatar_size"] && $attr["show_avatar"] && ($_avatar = get_avatar($_user->ID, $attr["avatar_size"]))): ?>
								<div class="ws-plugin--s2member-list-user-avatar">
									<?php if(($_avatar_link = c_ws_plugin__s2member_pro_sc_member_list_in::parse_replacement_codes($attr["link_avatar"], $_user))): ?>
										<a href="<?php echo esc_attr($_avatar_link); ?>"<?php echo c_ws_plugin__s2member_pro_sc_member_list_in::link_attributes($_avatar_link); ?>><?php echo $_avatar; ?></a>
									<?php else: echo $_avatar; endif; ?>
								</div>
							<?php endif; ?>

							<?php if($attr["show_display_name"] && $_user->display_name): ?>
								<div class="ws-plugin--s2member-list-user-display-name">
									<?php if(($_display_name_link = c_ws_plugin__s2member_pro_sc_member_list_in::parse_replacement_codes($attr["link_display_name"], $_user))): ?>
										<a href="<?php echo esc_attr($_display_name_link); ?>"<?php echo c_ws_plugin__s2member_pro_sc_member_list_in::link_attributes($_display_name_link); ?>><?php echo esc_html($_user->display_name); ?></a>
									<?php else: echo esc_html($_user->display_name); endif; ?>
								</div>
							<?php endif; ?>

							<?php if(($_fields = preg_split('/[;,\s]+/', $attr["show_fields"], NULL, PREG_SPLIT_NO_EMPTY))): ?>
								<table class="ws-plugin--s2member-list-user-fields">
									<?php foreach($_fields as $_field): ?>
										<?php
										if(strpos($_field, ":") !== FALSE)
											list($_field_label, $_field) = explode(":", $_field, 2);
										else $_field_label = ucwords(preg_replace('/[^a-z0-9]+/i', " ", $_field));

										$_field_value = get_user_field($_field, $_user->ID);
										if($_field_value && is_array($_field_value))
											$_field_value = implode(", ", $_field_value);
										else $_field_value = (string)$_field_value;

										$_field_label = esc_html($_field_label);
										$_field_value = wp_rel_nofollow(make_clickable(esc_html($_field_value)));
										$_field_label = apply_filters("ws_plugin__s2member_pro_sc_member_list_field_label", $_field_label, get_defined_vars());
										$_field_value = apply_filters("ws_plugin__s2member_pro_sc_member_list_field_value", $_field_value, get_defined_vars());
										?>
										<?php if($_field_label && $_field_value): ?>
											<tr>
												<td>
													<?php echo $_field_label; ?>
												</td>
												<td>
													<?php echo $_field_value; ?>
												</td>
											</tr>
										<?php endif; ?>
									<?php endforeach; ?>
								</table>
							<?php endif; ?>

						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if(count($pagination) > 1): ?>
				<ul class="ws-plugin--s2member-list-pagination">
					<?php foreach($pagination as $_page): ?>
						<li><?php echo $_page["link"]; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</div>
	</div>
</div>