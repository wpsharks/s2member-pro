<?php
/**
 * Per-level EOT demotion rules for s2Member Pro.
 *
 * @package s2Member\Pro\EOT_Rules
 * @since 261007
 */
if(!defined('WPINC'))
	exit('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_eot_rules'))
{
	/**
	 * Applies optional destination and capability-removal rules to paid levels.
	 *
	 * @package s2Member\Pro\EOT_Rules
	 * @since 261007
	 */
	class c_ws_plugin__s2member_pro_eot_rules
	{
		/**
		 * Normalizes stored or submitted rows without initializing WordPress roles.
		 *
		 * Empty rows use the existing defaults and need no stored override. The first
		 * nonempty rule for a paid role wins when a request contains duplicate rows.
		 * Registered-role existence is checked later, when applying a destination.
		 *
		 * @param mixed $rules Candidate rule rows.
		 * @return array Ordered rows with from_role, to_role and remove_ccaps fields.
		 */
		public static function normalize($rules)
		{
			$normalized = $seen = array();
			foreach(is_array($rules) ? $rules : array() as $row)
			{
				if(!is_array($row) || !isset($row['from_role']) || !is_string($row['from_role']))
					continue;
				$from = trim($row['from_role']);
				if(!preg_match('/^s2member_level[1-9][0-9]*$/', $from) || isset($seen[$from]))
					continue;
				$to = isset($row['to_role']) && is_string($row['to_role']) ? trim($row['to_role']) : '';
				if($to !== 'subscriber' && !preg_match('/^s2member_level[1-9][0-9]*$/', $to))
					$to = '';
				$names = array();
				if(isset($row['remove_ccaps']) && is_string($row['remove_ccaps']))
					foreach(explode(',', $row['remove_ccaps']) as $name)
						if(($name = sanitize_key(trim($name))) !== '' && !in_array($name, $names, TRUE))
							$names[] = $name;
				$ccaps = implode(',', $names);
				if($to === '' && $ccaps === '')
					continue;
				$seen[$from] = TRUE;
				$normalized[] = array('from_role' => $from, 'to_role' => $to, 'remove_ccaps' => $ccaps);
			}
			return $normalized;
		}

		/**
		 * Finds the configured override for a prior paid membership role.
		 *
		 * @param string $role Paid membership role before EOT.
		 * @return array Matching normalized row, or an empty array for the defaults.
		 */
		private static function rule_for_role($role)
		{
			$rules = isset($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_demotion_rules']) ? $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_demotion_rules'] : array();
			foreach(self::normalize($rules) as $row)
				if($row['from_role'] === $role)
					return $row;
			return array();
		}

		/**
		 * Resolves the role before EOT, including callers that already changed roles.
		 *
		 * @param array $vars EOT filter context, containing the user and optional snapshot.
		 * @return string Prior paid role, or the current role when no snapshot exists.
		 */
		private static function previous_role($vars)
		{
			if(isset($vars['eot_access_before']['previous_role']))
				return (string)$vars['eot_access_before']['previous_role'];
			$snapshot = c_ws_plugin__s2member_eot_notifications::snapshot(isset($vars['user']) ? $vars['user'] : NULL);
			return $snapshot['previous_role'];
		}

		/**
		 * Selects a registered s2Member destination before the final developer filter.
		 *
		 * @param string $role Existing global demotion destination.
		 * @param array $vars Destination resolver context, including the user if available.
		 * @return string Rule destination, or the existing global destination.
		 */
		public static function destination($role, $vars = array())
		{
			$rule = self::rule_for_role(self::previous_role($vars));
			return $rule && $rule['to_role'] !== '' && get_role($rule['to_role']) ? $rule['to_role'] : $role;
		}

		/**
		 * Enables configured removal while retaining refund/reversal policy.
		 *
		 * The established removal-permission filter runs afterward, so developer
		 * customizations can still prevent capability removal.
		 *
		 * @param bool $default Existing global/event removal choice.
		 * @param array $vars EOT processing context.
		 * @return bool Whether the existing permission filter should allow removal by default.
		 */
		public static function removal_default($default, $vars = array())
		{
			if(!empty($vars['is_refund_or_reversal']))
				return $default;
			$rule = self::rule_for_role(self::previous_role($vars));
			return $rule && $rule['remove_ccaps'] !== '' ? TRUE : $default;
		}

		/**
		 * Restricts the permitted removal to the prior level's configured list.
		 *
		 * The special whole-field value all-ccaps retains the full removal list;
		 * all is an ordinary capability name. Refund/reversal removal stays intact.
		 *
		 * @param array $caps Capability keys eligible for removal after role demotion.
		 * @param array $vars EOT processing context.
		 * @return array Capability keys eligible for the selected removal policy.
		 */
		public static function removal_list($caps, $vars = array())
		{
			$rule = self::rule_for_role(self::previous_role($vars));
			if(!empty($vars['is_refund_or_reversal']) || !$rule || $rule['remove_ccaps'] === '' || $rule['remove_ccaps'] === 'all-ccaps')
				return $caps;
			$selected = array();
			foreach(explode(',', $rule['remove_ccaps']) as $name)
				$selected[] = 'access_s2member_ccap_'.$name;
			return array_values(array_intersect($caps, $selected));
		}

		/**
		 * Renders the combined table after the shared EOT demotion controls.
		 *
		 * @param array $vars Shared renderer context; the saved settings determine disabled state.
		 * @return null
		 */
		public static function render($vars = array())
		{
			$disabled = $GLOBALS['WS_PLUGIN__']['s2member']['o']['membership_eot_behavior'] === 'delete';
			$levels = array('subscriber' => translate_user_role(wp_roles()->roles['subscriber']['name']));
			$paid = array();
			foreach(wp_roles()->roles as $slug => $data)
				if(preg_match('/^s2member_level([1-9][0-9]*)$/', $slug, $matches))
					$paid[(int)$matches[1]] = array($slug, translate_user_role($data['name']));
			ksort($paid, SORT_NUMERIC);
			foreach($paid as $entry)
				$levels[$entry[0]] = $entry[1];
			$rules = self::normalize(isset($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_demotion_rules']) ? $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_eot_demotion_rules'] : array());
			echo '<tr class="ws-plugin--s2member-eot-demotion-option-row"'.($disabled ? ' style="opacity:0.5;"' : '').'><td>';
			echo '<h3>Custom EOT Rules</h3>';
			echo '<p>Choose a paid level in <strong>From</strong> and the level it should become in <strong>To</strong>. A blank choice uses the site-wide Demote To setting. <strong>Remove CCaps</strong> accepts comma-separated custom capabilities to remove, or <code>all-ccaps</code> on its own to remove all. A blank list uses the site-wide custom capability removal setting. Other custom capabilities remain available when a named list is used. Refunds/reversals that trigger an Immediate EOT still remove all custom capabilities.</p>';
			echo '<input type="hidden" name="ws_plugin__s2member_pro_eot_demotion_rules[update]" value="update-signal"'.($disabled ? ' disabled="disabled"' : '').' />';
			echo '<table id="ws-plugin--s2member-pro-eot-demotion-rules" class="widefat"><thead><tr><th scope="col">From</th><th scope="col">To</th><th scope="col">Remove CCaps</th><th scope="col">Action</th></tr></thead><tbody>';
			foreach($rules as $index => $rule)
				self::render_row($rule, $index, $levels, $disabled);
			echo '</tbody></table>';
			echo '<p id="ws-plugin--s2member-pro-eot-rules-empty"'.($rules ? ' style="display:none;"' : '').'>No custom rules. Existing site-wide defaults apply.</p>';
			echo '<p><button type="button" id="ws-plugin--s2member-pro-eot-add-rule" class="button"'.($disabled ? ' disabled="disabled"' : '').'>Add rule</button></p>';
			echo '<template id="ws-plugin--s2member-pro-eot-rule-template">';
			self::render_row(array('from_role' => '', 'to_role' => '', 'remove_ccaps' => ''), NULL, $levels, FALSE);
			echo '</template></td></tr>';
		}

		/**
		 * Renders a saved row or an inert template for the Add rule button.
		 *
		 * @param array $rule Normalized row values.
		 * @param int|null $index Saved row index; null omits names in the inert template.
		 * @param array $levels Registered level role slugs and display labels.
		 * @param bool $disabled Whether EOT behavior currently disables demotion settings.
		 * @return null
		 */
		private static function render_row($rule, $index, $levels, $disabled)
		{
			$disabled_attr = $disabled ? ' disabled="disabled"' : '';
			echo '<tr data-eot-rule="1">';
			foreach(array('from_role', 'to_role') as $field)
			{
				$name = $index === NULL ? '' : ' name="ws_plugin__s2member_pro_eot_demotion_rules['.(int)$index.']['.$field.']"';
				echo '<td><select data-eot-field="'.$field.'" aria-label="'.($field === 'from_role' ? 'From level' : 'To level').'"'.$name.$disabled_attr.'>';
				echo '<option value="">'.($field === 'from_role' ? 'Choose a level' : 'Use site-wide default').'</option>';
				foreach($levels as $slug => $label)
					if($field !== 'from_role' || $slug !== 'subscriber')
						echo '<option value="'.esc_attr($slug).'"'.($rule[$field] === $slug ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
				echo '</select></td>';
			}
			$name = $index === NULL ? '' : ' name="ws_plugin__s2member_pro_eot_demotion_rules['.(int)$index.'][remove_ccaps]"';
			echo '<td><input type="text" data-eot-field="remove_ccaps" aria-label="Custom capabilities to remove" value="'.esc_attr($rule['remove_ccaps']).'"'.$name.$disabled_attr.' placeholder="Use site-wide default" /></td>';
			echo '<td><button type="button" data-eot-remove="1" class="button"'.$disabled_attr.'>Remove rule</button></td></tr>';
		}
	}
}
