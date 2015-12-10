/**
 * Core JavaScript routines for s2Member Pro menu pages.
 *
 * Copyright: © 2009-2011
 * {@link http://websharks-inc.com/ WebSharks, Inc.}
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
 *   See: {@link http://s2member.com/prices/}
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
 * @package s2Member\Menu_Pages
 * @since 1.5
 */
jQuery(document).ready(
	function($)
	{
		var esc_attr = esc_html = function(string/* Convert special characters. */)
		{
			if(/[&\<\>"']/.test(string = String(string)))
				string = string.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'),
					string = string.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
			return string;
		};
		if(location.href.match(/page\=ws-plugin--s2member-.+?-ops/))
		{
			$('#ws-plugin--s2member-pro-eot-reminder-email-enable').on('change', function(){
				var $this = $(this), val = $this.val(),
					$ops = $('.ws-menu-page-pro-eot-reminder-email-ops');

				$ops.css('opacity', val === '1' ? '' : '0.5');
			}).trigger('change');

			var $eot_reminder_email_day = $('#ws-plugin--s2member-pro-eot-reminder-email-day'),
				$eot_reminder_email_day_tabs = $eot_reminder_email_day.find('.-tabs');

			var $eot_reminder_email_days = $('#ws-plugin--s2member-pro-eot-reminder-email-days'),

				$eot_reminder_email_recipients = $('#ws-plugin--s2member-pro-eot-reminder-email-recipients'),
				eot_reminder_email_recipients = $eot_reminder_email_recipients.length ? $.JSON.parse($eot_reminder_email_recipients.val()) : {},

				$eot_reminder_email_recipients_for_day = $('#ws-plugin--s2member-pro-eot-reminder-email-recipients-for-day'),
				eot_reminder_email_recipients_for_day = function(day, _fallback) {
					if (typeof eot_reminder_email_recipients[day] === 'string') {
						return eot_reminder_email_recipients[day];
					} else if (_fallback && typeof eot_reminder_email_recipients['_'] === 'string') {
						return eot_reminder_email_recipients['_'];
					}
					return ''; // Default return value.
				},
				$eot_reminder_email_subject = $('#ws-plugin--s2member-pro-eot-reminder-email-subject'),
				eot_reminder_email_subject = $eot_reminder_email_subject.length ? $.JSON.parse($eot_reminder_email_subject.val()) : {},

				$eot_reminder_email_subject_for_day = $('#ws-plugin--s2member-pro-eot-reminder-email-subject-for-day'),
				eot_reminder_email_subject_for_day = function(day, _fallback) {
					if (typeof eot_reminder_email_subject[day] === 'string') {
						return eot_reminder_email_subject[day];
					} else if (_fallback && typeof eot_reminder_email_subject['_'] === 'string') {
						return eot_reminder_email_subject['_'];
					}
					return ''; // Default return value.
				},
				$eot_reminder_email_message = $('#ws-plugin--s2member-pro-eot-reminder-email-message'),
				eot_reminder_email_message = $eot_reminder_email_message.length ? $.JSON.parse($eot_reminder_email_message.val()) : {},

				$eot_reminder_email_message_for_day = $('#ws-plugin--s2member-pro-eot-reminder-email-message-for-day'),
				eot_reminder_email_message_for_day = function(day, _fallback) {
					if (typeof eot_reminder_email_message[day] === 'string') {
						return eot_reminder_email_message[day];
					} else if (_fallback && typeof eot_reminder_email_message['_'] === 'string') {
						return eot_reminder_email_message['_'];
					}
					return ''; // Default return value.
				};
			var eot_reminder_get_unique_days = function() {
				var list_of_days, days = [];

				list_of_days = $eot_reminder_email_days.val();
				list_of_days = list_of_days.replace(/[^0-9,\-]/g, '').split(/,+/);
				list_of_days = $.unique(list_of_days);

				$.each(list_of_days, function(i, day) {
					if (/^\-?[0-9]+$/.test(day)) days.push(day);
				});
				return days;
			};
			var eot_reminder_email_save_current_day = function() {
				var current_day = String($eot_reminder_email_day.data('current'));

				if (current_day !== 'undefined' && current_day !== '' && current_day !== '_') {
					eot_reminder_email_recipients[current_day] = $.trim($eot_reminder_email_recipients_for_day.val());
					eot_reminder_email_subject[current_day] = $.trim($eot_reminder_email_subject_for_day.val());
					eot_reminder_email_message[current_day] = $.trim($eot_reminder_email_message_for_day.val());
				}
			};
			var eot_reminder_email_switch_to_day = function(day, _fallback) {
				eot_reminder_email_save_current_day();

				$eot_reminder_email_recipients_for_day.val(eot_reminder_email_recipients_for_day(day, _fallback));
				$eot_reminder_email_subject_for_day.val(eot_reminder_email_subject_for_day(day, _fallback));
				$eot_reminder_email_message_for_day.val(eot_reminder_email_message_for_day(day, _fallback));

				$eot_reminder_email_day.data('current', day);
				$eot_reminder_email_day_tabs.find('a[data-day]').removeClass('-current');
				$eot_reminder_email_day_tabs.find('a[data-day="'+day+'"]').addClass('-current');
			};
			var eot_reminder_email_check_days = function(fallback)
			{
				var days = eot_reminder_get_unique_days(),
					tabAnchors = ''; // Initialize.

				$eot_reminder_email_day_tabs.html('');

				if (days.length) {
					$.each(days, function(i, day) {
						tabAnchors += '<a href="#" data-day="'+esc_attr(day)+'">'+esc_html(day)+'</a>';
					});
					$eot_reminder_email_day_tabs.html(tabAnchors);
					eot_reminder_email_switch_to_day(days[0], fallback);
					$eot_reminder_email_day.show();
				} else {
					$eot_reminder_email_day.hide();
					eot_reminder_email_switch_to_day('_');
				}
			};
			$eot_reminder_email_days.on('keyup cut copy paste', function(event) {
				eot_reminder_email_check_days(true);
			});
			$eot_reminder_email_day_tabs.on('click', 'a[data-day]', function(event) {
				event.preventDefault();
				event.stopImmediatePropagation();

				var day = $(this).data('day');
				eot_reminder_email_switch_to_day(day);
			});
			$eot_reminder_email_days.closest('form').on('submit', function(event) {
				eot_reminder_email_save_current_day(); // Save current day.

				var days = eot_reminder_get_unique_days(),
					_eot_reminder_email_recipients = {},
					_eot_reminder_email_subject = {},
					_eot_reminder_email_message = {};

				days.push('_'); // Add the default day option too.

				$.each(days, function(i, day) {
					_eot_reminder_email_recipients[day] = eot_reminder_email_recipients_for_day(day);
					_eot_reminder_email_subject[day] = eot_reminder_email_subject_for_day(day);
					_eot_reminder_email_message[day] = eot_reminder_email_message_for_day(day);
				});
				$eot_reminder_email_recipients.val($.JSON.stringify(_eot_reminder_email_recipients));
				$eot_reminder_email_subject.val($.JSON.stringify(_eot_reminder_email_subject));
				$eot_reminder_email_message.val($.JSON.stringify(_eot_reminder_email_message));
			});
			if ($eot_reminder_email_days.length) {
				eot_reminder_email_check_days(true); // Initialize.
			}
		}
		if(location.href.match(/page\=ws-plugin--s2member-pro-coupon-codes/))
		{
			var $menuTable = $('.ws-menu-page-table'),
				$couponsTable = $menuTable.find('.coupons-table'),
				newRow = '<tr>' +
				         '<td class="-code"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-discount"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-active_time"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-expires_time"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-directive"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-singulars"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-users"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-max_uses"><input type="text" spellcheck="false" value="" /></td>' +
				         '<td class="-uses"><input type="text" spellcheck="false" value="0" /></td>' +
				         '<td class="-actions"><a href="#" class="-up" title="Move Up" tabindex="-1"><i class="fa fa-chevron-circle-up"></i></a><a href="#" class="-down" title="Move Down" tabindex="-1"><i class="fa fa-chevron-circle-down"></i></a><a href="#" class="-delete" title="Delete" tabindex="-1"><i class="fa fa-times-circle"></i></a></td>' +
				         '</tr>';
			$couponsTable.find('tbody').on('click', 'a.-up,a.-down', function(e)
			{
				e.preventDefault(),
					e.stopImmediatePropagation();

				var $this = $(this), $thisTr = $this.closest('tr');

				if($this.is('.-up'))
					$thisTr.insertBefore($thisTr.prev());
				else $thisTr.insertAfter($thisTr.next());
			});
			$couponsTable.find('tbody').on('click', 'a.-delete', function(e)
			{
				e.preventDefault(),
					e.stopImmediatePropagation();

				var $this = $(this), $thisTr = $this.closest('tr');

				if(confirm('Delete? Are you sure?'))
					$thisTr.remove();
			});
			$couponsTable.find('tbody').on('keypress', 'input', function(e)
			{
				return e.which !== 13;
			});
			$menuTable.find('.coupon-add > a').on('click', function(e)
			{
				e.preventDefault(),
					e.stopImmediatePropagation();

				var $this = $(this);

				$couponsTable.find('tbody').append(newRow);
			});
			$menuTable.find('form').on('submit', function(e)
			{
				var $this = $(this), list = '';

				$couponsTable.find('tbody > tr').
					each(function(i, obj)
					     {
						     $(this).find('input').
							     each(function(i, obj)
							          {
								          if(i === 2)
									          list += $(obj).val() + '~';
								          else list += $(obj).val() + '|';
							          });
						     list += '\n';
					     });
				$('#ws-plugin--s2member-pro-coupon-codes').val(list);
			});
			if(!$couponsTable.find('tbody > tr').length)
				$couponsTable.find('tbody').append(newRow);
		}
	});
