<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
?>

<div id="s2p-form"></div><!-- This is for hash anchors; do NOT remove please. -->

<form id="s2member-pro-stripe-update-form" class="s2member-pro-stripe-form s2member-pro-stripe-update-form" method="post" action="%%action%%">

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

	<!-- Billing Method (Customers can use a Credit/Debit card only). -->
	<div id="s2member-pro-stripe-update-form-billing-method-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-update-form-section s2member-pro-stripe-form-billing-method-section s2member-pro-stripe-update-form-billing-method-section">
		<div id="s2member-pro-stripe-update-form-billing-method-section-title" class="s2member-pro-stripe-form-section-title s2member-pro-stripe-update-form-section-title s2member-pro-stripe-form-billing-method-section-title s2member-pro-stripe-update-form-billing-method-section-title">
			<?php echo _x ("New Billing Method", "s2member-front", "s2member"); ?>
		</div>
		<div id="s2member-pro-stripe-update-form-card-type-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-card-type-div s2member-pro-stripe-update-form-card-type-div">
			%%card_type_options%%
		</div>
		<div id="s2member-pro-stripe-update-form-card-number-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-card-number-div s2member-pro-stripe-update-form-card-number-div">
			<label for="s2member-pro-stripe-update-card-number" id="s2member-pro-stripe-update-form-card-number-label" class="s2member-pro-stripe-form-card-number-label s2member-pro-stripe-update-form-card-number-label">
				<span><?php echo _x ("Card Number (no dashes or spaces)", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="100" autocomplete="off" name="s2member_pro_stripe_update[card_number]" id="s2member-pro-stripe-update-card-number" class="s2member-pro-stripe-card-number s2member-pro-stripe-update-card-number form-control" value="%%card_number_value%%" tabindex="20" />
			</label>
		</div>
		<div id="s2member-pro-stripe-update-form-card-expiration-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-card-expiration-div s2member-pro-stripe-update-form-card-expiration-div">
			<label for="s2member-pro-stripe-update-card-expiration" id="s2member-pro-stripe-update-form-card-expiration-label" class="s2member-pro-stripe-form-card-expiration-label s2member-pro-stripe-update-form-card-expiration-label">
				<span><?php echo _x ("Card Expiration Date (mm/yyyy)", "s2member-front", "s2member"); ?> *</span><br />
				<select aria-required="true" autocomplete="off" name="s2member_pro_stripe_update[card_expiration_month]" id="s2member-pro-stripe-update-card-expiration-month" class="s2member-pro-stripe-card-expiration-month s2member-pro-stripe-update-card-expiration-month form-control" tabindex="30">
					%%card_expiration_month_options%%
				</select>
				<select aria-required="true" autocomplete="off" name="s2member_pro_stripe_update[card_expiration_year]" id="s2member-pro-stripe-update-card-expiration-year" class="s2member-pro-stripe-card-expiration-year s2member-pro-stripe-update-card-expiration-year form-control" tabindex="31">
					%%card_expiration_year_options%%
				</select>
			</label>
		</div>
		<div id="s2member-pro-stripe-update-form-card-verification-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-card-verification-div s2member-pro-stripe-update-form-card-verification-div">
			<label for="s2member-pro-stripe-update-card-verification" id="s2member-pro-stripe-update-form-card-verification-label" class="s2member-pro-stripe-form-card-verification-label s2member-pro-stripe-update-form-card-verification-label">
				<span><?php echo _x ("Card Verification Code (3-4 digits)", "s2member-front", "s2member"); ?> * <a href="http://en.wikipedia.org/wiki/Card_security_code" target="_blank" tabindex="-1" rel="external nofollow"><?php echo _x ("need help?", "s2member-front", "s2member"); ?></a></span><br />
				<input type="text" aria-required="true" maxlength="100" autocomplete="off" name="s2member_pro_stripe_update[card_verification]" id="s2member-pro-stripe-update-card-verification" class="s2member-pro-stripe-card-verification s2member-pro-stripe-update-card-verification form-control" value="%%card_verification_value%%" tabindex="40" />
			</label>
		</div>
		<!-- This is displayed only when Maestro/Solo cards are selected as the Payment Method. -->
		<div id="s2member-pro-stripe-update-form-card-start-date-issue-number-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-card-start-date-issue-number-div s2member-pro-stripe-update-form-card-start-date-issue-number-div">
			<label for="s2member-pro-stripe-update-card-start-date-issue-number" id="s2member-pro-stripe-update-form-card-start-date-issue-number-label" class="s2member-pro-stripe-form-card-start-date-issue-number-label s2member-pro-stripe-update-form-card-start-date-issue-number-label">
				<span><?php echo _x ("Card Start Date (mm/yyyy), or Issue Number", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="100" autocomplete="off" name="s2member_pro_stripe_update[card_start_date_issue_number]" id="s2member-pro-stripe-update-card-start-date-issue-number" class="s2member-pro-stripe-card-start-date-issue-number s2member-pro-stripe-update-card-start-date-issue-number form-control" value="%%card_start_date_issue_number_value%%" tabindex="50" />
			</label>
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Billing Address (hidden dynamically when/if no Payment Method is selected yet). -->
	<div id="s2member-pro-stripe-update-form-billing-address-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-update-form-section s2member-pro-stripe-form-billing-address-section s2member-pro-stripe-update-form-billing-address-section">
		<div id="s2member-pro-stripe-update-form-billing-address-section-title" class="s2member-pro-stripe-form-section-title s2member-pro-stripe-update-form-section-title s2member-pro-stripe-form-billing-address-section-title s2member-pro-stripe-update-form-billing-address-section-title">
			<?php echo _x ("Billing Address", "s2member-front", "s2member"); ?>
		</div>
		<div id="s2member-pro-stripe-update-form-street-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-street-div s2member-pro-stripe-update-form-street-div">
			<label for="s2member-pro-stripe-update-street" id="s2member-pro-stripe-update-form-street-label" class="s2member-pro-stripe-form-street-label s2member-pro-stripe-update-form-street-label">
				<span><?php echo _x ("Street Address", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="60" autocomplete="off" name="s2member_pro_stripe_update[street]" id="s2member-pro-stripe-update-street" class="s2member-pro-stripe-street s2member-pro-stripe-update-street form-control" value="%%street_value%%" tabindex="100" />
			</label>
		</div>
		<div id="s2member-pro-stripe-update-form-city-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-city-div s2member-pro-stripe-update-form-city-div">
			<label for="s2member-pro-stripe-update-city" id="s2member-pro-stripe-update-form-city-label" class="s2member-pro-stripe-form-city-label s2member-pro-stripe-update-form-city-label">
				<span><?php echo _x ("City / Town", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="40" autocomplete="off" name="s2member_pro_stripe_update[city]" id="s2member-pro-stripe-update-city" class="s2member-pro-stripe-city s2member-pro-stripe-update-city form-control" value="%%city_value%%" tabindex="110" />
			</label>
		</div>
		<div id="s2member-pro-stripe-update-form-state-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-state-div s2member-pro-stripe-update-form-state-div">
			<label for="s2member-pro-stripe-update-state" id="s2member-pro-stripe-update-form-state-label" class="s2member-pro-stripe-form-state-label s2member-pro-stripe-update-form-state-label">
				<span><?php echo _x ("State / Province", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="2" autocomplete="off" name="s2member_pro_stripe_update[state]" id="s2member-pro-stripe-update-state" class="s2member-pro-stripe-state s2member-pro-stripe-update-state form-control" value="%%state_value%%" tabindex="120" />
			</label>
		</div>
		<div id="s2member-pro-stripe-update-form-country-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-country-div s2member-pro-stripe-update-form-country-div">
			<label for="s2member-pro-stripe-update-country" id="s2member-pro-stripe-update-form-country-label" class="s2member-pro-stripe-form-country-label s2member-pro-stripe-update-form-country-label">
				<span><?php echo _x ("Country", "s2member-front", "s2member"); ?> *</span><br />
				<select aria-required="true" name="s2member_pro_stripe_update[country]" id="s2member-pro-stripe-update-country" class="s2member-pro-stripe-country s2member-pro-stripe-update-country form-control" tabindex="130">
					%%country_options%%
				</select>
			</label>
		</div>
		<div id="s2member-pro-stripe-update-form-zip-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-zip-div s2member-pro-stripe-update-form-zip-div">
			<label for="s2member-pro-stripe-update-zip" id="s2member-pro-stripe-update-form-zip-label" class="s2member-pro-stripe-form-zip-label s2member-pro-stripe-update-form-zip-label">
				<span><?php echo _x ("Postal / Zip Code", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="20" autocomplete="off" name="s2member_pro_stripe_update[zip]" id="s2member-pro-stripe-update-zip" class="s2member-pro-stripe-zip s2member-pro-stripe-update-zip form-control" value="%%zip_value%%" tabindex="140" />
			</label>
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Captcha ( A reCaptcha section, with a required security code will appear here; if captcha="1" ). -->
	%%captcha%%

	<!-- Checkout Now (this holds the submit button, and also some dynamic hidden input variables). -->
	<div id="s2member-pro-stripe-update-form-submission-section" class="s2member-pro-stripe-form-section s2member-pro-stripe-update-form-section s2member-pro-stripe-form-submission-section s2member-pro-stripe-update-form-submission-section">
		<div id="s2member-pro-stripe-update-form-submission-section-title" class="s2member-pro-stripe-form-section-title s2member-pro-stripe-update-form-section-title s2member-pro-stripe-form-submission-section-title s2member-pro-stripe-update-form-submission-section-title">
			<?php echo _x ("Update Billing Information", "s2member-front", "s2member"); ?>
		</div>
		<div id="s2member-pro-stripe-update-form-submit-div" class="s2member-pro-stripe-form-div s2member-pro-stripe-update-form-div s2member-pro-stripe-form-submit-div s2member-pro-stripe-update-form-submit-div">
			%%hidden_inputs%% <!-- Auto-filled by the s2Member software. Do NOT remove this under any circumstance. -->
			<button type="submit" id="s2member-pro-stripe-update-submit" class="s2member-pro-stripe-submit s2member-pro-stripe-update-submit btn btn-primary" tabindex="300"><?php echo esc_html (_x ("Submit Form", "s2member-front", "s2member")); ?></button>
		</div>
		<div style="clear:both;"></div>
	</div>
</form>