<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
?>

<form id="s2member-pro-paypal-registration-form" class="s2member-pro-paypal-form s2member-pro-paypal-registration-form" method="post" action="%%action%%">

	<!-- Response Section (this is auto-filled after form submission). -->
	<div id="s2member-pro-paypal-registration-form-response-section" class="s2member-pro-paypal-form-section s2member-pro-paypal-registration-form-section s2member-pro-paypal-form-response-section s2member-pro-paypal-registration-form-response-section">
		<div id="s2member-pro-paypal-registration-form-response-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-response-div s2member-pro-paypal-registration-form-response-div">
			%%response%%
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Registration Description (this is the desc="" attribute from your Shortcode). -->
	<div id="s2member-pro-paypal-registration-form-description-section" class="s2member-pro-paypal-form-section s2member-pro-paypal-registration-form-section s2member-pro-paypal-form-description-section s2member-pro-paypal-registration-form-description-section">
		<div id="s2member-pro-paypal-registration-form-description-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-description-div s2member-pro-paypal-registration-form-description-div">
			%%description%%
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Registration Details (Name, Email, Username, Password). -->
	<!-- Name fields will be hidden automatically when/if your Registration/Profile Field options dictate this behavior. -->
	<div id="s2member-pro-paypal-registration-form-registration-section" class="s2member-pro-paypal-form-section s2member-pro-paypal-registration-form-section s2member-pro-paypal-form-registration-section s2member-pro-paypal-registration-form-registration-section">
		<div id="s2member-pro-paypal-registration-form-registration-section-title" class="s2member-pro-paypal-form-section-title s2member-pro-paypal-registration-form-section-title s2member-pro-paypal-form-registration-section-title s2member-pro-paypal-registration-form-registration-section-title">
			<?php echo _x ("Create Profile", "s2member-front", "s2member"); ?>
		</div>
		<div id="s2member-pro-paypal-registration-form-first-name-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-first-name-div s2member-pro-paypal-registration-form-first-name-div">
			<label for="s2member-pro-paypal-registration-first-name" id="s2member-pro-paypal-registration-form-first-name-label" class="s2member-pro-paypal-form-first-name-label s2member-pro-paypal-registration-form-first-name-label">
				<span><?php echo _x ("First Name", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="100" autocomplete="off" name="s2member_pro_paypal_registration[first_name]" id="s2member-pro-paypal-registration-first-name" class="s2member-pro-paypal-first-name s2member-pro-paypal-registration-first-name" value="%%first_name_value%%" tabindex="10" />
			</label>
		</div>
		<div id="s2member-pro-paypal-registration-form-last-name-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-last-name-div s2member-pro-paypal-registration-form-last-name-div">
			<label for="s2member-pro-paypal-registration-last-name" id="s2member-pro-paypal-registration-form-last-name-label" class="s2member-pro-paypal-form-last-name-label s2member-pro-paypal-registration-form-last-name-label">
				<span><?php echo _x ("Last Name", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="100" autocomplete="off" name="s2member_pro_paypal_registration[last_name]" id="s2member-pro-paypal-registration-last-name" class="s2member-pro-paypal-last-name s2member-pro-paypal-registration-last-name" value="%%last_name_value%%" tabindex="20" />
			</label>
		</div>
		<div id="s2member-pro-paypal-registration-form-email-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-email-div s2member-pro-paypal-registration-form-email-div">
			<label for="s2member-pro-paypal-registration-email" id="s2member-pro-paypal-registration-form-email-label" class="s2member-pro-paypal-form-email-label s2member-pro-paypal-registration-form-email-label">
				<span><?php echo _x ("Email Address", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" data-expected="email" maxlength="100" autocomplete="off" name="s2member_pro_paypal_registration[email]" id="s2member-pro-paypal-registration-email" class="s2member-pro-paypal-email s2member-pro-paypal-registration-email" value="%%email_value%%" tabindex="30" />
			</label>
		</div>
		<div id="s2member-pro-paypal-registration-form-username-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-username-div s2member-pro-paypal-registration-form-username-div">
			<label for="s2member-pro-paypal-registration-username" id="s2member-pro-paypal-registration-form-username-label" class="s2member-pro-paypal-form-username-label s2member-pro-paypal-registration-form-username-label">
				<span><?php echo _x ("Username (lowercase letters and/or numbers)", "s2member-front", "s2member"); ?> *</span><br />
				<input type="text" aria-required="true" maxlength="60" autocomplete="off" name="s2member_pro_paypal_registration[username]" id="s2member-pro-paypal-registration-username" class="s2member-pro-paypal-username s2member-pro-paypal-registration-username" value="%%username_value%%" tabindex="40" />
			</label>
		</div>
		<div id="s2member-pro-paypal-registration-form-password-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-password-div s2member-pro-paypal-registration-form-password-div">
			<label for="s2member-pro-paypal-registration-password1" id="s2member-pro-paypal-registration-form-password-label" class="s2member-pro-paypal-form-password-label s2member-pro-paypal-registration-form-password-label">
				<span><?php echo _x ("Password (type this twice please)", "s2member-front", "s2member"); ?> *</span><br />
				<input type="password" aria-required="true" maxlength="100" autocomplete="off" name="s2member_pro_paypal_registration[password1]" id="s2member-pro-paypal-registration-password1" class="s2member-pro-paypal-password1 s2member-pro-paypal-registration-password1" value="%%password1_value%%" tabindex="50" />
			</label>
			<input type="password" maxlength="100" autocomplete="off" name="s2member_pro_paypal_registration[password2]" id="s2member-pro-paypal-registration-password2" class="s2member-pro-paypal-password2 s2member-pro-paypal-registration-password2" value="%%password2_value%%" tabindex="60" />
			<div id="s2member-pro-paypal-registration-form-password-strength" class="ws-plugin--s2member-password-strength s2member-pro-paypal-form-password-strength s2member-pro-paypal-registration-form-password-strength"><em><?php echo _x ("password strength indicator", "s2member-front", "s2member"); ?></em></div>
		</div>
		<div style="clear:both;"></div>
	</div>

	<!-- Custom Fields (Custom Registration/Profile Fields will appear here, when/if they've been configured). -->
	%%custom_fields%%

	<!-- Captcha ( A reCaptcha section, with a required security code will appear here; if captcha="1" ). -->
	%%captcha%%

	<!-- Complete Registration (this holds the submit button, and also some dynamic hidden input variables). -->
	<div id="s2member-pro-paypal-registration-form-submission-section" class="s2member-pro-paypal-form-section s2member-pro-paypal-registration-form-section s2member-pro-paypal-form-submission-section s2member-pro-paypal-registration-form-submission-section">
		<div id="s2member-pro-paypal-registration-form-submission-section-title" class="s2member-pro-paypal-form-section-title s2member-pro-paypal-registration-form-section-title s2member-pro-paypal-form-submission-section-title s2member-pro-paypal-registration-form-submission-section-title">
			<?php echo _x ("Complete Registration", "s2member-front", "s2member"); ?>
		</div>
		%%opt_in%% <!-- s2Member will fill this when/if there are list servers integrated, and the Opt-In Box is turned on. -->
		<div id="s2member-pro-paypal-registration-form-submit-div" class="s2member-pro-paypal-form-div s2member-pro-paypal-registration-form-div s2member-pro-paypal-form-submit-div s2member-pro-paypal-registration-form-submit-div">
			%%hidden_inputs%% <!-- Auto-filled by the s2Member software. Do NOT remove this under any circumstance. -->
			<input type="submit" id="s2member-pro-paypal-registration-submit" class="s2member-pro-paypal-submit s2member-pro-paypal-registration-submit" value="<?php echo esc_attr (_x ("Submit Form", "s2member-front", "s2member")); ?>" tabindex="400" />
		</div>
		<div style="clear:both;"></div>
	</div>
</form>