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

	var cfg = S2MEMBER_PRO_PAYPAL_CHECKOUT, sdkLoads = {};

	//260818.2056 Share one browser payment engine across membership and Specific Post/Page Pro-Forms.
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
		var $lang = $form.find('input#s2member-pro-paypal-lang-attr');
		var expectedFlow = $.trim($flow.val());
		var currency = $.trim($currency.val()).toUpperCase();
		var locale = $.trim($lang.val());
		var lc = $.trim($lc.val()).toUpperCase();
		var prepared = null, preparedFingerprint = '', planId = null;

		if(!expectedFlow || !currency || !cfg.client_id)
			return;

		//260818.2056 Different flow/currency/locale combinations need isolated SDK globals when multiple Pro-Forms share a page.
		var sdkNamespace = ('s2m_pro_ppco_' + expectedFlow + '_' + currency + '_' + locale + '_' + lc).replace(/[^a-z0-9_]/gi, '_');
		var sdkScriptId = 's2member-pro-paypal-checkout-sdk-' + sdkNamespace;

		var buttonId = options.prefix + '-paypal-checkout-button';
		var messageId = options.prefix + '-paypal-checkout-message';
		var responseId = options.prefix + '-paypal-checkout-response';
		var $submissionSection = $form.find('div#' + options.prefix + '-form-submission-section');
		var $responseDiv = $form.find('div#' + options.prefix + '-form-response-div');
		var $button = $('<div />', {'id': buttonId, 'class': 's2member-pro-paypal-checkout-button'}).css({'display': 'none', 'max-width': '145px', 'width': 'auto', 'margin': '0'});
		//260819.0124 Announce inline Checkout feedback without requiring visible keyboard focus on the message.
		var $message = $('<div />', {'id': messageId, 'class': 's2member-pro-paypal-checkout-message ws-plugin--s2member-ppco-message', 'role': 'status', 'aria-live': 'polite'}).css({'display': 'none', 'clear': 'both', 'margin': '6px 0 0', 'text-align': 'right'});
		var $response = $('<div />', {'id': responseId}).hide();

		$submitDiv.append($button);
		$submissionSection.append($message); //260819.0009 Keep the inline copy outside the floated submit div so messages cannot move the PayPal button.
		$responseDiv.append($response);

		var showMessage = function(message, type)
		{
			var content = message || cfg.messages.payment_failed;
			var responseType = (type === 'info') ? 'info' : 'error';
			var responseClass = 's2member-pro-paypal-form-response-' + responseType + ' ' + options.prefix + '-form-response-' + responseType;
			var messageClass = 'ws-plugin--s2member-ppco-' + responseType;

			//260819.0042 Mirror the full Pro-Form response above and shared compact Checkout feedback beside the payment control.
			$response.attr('class', responseClass).html(content).show();
			$message.empty().append($('<span />', {'class': messageClass}).html(content)).show();

			//260819.0124 Release PayPal's failed/cancelled button focus without moving visible focus onto another control.
			try
			{
				if(document.activeElement && typeof document.activeElement.blur === 'function')
					document.activeElement.blur();
			}
			catch(error){}
		};
		var showError = function(message)
		{
			showMessage(message, 'error');
		};
		var showInfo = function(message)
		{
			showMessage(message, 'info');
		};
		var clearMessage = function()
		{
			$response.hide().removeAttr('class').empty();
			$message.hide().empty();
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
		var formFingerprint = function()
		{
			var values = [];
			$.each($form.serializeArray(), function(index, field)
			{
				//260818.2056 CAPTCHA responses are one-time transport proof, not purchase data; exclude them from prepared-token reuse.
				if(/(?:^|\[)(?:g-recaptcha-response|recaptcha_challenge_field|recaptcha_response_field)(?:\]|$)/.test(field.name))
					return;
				values.push(encodeURIComponent(field.name) + '=' + encodeURIComponent(field.value));
			});
			return values.join('&');
		};
		var resetCaptcha = function()
		{
			//260818.2056 Server preparation consumes the CAPTCHA token; reset its browser widget before any changed purchase can prepare again.
			try
			{
				if(window.grecaptcha && typeof window.grecaptcha.reset === 'function')
					window.grecaptcha.reset();
				else if(window.Recaptcha && typeof window.Recaptcha.reload === 'function')
					window.Recaptcha.reload();
			}
			catch(error){}
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
		var submitFree = function(freeHandoff)
		{
			//260818.2056 The opaque handoff proves preparation already validated this exact free purchase, including CAPTCHA.
			$cardType.val(['Free']);
			$form.find('input[name="' + options.postName + '[paypal_checkout_op]"], input[name="' + options.postName + '[paypal_checkout_free_handoff]"]').remove();
			$('<input />', {type: 'hidden', name: options.postName + '[paypal_checkout_op]', value: 'free'}).appendTo($form);
			$('<input />', {type: 'hidden', name: options.postName + '[paypal_checkout_free_handoff]', value: freeHandoff}).appendTo($form);
			HTMLFormElement.prototype.submit.call($form[0]);
		};
		var prepare = function(formData, fingerprint)
		{
			var body = formData;
			body += (body ? '&' : '') + encodeURIComponent(options.postName + '[paypal_checkout_op]') + '=prepare';

			return fetchJson($form.attr('action') || window.location.href, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
				body: body,
				credentials: 'same-origin'
			}).then(function(result)
			{
				if(result && result.error === 'pro_checkout_payment_not_required' && result.free_handoff)
				{
					submitFree(result.free_handoff);
					throw new Error('payment_not_required');
				}
				if(!result || !result.ok || !result.token || !result.endpoint || !result.invoice || result.flow !== expectedFlow)
				{
					showError(result && result.message ? result.message : cfg.messages.prepare_failed);
					throw new Error(result && result.error ? result.error : 'prepare_failed');
				}

				prepared = result;
				preparedFingerprint = fingerprint;
				resetCaptcha();
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
			var PayPal = window[sdkNamespace], load = sdkLoads[sdkNamespace];
			if(PayPal && PayPal.Buttons)
			{
				callback(PayPal);
				return;
			}

			if(load)
			{
				if(load.status === 'failed' || load.status === 'loaded')
					showError(cfg.messages.sdk_failed);
				else
					load.callbacks.push({success: callback, failure: function(){ showError(cfg.messages.sdk_failed); }});
				return;
			}

			//260818.2056 One SDK request is shared by forms with identical SDK configuration; each form keeps its own render callback.
			load = sdkLoads[sdkNamespace] = {status: 'loading', callbacks: [{success: callback, failure: function(){ showError(cfg.messages.sdk_failed); }}]};
			var script = document.createElement('script');
			script.id = sdkScriptId;
			script.setAttribute('data-namespace', sdkNamespace);
			script.src = sdkSource();
			script.async = true;
			script.onload = function()
			{
				PayPal = window[sdkNamespace];
				var callbacks = load.callbacks.slice(0);
				load.callbacks = [];
				load.status = (PayPal && PayPal.Buttons) ? 'loaded' : 'failed';

				$.each(callbacks, function(index, queued)
				{
					if(load.status === 'loaded')
						queued.success(PayPal);
					else
						queued.failure();
				});
			};
			script.onerror = function()
			{
				var callbacks = load.callbacks.slice(0);
				load.callbacks = [];
				load.status = 'failed';
				$.each(callbacks, function(index, queued){ queued.failure(); });
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
					clearMessage();

					//260818.2056 An unchanged purchase can safely reuse its prepared token even though its one-time CAPTCHA response was reset.
					var fingerprint = formFingerprint();
					if(prepared && preparedFingerprint === fingerprint)
						return actions.resolve();

					if(!validate())
						return actions.reject();

					var formData = $form.serialize();
					fingerprint = formFingerprint();
					prepared = null, preparedFingerprint = '', planId = null;
					return prepare(formData, fingerprint).then(function()
					{
						return actions.resolve();
					}).catch(function(error)
					{
						return actions.reject();
					});
				},
				onCancel: function()
				{
					showInfo(cfg.messages.cancelled);
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
				clearMessage();
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

	initForm({
		form: 'form#s2member-pro-paypal-sp-checkout-form',
		prefix: 's2member-pro-paypal-sp-checkout',
		postName: 's2member_pro_paypal_sp_checkout',
		cardType: 'input[name="s2member_pro_paypal_sp_checkout[card_type]"]',
		submitDiv: 'div#s2member-pro-paypal-sp-checkout-form-submit-div',
		submit: '#s2member-pro-paypal-sp-checkout-submit',
		couponApply: 'input#s2member-pro-paypal-sp-checkout-coupon-apply',
		nonce: 'input#s2member-pro-paypal-sp-checkout-nonce',
		flow: 'input#s2member-pro-paypal-sp-checkout-ppco-flow',
		currency: 'input#s2member-pro-paypal-sp-checkout-ppco-currency',
		lc: 'input#s2member-pro-paypal-sp-checkout-ppco-lc'
	});
});
