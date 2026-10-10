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
			$snapshot = c_ws_plugin__s2member_eot::access_snapshot(isset($vars['user']) ? $vars['user'] : NULL);
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
		 * Outputs styles scoped to the Pro EOT demotion editor.
		 *
		 * @param array $vars Shared administrative stylesheet context.
		 * @return null
		 */
		public static function styles($vars = array())
		{
			include dirname(dirname(__FILE__)).'/menu-pages/eot-demotions.css';
		}

		/**
		 * Renders the per-level editor after the site-wide EOT capability setting.
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
			//261008.0829 Match the neighboring EOT setting rows; the label names this dynamic group instead of one field.
			echo '<tr class="ws-plugin--s2member-eot-demotion-option-row"'.($disabled ? ' style="opacity:0.5;"' : '').'>'."\n";
			echo '<th>'."\n";
			echo '<label id="ws-plugin--s2member-pro-eot-demotions-label">'."\n";
			echo 'Custom EOT Demotions (beta)'."\n";
			echo '</label>'."\n";
			echo '</th>'."\n";
			echo '</tr>'."\n";
			echo '<tr class="ws-plugin--s2member-eot-demotion-option-row"'.($disabled ? ' style="opacity:0.5;"' : '').'>'."\n";
			echo '<td>'."\n";
			echo '<div id="ws-plugin--s2member-pro-eot-demotions" role="group" aria-labelledby="ws-plugin--s2member-pro-eot-demotions-label">'."\n";
			echo '<p>Choose the level <strong>From</strong>, its destination <strong>To</strong>, and the custom capabilities to remove. Choose the same level to keep it. Blank fields follow the site-wide defaults.</p>';
			echo '<p class="s2-eot-help"><strong>Remove CCaps:</strong> separate names with commas, or enter <code>all-ccaps</code> to remove all. Immediate refund/reversal EOTs still remove all custom capabilities.</p>';
			echo '<input type="hidden" name="ws_plugin__s2member_pro_eot_demotion_rules[update]" value="update-signal"'.($disabled ? ' disabled="disabled"' : '').' />';
			echo '<table id="ws-plugin--s2member-pro-eot-demotion-rules" class="widefat"'.($rules ? '' : ' style="display:none;"').'><thead><tr><th scope="col">From</th><th scope="col">To</th><th scope="col">Remove CCaps</th><th scope="col" class="s2-eot-action"><span class="screen-reader-text">Remove demotion</span></th></tr></thead><tbody>';
			foreach($rules as $index => $rule)
				self::render_row($rule, $index, $levels, $disabled);
			echo '</tbody></table>';
			echo '<p id="ws-plugin--s2member-pro-eot-rules-empty"'.($rules ? ' style="display:none;"' : '').'>No custom demotions. Site-wide defaults apply.</p>';
			echo '<p><button type="button" id="ws-plugin--s2member-pro-eot-add-rule" class="button"'.($disabled ? ' disabled="disabled"' : '').'>Add demotion</button></p>';
			echo '<template id="ws-plugin--s2member-pro-eot-rule-template">';
			self::render_row(array('from_role' => '', 'to_role' => '', 'remove_ccaps' => ''), NULL, $levels, FALSE);
			echo '</template>'."\n";
			echo '</div>'."\n";
			echo '</td>'."\n";
			echo '</tr>'."\n";
		}

		/**
		 * Renders a saved row or an inert template for the Add demotion button.
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
				echo '<td><span class="s2-eot-field-label" aria-hidden="true">'.($field === 'from_role' ? 'From' : 'To').'</span><select data-eot-field="'.$field.'" aria-label="'.($field === 'from_role' ? 'From level' : 'To level').'"'.$name.$disabled_attr.'>';
				echo '<option value="">'.($field === 'from_role' ? 'Choose a level' : 'Use site-wide default').'</option>';
				foreach($levels as $slug => $label)
					if($field !== 'from_role' || $slug !== 'subscriber')
						echo '<option value="'.esc_attr($slug).'"'.($rule[$field] === $slug ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
				echo '</select></td>';
			}
			$name = $index === NULL ? '' : ' name="ws_plugin__s2member_pro_eot_demotion_rules['.(int)$index.'][remove_ccaps]"';
			echo '<td><span class="s2-eot-field-label" aria-hidden="true">Remove CCaps</span><input type="text" data-eot-field="remove_ccaps" aria-label="Custom capabilities to remove" value="'.esc_attr($rule['remove_ccaps']).'"'.$name.$disabled_attr.' placeholder="Use site-wide default" /></td>';
			echo '<td class="s2-eot-action"><button type="button" data-eot-remove="1" aria-label="Remove demotion" title="Remove demotion"'.$disabled_attr.'><span class="dashicons dashicons-trash" aria-hidden="true"></span></button></td></tr>';
		}
	}
}
