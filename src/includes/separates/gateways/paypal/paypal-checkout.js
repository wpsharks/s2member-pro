/**
 * Modern PayPal Checkout UI for PayPal Pro-Forms.
 *
 * @package s2Member\PayPal
 * @since 260818
 */
jQuery(document).ready(function($)
{
	if(typeof S2MEMBER_PRO_PAYPAL_CHECKOUT !== 'object' || !S2MEMBER_PRO_PAYPAL_CHECKOUT.enabled)
		return;

	var cfg = S2MEMBER_PRO_PAYPAL_CHECKOUT;

	//260818.2010 Keep the browser layer generic so Specific Post/Page can reuse the same PayPal SDK flow next.
	var initForm = function(options)
	{
		var $form = $(options.form);
		if($form.length !== 1)
			return;

		var $cardType = $form.find(options.cardType);
		var $submitDiv = $form.find(options.submitDiv);
		var $flow = $form.find(options.flow);
		var $currency = $form.find(options.currency);
		var $lc = $form.find(options.lc);
		var $lang = $('input#s2member-pro-paypal-lang-attr');
		var expectedFlow = $.trim($flow.val());
		var currency = $.trim($currency.val()).toUpperCase();
		var locale = $.trim($lang.val());
		var lc = $.trim($lc.val()).toUpperCase();
		var prepared = null, preparedFingerprint = '', planId = null, sdkLoading = false, sdkLoaded = false;

		if(!expectedFlow || !currency || !cfg.client_id)
			return;

		var buttonId = options.prefix + '-paypal-checkout-button';
		var errorId = options.prefix + '-paypal-checkout-error';
		var $button = $('<div />', {'id': buttonId, 'class': 's2member-pro-paypal-checkout-button'}).css({'display': 'none', 'max-width': '145px', 'width': 'auto', 'margin': '0'});
		var $error = $('<div />', {'id': errorId, 'class': 's2member-pro-paypal-checkout-error'}).css({'display': 'none', 'margin': '6px 0 0'});

		$submitDiv.append($button).append($error);

		var showError = function(message)
		{
			$error.text(message || cfg.messages.payment_failed).show();
		};
		var clearError = function()
		{
			$error.hide().text('');
		};
		var currentSubmit = function()
		{
			return $submitDiv.find(options.submit);
		};
		var resetLegacySubmit = function()
		{
			var $submit = currentSubmit();
			ws_plugin__s2member_animateProcessing($submit, 'reset');
			$submit.removeAttr('disabled');
			$form.find(options.couponApply).removeAttr('disabled');
		};
		var billingMethod = function()
		{
			return $cardType.filter(':checked').val() || 'PayPal';
		};
		var isPayPal = function()
		{
			return billingMethod() === 'PayPal';
		};
		var encode = function(object)
		{
			var values = [];
			$.each(object, function(key, value)
			{
				values.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
			});
			return values.join('&');
		};
		var fetchJson = function(url, request)
		{
			return fetch(url, request).then(function(response)
			{
				return response.text();
			}).then(function(text)
			{
				try
				{
					return JSON.parse(text);
				}
				catch(error)
				{
					throw new Error('invalid_json');
				}
			});
		};
		var postTo = function(url, data)
		{
			var form = document.createElement('form');
			form.method = 'post';
			form.acceptCharset = 'UTF-8';
			form.action = url;
			$.each(data, function(name, value)
			{
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = name;
				input.value = value;
				form.appendChild(input);
			});
			document.body.appendChild(form);
			form.submit();
		};
		var validate = function()
		{
			var event = $.Event('submit');
			$form.data('s2member-ppco-validating', true);
			$form.triggerHandler(event);
			$form.removeData('s2member-ppco-validating');
			resetLegacySubmit();
			return !event.isDefaultPrevented();
		};
		var submitFree = function()
		{
			//260818.2010 The server already proved payment is unnecessary; submit through legacy free fulfillment without PayPal.
			$cardType.val(['Free']);
			$form.find('input[name="' + options.postName + '[paypal_checkout_op]"]').remove();
			$('<input />', {type: 'hidden', name: options.postName + '[paypal_checkout_op]', value: 'free'}).appendTo($form);
			HTMLFormElement.prototype.submit.call($form[0]);
		};
		var prepare = function(fingerprint)
		{
			var body = fingerprint;
			body += (body ? '&' : '') + encodeURIComponent(options.postName + '[paypal_checkout_op]') + '=prepare';

			return fetchJson($form.attr('action') || window.location.href, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
				body: body,
				credentials: 'same-origin'
			}).then(function(result)
			{
				if(result && result.error === 'pro_checkout_payment_not_required')
				{
					submitFree();
					throw new Error('payment_not_required');
				}
				if(!result || !result.ok || !result.token || !result.endpoint || !result.invoice || result.flow !== expectedFlow)
				{
					showError(result && result.message ? result.message : cfg.messages.prepare_failed);
					throw new Error(result && result.error ? result.error : 'prepare_failed');
				}

				prepared = result;
				preparedFingerprint = fingerprint;
				return result;
			});
		};
		var sdkSource = function()
		{
			var base = cfg.sandbox ? 'https://www.sandbox.paypal.com/sdk/js' : 'https://www.paypal.com/sdk/js';
			var query = 'client-id=' + encodeURIComponent(cfg.client_id) + '&currency=' + encodeURIComponent(currency) + '&disable-funding=card';
			query += (expectedFlow === 'subscription') ? '&vault=true&intent=subscription' : '&intent=capture&commit=true';
			if(cfg.sandbox && lc)
				query += '&buyer-country=' + encodeURIComponent(lc);
			if(locale)
				query += '&locale=' + encodeURIComponent(locale);
			return base + '?' + query;
		};
		var loadSdk = function(callback)
		{
			var namespace = 's2m_pro_ppco', PayPal = window[namespace];
			if(PayPal && PayPal.Buttons)
			{
				sdkLoaded = true;
				callback(PayPal);
				return;
			}
			if(sdkLoading)
			{
				setTimeout(function(){ loadSdk(callback); }, 50);
				return;
			}

			sdkLoading = true;
			var script = document.createElement('script');
			script.id = 's2member-pro-paypal-checkout-sdk';
			script.setAttribute('data-namespace', namespace);
			script.src = sdkSource();
			script.async = true;
			script.onload = function()
			{
				sdkLoading = false;
				PayPal = window[namespace];
				if(PayPal && PayPal.Buttons)
				{
					sdkLoaded = true;
					callback(PayPal);
				}
				else
					showError(cfg.messages.sdk_failed);
			};
			script.onerror = function()
			{
				sdkLoading = false;
				showError(cfg.messages.sdk_failed);
			};
			(document.head || document.body || document.documentElement).appendChild(script);
		};
		var frameworkRequest = function(operation, extra)
		{
			if(!prepared || !prepared.endpoint || !prepared.token)
				return Promise.reject(new Error('purchase_not_prepared'));

			var data = {
				s2member_paypal_checkout_op: operation,
				s2member_paypal_checkout_t: prepared.token
			};
			$.extend(data, extra || {});

			return fetchJson(prepared.endpoint, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
				body: encode(data),
				credentials: 'same-origin'
			});
		};
		var renderButton = function(PayPal)
		{
			if($button.attr('data-s2m-ppco-rendered') === '1')
				return;

			var buttonOptions = {
				fundingSource: PayPal.FUNDING.PAYPAL,
				style: {layout: 'vertical', tagline: false, height: 40},
				onClick: function(data, actions)
				{
					clearError();
					if(!validate())
						return actions.reject();

					var fingerprint = $form.serialize();
					if(prepared && preparedFingerprint === fingerprint)
						return actions.resolve(); //260818.2010 Reuse the same token so payment retries retain Framework idempotency.

					prepared = null, preparedFingerprint = '', planId = null;
					return prepare(fingerprint).then(function()
					{
						return actions.resolve();
					}).catch(function(error)
					{
						return actions.reject();
					});
				},
				onCancel: function()
				{
					showError(cfg.messages.cancelled);
				},
				onError: function()
				{
					showError(expectedFlow === 'subscription' ? cfg.messages.subscription_failed : cfg.messages.payment_failed);
				}
			};

			if(expectedFlow === 'subscription')
			{
				buttonOptions.createSubscription = function(data, actions)
				{
					if(planId)
						return actions.subscription.create({plan_id: planId, custom_id: prepared.invoice, application_context: {shipping_preference: 'NO_SHIPPING'}});

					return frameworkRequest('get_plan_id').then(function(result)
					{
						if(!result || !result.plan_id)
							throw new Error(result && result.error ? result.error : 'plan_get_failed');
						planId = result.plan_id;
						return actions.subscription.create({plan_id: planId, custom_id: prepared.invoice, application_context: {shipping_preference: 'NO_SHIPPING'}});
					});
				};
				buttonOptions.onApprove = function(data)
				{
					return frameworkRequest('confirm_subscription', {subscription_id: data && data.subscriptionID ? data.subscriptionID : ''}).then(function(result)
					{
						if(result && result.rtn_url && result.rtn_post)
						{
							postTo(result.rtn_url, result.rtn_post);
							return;
						}
						throw new Error(result && result.error ? result.error : 'subscription_confirm_failed');
					}).catch(function()
					{
						showError(cfg.messages.subscription_failed);
					});
				};
			}
			else
			{
				buttonOptions.createOrder = function()
				{
					return frameworkRequest('create_order').then(function(result)
					{
						if(result && result.order_id)
							return result.order_id;
						throw new Error(result && result.error ? result.error : 'order_create_failed');
					});
				};
				buttonOptions.onApprove = function(data)
				{
					return frameworkRequest('capture_order', {order_id: data && data.orderID ? data.orderID : ''}).then(function(result)
					{
						if(result && result.rtn_url && result.rtn_post)
						{
							postTo(result.rtn_url, result.rtn_post);
							return;
						}
						throw new Error(result && result.error ? result.error : 'order_capture_failed');
					}).catch(function()
					{
						showError(cfg.messages.payment_failed);
					});
				};
			}

			try
			{
				$button.attr('data-s2m-ppco-rendered', '1');
				PayPal.Buttons(buttonOptions).render('#' + buttonId);
			}
			catch(error)
			{
				$button.removeAttr('data-s2m-ppco-rendered');
				showError(cfg.messages.sdk_failed);
			}
		};
		var ensureButton = function()
		{
			if(sdkLoaded)
				renderButton(window.s2m_pro_ppco);
			else
				loadSdk(renderButton);
		};
		var syncBillingMethod = function()
		{
			if(isPayPal())
			{
				currentSubmit().hide();
				$button.show();
				ensureButton();
			}
			else
			{
				$button.hide();
				$error.hide();
				currentSubmit().show();
			}
		};

		//260818.2010 Legacy billing-method logic runs first; this second handler replaces only its PayPal submit presentation.
		$cardType.on('click.s2memberPpco change.s2memberPpco', syncBillingMethod);

		$form.on('submit.s2memberPpco', function(event)
		{
			if($form.data('s2member-ppco-validating'))
				return true;
			if($.inArray($form.find(options.nonce).val(), ['option', 'apply-coupon']) !== -1)
				return true;
			if(isPayPal())
			{
				event.preventDefault();
				resetLegacySubmit();
				return false;
			}
			return true;
		});

		syncBillingMethod();
	};

	initForm({
		form: 'form#s2member-pro-paypal-checkout-form',
		prefix: 's2member-pro-paypal-checkout',
		postName: 's2member_pro_paypal_checkout',
		cardType: 'input[name="s2member_pro_paypal_checkout[card_type]"]',
		submitDiv: 'div#s2member-pro-paypal-checkout-form-submit-div',
		submit: '#s2member-pro-paypal-checkout-submit',
		couponApply: 'input#s2member-pro-paypal-checkout-coupon-apply',
		nonce: 'input#s2member-pro-paypal-checkout-nonce',
		flow: 'input#s2member-pro-paypal-checkout-ppco-flow',
		currency: 'input#s2member-pro-paypal-checkout-ppco-currency',
		lc: 'input#s2member-pro-paypal-checkout-ppco-lc'
	});
});
