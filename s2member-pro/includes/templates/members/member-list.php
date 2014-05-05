<?php
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

/** @var $attr array */
/** @var $member_list_query array */
/** @var $query WP_User_Query */
/** @var $pagination array */
$query      = $member_list_query['query'];
$pagination = $member_list_query['pagination'];
?>
<div class="ws-plugin--s2member-list-wrapper">
	<div class="ws-plugin--s2member-list-container">
		<div class="ws-plugin--s2member-list">

			<ul class="ws-plugin--s2member-list-users">

				<?php foreach($query->get_results() as $_user): /** @var $_user WP_User */ ?>

					<li class="ws-plugin--s2member-list-user">

						<?php if($attr['avatar_size'] && $attr['show_avatar']): ?>
							<div class="ws-plugin--s2member-list-user-avatar">
								<?php echo get_avatar($_user->ID, $attr['avatar_size']); ?>
							</div>
						<?php endif; ?>

						<?php if($attr['show_display_name'] && $_user->display_name): ?>
							<div class="ws-plugin--s2member-list-user-display-name">
								<?php echo esc_html($_user->display_name); ?>
							</div>
						<?php endif; ?>

						<?php foreach(preg_split('/[;,\s]+/', $attr["show_fields"], NULL, PREG_SPLIT_NO_EMPTY) as $_field): ?>
							<?php
							$_field_value = get_user_field($_field, $_user->ID);
							?>
						<?php endforeach;
						unset($_field, $_field_value); ?>

					</li>

				<?php endforeach; ?>

			</ul>

			<ul class="ws-plugin--s2member-list-pagination">

				<?php if(count($pagination) > 1): ?>
					<?php foreach($pagination as $_page): ?>
						<li><?php echo $_page['link']; ?></li>
					<?php endforeach;
					unset($_page); ?>
				<?php endif; ?>

			</ul>

		</div>
	</div>
</div>

