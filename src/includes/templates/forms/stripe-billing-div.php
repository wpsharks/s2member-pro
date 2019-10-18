<?php
// @codingStandardsIgnoreFile
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");
?>

	<div id="s2member-pro-stripe-form-billing-method-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-form-billing-method-section">
		<div id="s2member-pro-stripe-form-billing-method-section-title" class="s2member-pro-stripe-form-section-title s2member-pro-stripe-form-billing-method-section-title">
			<?php echo _x("Billing Method", "s2member-front", "s2member"); ?>
		</div>
		<label for="s2member-pro-stripe-form-card-element" id="s2member-pro-stripe-form-card-element-label" class="s2member-pro-stripe-form-card-element-label">
			<?php echo _x("Credit or debit card", "s2member-front", "s2member"); ?>
		</label>
		<div id="s2member-pro-stripe-form-card-element">
			<!-- A Stripe Element will be inserted here. -->
		</div>

		<!-- Used to display Element errors. -->
		<div id="s2member-pro-stripe-form-card-errors" role="alert"></div>

		<!-- Update with IDs -->
		<input type="hidden" id="s2member-pro-stripe-form-pm-id" name="stripe_pm_id" value="" />
		<input type="hidden" id="s2member-pro-stripe-form-pi-id" name="stripe_pi_id" value="" />
		<input type="hidden" id="s2member-pro-stripe-form-seti-id" name="stripe_seti_id" value="" />
		<input type="hidden" id="s2member-pro-stripe-form-sub-id" name="stripe_sub_id" value="%%sub_id%%" />
		<input type="hidden" id="s2member-pro-stripe-form-pi-secret" name="stripe_pi_secret" value="%%pi_secret%%" />
		<input type="hidden" id="s2member-pro-stripe-form-seti-secret" name="stripe_seti_secret" value="%%seti_secret%%" />

		<div id="s2member-pro-stripe-checkout-form-source-token-summary" class="s2member-pro-stripe-form-source-token-summary s2member-pro-stripe-checkout-form-source-token-summary">
				<!--%%source_token_summary%%-->
		</div>
		<div style="clear:both;"></div>
	</div>

	<style>
		#s2member-pro-stripe-form-card-element {border: 1px solid #b6b6b3; border-radius: 4px; padding: 10px 13px;}
		#s2member-pro-stripe-form-card-errors {color: #eb1c26;}
		.s2member-pro-stripe-form-disabled {opacity: .5;}
	</style>
