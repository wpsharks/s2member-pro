<?php
// @codingStandardsIgnoreFile
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");
?>

<div id="s2p-form"></div><!-- This is for hash anchors; do NOT remove please. -->

<form id="s2member-pro-stripe-update-form" class="s2member-pro-stripe-form s2member-pro-stripe-update-form" method="post" action="%%action%%" autocomplete="off">

	<!-- Response Section (this is auto-filled after form submission). -->
	<div id="s2member-pro-stripe-update-form-response-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-update-form-section s2member-pro-stripe-form-response-section s2member-pro-stripe-update-form-response-section">
		<div id="s2member-pro-stripe-update-form-response-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-response-div s2member-pro-stripe-update-form-response-div">
			%%response%%
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Checkout Description (this is the desc="" attribute from your Shortcode). -->
	<div id="s2member-pro-stripe-update-form-description-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-update-form-section s2member-pro-stripe-form-description-section s2member-pro-stripe-update-form-description-section">
		<div id="s2member-pro-stripe-update-form-description-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-description-div s2member-pro-stripe-update-form-description-div">
			%%description%%
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Billing Method (powered by Stripe). -->
	%%billing_method%%

	<!-- Name on Card -->
	<div class="s2member-pro-stripe-form-section">
		<div id="s2member-pro-stripe-update-form-name-on-card-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-form-name-on-card-div">
			<label for="s2member-pro-stripe-update-name-on-card" id="s2member-pro-stripe-update-form-name-on-card-label" class="s2member-pro-stripe-form-name-on-card-label s2member-pro-stripe-update-form-name-on-card-label">
				<span><?php echo _x("Name on Card", "s2member-front", "s2member"); ?></span> *<br />
				<input type="text" aria-required="true" maxlength="50" autocomplete="off" name="s2member_pro_stripe_update[name_on_card]" id="s2member-pro-stripe-update-name-on-card" class="s2member-pro-stripe-name-on-card s2member-pro-stripe-update-name-on-card form-control" value="" tabindex="10" style="border: 1px solid #b6b6b3; border-radius: 4px; padding: 10px 13px; font-size: 17px; color: #333;" />
			</label>
		</div>
	</div>

	<!-- Captcha ( A reCaptcha section, with a required security code will appear here; if captcha="1" ). -->
	%%captcha%%

	<!-- Checkout Now (this holds the submit button, and also some dynamic hidden input variables). -->
	<div id="s2member-pro-stripe-update-form-submission-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-update-form-section s2member-pro-stripe-form-submission-section s2member-pro-stripe-update-form-submission-section">
		<div id="s2member-pro-stripe-update-form-submission-section-title" class="s2member-pro-stripe-form-section-title s2member-pro-stripe-update-form-section-title s2member-pro-stripe-form-submission-section-title s2member-pro-stripe-update-form-submission-section-title">
			<?php echo _x("Update Billing Information", "s2member-front", "s2member"); ?>
		</div>
		<div id="s2member-pro-stripe-update-form-submit-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-submit-div s2member-pro-stripe-update-form-submit-div">
			%%hidden_inputs%% <!-- Auto-filled by the s2Member software. Do NOT remove this under any circumstance. -->
			<button type="submit" id="s2member-pro-stripe-update-submit" class="s2member-pro-stripe-submit s2member-pro-stripe-update-submit btn btn-primary" tabindex="300"><?php echo esc_html(_x("Submit Form", "s2member-front", "s2member")); ?></button>
		</div>
		<div style="clear:both;"></div>
	</div>
</form>
