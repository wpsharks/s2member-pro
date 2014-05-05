<?php
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

/** @var $member_list_query array */
/** @var $query WP_User_Query */
/** @var $pagination array */
$query      = $member_list_query['query'];
$pagination = $member_list_query['pagination'];
?>
<div class="ws-plugin--s2member-member-list">
	<?php foreach($query->get_results() as $_user): ?>
		<?php
		/** @var $_user WP_User */
		?>
	<?php endforeach; ?>
</div>

