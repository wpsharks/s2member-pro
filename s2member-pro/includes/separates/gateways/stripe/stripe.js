/**
 * Core JavaScript routines for Stripe.
 *
 * Copyright: © 2009-2011
 * {@link http://www.websharks-inc.com/ WebSharks, Inc.}
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
 *   See: {@link http://www.s2member.com/prices/}
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
 * @package s2Member\Stripe
 * @since 140617
 */
jQuery(document).ready( // DOM ready.
	function($) // Depends on Stripe lib.
	{
		var stripeCheck = function()
		{
			if(window.Stripe) // Stripe available?
				clearInterval(stripeCheckInterval), setupProForms;
		}, stripeCheckInterval = setInterval(stripeCheck, 100);

		var setupProForms = function()
		{
			Stripe.setPublishableKey('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_stripe_api_publishable_key"]); ?>');

			var $clForm, $upForm, $rgForm, $spForm, $coForm, jumpToResponses, preloadAjaxLoader,
				ariaTrue = {'aria-required': 'true'}, ariaFalse = {'aria-required': 'false'},
				disabled = {'disabled': 'disabled'}, ariaFalseDis = {'aria-required': 'false', 'disabled': 'disabled'};

			var handleNameIssues, handlePasswordIssues, handleExistingUsers,
				handleBillingMethod, handleOptions, handleCouponIssues, handleTaxIssues,
				taxMayApply = true, calculateTax, cTaxDelay, cTaxTimeout, cTaxReq, cTaxLocation, $ajaxTaxDiv,
				optionsSection, descSection, couponSection, couponApplyButton, registrationSection, customFieldsSection,
				billingMethodSection, cardType, billingAddressSection, captchaSection, submissionSection,
				submissionButton, $submissionButton, submissionNonceVerification;

			preloadAjaxLoader = new Image(), preloadAjaxLoader.src = '<?php echo $vars["i"]; ?>/ajax-loader.gif';

			if($('form.s2member-pro-stripe-registration-form').length > 1 // No more than one form on a page please.
			   || $('form.s2member-pro-stripe-checkout-form').length > 1 || $('form.s2member-pro-stripe-sp-checkout-form').length > 1)
			{
				return alert('Detected more than one s2Member Pro Form.\n\nPlease use only ONE s2Member Pro Form Shortcode on each Post/Page.' +
				             ' Attempting to serve more than one Pro Form on each Post/Page (even w/ DHTML) may result in unexpected/broken functionality.');
			}
			if(($clForm = $('form#s2member-pro-stripe-cancellation-form')).length === 1)
			{
				captchaSection = 'div#s2member-pro-stripe-cancellation-form-captcha-section',
					submissionSection = 'div#s2member-pro-stripe-cancellation-form-submission-section',
					$submissionButton = $(submissionSection + ' button#s2member-pro-stripe-cancellation-submit');

				ws_plugin__s2member_animateProcessing($submissionButton, 'reset'),
					$submissionButton.removeAttr('disabled');

				$clForm.submit(function(/* Form validation. */)
				               {
					               var context = this, label = '', error = '', errors = '',
						               $recaptchaResponse = $(captchaSection + ' input#recaptcha_response_field');

					               $(':input', context)
						               .each(function(/* Go through them all together. */)
						                     {
							                     var id = $.trim($(this).attr('id')).replace(/---[0-9]+$/g, ''/* Remove numeric suffixes. */);

							                     if(id && (label = $.trim($('label[for="' + id + '"]', context).first().children('span').first().text().replace(/[\r\n\t]+/g, ' '))))
							                     {
								                     if(error = ws_plugin__s2member_validationErrors(label, this, context))
									                     errors += error + '\n\n'/* Collect errors. */;
							                     }
						                     });
					               if(errors = $.trim(errors))
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + errors);
						               return false;
					               }
					               else if($recaptchaResponse.length && !$recaptchaResponse.val())
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Security Code missing. Please try again.", "s2member-front", "s2member")); ?>');
						               return false;
					               }
					               $submissionButton.attr(disabled),
						               ws_plugin__s2member_animateProcessing($submissionButton);
					               return true;
				               });
			}
			else if(($upForm = $('form#s2member-pro-stripe-update-form')).length === 1)
			{
				billingMethodSection = 'div#s2member-pro-stripe-update-form-billing-method-section',
					cardType = billingMethodSection + ' input[name="' + ws_plugin__s2member_escjQAttr('s2member_pro_stripe_update[card_type]') + '"]',
					billingAddressSection = 'div#s2member-pro-stripe-update-form-billing-address-section',
					captchaSection = 'div#s2member-pro-stripe-update-form-captcha-section',
					submissionSection = 'div#s2member-pro-stripe-update-form-submission-section',
					$submissionButton = $(submissionSection + ' button#s2member-pro-stripe-update-submit');

				ws_plugin__s2member_animateProcessing($submissionButton, 'reset'),
					$submissionButton.removeAttr('disabled');

				(handleBillingMethod = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					var billingMethod = $(cardType + ':checked').val(/* Billing Method. */);

					if($.inArray(billingMethod, ['Visa', 'MasterCard', 'Amex', 'Discover']) !== -1)
					{
						$(billingMethodSection + ' > div.s2member-pro-stripe-update-form-div').show();
						$(billingMethodSection + ' > div.s2member-pro-stripe-update-form-div :input').attr(ariaTrue);

						$(billingMethodSection + ' > div#s2member-pro-stripe-update-form-card-start-date-issue-number-div').hide();
						$(billingMethodSection + ' > div#s2member-pro-stripe-update-form-card-start-date-issue-number-div :input').attr(ariaFalse);

						$(billingAddressSection + ' > div.s2member-pro-stripe-update-form-div').show();
						$(billingAddressSection + ' > div.s2member-pro-stripe-update-form-div :input').attr(ariaTrue);

						$(billingAddressSection).show(), (eventTrigger) ? $(billingMethodSection + ' input#s2member-pro-stripe-update-card-number').focus() : null;
					}
					else if($.inArray(billingMethod, ['Maestro', 'Solo']) !== -1)
					{
						$(billingMethodSection + ' > div.s2member-pro-stripe-update-form-div').show();
						$(billingMethodSection + ' > div.s2member-pro-stripe-update-form-div :input').attr(ariaTrue);

						$(billingAddressSection + ' > div.s2member-pro-stripe-update-form-div').show();
						$(billingAddressSection + ' > div.s2member-pro-stripe-update-form-div :input').attr(ariaTrue);

						$(billingAddressSection).show(), (eventTrigger) ? $(billingMethodSection + ' input#s2member-pro-stripe-update-card-number').focus() : null;
					}
					else if(!billingMethod/* Else there was no Billing Method supplied. */)
					{
						$(billingMethodSection + ' > div.s2member-pro-stripe-update-form-div').hide();
						$(billingMethodSection + ' > div.s2member-pro-stripe-update-form-div :input').attr(ariaFalse);

						$(billingMethodSection + ' > div#s2member-pro-stripe-update-form-card-type-div').show();
						$(billingMethodSection + ' > div#s2member-pro-stripe-update-form-card-type-div :input').attr(ariaTrue);

						$(billingAddressSection + ' > div.s2member-pro-stripe-update-form-div').hide();
						$(billingAddressSection + ' > div.s2member-pro-stripe-update-form-div :input').attr(ariaFalse);

						$(billingAddressSection).hide(), (eventTrigger) ? $(submissionSection + ' button#s2member-pro-stripe-update-submit').focus() : null;
					}
				})();
				$(cardType).click(handleBillingMethod).change(handleBillingMethod);

				$upForm.submit(function(/* Form validation. */)
				               {
					               var context = this, label = '', error = '', errors = '',
						               $recaptchaResponse = $(captchaSection + ' input#recaptcha_response_field');

					               if(!$(cardType + ':checked').val())
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Please choose a Billing Method.", "s2member-front", "s2member")); ?>');
						               return false;
					               }
					               $(':input', context)
						               .each(function(/* Go through them all together. */)
						                     {
							                     var id = $.trim($(this).attr('id')).replace(/---[0-9]+$/g, ''/* Remove numeric suffixes. */);

							                     if(id && (label = $.trim($('label[for="' + id.replace(/-(month|year)/, '') + '"]', context).first().children('span').first().text().replace(/[\r\n\t]+/g, ' '))))
							                     {
								                     if(error = ws_plugin__s2member_validationErrors(label, this, context))
									                     errors += error + '\n\n'/* Collect errors. */;
							                     }
						                     });
					               if(errors = $.trim(errors))
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + errors);
						               return false;
					               }
					               else if($recaptchaResponse.length && !$recaptchaResponse.val())
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Security Code missing. Please try again.", "s2member-front", "s2member")); ?>');
						               return false;
					               }
					               $submissionButton.attr(disabled),
						               ws_plugin__s2member_animateProcessing($submissionButton);
					               return true;
				               });
			}
			else if(($rgForm = $('form#s2member-pro-stripe-registration-form')).length === 1)
			{
				registrationSection = 'div#s2member-pro-stripe-registration-form-registration-section',
					captchaSection = 'div#s2member-pro-stripe-registration-form-captcha-section',
					submissionSection = 'div#s2member-pro-stripe-registration-form-submission-section',
					$submissionButton = $(submissionSection + ' button#s2member-pro-stripe-registration-submit');

				ws_plugin__s2member_animateProcessing($submissionButton, 'reset'), $submissionButton.removeAttr('disabled');

				(handleNameIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-registration-names-not-required-or-not-possible').length)
					{
						$(registrationSection + ' > div#s2member-pro-stripe-registration-form-first-name-div').hide();
						$(registrationSection + ' > div#s2member-pro-stripe-registration-form-first-name-div :input').attr(ariaFalseDis);

						$(registrationSection + ' > div#s2member-pro-stripe-registration-form-last-name-div').hide();
						$(registrationSection + ' > div#s2member-pro-stripe-registration-form-last-name-div :input').attr(ariaFalseDis);
					}
				})();
				(handlePasswordIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-registration-password-not-required-or-not-possible').length)
					{
						$(registrationSection + ' > div#s2member-pro-stripe-registration-form-password-div').hide();
						$(registrationSection + ' > div#s2member-pro-stripe-registration-form-password-div :input').attr(ariaFalseDis);
					}
				})();
				$(registrationSection + ' > div#s2member-pro-stripe-registration-form-password-div :input')
					.keyup(function()
					       {
						       ws_plugin__s2member_passwordStrength(
							       $(registrationSection + ' input#s2member-pro-stripe-registration-username'),
							       $(registrationSection + ' input#s2member-pro-stripe-registration-password1'),
							       $(registrationSection + ' input#s2member-pro-stripe-registration-password2'),
							       $(registrationSection + ' div#s2member-pro-stripe-registration-form-password-strength')
						       );
					       });
				$rgForm.submit(function(/* Form validation. */)
				               {
					               var context = this, label = '', error = '', errors = '',
						               $recaptchaResponse = $(captchaSection + ' input#recaptcha_response_field'),
						               $password1 = $(registrationSection + ' input#s2member-pro-stripe-registration-password1[aria-required="true"]'),
						               $password2 = $(registrationSection + ' input#s2member-pro-stripe-registration-password2');

					               $(':input', context)
						               .each(function(/* Go through them all together. */)
						                     {
							                     var id = $.trim($(this).attr('id')).replace(/---[0-9]+$/g, ''/* Remove numeric suffixes. */);

							                     if(id && (label = $.trim($('label[for="' + id + '"]', context).first().children('span').first().text().replace(/[\r\n\t]+/g, ' '))))
							                     {
								                     if(error = ws_plugin__s2member_validationErrors(label, this, context))
									                     errors += error + '\n\n'/* Collect errors. */;
							                     }
						                     });
					               if(errors = $.trim(errors))
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + errors);
						               return false;
					               }
					               else if($password1.length && $.trim($password1.val()) !== $.trim($password2.val()))
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Passwords do not match up. Please try again.", "s2member-front", "s2member")); ?>');
						               return false;
					               }
					               else if($password1.length && $.trim($password1.val()).length < 6/* Enforce minimum length requirement here. */)
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Password MUST be at least 6 characters. Please try again.", "s2member-front", "s2member")); ?>');
						               return false;
					               }
					               else if($recaptchaResponse.length && !$recaptchaResponse.val())
					               {
						               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Security Code missing. Please try again.", "s2member-front", "s2member")); ?>');
						               return false;
					               }
					               $submissionButton.attr(disabled),
						               ws_plugin__s2member_animateProcessing($submissionButton);
					               return true;
				               });
			}
			else if(($spForm = $('form#s2member-pro-stripe-sp-checkout-form')).length === 1)
			{
				optionsSection = 'div#s2member-pro-stripe-sp-checkout-form-options-section',
					descSection = 'div#s2member-pro-stripe-sp-checkout-form-description-section',
					couponSection = 'div#s2member-pro-stripe-sp-checkout-form-coupon-section',
					couponApplyButton = couponSection + ' input#s2member-pro-stripe-sp-checkout-coupon-apply',
					registrationSection = 'div#s2member-pro-stripe-sp-checkout-form-registration-section',
					billingMethodSection = 'div#s2member-pro-stripe-sp-checkout-form-billing-method-section',
					cardType = billingMethodSection + ' input[name="' + ws_plugin__s2member_escjQAttr('s2member_pro_stripe_sp_checkout[card_type]') + '"]',
					billingAddressSection = 'div#s2member-pro-stripe-sp-checkout-form-billing-address-section',
					$ajaxTaxDiv = $(billingAddressSection + ' > div#s2member-pro-stripe-sp-checkout-form-ajax-tax-div'),
					captchaSection = 'div#s2member-pro-stripe-sp-checkout-form-captcha-section',
					submissionSection = 'div#s2member-pro-stripe-sp-checkout-form-submission-section',
					submissionNonceVerification = submissionSection + ' input#s2member-pro-stripe-sp-checkout-nonce',
					submissionButton = submissionSection + ' button#s2member-pro-stripe-sp-checkout-submit';

				ws_plugin__s2member_animateProcessing($(submissionButton), 'reset'),
					$(submissionButton).removeAttr('disabled'), $(couponApplyButton).removeAttr('disabled');

				(handleOptions = function(eventTrigger /* eventTrigger is passed by jQuery for DOM events. */)
				{
					if(!$(optionsSection + ' select#s2member-pro-stripe-sp-checkout-options option').length)
					{
						$(optionsSection).hide(/* No options on this particular form. */);
						$(descSection).show(/* Show description on this particular form. */);
					}
					else // This is turned off by default for smoother loading. (via: display:none).
					{
						$(optionsSection).show(/* OK. So we need to display this now. */);
						$(descSection).hide(/* OK. So we need to hide this now. */);
						$(optionsSection + ' select#s2member-pro-stripe-sp-checkout-options').change
						(function() // Handle option changes.
						 {
							 $(submissionNonceVerification).val('option');
							 $spForm.attr('action', $spForm.attr('action').replace(/#.*$/, '') + '#s2p-form');
							 $spForm.submit();
						 });
					}
				})();
				(handleCouponIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-sp-checkout-coupons-not-required-or-not-possible').length)
					{
						$(couponSection).hide(/* Not accepting Coupons on this particular form. */);
					}
					else // This is turned off by default for smoother loading. (via: display:none).
						$(couponSection).show(/* OK. So we need to display this now. */);
				})();
				(handleTaxIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-sp-checkout-tax-not-required-or-not-possible').length)
					{
						$ajaxTaxDiv.hide(), taxMayApply = false/* Tax does NOT even apply. */;
					}
				})();
				(calculateTax = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if(taxMayApply && !(eventTrigger && eventTrigger.interval && document.activeElement.id === 's2member-pro-stripe-sp-checkout-country'))
					{
						var attr = $(submissionSection + ' input#s2member-pro-stripe-sp-checkout-attr').val(),
							state = $.trim($(billingAddressSection + ' input#s2member-pro-stripe-sp-checkout-state').val()),
							country = $(billingAddressSection + ' select#s2member-pro-stripe-sp-checkout-country').val(),
							zip = $.trim($(billingAddressSection + ' input#s2member-pro-stripe-sp-checkout-zip').val()),
							thisTaxLocation = state + '|' + country + '|' + zip/* Three part location. */;

						if(state && country && zip && thisTaxLocation && (!cTaxLocation || cTaxLocation !== thisTaxLocation) && (cTaxLocation = thisTaxLocation))
						{
							(cTaxReq) ? cTaxReq.abort() : null, clearTimeout(cTaxTimeout/* Clear. */), cTaxTimeout = 0;

							$ajaxTaxDiv.html('<div><img src="' + ws_plugin__s2member_escAttr(preloadAjaxLoader.src) + '" alt="<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(esc_attr(_x("Calculating Sales Tax...", "s2member-front", "s2member"))); ?>" /> <?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("calculating sales tax...", "s2member-front", "s2member")); ?></div>');

							cTaxTimeout = setTimeout(function(/* Create a new cTaxTimeout with a one second delay. */)
							                         {
								                         cTaxReq = $.post('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(admin_url("/admin-ajax.php")); ?>', {'action': 'ws_plugin__s2member_pro_stripe_ajax_tax', 'ws_plugin__s2member_pro_stripe_ajax_tax': '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(c_ws_plugin__s2member_utils_encryption::encrypt("ws-plugin--s2member-pro-stripe-ajax-tax")); ?>', 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[attr]': attr, 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[state]': state, 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[country]': country, 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[zip]': zip}, function(response)
								                         {
									                         clearTimeout /* Clear cTaxTimeout. */(cTaxTimeout), cTaxTimeout = 0;

									                         try // Try/catch here. jQuery will sometimes return a successful response in IE whenever the connection is aborted with a null response.
									                         {
										                         /* translators: `Sales Tax (Today)` and `Total (Today)` The word `Today` is displayed when/if a trial period is offered. The word `Today` is translated elsewhere. */
										                         $ajaxTaxDiv.html('<div>' + $.sprintf('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("<strong>Sales Tax%s:</strong> %s<br /><strong>— Total%s:</strong> %s", "s2member-front", "s2member")); ?>', ((response.trial) ? ' ' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Today", "s2member-front", "s2member")); ?>' : ''), ((response.tax_per) ? '<em>' + response.tax_per + '</em> ( ' + response.cur_symbol + '' + response.tax + ' )' : response.cur_symbol + '' + response.tax), ((response.trial) ? ' ' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Today", "s2member-front", "s2member")); ?>' : ''), response.cur_symbol + '' + response.total) + '</div>');
									                         }
									                         catch(e)
									                         {console.log(e);}
								                         }, 'json');
							                         }, ((eventTrigger && eventTrigger.keyCode) ? 1000 : 100));
						}
						else if(!state || !country || !zip || !thisTaxLocation)
							$ajaxTaxDiv.html(''), cTaxLocation = null;
					}
				})();
				cTaxDelay = function(eventTrigger /* eventTrigger is passed by jQuery for DOM events. */)
				{
					setTimeout(function(/* Trigger event handler with a brief delay. */)
					           {
						           calculateTax(eventTrigger);
					           }, 10); // Brief delay.
				};
				$(billingAddressSection + ' input#s2member-pro-stripe-sp-checkout-state').bind('keyup blur', calculateTax).bind('cut paste', cTaxDelay);
				$(billingAddressSection + ' input#s2member-pro-stripe-sp-checkout-zip').bind('keyup blur', calculateTax).bind('cut paste', cTaxDelay);
				$(billingAddressSection + ' select#s2member-pro-stripe-sp-checkout-country').bind('change', calculateTax);

				setInterval(function(/* Helps with things like Google's Autofill feature. */)
				            {
					            calculateTax({interval: true}/* Identify as interval trigger. */);
				            }, 1000);

				(handleExistingUsers = function(eventTrigger /* eventTrigger is passed by jQuery for DOM events. */)
				{
					if(S2MEMBER_CURRENT_USER_IS_LOGGED_IN/* If User/Member is already logged in. */)
					{
						$(registrationSection + ' input#s2member-pro-stripe-sp-checkout-first-name')
							.each(function()
							      {
								      var $this = $(this), val = $this.val();
								      (!val) ? $this.val(S2MEMBER_CURRENT_USER_FIRST_NAME) : null;
							      });
						$(registrationSection + ' input#s2member-pro-stripe-sp-checkout-last-name')
							.each(function()
							      {
								      var $this = $(this), val = $this.val();
								      (!val) ? $this.val(S2MEMBER_CURRENT_USER_LAST_NAME) : null;
							      });
						$(registrationSection + ' input#s2member-pro-stripe-sp-checkout-email')
							.each(function()
							      {
								      var $this = $(this), val = $this.val();
								      (!val) ? $this.val(S2MEMBER_CURRENT_USER_EMAIL) : null;
							      });
					}
				})();
				(handleBillingMethod = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-sp-checkout-payment-not-required-or-not-possible').length)
						$(cardType).val(['Free']); // No payment required in this VERY special case.

					var billingMethod = $(cardType + ':checked').val(/* Billing Method. */);

					if($.inArray(billingMethod, ['Free']) !== -1)
					{
						$(billingMethodSection).hide(), $(billingAddressSection).hide();

						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').hide();
						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaFalse);

						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').hide();
						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaFalse);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(submissionSection + ' button#s2member-pro-stripe-sp-checkout-submit').focus() : null;
					}
					else if($.inArray(billingMethod, ['Visa', 'MasterCard', 'Amex', 'Discover']) !== -1)
					{
						$(billingMethodSection).show(), $(billingAddressSection).show();

						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').show();
						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaTrue);

						$(billingMethodSection + ' > div#s2member-pro-stripe-sp-checkout-form-card-start-date-issue-number-div').hide();
						$(billingMethodSection + ' > div#s2member-pro-stripe-sp-checkout-form-card-start-date-issue-number-div :input').attr(ariaFalse);

						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').show();
						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaTrue);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(billingMethodSection + ' input#s2member-pro-stripe-sp-checkout-card-number').focus() : null;
					}
					else if($.inArray(billingMethod, ['Maestro', 'Solo']) !== -1)
					{
						$(billingMethodSection).show(), $(billingAddressSection).show();

						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').show();
						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaTrue);

						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').show();
						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaTrue);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(billingMethodSection + ' input#s2member-pro-stripe-sp-checkout-card-number').focus() : null;
					}
					else if(!billingMethod/* Else there was no Billing Method supplied. */)
					{
						$(billingMethodSection).show(), $(billingAddressSection).hide();

						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').hide();
						$(billingMethodSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaFalse);

						$(billingMethodSection + ' > div#s2member-pro-stripe-sp-checkout-form-card-type-div').show();
						$(billingMethodSection + ' > div#s2member-pro-stripe-sp-checkout-form-card-type-div :input').attr(ariaTrue);

						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div').hide();
						$(billingAddressSection + ' > div.s2member-pro-stripe-sp-checkout-form-div :input').attr(ariaFalse);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(submissionSection + ' button#s2member-pro-stripe-sp-checkout-submit').focus() : null;
					}
					handleTaxIssues(/* Tax issues. */);
				})();
				$(cardType).click(handleBillingMethod).change(handleBillingMethod);

				$(couponApplyButton)
					.click(function(/* Only applying coupon. */)
					       {
						       $(submissionNonceVerification).val('apply-coupon'), $spForm.submit();
					       });
				$spForm.submit(function(/* Form validation. */)
				               {
					               if($.inArray($(submissionNonceVerification).val(), ['option', 'apply-coupon']) === -1)
					               {
						               var context = this, label = '', error = '', errors = '',
							               $recaptchaResponse = $(captchaSection + ' input#recaptcha_response_field');

						               if(!$(cardType + ':checked').val())
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Please choose a Billing Method.", "s2member-front", "s2member")); ?>');
							               return false;
						               }
						               $(':input', context)
							               .each(function(/* Go through them all together. */)
							                     {
								                     var id = $.trim($(this).attr('id')).replace(/---[0-9]+$/g, ''/* Remove numeric suffixes. */);

								                     if(id && (label = $.trim($('label[for="' + id.replace(/-(month|year)/, '') + '"]', context).first().children('span').first().text().replace(/[\r\n\t]+/g, ' '))))
								                     {
									                     if(error = ws_plugin__s2member_validationErrors(label, this, context))
										                     errors += error + '\n\n'/* Collect errors. */;
								                     }
							                     });
						               if(errors = $.trim(errors))
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + errors);
							               return false;
						               }
						               else if($recaptchaResponse.length && !$recaptchaResponse.val())
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Security Code missing. Please try again.", "s2member-front", "s2member")); ?>');
							               return false;
						               }
					               }
					               $(submissionButton).attr(disabled),
						               ws_plugin__s2member_animateProcessing($(submissionButton)),
						               $(couponApplyButton).attr(disabled);
					               return true;
				               });
			}
			else if(($coForm = $('form#s2member-pro-stripe-checkout-form')).length === 1)
			{
				optionsSection = 'div#s2member-pro-stripe-checkout-form-options-section',
					descSection = 'div#s2member-pro-stripe-checkout-form-description-section',
					couponSection = 'div#s2member-pro-stripe-checkout-form-coupon-section',
					couponApplyButton = couponSection + ' input#s2member-pro-stripe-checkout-coupon-apply',
					registrationSection = 'div#s2member-pro-stripe-checkout-form-registration-section',
					customFieldsSection = 'div#s2member-pro-stripe-checkout-form-custom-fields-section',
					billingMethodSection = 'div#s2member-pro-stripe-checkout-form-billing-method-section',
					cardType = billingMethodSection + ' input[name="' + ws_plugin__s2member_escjQAttr('s2member_pro_stripe_checkout[card_type]') + '"]',
					billingAddressSection = 'div#s2member-pro-stripe-checkout-form-billing-address-section',
					$ajaxTaxDiv = $(billingAddressSection + ' > div#s2member-pro-stripe-checkout-form-ajax-tax-div'),
					captchaSection = 'div#s2member-pro-stripe-checkout-form-captcha-section',
					submissionSection = 'div#s2member-pro-stripe-checkout-form-submission-section',
					submissionNonceVerification = submissionSection + ' input#s2member-pro-stripe-checkout-nonce',
					submissionButton = submissionSection + ' button#s2member-pro-stripe-checkout-submit';

				ws_plugin__s2member_animateProcessing($(submissionButton), 'reset'),
					$(submissionButton).removeAttr('disabled'), $(couponApplyButton).removeAttr('disabled');

				(handleOptions = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if(!$(optionsSection + ' select#s2member-pro-stripe-checkout-options option').length)
					{
						$(optionsSection).hide(/* No options on this particular form. */);
						$(descSection).show(/* Show description on this particular form. */);
					}
					else // This is turned off by default for smoother loading. (via: display:none).
					{
						$(optionsSection).show(/* OK. So we need to display this now. */);
						$(descSection).hide(/* OK. So we need to hide this now. */);
						$(optionsSection + ' select#s2member-pro-stripe-checkout-options').change
						(function() // Handle option changes.
						 {
							 $(submissionNonceVerification).val('option');
							 $coForm.attr('action', $coForm.attr('action').replace(/#.*$/, '') + '#s2p-form');
							 $coForm.submit();
						 });
					}
				})();
				(handleCouponIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-checkout-coupons-not-required-or-not-possible').length)
					{
						$(couponSection).hide(/* Not accepting Coupons on this particular form. */);
					}
					else // This is turned off by default for smoother loading. (via: display:none).
						$(couponSection).show(/* OK. So we need to display this now. */);
				})();
				(handleTaxIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-checkout-tax-not-required-or-not-possible').length)
					{
						$ajaxTaxDiv.hide(), taxMayApply = false/* Tax does NOT even apply. */;
					}
				})();
				(calculateTax = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if(taxMayApply && !(eventTrigger && eventTrigger.interval && document.activeElement.id === 's2member-pro-stripe-checkout-country'))
					{
						var attr = $(submissionSection + ' input#s2member-pro-stripe-checkout-attr').val();
						var state = $.trim($(billingAddressSection + ' input#s2member-pro-stripe-checkout-state').val());
						var country = $(billingAddressSection + ' select#s2member-pro-stripe-checkout-country').val();
						var zip = $.trim($(billingAddressSection + ' input#s2member-pro-stripe-checkout-zip').val());
						var thisTaxLocation = state + '|' + country + '|' + zip/* Three part location. */;

						if(state && country && zip && thisTaxLocation && (!cTaxLocation || cTaxLocation !== thisTaxLocation) && (cTaxLocation = thisTaxLocation))
						{
							(cTaxReq) ? cTaxReq.abort(/* Abort. */) : null, clearTimeout(cTaxTimeout/* Clear. */), cTaxTimeout = 0;

							$ajaxTaxDiv.html('<div><img src="' + ws_plugin__s2member_escAttr(preloadAjaxLoader.src) + '" alt="<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(esc_attr(_x("Calculating Sales Tax...", "s2member-front", "s2member"))); ?>" /> <?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("calculating sales tax...", "s2member-front", "s2member")); ?></div>');

							cTaxTimeout = setTimeout(function(/* Create a new cTaxTimeout with a one second delay. */)
							                         {
								                         cTaxReq = $.post('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(admin_url("/admin-ajax.php")); ?>', {'action': 'ws_plugin__s2member_pro_stripe_ajax_tax', 'ws_plugin__s2member_pro_stripe_ajax_tax': '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(c_ws_plugin__s2member_utils_encryption::encrypt("ws-plugin--s2member-pro-stripe-ajax-tax")); ?>', 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[attr]': attr, 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[state]': state, 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[country]': country, 'ws_plugin__s2member_pro_stripe_ajax_tax_vars[zip]': zip}, function(response, textStatus)
								                         {
									                         clearTimeout(cTaxTimeout/* Clear cTaxTimeout. */), cTaxTimeout = 0;

									                         try // Try/catch here. jQuery will sometimes return a successful response in IE whenever the connection is aborted with a null response.
									                         {
										                         /* translators: `Sales Tax (Today)` and `Total (Today)` The word `Today` is displayed when/if a trial period is offered. The word `Today` is translated elsewhere. */
										                         $ajaxTaxDiv.html('<div>' + $.sprintf('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("<strong>Sales Tax%s:</strong> %s<br /><strong>— Total%s:</strong> %s", "s2member-front", "s2member")); ?>', ((response.trial) ? ' ' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Today", "s2member-front", "s2member")); ?>' : ''), ((response.tax_per) ? '<em>' + response.tax_per + '</em> ( ' + response.cur_symbol + '' + response.tax + ' )' : response.cur_symbol + '' + response.tax), ((response.trial) ? ' ' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Today", "s2member-front", "s2member")); ?>' : ''), response.cur_symbol + '' + response.total) + '</div>');
									                         }
									                         catch(e)
									                         {console.log(e);}
								                         }, 'json');
							                         }, ((eventTrigger && eventTrigger.keyCode) ? 1000 : 100));
						}
						else if(!state || !country || !zip || !thisTaxLocation)
							$ajaxTaxDiv.html(''), cTaxLocation = null;
					}
				})();
				cTaxDelay = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					setTimeout(function(/* Trigger event handler with a brief delay. */)
					           {
						           calculateTax(eventTrigger);
					           }, 10); // Brief delay.
				};
				$(billingAddressSection + ' input#s2member-pro-stripe-checkout-state').bind('keyup blur', calculateTax).bind('cut paste', cTaxDelay);
				$(billingAddressSection + ' input#s2member-pro-stripe-checkout-zip').bind('keyup blur', calculateTax).bind('cut paste', cTaxDelay);
				$(billingAddressSection + ' select#s2member-pro-stripe-checkout-country').bind('change', calculateTax);

				setInterval(function(/* Helps with things like Google's Autofill feature. */)
				            {
					            calculateTax({interval: true}/* Identify as interval trigger. */);
				            }, 1000);

				(handlePasswordIssues = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-checkout-password-not-required-or-not-possible').length)
					{
						$(registrationSection + ' > div#s2member-pro-stripe-checkout-form-password-div').hide();
						$(registrationSection + ' > div#s2member-pro-stripe-checkout-form-password-div :input').attr(ariaFalseDis);
					}
				})();
				(handleExistingUsers = function(eventTrigger/* eventTrigger is passed by jQuery for DOM events. */)
				{
					if(S2MEMBER_CURRENT_USER_IS_LOGGED_IN/* If User/Member is already logged in. */)
					{
						$(registrationSection + ' input#s2member-pro-stripe-checkout-first-name')
							.each(function()
							      {
								      var $this = $(this), val = $this.val();
								      (!val) ? $this.val(S2MEMBER_CURRENT_USER_FIRST_NAME) : null;
							      });
						$(registrationSection + ' input#s2member-pro-stripe-checkout-last-name')
							.each(function()
							      {
								      var $this = $(this), val = $this.val();
								      (!val) ? $this.val(S2MEMBER_CURRENT_USER_LAST_NAME) : null;
							      });
						$(registrationSection + ' input#s2member-pro-stripe-checkout-email').val(S2MEMBER_CURRENT_USER_EMAIL).attr(ariaFalseDis);
						$(registrationSection + ' input#s2member-pro-stripe-checkout-username').val(S2MEMBER_CURRENT_USER_LOGIN).attr(ariaFalseDis);

						$(registrationSection + ' > div#s2member-pro-stripe-checkout-form-password-div').hide();
						$(registrationSection + ' > div#s2member-pro-stripe-checkout-form-password-div :input').attr(ariaFalseDis);

						if($.trim($(registrationSection + ' > div#s2member-pro-stripe-checkout-form-registration-section-title').html()) === '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Create Profile", "s2member-front", "s2member")); ?>')
							$(registrationSection + ' > div#s2member-pro-stripe-checkout-form-registration-section-title').html('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Your Profile", "s2member-front", "s2member")); ?>');

						$(customFieldsSection).hide(), $(customFieldsSection + ' :input').attr(ariaFalseDis);
					}
				})();
				(handleBillingMethod = function(eventTrigger /* eventTrigger is passed by jQuery for DOM events. */)
				{
					if($(submissionSection + ' input#s2member-pro-stripe-checkout-payment-not-required-or-not-possible').length)
						$(cardType).val(['Free']); // No payment required in this VERY special case.

					var billingMethod = $(cardType + ':checked').val(/* Billing Method. */);

					if($.inArray(billingMethod, ['Free']) !== -1)
					{
						$(billingMethodSection).hide(), $(billingAddressSection).hide();

						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div').hide();
						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaFalse);

						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div').hide();
						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaFalse);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(submissionSection + ' button#s2member-pro-stripe-checkout-submit').focus() : null;
					}
					else if($.inArray(billingMethod, ['Visa', 'MasterCard', 'Amex', 'Discover']) !== -1)
					{
						$(billingMethodSection).show(), $(billingAddressSection).show();

						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div').show();
						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaTrue);

						$(billingMethodSection + ' > div#s2member-pro-stripe-checkout-form-card-start-date-issue-number-div').hide();
						$(billingMethodSection + ' > div#s2member-pro-stripe-checkout-form-card-start-date-issue-number-div :input').attr(ariaFalse);

						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div').show();
						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaTrue);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(billingMethodSection + ' input#s2member-pro-stripe-checkout-card-number').focus() : null;
					}
					else if($.inArray(billingMethod, ['Maestro', 'Solo']) !== -1)
					{
						$(billingMethodSection).show(), $(billingAddressSection).show();

						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div').show();
						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaTrue);

						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div').show();
						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaTrue);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(billingMethodSection + ' input#s2member-pro-stripe-checkout-card-number').focus() : null;
					}
					else if(!billingMethod/* Else there was no Billing Method supplied. */)
					{
						$(billingMethodSection).show(), $(billingAddressSection).hide();

						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div').hide();
						$(billingMethodSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaFalse);

						$(billingMethodSection + ' > div#s2member-pro-stripe-checkout-form-card-type-div').show();
						$(billingMethodSection + ' > div#s2member-pro-stripe-checkout-form-card-type-div :input').attr(ariaTrue);

						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div').hide();
						$(billingAddressSection + ' > div.s2member-pro-stripe-checkout-form-div :input').attr(ariaFalse);

						(!taxMayApply) ? $ajaxTaxDiv.hide(/* Tax does NOT even apply. */) : null;

						(eventTrigger) ? $(submissionSection + ' button#s2member-pro-stripe-checkout-submit').focus() : null;
					}
				})();
				$(cardType).click(handleBillingMethod).change(handleBillingMethod);

				$(couponApplyButton)
					.click(function(/* Only applying coupon. */)
					       {
						       $(submissionNonceVerification).val('apply-coupon'), $coForm.submit();
					       });
				$(registrationSection + ' > div#s2member-pro-stripe-checkout-form-password-div :input')
					.keyup(function()
					       {
						       ws_plugin__s2member_passwordStrength(
							       $(registrationSection + ' input#s2member-pro-stripe-checkout-username'),
							       $(registrationSection + ' input#s2member-pro-stripe-checkout-password1'),
							       $(registrationSection + ' input#s2member-pro-stripe-checkout-password2'),
							       $(registrationSection + ' div#s2member-pro-stripe-checkout-form-password-strength')
						       );
					       });
				$coForm.submit(function(/* Form validation. */)
				               {
					               if($.inArray($(submissionNonceVerification).val(), ['option', 'apply-coupon']) === -1)
					               {
						               var context = this, label = '', error = '', errors = '',
							               $recaptchaResponse = $(captchaSection + ' input#recaptcha_response_field'),
							               $password1 = $(registrationSection + ' input#s2member-pro-stripe-checkout-password1[aria-required="true"]'),
							               $password2 = $(registrationSection + ' input#s2member-pro-stripe-checkout-password2');

						               if(!$(cardType + ':checked').val())
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Please choose a Billing Method.", "s2member-front", "s2member")); ?>');
							               return false;
						               }
						               $(':input', context)
							               .each(function(/* Go through them all together. */)
							                     {
								                     var id = $.trim($(this).attr('id')).replace(/---[0-9]+$/g, ''/* Remove numeric suffixes. */);

								                     if(id && (label = $.trim($('label[for="' + id.replace(/-(month|year)/, '') + '"]', context).first().children('span').first().text().replace(/[\r\n\t]+/g, ' '))))
								                     {
									                     if(error = ws_plugin__s2member_validationErrors(label, this, context))
										                     errors += error + '\n\n'/* Collect errors. */;
								                     }
							                     });
						               if(errors = $.trim(errors))
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + errors);
							               return false;
						               }
						               else if($password1.length && $.trim($password1.val()) !== $.trim($password2.val()))
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Passwords do not match up. Please try again.", "s2member-front", "s2member")); ?>');
							               return false;
						               }
						               else if($password1.length && $.trim($password1.val()).length < 6/* Enforce minimum length requirement here. */)
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Password MUST be at least 6 characters. Please try again.", "s2member-front", "s2member")); ?>');
							               return false;
						               }
						               else if($recaptchaResponse.length && !$recaptchaResponse.val())
						               {
							               alert('<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("— Oops, you missed something: —", "s2member-front", "s2member")); ?>' + '\n\n' + '<?php echo c_ws_plugin__s2member_utils_strings::esc_js_sq(_x("Security Code missing. Please try again.", "s2member-front", "s2member")); ?>');
							               return false;
						               }
					               }
					               $(submissionButton).attr(disabled),
						               ws_plugin__s2member_animateProcessing($(submissionButton)),
						               $(couponApplyButton).attr(disabled);
					               return true;
				               });
			}
			(jumpToResponses = function(/* Jump to form responses. Make sure Customers see messages. */)
			{
				$('div#s2member-pro-stripe-form-response')
					.each(function()
					      {
						      var offset = $(this).offset();
						      window.scrollTo(0, offset.top - 100);
					      });
			})();
		}
	}), jQuery.ajax({cache: true, dataType: 'script', url: 'https://js.stripe.com/v2/'});