=== s2Member® Pro ===

Version: 250419
Stable tag: 250419
Tested up to: 6.9-alpha-60174
Requires at least: 4.2
Requires PHP: 5.6.2
Tested up to PHP: 8.3
License: GNU General Public License v2 or later.
Contributors: WebSharks, JasWSInc, raamdev, clavaque, eduan
Author: WP Sharks
Author URI: http://s2member.com/
Donate link: http://s2member.com/donate/
Beta link: http://s2member.com/beta-testers/
Text Domain: s2member
Domain Path: ../s2member/src/includes/translations
Plugin Name: s2Member® Pro Add-on
Plugin URI: http://s2member.com/
Tags: membership, subscribers, subscriber, members only, roles, capabilities, capability, register, signup, paypal, ecommerce, restriction

s2Member® Pro adds Stripe™, PayPal® Payments Pro and Authorize.Net integrations, advanced import/export tools, and many other enhancements.

== Description ==

You can learn more about s2Member® Pro at [s2Member.com](http://s2member.com/).

== Installation ==

= s2Member® Pro is Very Easy to Install =

1. First, you need to have the latest version of the [s2Member® Framework](http://s2member.com/release-archive/) already installed.
2. Then, upload the `/s2member-pro` folder to your `/wp-content/plugins/` directory.
3. That's it! s2Member® Pro will be loaded into the free version of s2Member automatically.

= See Also (s2Member.com) =

[Detailed installation/upgrade instructions](http://s2member.com/installation/).

= Is s2Member compatible with Multisite Networking? =

Yes. s2Member and s2Member Pro, are also both compatible with Multisite Networking. After you enable Multisite Networking, install the s2Member plugin. Then navigate to `s2Member → Multisite (Config)` in the Dashboard on your Main Site.

== Frequently Asked Questions ==

= Please Check the Following s2Member® Resources =

* s2Member® FAQs: <http://s2member.com/faqs/>
* Knowledge Base: <http://s2member.com/kb/>
* Video Tutorials: <http://s2member.com/videos/>
* Community: <http://s2member.com/r/forum/>
* Codex: <http://s2member.com/codex/>

= Translating s2Member® =

Please see: <http://s2member.com/r/translations/>

== License ==

Copyright: © 2013 [WP Sharks](http://wpsharks.com) (coded in the USA)

Released under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html).

= Credits / Additional Acknowledgments =

* Software designed for WordPress®.
	- GPL License <http://codex.wordpress.org/GPL>
	- WordPress® <http://wordpress.org>
* JavaScript extensions require jQuery.
	- GPL License <http://jquery.org/license>
	- jQuery <http://jquery.com/>
* Readme parsing routines, powered (in part) by PHP Markdown.
	- BSD / GPL Compatible License <http://michelf.com/projects/php-markdown/license/>
	- PHP Markdown <http://michelf.com/projects/php-markdown/>
* Administration panel (tools icon) was provided by Everaldo.com.
	- LGPL License <http://www.everaldo.com/crystal/?action=license>
	- Everaldo <http://www.everaldo.com/crystal/?action=downloads>
* Administration panel (videos icon) was provided by David Vignoni.
	- LGPL License <http://www.iconfinder.com/search/?q=iconset%3Anuvola2>
	- David Vignoni <http://www.icon-king.com/>
* PayPal® and its associated API, buttons & services have been integrated into this software via external hyperlinks.
  The files/services provided by PayPal® are not distributed with this software. They have their own terms & conditions.
	- PayPal®, a 3rd party service, is powered by eBay, Inc. <http://www.paypal.com/>
	- PayPal® is a trademark of eBay, Inc. <http://www.ebay.com/>
* The W3C® and its associated validator & services have been integrated into this software via external hyperlinks.
  The files/services provided by the W3C® are not distributed with this software. They have their own terms & conditions.
	- The W3C®, a 3rd party service, is powered by the World Wide Web Consortium <http://validator.w3.org/>
	- W3C® is a trademark of the World Wide Web Consortium. <http://www.w3.org/>
* The MailChimp® services have been integrated into this software through a GPL compatible API & hyperlinks.
  The services provided by MailChimp® are not distributed with this software. They have their own terms & conditions.
	- MailChimp®, a 3rd party service, is powered by The Rocket Science Group, LLC <http://www.mailchimp.com/>
	- MailChimp® is a trademark of The Rocket Science Group, LLC. <http://www.mailchimp.com/terms-policies/terms-of-use/>
* The AWeber® services have been integrated into this software through hyperlinks & email commands.
  The services provided by AWeber® are not distributed with this software. They have their own terms & conditions.
	- AWeber®, a 3rd party service, is powered by AWeber Communications <http://www.aweber.com/about.htm>
	- AWeber® is a trademark of AWeber Communications. <http://www.aweber.com/service-agreement.htm>

== Upgrade Notice ==

= v250419 =

(Maintenance Release) Upgrade immediately.

== Changelog ==

= v250419 =

- (Pro) **Enhancement**: Improved the new coupon code limit per user which prevents a user from applying a coupon code unlimited times, Instead of single use, it can now be limited to more uses, e.g. 3. It's been renamed from "User Once" to "User Max", max number of times a user can use that coupon. This is optional and leaving it blank will give the default "no limit".
- 
- (Pro) **Enhancement**: Improved validation of the template attribute in the s2Member-List-Search-Box shortcode.

- (Framework) **UI**: Temporary admin notice about Easter promo for Pro add-on at 20% off.

= v250214 =

- (Pro) **Enhancement**: Improved coupon usage logging for better tracking.

- (Pro) **Enhancement**: Added a new single-use per user option for coupons. Thanks to Carl Borsani for sponsoring this.

- (Pro) **Enhancement**: Coupons can now be limited to specific pro-forms. Thanks to Carl Borsani for sponsoring this.

- (Framework) **Fix**: s2Get can now handle s2Member’s custom profile fields. Thanks to Gerard Earley for reporting this.

- (Framework) **Fix**: Updated the admin notice about the PayPal button encryption setting. 

- (Pro) **Enhancement**: Improved data handling in the Remote Operations API. Props to István.

- (Pro) **Enhancement**: Improved validation of the template attribute in pro-forms and s2Member-List shortcodes. Props to István.

= v241216 =

- (Framework) **Enhancement**: Added extra attribute validation to the s2Get shortcode. Props to wcraft.

- (Framework) **Enhancement**: Improved New User email preparation before send. Props to Hakiduck.

= v241114 =

- (Framework & Pro) **Fix**: An error could happen on PHP8 during Pro activation. Fixed in this release.

- (Framework) **Enhancement**: s2Get shortcode can now be used to show s2's current user constants. E.g. `[s2Get constant="S2MEMBER_CURRENT_USER_DISPLAY_NAME" /]` _WP Admin > s2Member > API / Scripting > s2Member PHP/API Constants_

= v240325 =

- (Framework) **Fix**: Some sites were getting a warning from v240315's restriction improvement when the WP REST request doesn't include a type or ID. Fixed in this release.See [thread 11347](https://f.wpsharks.com/t/11347)

- (Pro) **Enhancement**: Checkout success redirection URLs are now validated as safe with WordPress' _wp_validate_redirect_. To use a domain different than the site's, it can be allowed with wp's filter [allowed_redirect_hosts](https://developer.wordpress.org/reference/hooks/allowed_redirect_hosts/).

- (Framework) **Enhancement**: Additional validation to prevent an invalid s2Member Level role during registration. 

= v240315 =

- (Framework) **Enhancement**: Improved access restrictions applied to WP REST requests.

= v240218 =

- (Framework) **Fix**: PayPal button encryption default changed to "disabled".

- (Framework) **Fix**: Mailchimp interest groups integration wasn't working correctly all the time. Fixed in this release.

= v230815 =

- (Framework) **Fix**: Added some missing functions to the list of conditionals allowed by default for s2If (e.g. `current_user_days_to_eot_less_than`, `current_user_gateway_is`). See also: https://s2member.com/kb-article/s2if-simple-shortcode-conditionals/#toc-5bb69568

- (Pro) **Enhancement**: New s2If whitelist option for custom conditional functions to be allowed. _s2Member Pro > Restriction Options > Simple Shortcode Conditionals > Whitelist_

- (Framework) **Enhancement**: Handle s2If conditional problems more gracefully. Instead of giving an error that prevents loading the rest of the page, it now just doesn't display that s2If's block, and enters a message in the error log (e.g. `/wp-content/debug.log`).

- (Framework & Pro) **Enhancement**: Prevent output from s2If conditions, only _true_ or _false_.

- (Framework) **UI**: Update the Mailchimp example from `Group Title` to `Group Category`, to match Mailchimp's current name in their settings. _s2Member > API / List Servers > Mailchimp_

= v230808 =

- (Framework) **Fix**: Potential security issue under rare circumstances. Fixed in this release.

- (Framework) **Fix**: Mailchimp's groups/interests were not transitioning correctly with the updated integration. Fixed in this release.

- (Framework) **UI**: Added a notice about PayPal giving trouble with encrypted buttons recently, recommending to not encrypt them for now. You may need to disable button encryption, and allow non-encrypted payments. _s2Member > PayPal Options > Account Details > Button Encryption_ 
 
- (Framework) **UI**: Brought back the "Expand All" and "Collapse All" buttons for the admin panels. See [thread 10796](https://f.wpsharks.com/t/10796)

- (Framework) **UI**: Updated the link to the PayPal IPN configuration.

- (Framework) **UI**: Added link to PayPal's IPN History page. _s2Member > PayPal Options > PayPal IPN > More Information_

- (Pro) **Fix**: Stripe's billing update pro-form gave an error sometimes. Fixed in this release.  See [thread 10752](https://f.wpsharks.com/t/10752)

= v230530 =

- (Framework) **Enhancement**: Updated the Mailchimp integration to v3 of their API.  I made it so you shouldn't need to change anything, it should work with your existing configuration. Still worth doing a test or checking that things are normal after the update, and report any issues you notice. See: [thread 10666](https://f.wpsharks.com/t/10666)

= v230504 =

- (Pro) **Fix**: Stripe subscriptions weren't using customer cards updated with the Billing Update pro-form. The subscription saved the first card, instead of defaulting to the card in the customer's profile. This release fixes that. The card is not added to a new subscription anymore, only to the customer's profile, and updating his profile's card with the Billing Update pro-form, will also update the subscription so it uses it. Thanks to Jim Antonucci for his help with this.

- (Pro) **Enhancement**: The Stripe Billing Update pro-form now includes a field for the cardholder's name (i.e. Name On Card). Adding the name to the card will improve successful subscription charges. Thanks to Andy Johnsen for the idea.

= v230425 =

- (Framework) **Fix**: Fixed domain name format validation for custom profile fields.

- (Framework) **Fix**: Fixes to markdown parser for PHP8 compatibility.

- (Framework) **Fix**: Fixed HTML near AWeber's API key field.

= v230413 =

- (Pro) **Bug Fix**: An error could happen on PHP8 during Pro installation in a multisite network. Fixed in this release.

- (Framework) **Bug Fix**: An error could happen on PHP8 when saving an edited user profile. Fixed in this release.

- (Framework) **UI Enhancement**: In the List Servers admin page, removed mentions of the AWeber email parser, which isn't available any more. 

= v221103 =

- (Framework) **Bug Fix**: Removed latest changes to gateway notification and return handlers, that were causing difficulties with member access in some scenarios. 

= v221031 =

- (Framework) **Bug Fix**: Fix PayPal IPNs being ignored because a bug in the last release. After updating to this release, you may want to [review your latest IPNs](https://www.paypal.com/merchantnotification/ipn/history) since updating to v221028, and re-send them from PayPal. See [thread 10208](https://f.wpsharks.com/t/10208)

= v221028 =

- (Framework) **Fix**: Initialized some array keys to prevent PHP warnings in PayPal notify and return files. Thanks Greg M. for your help.

- (Framework) **UI**: Widened the Logs viewer. Thanks Sim. See [thread 10064](https://f.wpsharks.com/t/10064)

- (Framework) **UI**: Framework auto-update is now allowed when Pro add-on installed.

- (Pro) **UI**: The Pro updater now shows when a newer version available, not just when required.

= v220925 =

- (Pro) **UI Enhancement**: In ClickBank Options admin page, added note about keeping IPN encryption disabled.

- (Pro) **Enhancement**: Removed ClickBank's name from the notify, return, and success URLs, replaced with just `cb`. Kudos to Eduardo for telling me about this. See [thread 9910](https://f.wpsharks.com/t/9910)

- (Pro) **Enhancement**: Added a PayPal payment request ID to help prevent random/rare PayPal duplicate charges. Kudos to Nathan for his help. See [thread 7999](https://f.wpsharks.com/t/7999/27)

- (Framework) **UI Enhancement**: Admin page panels widened for larger displays.

- (Framework) **UI Enhancement**: Simplified Getting Started and Getting Help admin pages.

- (Framework) **UI Enhancement**: In PayPal Options admin page, updated paths and links to PayPal settings.

- (Framework) **Bug Fix**: Removed the Security Badge's link to the old Flash powered page on s2Member's site.

- (Pro) **UI Enhancement**: Small improvements to the Pro upgrader.

= v220809 =

- (Framework) **Enhancement**: New `current_user_days_to_eot_less_than` function for conditionals. Useful when you want to show a message to a user on his last days of access before the EOT time in his profile. E.g. `[s2If current_user_days_to_eot_less_than(31)]Please renew your membership[/s2If]`. Kudos to Felix for his help, see [post 6783](https://f.wpsharks.com/t/6783).

= v220421 =

- (Framework & Pro) **Enhancement**: Improved PHP compatibility to 8.1.

- (Framework) **UI Fix**: `More Updates` link fixed.

= v220318 =

- (Framework) **Enhancement**: New `current_user_gateway_is` function for conditionals. Useful for sites using more than one gateway. E.g. `[s2If current_user_gateway_is(stripe)] ...`

- (Pro) **UI Fix**: Removed "Image Branding" setting from s2's Stripe options, not used in current integration.

= v210526 =

- (s2Member Framework & Pro) **UI Enhancement**: Started improving the admin interface. Lightened up the colors, and changed the layout a little bit. 

- (s2Member Framework) **UI Enhancement**: Added title tag to buttons to manage custom profile fields in admin, to improve use with screen-reader. [Thread 8836](https://f.wpsharks.com/t/8836/12)

- (s2Member Pro) **UI Fix**: Fixed typo in pro-form `rrt` attribute description. [Issue 1204](https://github.com/wpsharks/s2member/issues/1204)

- (s2Member Framework) **Bug Fix**: Registration Date sometimes wasn't formatted correctly with the s2Get shortcode. [Thread 8730](https://f.wpsharks.com/t/8730)

= v210208 =

- (s2Member Pro) **Enhancement**: In the Stripe integration, cancelling a subscription in the last minutes of a period, may cause the invoice for the new period to remain there and still be charged later. Now s2Member Pro attempts to find a draft or open invoice for the subscription being cancelled, and void it. Thanks Alan for reporting it. See [post 8386](https://f.wpsharks.com/t/8098).

- (s2Member Pro) **UI Enhancement**: Improved Stripe pro-form error message when trying to create a subscription with a bad card. Thanks everyone that reported it. See [issue #1184](https://github.com/wpsharks/s2member/issues/1184), [post 6043](https://f.wpsharks.com/t/6043), and [post 8386](https://f.wpsharks.com/t/8386).

- (s2Member Pro) **Enhancement**: Added the new action hooks `ws_plugin__s2member_pro_before_stripe_notify_event_switch` and `ws_plugin__s2member_pro_after_stripe_notify_event_switch` in the Stripe endpoint to allow customizations, e.g. new event handlers.

- (s2Member Pro) **UI Fix**: Removed some leftover mentions of Bitcoin support in Stripe's options.

- (s2Member Pro) **UI Fix**: Removed a couple of deprecated shortcode attributes from the documentation for Stripe's pro-form, leftovers from the old integration. Kudos to Debbie for bringing my attention to them. See [post 8053](https://f.wpsharks.com/t/8053).

- (s2Member Framework) **UI Fix**: Fixed some broken links and video players in the admin pages.

- (s2Member Framework) **Bug Fix**: Resolved a warning given when changing users role in bulk from the WP Admin > Users page.

- (s2Member Server Scanner) **Bug Fix**: Updated the [Server Scanner](https://s2member.com/kb-article/server-scanner/) to remove some outdated warnings.

= v201225 =

- (s2Member Framework) **Bug Fix**: View Password icon WP's login page was not displaying correctly. Kudos to Beee4life for reporting it. See [issue #1187](https://github.com/wpsharks/s2member/issues/1187)

- (s2Member Framework and Pro) **Enhancement**: Refactored PHP's deprecated _create_function_ with anonymous functions. Kudos to Berry for reporting it, see [post 6069](https://f.wpsharks.com/t/6069) 

- (s2Member Framework) **Bug Fix**: Added a check for empty return variable before trying to use it in paypal-utilities.inc.php.

- (s2Member Framework) **Bug Fix**: Added checks for undefined indexes before trying to use them in paypal-return-in-subscr-or-wa-w-level.inc.php.

- (s2Member Framework) **Bug Fix:** Added a check for undefined index before using it to define a couple of s2 constants. Kudos to Berry for reporting it, see [post 8181](https://f.wpsharks.com/t/8181/) 

- (s2Member Pro) **Bug Fix**: s2's payment notification when creating a Stripe subscription, was being sent twice. Added a check to ignore the webhook for the subscription's on-session first payment; s2's webhook endpoint is for off-session events. 

- (s2Member Framework) **Enhancement**: Added a new hook for the payment notification on subscription creation or buy now payments.

- (s2Member Pro) **Bug Fix**: Stripe paid trials were accumulating on failed payment attempts, causing a larger charge when it finally succeeded. Kudos to Alan for his help through the many attempts to fix this one, see [post 7002](https://f.wpsharks.com/t/7002).

- (s2Member Pro) **Enhancement**: Stripe duplicate payments were happening randomly to a few site owners, apparently from bad communication between their server and Stripe's. Added idempotency to prevent duplicates. Kudos to Alan and everyone in the forum that reported and gave details on this behavior, see [post 7002](https://f.wpsharks.com/t/7002)

= v200301 =

- (s2Member Pro) **Enhancement:** Added "Powered by Stripe" to Stripe pro-form's payment card field. Kudos to Josh, see [post 6716](https://f.wpsharks.com/t/6716).

- (s2Member Pro) **Bug Fix:** Stripe subscription cancellations were not happening when they should. This release updates the API integration for it and fixes that behavior. Kudos to Matt for reporting it, see [post 6909](https://f.wpsharks.com/t/6909).

- (s2Member Pro) **Bug Fix:** Updating the card with Stripe's pro-form sometimes gave an incorrect "missing billing method" error. Kudos to Corey, see [post 7058](https://f.wpsharks.com/t/7058).

- (s2Member Pro) **Small fix:** Removed Bitcoin mention next to Stripe in Gateways list. Missed it in [v191022](https://s2member.com/s2member-v191022-now-available/).

= v200221 =

- (s2Member Pro) **Bug Fix:** In some rare cases, another plugin loaded Stripe's class before s2Member, so when s2 tried loading it there'd be an error. This release fixes the check for the class before trying to load it. See [issue #1170](https://github.com/wpsharks/s2member/issues/1170)

  **Note:** s2Member won't have control over what version of the Stripe SDK was loaded by the other plugin. You'll need to get that other plugin to have an up-to-date version. If you don't have another plugin loading Stripe, this is not relevant to you.

- (s2Member Pro) **Bug Fix:** When using a 100% off coupon, requiring no payment, the Stripe pro-form was still loading the card field and requiring it, preventing the free signup. That's fixed in this release. See [issue #1171](https://github.com/wpsharks/s2member/issues/1171)

- (s2Member Pro) **Bug Fix:** The Stripe pro-form, when given an invalid card, didn't give a clear error message for it, and instead just "invalid parameter". Now it shows the correct card error, making it possible for the customer to try a different card to complete the payment.

- (s2Member Pro) **Feature Update:** The Indian Rupee was added to the list of currency symbols.

- (s2Member Pro) **Feature Enhancement:** The s2Member Pro add-on, not being a regular plugin was not uploadable via the WP plugin manager. This made it necessary to FTP, which is complicated for some site owners. In this release I made it possible for the plugin manager to upload or remove the Pro add-on.

  **Note:** It still is not a regular plugin. The activation link or status in the plugins manager is irrelevant, but I couldn't find how to remove it. s2Member Pro activates automatically when its version matches the Framework's, and it'll be mentioned next to the Framework's version in the plugins manager.

= v191022 =

- (s2Member Pro) **Feature Enhancement:** The Stripe pro-forms can now handle 3D Secure 2 for [Strong Customer Authentication](https://stripe.com/guides/strong-customer-authentication), as required by the new European regulation that came into effect recently. Props to those in the beta testing group, especially Brice and Felix. See [thread 5585](https://f.wpsharks.com/t/5585/).

- (s2Member Pro) **Feature Enhancement:** The Stripe pro-form now has the card field inline, instead of opening a modal to enter it. Before it required clicking the link to open the modal, enter the card details, submit that, and then submit the pro-form. Now you enter the card details as part of the pro-form. See [issue #588](https://github.com/wpsharks/s2member/issues/588).

- (s2Member Pro) **Stripe Integration Updates:** Upgraded the Stripe PHP SDK from v1.18 to v7.4.0, and the API from 2015-07-13 to 2019-10-08. Upgraded the integration from the Charges API to the latest Payment Intents API. Upgraded the card input from the old Stripe Checkout modal, to the new Stripe.js and Elements. 

- (s2Member Pro) **Optimization:** Stripe's JavaScript now only gets included if the page has a Stripe pro-form.

- (s2Member Pro) **Removed Stripe Bitcoin**: Stripe [dropped Bitcoin](https://stripe.com/blog/ending-bitcoin-support) last year, it's not available anymore. This update removes the Bitcoin options and mentions from the s2 admin pages.

- (s2Member Pro) **Bug Fix:** Subscriptions without at trial were showing a "trialing" status in Stripe for the first period. This behavior has now been solved. It will only say trialing when you set a trial period (free or paid) in your Stripe pro-form shortcode. See [issue #1052](https://github.com/wpsharks/s2member/issues/1052).

- (s2Member Pro) **Bug Fix:** The Stripe pro-form installments via the `rrt` shortcode attribute were charging an extra payment before ending the subscription. There was an error in the time calculation for this. This is solved in this release. Props to Brice. See [thread 5817](https://f.wpsharks.com/t/5817/).

- (s2Member Pro) **Bug Fix:** Some payments through the Stripe pro-form were creating a new Stripe customer when the user was already a customer. The Stripe customer ID was not being saved correctly in the user's profile. This is solved in this release. Props to demeritcowboy for reporting it.

= v190822 =

- (s2Member) **PayPal Integration Update:** PayPal deprecated the subscription modification button. Using the old possible values for this, now gives an error on PayPal's site. This button has been removed from the PayPal Standard integration in s2Member. Props to Tim for reporting it, see [forum thread 5861](https://f.wpsharks.com/t/5861), and [issue #1157](https://github.com/wpsharks/s2member/issues/1157).

- (s2Member) **Bug Fix:** PayPal would sometimes return the customer without the Custom Value expected by s2Member, incorrectly triggering an error. A small delay has now been added when needed to wait for PayPal to provide the missing value, so that the customer is met with the correct success message on return. Props to Josh Hartman for his help. See [forum thread 5250](https://f.wpsharks.com/t/5250).

- (s2Member) **Bug Fix:** Google's URL shortening service has been [discontinued](https://developers.googleblog.com/2018/03/transitioning-google-url-shortener.html). The s2Member integration with it was removed in this release. Props to Felix Hartmann for reporting it.

- (s2Member) **Feature Enhancement:** The popular URL shortening services have been abused in spam emails, and this can cause your site's emails with shortened signup URLs to end up in the spam folder. It's now possible to disable URL shortening when trying to avoid this problem. Props to Felix Hartmann for suggesting it. See [forum thread 5697](https://f.wpsharks.com/t/5697).

- (s2Member Pro) **New Feature:** It is now possible to use a custom URL shortener other than the defaults in the s2Member Framework. This is particularly useful to use [YOURLS](http://yourls.org/) for your links, making them unique to your site, looking more professional and avoiding the spam filters issue mentioned above. For more info see this [forum post](https://f.wpsharks.com/t/5697/19).

= v190617 =

- (s2Member Pro) **Authorize.Net Hash Upgrade:** Authorize.Net [announced](https://support.authorize.net/s/article/MD5-Hash-End-of-Life-Signature-Key-Replacement) the end-of-life for their MD5 Hash in favor of their new SHA512 Signature Key. Support for this has been added to s2Member Pro. The MD5 Hash is not provided by Authorize.Net any more, so the field for it in s2Member has been disabled. Props @krumch for his work. For further details see [forum thread 5514](https://f.wpsharks.com/t/5514).

  **Note:** For those that already used the MD5 Hash in their configuration, it is kept there and will keep working while Authorize.Net accepts it, which will not be much longer. It's important to update your integration with the new Signature Key. Once you have your Signature Key in the s2Member configuration, it will be favored over the old MD5 Hash._

- (s2Member Pro) **Bug Fix:** The multisite patch for `wp-admin/user_new.php` wasn't finding the code to replace because of changes in the latest releases of WordPress. It has now been updated, as well as the instructions in the Dashboard for those that prefer to apply it manually. Props @crazycoolcam for reporting it. For further details see [Issue #1132](https://github.com/wpsharks/s2member/issues/1132).

  **Note:** If you already had patched this file in the past, it's recommended that you remove the previous patch restoring it to the original file, and let s2Member Pro patch it again now, otherwise you risk getting it patched over the previous one and ending up with errors. After the new patch, please review that file to verify that it's correct._

- (s2Member Pro) **Bug Fix:** The search results for `s2Member-List` were not being ordered as specified in the `orderby` attribute when this was a field from the `usermeta` table in the database, e.g. `first_name`, `last_name`. This is now fixed and working correctly. Props to @stevenwolock for reporting it. For further details see [Issue #1103](https://github.com/wpsharks/s2member/issues/1103).

- (s2Member) **WP 5.2 Compat. Enhancement:** s2Member has been tested with WP up to 5.2.2-alpha. With `WP_DEBUG` enabled, only one "notice" was found. In `wp-login.php` it said 'login_headertitle is deprecated since version 5.2.0! Use login_headertext instead.' This release now uses `login_headertext` and doesn't get that notice anymore. Props Azunga for reporting it. See [forum thread 5962](https://f.wpsharks.com/t/5962).

= v170722 =

- (s2Member/s2Member Pro) **PayPal IPN Compatibility:** This release includes an updated PayPal IPN handler that is capable of reading number-suffixed IPN variables that are now being sent by PayPal's IPN system in some cases, for some customers. We strongly encourage all site owners to upgrade to this release as soon as possible, particularly if you're using PayPal to process transactions. Props @openmtbmap and @patdumond for reporting. See: [Issue #1112](https://github.com/websharks/s2member/issues/1112)

= v170524 =

- (s2Member/s2Member Pro) **PHP v7 Compat. Enhancements**: This release adds an integration with the [Defuse encryption library](https://github.com/defuse/php-encryption) for PHP, making it possible for s2Member to move away from the `mcrypt_*()` family of functions in versions of PHP >= 7.0.4, where the mcrypt library has been deprecated — `mcrypt_*()` will eventually be removed entirely.

  Starting with this release of s2Member, if you're running s2Member on PHP v7.0.4+, the Defuse library will be used automatically instead of mcrypt. See [Issue #1079](https://github.com/websharks/s2member/pull/1079).

  **Note:** Backward compatibility with mcrypt functions will remain for now, especially for the decryption of any data that was previously encrypted using RIJNDAEL-256; i.e., data encrypted by a previous release of the s2Member software. s2Member is capable of automatically determining the algorithm originally used to encrypt, which allows it to decrypt data using Defuse, else RIJNDAEL-256, else XOR as a last-ditch fallback.

  **API Functions:** `s2member_encrypt()` & `s2member_decrypt()`. These two API Functions provided by s2Member are impacted by this change. Starting with this release, if you're running s2Member on PHP v7.0.4+, the Defuse library is used automatically instead of the older mcrypt extension. Not to worry though; the `s2member_decrypt()` function is still capable of decrypting data encrypted by previous versions of the s2Member software.

- (s2Member/s2Member Pro) **UI Fix:** All menu page notices should be given the `notice` class and the additional `notice-[type]` class instead of the older generic `updated` and `error` classes. Fixed in this release. Related to [Issue #1034](https://github.com/websharks/s2member/issues/1034)

- (s2Member/s2Member Pro) **UI Fix:** Plugins displaying Dashboard-wide notices using the older `updated` and `error` classes should be handled better to avoid displaying them below the s2Member header (on s2Member menu pages) and with non-default WordPress styles. See: [Issue #1034](https://github.com/websharks/s2member/issues/1034)

- (s2Member/s2Member Pro) **UI Fix:** Improving color highlighting in input fields following a media library insertion; e.g., when adding a custom logo to the login/registration page.

- (s2Member Pro) **Bug Fix:** Merchants using PayPal Pro (Payflow Edition) to charge a fixed non-recurring fee following an initial 100% free trial period, were seeing their member accounts EOTd after the trial ended, instead of the EOT Time being set to the end of the fixed term period. Props @patdumond, James Hall, and many others for reporting this in the forums and at GitHub. See [Issue #1077](https://github.com/websharks/s2member/issues/1077).

- (s2Member Pro) **Bug Fix:** Updating PHP syntax in Simple Export tool, for compatibility w/ modern versions of PHP. Props @patdumond for reporting and helping us locate the underlying cause of this problem. See [Issue #1055](https://github.com/websharks/s2member/issues/1055).

- (s2Member Pro) **Stripe Bug Fix:** This releases corrects a seemingly rare conflict between s2Member and Stripe on certain mobile devices and in certain scenarios. In a case we examined, there was a problematic CSS `z-index` setting in the s2Member source code that was, at times, causing problems in the stacking order, which resulted in a user's inability to enter details into the Stripe popup form. In this release, s2Member's customization of the `z-index` stacking order has been removed entirely, as it is no longer necessary in the latest revision of the Stripe popup, which already handles `z-index` adequately. Props @jaspuduf for reporting and for helping us diagnose the problem. See [Issue #1057](https://github.com/websharks/s2member/issues/1057).

- (s2Member/s2Member Pro) **Security Enhancement:** This release removes the `%%user_pass%%` Replacement Code from the API Registration Notification email that is sent to a site owner; i.e., when/if it is configured by a site owner. Props @patdumond see [Issue #954](https://github.com/websharks/s2member/issues/954). This Replacement Code was removed as a security precaution.

- (s2Member/s2Member Pro) **Bug Fix:** Resolving internal warning: 'PHP Warning: Parameter 2 to c_ws_plugin__s2member_querys::_query_level_access_coms() expected to be a reference, value given'. This was resolved by removing the strict 'by reference' requirement from the list of parameters requested by s2Member.

- (s2Member/s2Member Pro) **Bug Fix:** Resolving internal warning: 'PHP Warning: Illegal string offset 'user_id' in s2member/src/includes/classes/sc-eots-in.inc.php'. This was resolved by typecasting `$attr` to an array in cases where WordPress core passes this as a string; e.g., when there are no attributes.

- (s2Member Pro) **Bug Fix:** Incorrect default option value for `reject_prepaid=""` attribute in Stripe Pro-Forms. See: [Issue #1089](https://github.com/websharks/s2member/issues/1089)

= v170221 =

- (s2Member/s2Member Pro) **JW Player v7:** This release adds support for JW Player v7 in the `[s2Stream /]` shortcode. See [Issue #774](https://github.com/websharks/s2member/issues/774).

- (s2Member Pro) **Bug Fix:** Allow Pro-Forms to use `success="%%sp_access_url%%"` without issue. See [Issue #1024](https://github.com/websharks/s2member/issues/1024).

- (s2Member/s2Member Pro) **AWS Region:** Adding AWS region `ap-northeast-2`. See [Issue #1033](https://github.com/websharks/s2member/issues/1033).

- (s2Member/s2Member Pro) **AWS Region:** Adding AWS region `eu-west-2`. See [Issue #1033](https://github.com/websharks/s2member/issues/1033).

- (s2Member) **Bug Fix:** This release corrects a minor server-side validation bug that was related to the use of non-personal email address. See [Thread #1195](https://forums.wpsharks.com/t/bugfix-file-custom-reg-fields-inc-php-missing-bracket/1195) and [Issue #1054](https://github.com/websharks/s2member/issues/1054).

- (s2Member) **Bug Fix:** Updated several outdated links within the software; e.g., removing older `www.` references, correcting forum links, and more. Also corrected missing changelog. See [Issue #1027](https://github.com/websharks/s2member/issues/1027).

- (s2Member Pro) **Pro Upgrader:** The pro upgrader has been refactored and now asks for your s2Member Pro License Key instead of your s2Member.com password. The next time you upgrade to the most recent version of s2Member Pro, you will be asked for your License Key. You can obtain your License Key by logging into your account at s2Member.com. Once logged in, visit your 'My Account' page, where you will find your License Key right at the top. See [Issue #668](https://github.com/websharks/s2member/issues/668).

- (s2Member/s2Member Pro) **CloudFlare Compat.:** Enhancing compatibility with Rocket Loader via `data-cfasync="false"` on dynamic s2Member scripts. See: [Issue #1038](https://github.com/websharks/s2member/issues/1038).

= v161129 =

- (s2Member Pro) **Bug Fix:** Stripe refund notifications via the Stripe Webhook were always interpreted by s2Member as full refunds. This release corrects this bug so that s2Member will handle partial refunds via the Stripe API properly in all cases. Props @raamdev for reporting.

- (s2Member/s2Member Pro) **Bug Fix:** Updating profile via `[s2Member-Profile /]` when changing email addresses may leave the old email address on configured email list servers in some scenarios. Props @renzms for reporting. For further details see [issue #1007](https://github.com/websharks/s2member/issues/1007).

- (s2Member/s2Member Pro) **SSL Compatibility & Option Deprecation:** In previous versions of s2Member there was a setting in the UI that allowed you to force non-SSL redirects to the Login Welcome Page. By popular demand, this setting has been deprecated and removed from the UI.

  _**New Approach:** The new approach taken in the latest release of s2Member is to automatically detect when a non-SSL redirection should occur, and when it should not occur (i.e., when the default WordPress core behavior should remain as-is)._

  _s2Member does this by looking at the `FORCE_SSL_LOGIN` and `FORCE_SSL_ADMIN` settings in WordPress, and also at your configured `siteurl` option in WordPress. If you are not forcing SSL logins, or your `siteurl` begins with `https://` (indicating that your entire site is served over SSL), non-SSL redirects will no longer be forced by s2Member, which resolves problems on many sites that serve their entire site over SSL (a growing trend over the past couple years)._

  _Conversely, if `FORCE_SSL_LOGIN` or `FORCE_SSL_ADMIN` are true, and your configured `siteurl` option in WordPress does NOT begin with `https://` (e.g., just plain `http://`), then a non-SSL redirect **is** forced, as necessary, in order to avoid login cookie conflicts; i.e., the old behavior is preserved by this automatic detection._

  _Overall, this new approach improves compatibility with WordPress core, particularly on sites that serve all of their pages over `https://` (as recommended by Google)._

  _**Backward Compatibility:** As noted previously, the old option that allowed you to configure s2Member to force non-SSL redirects to the Login Welcome Page has been officially deprecated and removed from the UI. However, the old option does still exist internally, but only for backward compatibility. A WordPress filter is exposed that allows developers to alter the old setting if necessary. You can use the filter to force a `true` or `false` value._

  ```php
  <?php
  add_filter('ws_plugin__s2member_login_redirection_always_http', '__return_true');
  // OR add_filter('ws_plugin__s2member_login_redirection_always_http', '__return_false');
  ```

- (s2Member/s2Member Pro) **Bug Fix:** Username/password email being sent to users whenever Custom Passwords are enabled in your s2Member configuration and registration occurs via the default `wp-login.php?action=register` form. Fixed in this release. See also: [issue #870](https://github.com/websharks/s2member/issues/870) if you'd like additional details.

- (s2Member Pro) **Bug Fix:** In the `[s2Member-List /]` search box shortcode an empty `action=""` attribute produces a warning due to invalid syntax in HTML v5. Fixed in this release. See [Issue #1006](https://github.com/websharks/s2member/issues/1006)

- (s2Member/s2Member Pro) **IP Detection:** This release improves s2Member's ability to determine the current user's IP address. s2Member now searches through `HTTP_CF_CONNECTING_IP`, `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_FORWARDED`, `HTTP_X_CLUSTER_CLIENT_IP`, `HTTP_FORWARDED_FOR`, `HTTP_FORWARDED`, `HTTP_VIA`, and `REMOTE_ADDR` (in that order) to locate the first valid public IP address. Either IPv4 or IPv6. Among other things, this improves s2Member's compatibility with sites using CloudFlare. See also: [issue #526](https://github.com/websharks/s2member/issues/526) if you'd like additional details.

- (s2Member Pro) **JSON API:** In the pro version it is now possible to use the s2Member Pro Remote Operations API to send and receive JSON input/output. This makes the Remote Operations API in s2Member compatible with a variety of scripting languages, not just PHP; i.e., prior to this release the Remote Operations API required that you always use PHP's `serialize()` and `unserialize()` functions when making API calls. The use of `serialize()` and `unserialize()` are no longer a requirement since input/output data is now sent and received in the more portable JSON format. For new code samples, please see: **Dashboard → s2Member → API / Scripting → Pro API For Remote Operations**. See also: [issue #987](https://github.com/websharks/s2member/issues/987) if you'd like additional details on this change.

  _**Note:** The old s2Member Pro Remote Operations API has been deprecated but will continue to function just like before (via `serialize()` and `unserialize()`) for the foreseeable future. Moving forward, we recommend the new JSON code samples. Again, you will find those under: **Dashboard → s2Member → API / Scripting → Pro API For Remote Operations**_

- (s2Member/s2Member Pro) Enforce data types when determining PHP constants. See [this GitHub issue](https://github.com/websharks/s2member/issues/989) if you'd like further details.

- (s2Member/s2Member Pro) **Phing Build Routines:** Starting with this release, developers working on the s2Member project are now able to perform builds of the software via the `websharks/phings` project; i.e., the structure of the plugin directories has been changed (slightly) to conform to Phing and PSR4 standards. This makes it easier for our developers to prepare and release new versions of the software in the future.

= v160801 =

- (s2Member/s2Member Pro) **WP v4.6 Compatibility.** A full round of tests was performed against this release of s2Member, s2Member Pro, and the upcoming release of WordPress v4.6. In particular, the new HTTP API needed testing, along with the new optimized loading sequence in WordPress v4.6. Our tests indicate there are no compatibility issues, and we therefore encourage all s2Member site owners to upgrade to WordPress v4.6 whenever it becomes available publicly.

- (s2Member/s2Member Pro) **Bug Fix:** Allow for `<` and `>` to work in the `[s2If php="" /]` shortcode attribute as expected. Some Visual Editors convert these into `&lt;` and `&gt;`, so it's necessary to interpret them as such whenever the shortcode is parsed by s2Member.

- (s2Member/s2Member Pro) **JS API:** Reducing the number of variables provided by the s2Member JavaScript API by default, and adding a new filter that allows them to all be enabled when/if desirable: `ws_plugin__s2member_js_api_constants_enable`. Props @JeffStarr for reporting.

= v160503 =

- (s2Member/s2Member Pro) **Security Enhancement:** This release forces `CURLOPT_SSL_VERIFYPEER` to a value of `TRUE` in the AWeber SDK that is used when/if you integrate with AWeber. In short, this forces AWeber to have a valid/verifiable SSL certificate before any data is exchanged between s2Member and the AWeber API behind-the-scenes. Props at WordPress security team for reporting this.

= v160424 =

- (s2Member/s2Member Pro) **PHP Compat./Bug Fix:** This follow-up release includes a patch that will prevent fatal errors when s2Member and/or s2Member Pro are installed on a site running PHP v5.2 or PHP v5.3; i.e., this release corrects a bug that was causing fatal errors on these older versions of PHP. _Note that s2Member and s2Member Pro are once again compatible with PHP v5.2+, up to PHP v7.0._ Props @krumch. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/938) for details.

= v160423 =

- (s2Member/s2Member Pro) **WP v4.5 Compatibility.** This release offers full compatibility with the latest release of WordPress v4.5. Nothing major was changed for standard WordPress installations, but there were a few subtle tweaks here and there to improve v4.5 compatibility. We encourage all users to upgrade right away.

  **NOTE: WP v4.5 for Multisite Networks running s2Member Pro:** This release corrects a bug first introduced in the previous release of s2Member Pro that resulted in an error message (`Uncaught Error: Class 'c_ws_plugin__s2member_mms_patches' not found`) when updating to WP v4.5. It has been corrected in this release, but in order to avoid this problem altogether please follow this procedure when upgrading WordPress.

  **WP v4.5 Multisite Upgrade Procedure:**

  - Upgrade s2Member and s2Member Pro ​_before_​ updating WordPress core.
  - Then upgrade WordPress core and observe that Multisite Patches are applied properly.

  _If you have already upgraded to WP v4.5 and worked past this issue by patching manually, that's fine. You can still upgrade s2Member and s2Member Pro. After the upgrade you may feel free to enable automatic patching again if that's desirable._

- (s2Member/s2Member Pro) **Bug Fix:** This release corrects a bug first introduced in the previous release which was causing a PHP warning about `cf_stream_extn_resource_exclusions`. A symptom was to have mysterious problems with `[s2Stream /]` or the `[s2File /]` shortcode. Fixed in this release. Props at @raamdev @renzms for reporting. See also [this GitHub issue](https://github.com/websharks/s2member/issues/901) for details.

- (s2Member/s2Member Pro) **PayPal SSL Compatibility:** This release of s2Member provides an `https://` IPN URL for PayPal IPN integrations. It also provides a helpful note (in the Dashboard) about a new requirement that PayPal has with respect to the IPN URL that you configure at PayPal.com. s2Member has been updated to help you with this new requirement.

  **New PayPal.com IPN Requirement:** PayPal.com is now requiring any new IPN URL that you configure to be entered as an `https://` URL; i.e., if you log into your PayPal.com account and try to configure a _brand new_ IPN URL, that URL _must_ use `https://`. PayPal.com will refuse it otherwise.

  However, the `notify_url=` parameter in standard PayPal buttons should continue to work with either `http://` or `https://`, and any existing configurations out there that still use an `http://` IPN URL should continue to work as well. So this is about planning for the future. We have been told that PayPal will eventually _require_ that all IPN URLs use an `https://` protocol; i.e., they will eventually stop supporting `http://` IPN URLs altogether (at some point in the future), they are not giving anyone a date yet. For this reason we strongly suggest that you [review the details given here](https://github.com/websharks/s2member/issues/914).

  Since PayPal is moving in a direction that will eventually require all site owners to have an SSL certificate in the future, s2Member's instructions (and the IPN URL it provides you with) will now be presented in the form of an `https://` URL with additional details to help you through the process of configuring an IPN handler for PayPal.

  See: **Dashboard → s2Member → PayPal Options → PayPal IPN Integration**

  Props @codeforest for reporting. See [this GitHub issue](https://github.com/websharks/s2member/issues/914) for further details.

- (s2Member/s2Member Pro) **Bug Fix:** Email field on Registration page not shown as required via `*` symbol like other fields in this form. Caused by a change in WordPress core. Fixed in this release. Props @spottydog63 @renzms. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/907) for details.

- (s2Member/s2Member Pro) **Bug Fix:** `E_NOTICE` level errors in cache handler when running in `WP_DEBUG` mode. Props at @KTS915 for reporting. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/917).

- (s2Member/s2Member Pro) **i18n Compatibility:** This release of s2Member moves the `load_plugin_textdomain()` call into the `plugins_loaded` hook instead of it being run on `init`. Props @KTS915 for reporting. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/899) for details.

- (s2Member Pro) **Multisite Patches:** Fixed a bug (`Uncaught Error: Class 'c_ws_plugin__s2member_mms_patches' not found`) whenever WordPress was being updated and Multisite Patches were being applied in the pro version of s2Member. See: [this GitHub issue](https://github.com/websharks/s2member/issues/929) for details.

- (s2Member/s2Member Pro) **Security Enhancement:** This release of s2Member defaults PayPal Button Encryption to a value of `on` instead of `off`; i.e., there is a new default behavior. Existing s2Member installations are unaffected by this change, but if you install s2Member on a new site you will notice that (if using PayPal Buttons), Button Encryption will be enabled by default.

  _Note that in order for Button Encryption to work, you must fill-in the API credentials for s2Member under: **Dashboard → s2Member → PayPal Options → PayPal Account Details**_

= v160303 =

- (s2Member/s2Member Pro) **Comet Cache Compat.:** This release improves compatibility with Comet Cache (formerly ZenCache), whenever you have it configured to cache logged-in users. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/888). Props @KTS915 for reporting!

- (s2Member Pro) **ClickBank IPN v6 Compat.:** Version 6 of the ClickBank IPN system was recently updated in a way that causes it to return `transactionType = CANCEL-TEST-REBILL` in test mode, instead of the previous value, which was: `TEST_CANCEL-REBILL`. s2Member Pro has been updated to understand either/or. See also [this GitHub issue](https://github.com/websharks/s2member/issues/882) for further details.

- (s2Member Pro) **Stripe Bug Fix:** This release corrects a bug caused by typos in the source code that were preventing refunds from being processed as expected whenever Stripe was integrated. Props @YearOfBenj for reporting this important issue. Props @patdumond for relaying vital information. See also [this GitHub issue](https://github.com/websharks/s2member/issues/874) if you'd like additional details.

- (s2Member Pro) **PayPal Bug Fix:** Under some conditions, the EOT behavior in s2Member Pro (when integrated with PayPal Pro) would immediately terminate access whenever a customer's subscription naturally expires. Recent versions of the Payflow system set the status to `EXPIRED`, and this was handled as an immediate EOT instead of as a delayed EOT that is subject to date calculations to determine the correct date on which a customer should lose access; i.e., based on what they have already paid for. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/873) if you'd like additional details.

- (s2Member Pro) **One-Time Offer Bug Fix:** This release corrects some inconsistencies in the One-Time Offers system that comes with s2Member Pro. Symptoms included seemingly unpredictable behavior whenever redirections were configured without a specific Membership Level. Props @jacobposey for reporting. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/855) if you'd like additional details.

- (s2Member/s2Member Pro) **Bug Fix:** s2Member was not properly respecting `DISALLOW_FILE_MODS` in a specific scenario related to GZIP. Props @renzms @kristineds. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/832) for further details.

- (s2Member,s2Member Pro) **Bug Fix:** Resolved a minor glitch in the **WordPress Dashboard → Settings → General** panel, where s2Member's notice regarding Open Registration was inadvertently forcing the entire page into italics. Props @renzms @kristineds @raamdev ~ See also: [this GitHub issue](https://github.com/websharks/s2member/issues/831) if you'd like additional details.

- (s2Member/s2Member Pro) **PayPal Sandbox:** This release updates the inline documentation under the PayPal Account Settings section of s2Member. We now suggest that instead of enabling PayPal Sandbox Mode (sometimes buggy at best), that site owners run tests with low-dollar amounts against a live PayPal account instead; e.g., $0.01 test transactions in live mode work great also. See [this GitHub issue](https://github.com/websharks/s2member/issues/891) if you'd like additional details. Props @raamdev for mentioning this again.

= v160120 =

- (s2Member,s2Member Pro) **Bug Fix:** Resolved a minor glitch in the **WordPress Dashboard → Settings → General** panel, where s2Member's notice regarding Open Registration was inadvertently forcing the entire page into italics. Props @renzms @kristineds @raamdev ~ See also: [this GitHub issue](https://github.com/websharks/s2member/issues/831) if you'd like additional details.

- (s2Member) **Multisite Support:** This release of s2Member (the free version only) removes full support for Multisite Networks, which is now a Pro feature; i.e., only available in the Pro version.

  ##### Is s2Member still compatible with WordPress Multisite Networking?
  Multisite support is no longer included in the s2Member Framework. However, it is available with s2Member Pro. s2Member Pro is compatible with Multisite Networking. After you enable Multisite Networking, install the s2Member Pro Add-On. Then, navigate to `s2Member → Multisite (Config)` in the Dashboard of your Main Site. You can learn more about s2Member Pro at [s2Member.com](http://s2member.com/).

  ##### I was using the free version in a Multisite Network before. What happened?
  s2Member (when running on a Multisite Network) requires minor alterations in WordPress core that are not compatible with plugins available at WordPress.org (i.e., not allowed) at this time. For this reason, full support for Multisite Networks is now available only in the pro version.

  ##### What if I already configured Multisite options on a site running the free version?
  If you already customized s2Member's Multisite Network configuration options in a previous release, those settings will remain and still be functional over the short-term; i.e., the functionality that makes s2Member compatible with Multisite Networking is still included, even in the s2Member Framework. However, the routines that deal with core patches, and those that allow you to change Multisite options are no longer available. You will need to acquire the Pro version. Or, you can revert to [a previous release](http://s2member.com/release-archive/). s2Member Framework v151218 is suggested if you go that route.

  _See also: [this GitHub issue](https://github.com/websharks/s2member/issues/850) for further details._

= v151218 =

- (s2Member Pro) **Reminder Email Notification Exclusions:** It is now possible to enable/disable EOT Renewal/Reminder Email notifications on a per-user basis. You can edit a user's profile in the WP Dashboard and check "_No (exclude)_" to prevent specific users from receiving any reminder emails that you configured. Props at @patdumond @luisrock. See also [this GitHub issue](https://github.com/websharks/s2member/issues/816).

- (s2Member) **PHP v7 Compat.:** This release addresses one remaining issue with the `preg_replace` `/e` modifier as reported in [this GitHub issue](https://github.com/websharks/s2member/issues/811). Props @nerdworker for reporting. Thanks!

- (s2Member/s2Member Pro) **WP v4.4 Compat.:** This release corrects an issue that impacted sites attempting to run s2Member on a Multisite Network; i.e., it corrects a problem with the `load.php` patch against the latest release of WordPress. Props @crazycoolcam for reporting! See also [this GitHub issue](https://github.com/websharks/s2member/issues/812).

- (s2Member/s2Member Pro) **Getting Help:** This release adds a new menu page titled, "Getting Help w/ s2Member". This new section of your Dashboard provides quick & easy access to s2Member KB articles, suggestions, and our tech support department (for pro customers). Props @patdumond @raamdev. See also [this GitHub issue](https://github.com/websharks/s2member/issues/814).

= v151210 =

- (s2Member/s2Member Pro) **WP/PHP Compat:** Updated for compatibility with WordPress 4.4 and PHP v7.0. Note that s2Member and s2Member Pro also remain compatible with WordPress 4.3 and PHP 5.2. However, PHP 5.5+ is strongly recommended.

- (s2Member Pro) **New Feature! EOT Renewal/Reminder Email Notifications:** This release adds a long-awaited feature which allows you to configure & send EOT Renewal/Reminder Email notifications to your customers; to let them know their account with you will expire soon.

  It's possible to configure one or more notifications, each with a different set of recipients, and a different subject and message body. Notifications can be sent out X days before the EOT occurs, _the day_ of the EOT, or X days after the EOT has already occurred; e.g., to encourage renewals.

  See: **Dashboard → s2Member → Stripe Options → EOT Renewal/Reminder Email(s)**
  _Also works with PayPal Pro, Authorize.Net, and ClickBank._

  Props @clavaque @KTS915 @raamdev @patdumond @kristineds @pagelab @chronicelite @csexplorer17 @radven, and all of our great supporters. See [this GitHub issue](https://github.com/websharks/s2member/issues/122#issuecomment-161531763).

- (s2Member/s2Member Pro) **Cleanup:** This release improves the list of Other Gateways; moving deprecated payment gateways to the bottom of the list and improving the display of the list overall. Props @kristineds @clavaque. For further details, see [this GitHub issue](https://github.com/websharks/s2member/issues/715).

- (s2Member/s2Member Pro) **Bug Fix:** This release corrects an "Insecure Content Warning" that may have appeared in certain portions of the s2Member Dashboard panels whenever you accessed your Dashboard over the `https` protocol. The issue was seen in Google Chrome and it was simply a `<form>` tag that referenced the s2Member mailing list. This is now hidden by default if you access the Dashboard over SSL, in order to avoid this warning. Props @patdumond for reporting. Props @renzms for fixing. See also [this GitHub issue](https://github.com/websharks/s2member/issues/678) if you'd like additional details.

- (s2Member Pro) **Stripe Locale:** This release adjusts the Stripe overlay so that it will automatically display in the language associated with a visitor's country. This was accomplished by setting the Stripe Checkout variable `locale: 'auto'` as suggested in [this GitHub issue](https://github.com/websharks/s2member/issues/728). Props @renzms

- (s2Member Pro) **Stripe Bug Fix:** This release improves the way Stripe Image Branding and Stripe Statement Descriptions are applied whenever you intentionally leave them empty. It also changes the default value of Stripe Image Branding to an empty string; which will tell Stripe to use the account-level default value that you configured in your Stripe Dashboard in favor of that which you configure with s2Member. The choice is still yours, but this release sets what others have told us are better default values. See also [this GitHub issue](https://github.com/websharks/s2member/issues/666) if you'd like additional details.

- (s2Member Pro) **Stripe Enhancement:** This release makes it possible to configure the Stripe "Remember Me" functionality with s2Member; i.e., it is now possible to turn this on/off if you so desire. See also [this GitHub issue](https://github.com/websharks/s2member/issues/357) for details.

- (s2Member Pro) **Stripe Enhancement:** This release makes it possible for you to tell Stripe to collect a customer's full Billing Address and/or full Shipping Address. See [this GitHub issue](https://github.com/websharks/s2member/issues/667) for additional details.

- (s2Member/s2Member Pro) **UI Clarity:** This release improves the way the New User Email Notification panel behaves whenever you also have Custom Passwords enabled with s2Member. The New User Email Notification is only sent when Custom Passwords are off, so this panel should disable itself whenever that is the case. Fixed in this release. Props @raamdev See also: [this GitHub issue](https://github.com/websharks/s2member/issues/739) if you'd like additional details.

- (s2Member/s2Member Pro) **Bug Fix:** This release resolves a minor issue for developers running Vagrant and VVV with symlink plugins. Props @magbicaleman ~ See [this GitHub issue](https://github.com/websharks/s2member/issues/717) for further details.

- (s2Member Pro) **Conflict Resolution:** This release resolves a conflict with the WP Full Stripe plugin and any other plugins that already load an existing copy of the Stripe SDK at runtime; in concert with s2Member Pro. See [this GitHub issue](https://github.com/websharks/s2member/issues/750) if you'd like additional details.

- (s2Member/s2Member Pro) **New Log File:** This release of s2Member adds a new log file that keeps track of all automatic EOTs that occur through the underlying CRON job. The new log file is named: `auto-eot-system.log` and you can learn more about this file and view it from: **Dashboard → s2Member → Log Files (Debug) → Log Viewer**. Props @raamdev ~ See [this GitHub issue](https://github.com/websharks/s2member/issues/759) if you'd like additional details.

- (s2Member Pro) **`[s2Member-List /]` Bug Fix:** This release resolves a problem in the `[s2Member-List /]` shortcode whenever it is configured to search Custom Fields generated with s2Member. Props @patdumond @renzms. See [this GitHub issue](https://github.com/websharks/s2member/issues/765) if you'd like additional details.

- (s2Member Pro) **Stripe Enhancement:** This release updates s2Member's Stripe integration so that any Buy Now transaction spawns a Stripe popup with the amount and full description filled within the popup itself as well. Props @raamdev. See [this GitHub issue](https://github.com/websharks/s2member/issues/749) for further details.

- (s2Member/s2Member Pro) **WP v4.3 Compat.** This release addresses a minor conflict between functionality provided by s2Member and that of the WordPress core itself. Whenever you change a user's password by editing their account, you can choose to send them an email about this change (or not). Since WordPress v4.3, the WordPress core will _also_ send a more vague email to notify the user of a password change, which is not customizable. This release disables that default email notification in favor of the more helpful and customizable email message that can be sent by s2Member. Simply tick the "Reset Password & Resend New User Email Notification" checkbox whenever you are editing a user. Props @patdumond for reporting. See also [this GitHub issue](https://github.com/websharks/s2member/issues/777) if you'd like additional details.

- (s2Member/s2Member Pro) **PayPal Compat.** This release resolves a conflict between s2Member and a nasty bug at PayPal.com that came to light recently. In some cases, customers reported that clicking the "Continue" button at PayPal.com simply reloaded the page and gave no response. We found that this was attributed to a bug on the PayPal side (see [792](https://github.com/websharks/s2member/issues/792)). To work around this bug, we are using a new default value for the `ns="1"` shortcode attribute in PayPal Pro-Forms and PayPal Buttons. The new default value is `ns="0"`, which seems to work around this bug for the time being. Props @patdumond @raamdev for reporting and testing this fix. See also [full report here](https://github.com/websharks/s2member/issues/792).

  - `ns="0"` (**new default**) = prompt for a shipping address, but do not require one
  - `ns="1"` (old default) = do not prompt for a shipping address whatsoever

  See also: **Dashboard → s2Member → PayPal Pro-Forms → PayPal Shortcode Attributes (Explained)**

- (s2Member/s2Member Pro) **Getting Started:** The old Quick Start Guide was renamed to "Getting Started" in this release. It was also cleaned up and improved a bit; i.e., brought up-to-date. In addition, there is a new welcome message for first-time users of the software that invites them to read over the Getting Started page before they begin. Props @raamdev. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/655).

- (s2Member Pro) **Stripe Bug Fix:** This release corrects a problem with Stripe refund and chargeback notification handling. s2Member Pro will now receive and handle Stripe refund and/or chargeback events (through your existing Webhook) as expected.

  See: **Dashboard → s2Member → Stripe Options → Automatic EOT Behavior** for options that allow you to control the way in which s2Member should respond whenever a refund is processed, or when a dispute (chargeback) occurs at Stripe.

  Props @ElizWS and @tubiz w/ AffiliateWP. See also [this GitHub issue](https://github.com/websharks/s2member/issues/706).

- (s2Member Pro) **`[s2Member-List /]`** Added the ability to search usermeta data too. For instance, you can now search `first_name`, `last_name`, `nickname`, `description`, `s2member_subscr_id`, `s2member_custom`, etc, etc. See [this GitHub issue](https://github.com/websharks/s2member/issues/596).

  _**Note:** The `first_name`, `last_name`, and `nickname` columns are now a part of the default value for the `search_columns=""` attribute in the `[s2Member-List /]` shortcode. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/596). Props @patdumond for her ideas._

- (s2Member Pro) **`[s2Member-List /]`** There are some new `orderby=""` options. You may now choose to order the list by: `first_name`, `last_name`, or `nickname`.

- (s2Member Pro) **`[s2Member-List /]`** It is now possible to search through s2Member Custom Registration/Profile Fields that may contain an array of values; i.e., you can now search _any_ Custom Registration/Profile Field in s2Member. For instance, if a field is designed to accept multiple selections, or you provide a set of multiple checkbox options. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/555).

- (s2Member Pro) **`[s2Member-List /]` Bug Fix:** Meta fields that contained a timestamp were being displayed by the `date_i18n()` function in WP core. However, the time offset calculation was wrong; i.e., not a match to the local time configured by your installation of WordPress. Fixed in this release.

- (s2Member Pro) **`[s2Member-List /]` Bug Fix:** Minor formatting corrections for replacement codes made available for the `link_*=""` attributes in the `[s2Member-List /]` shortcode.

- (s2Member Pro) **`[s2Member-List /]`:** It is now possible to search for an exact match by surrounding your search query with double quotes; e.g., `"john doe"` (in quotes, for an exact match), instead of the default behavior, which is `*john doe*` behind-the-scenes; i.e., a fuzzy match.

- (s2Member Pro) **`[s2Member-List /]`:** Several behind-the-scenes performance enhancements.

- (s2Member/s2Member Pro) **PHP 7 Compat.** This release of s2Member removes its use of the `/e` modifier in calls to `preg_replace()`, which was deprecated in PHP 5.5 and has been removed in PHP 7. Props @bridgeport. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/415).

= v150925 =

- (s2Member/s2Member Pro) **WP v4.3 Compat.** This release corrects a minor backward compatibility issue with versions of WordPress before v4.3, and for installations of s2Member that still use the `%%user_pass%%` Replacement Code in their New User Email notification. See [this GitHub issue](https://github.com/websharks/s2member/issues/710) if you'd like additional details.

- (s2Member/s2Member Pro) **WP v4.3.1 Compat.** This release corrects a compatibility issue whenever you run s2Member together with WordPress v4.3.1+. Note that WordPress v4.3 made changes to the `wp_new_user_notification()` function in WordPress core. Then, a later release of WP v4.3.1 changed it again; breaking compatibility in both instances. This release brings s2Member up-to-date with WordPress v4.3.1 and preserves backward compatibility with WordPress v4.3, as well for versions prior. Props @bridgeport. See [this GitHub issue](https://github.com/websharks/s2member/issues/732) if you'd like additional details.

- (s2Member/s2Member Pro) **Bug Fix**: Fixed a bug where the s2Member CSS and JS was not loaded on the Dashboard when WordPress was installed in a subfolder that was different from the Home URL. Props @magbicaleman. See [Issue #696](https://github.com/websharks/s2member/pull/696).

- (s2Member Pro) **Bug Fix:** This release corrects a security issue related to the Pro Upgrade Wizard for s2Member Pro being displayed without checking `current_user_can('update_plugins')`. Resolved. Props @raamdev for identifying this and working to implement the fix. See [this GitHub issue](https://github.com/websharks/s2member/issues/697) if you'd like additional details.

- (s2Member Pro) **Bug Fix:** This release corrects a bug impacting the `wp_lostpassword_url()` function whenever s2Member is configured to run in a Multisite Network. The link is now adjusted automatically so that a lost password is always recovered from the current site, not the Main Site in the network. Props to @raamdev See also: [this GitHub issue](https://github.com/websharks/s2member/issues/711) for further details.

- (s2Member Pro) **Bug Fix:** Stripe Pro-Forms presented after a long block of text on a page, were not returning to the proper hash location after a Coupon Code was applied. Fixed in this release. Props @raamdev See also: [this GitHub issue](https://github.com/websharks/s2member/issues/730) if you'd like additional details.

- (s2Member/s2Member Pro) **SSL Edge Case:** This release corrects an SSL + Protected File Download problem that may have occurred in rare circumstances. Reproducing this required that you have a user with an ISP that changed their IP address whenever they accessed a site over `https` instead of `http`, and that an s2Member Protected File Download link is presented on an HTTPS page. And, that you were using s2Member's own force-SSL filters. A symptom of this issue was to receive mysterious reports of a user getting a 503 error when trying to access a protected file. Resolved in this release. See [this GitHub issue](https://github.com/websharks/s2member/issues/702) if you'd like additional details.

= v150827 =

- (s2Member/s2Member Pro) **WordPress v4.3 Compat./Bug Fix** This release of s2Member alters the way New User Notification Emails are sent, and in how they should be formatted in WordPress v4.3+.

  The New User Notification Email is now sent (to a user) only if they did _not_ set a Custom Password during their registration; i.e., only if they need this email to set their password for the first time. In short, s2Member now follows the same approach used by WordPress v4.3+.

  See:  **Dashboard → s2Member  → General Options → Email Configuration → New User Notification**

  So the purpose of this particular email has changed just a bit; i.e., the New User Notification Email. Instead of it being sent to every new user, it is only sent to users who need it for the purpose of obtaining a password which grants them access to their account for the first time.

  **Upgrading to WordPress v4.3 and the latest release of s2Member?**

  Please review this section of your Dashboard carefully:
  **s2Member  → General Options → Email Configuration → New User Notification**

  - If you are using s2Member to customize the New User Notification email, you should try to update this message so that it includes the new `%%wp_set_pass_url%%` Replacement Code.

  See also: [this comment at GitHub about the recent changes, with screenshots](https://github.com/websharks/s2member/issues/689#issuecomment-134563230).

- (s2Member Pro) **`[s2Member-List /]` Bug Fix:** This release corrects a bug in the `[s2Member-List /]` shortcode that was causing `levels="0"` not to work, and in fact any use of a `0` in the `levels=""` attribute was broken. See [this GitHub issue](https://github.com/websharks/s2member/issues/663) if you'd like additional details. Props to @patdumond for reproducing, reporting and testing this issue.

- (s2Member/s2Member Pro) **Emoji Bug Fix:** This release corrects a bug in s2Member's SSL filters that can be applied with the Post/Page Custom Field `s2member_force_ssl` being set to `yes`. A symptom of this bug was to see an SSL warning in the latest release of WordPress related to the new Emoji library. See [this GitHub issue](https://github.com/websharks/s2member/issues/674) if you'd like additional details.

= v150722 =

- (s2Member/s2Member Pro) **New Shortcode:** This release introduces a powerful new shortcode which allows you to display a user's EOT (End of Term) or NPT (next payment time) in a WordPress Post or Page. For further details and some minor limitations, please see [`[s2Eot /]` Shortcode Documentation](http://s2member.com/kb-article/s2eot-shortcode-documentation/). Props to @raamdev and @patdumond for their strategic assistance, feedback, and ideas for this shortcode.

- (s2Member/s2Member Pro) **Strong Password Enforcement:** This release of s2Member makes it possible for a site owner to enforce strong passwords; i.e., to require a minimum number of characters and a specific strength (i.e., mix of required characters). The default minimum length in s2Member changed from `6` to `8` characters minimum. The default password strength minimum is `good`. To customize, please see: **s2Member → General Options → Registration/Profile Fields & Options**. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/573) if you'd like additional details. Props to @patdumond and @KTS915 for ideas and feedback.

- (s2Member Pro) **reCAPTCHA v2 Upgrade:** This release of s2Member comes with an updated reCAPTCHA™ integration in order to take advantage of No CAPTCHA reCAPTCHA and other enhancements provided by the v2 update on Google's end.

  _Existing s2Member installations that already have an old set of reCAPTCHA v1 Public/Private keys will continue to function as before. However, it is suggested that you configure reCATPCHA v2 keys in order to put s2Member Pro-Forms into the v2 mode moving forward. Please see: **Dashboard → s2Member → General Options → CAPTCHA Anti-Spam Security** where you will find instructions._

- (s2Member/s2Member Pro) **PayPal IPN Compat.** This release addresses a problem with IPN connection failures that result in a 500 Internal Server Error on the PayPal side; occurring whenever s2Member attempts to verify IPN data. Please see: [this GitHub issue](https://github.com/websharks/s2member/issues/610) if you'd like additional details.

- (s2Member Pro) **Stripe Bug Fix:** This release corrects a bug in Stripe Pro-Form Checkout Options, where a Free Registration option could cause other paid Checkout Options to result in a checkout error under the right conditions. See [this GitHub issue](https://github.com/websharks/s2member/issues/569) for further details.

- (s2Member/s2Member) **Google Analytics Compat.** This release automatically preserves `utc_` variables that are used by Google Analytics whenever a Membership Options Page redirection occurs. i.e., if a visitor comes to the site with `utc_` variables and is redirected to the Membership Options Page, because the content they were trying to access is protected; the `utc_` variables are preserved during this redirection, and delivered as part of the Membership Options Page redirect.

- (s2Member Pro) **Authorize.Net Endpoint Filters:** This release adds two new WordPress Filters (i.e., Hooks) that can be used by developers in certain rare cases. Hook names are `ws_plugin__s2member_pro_authnet_aim_endpoint_url` and `ws_plugin__s2member_pro_authnet_arb_endpoint_url `. See [this GitHub issue](https://github.com/websharks/s2member/issues/575#issuecomment-104077606) if you'd like additional details and a quick example of use.

- (s2Member Pro) **Authorize.Net AIM Compat.:** This release addresses a compatibility issue that came to light recently, which was actually attributed to a bug in s2Member Pro that has been sliding through unnoticed until now. The format for an expiration date sent to the Authorize.Net AIM API should be `MM-YYYY`. The format for ARB API calls is `YYYY-MM`. s2Member Pro was sending `YYYY-MM` to both APIs. Fixed in this release. Props to @raamdev for investigating this. See also [this GitHub issue](https://github.com/websharks/s2member/issues/576) if you'd like additional details.

- (s2Member Pro) **`[s2Member-List /]` Bug:** This release corrects an issue in the `[s2Member-List /]` shortcode that was preventing the `display_name` DB column from being searchable. This release also adds the `display_name` to the list of default `search_columns=""` that are considered by the `[s2Member-List /]` shortcode. Props to @patdumond for researching this. See [this GitHub issue](https://github.com/websharks/s2member/issues/578) for further details.

- (s2Member/s2Member Pro) **Bug Fix:** This release corrects an issue where s2Member would fail to subscribe customers to configured mailing list IDs whenever an existing customer is upgrading and you have the Double Opt-In Checkbox turned off entirely. Fixed. See [this GitHub issue](https://github.com/websharks/s2member/issues/581) if you would like additional details.

- (s2Member Pro) **Stripe Bug Fix:** This release corrects a bug in s2Member's Stripe Pro-Forms, related to having multiple Checkout Options. The bug resulted in a missing error message whenever one of the Checkout Options was submitted incorrectly, and also resulted in the default Checkout Option being magically selected instead of the one that a customer was working with. Props to @patdumond and @bryanthankins. See: [this GitHub issue](https://github.com/websharks/s2member/issues/586) if you'd like additional details.

- (s2Member/s2Member Pro) **Bug Fix:** This release fixes an issue where the s2Drip shortcode was requiring PHP 5.3+; this fix allows the shortcode to work properly with PHP 5.2+.

- (s2Member Pro) **Compat.** A call to `WP_Widget` was updated to support WordPress v4.3+. See [this GitHub issue](https://github.com/websharks/s2member/issues/607) if you'd like additional details.

- (s2Member/s2Member Pro) **Bug Fix:** This release corrects a bug in the s2Member IPN handler that processes full refunds. In your s2Member EOT Behavior options, if you choose the  `refunds,partial_refunds,reversals` option it results in a full refund not being processed; i.e., an EOT does not occur as expected. s2Member was incorrectly recording that your configured preference was not to process refunds whenever a full refund occurs. Fixed in this release. See also [this GitHub issue](https://github.com/websharks/s2member/issues/614) if you'd like additional details.

- (s2Member/s2Member Pro) **Wikpedia Links:** Updated throughout to use an `https://` protocol. Now the Wikipedia default. This impacts mostly the back-end of s2Member which references a few articles at the Wikipedia. However, it also impacts Pro-Forms where a link is provided to users with more information about Security Codes that appear on the back of credit cards. See [this GitHub issue](https://github.com/websharks/s2member/issues/617) if you'd like additional details.

- (s2Member/s2Member Pro) **qTranslate X Compat.** This release includes a minor update that improves compatibility with qTranslate X. See [this GitHub issue](https://github.com/websharks/s2member/issues/618) if you'd like additional details.

- (s2Member/s2Member Pro) **AWeber Compat.** This release resolves an issue with AWeber rejecting subscribers that have IPv6 addresses. Until such time as AWeber adds support for IPv6 addresses, s2Member will simply send an empty IP address whenever it encounters an IPv6 address. This behavior was requested by the AWeber team. See [this GitHub issue](https://github.com/websharks/s2member/issues/611) if you'd like additional details.

- (s2Member Pro) **Coupon Code Expiration:** This release improves the way coupons that are set to expire are handled. Instead of expiring at midnight the day before the configured  expiration date, coupon codes now expire at the end of the configured day. As always, all times are calculated from GMT/UTC time, the same as WordPress itself. In short, if you set a coupon to expire Dec 5th, the coupon will now expire Dec 5th, at the end of the day (UTC time). The old behavior, was for the coupon to expire Dec 4th at midnight UTC time, which led to confusion in many cases. Please see [this GitHub issue](https://github.com/websharks/s2member/issues/612) if you'd like additional details.

- (s2Member/s2Member Pro) **IPN Proxy Key Bug:** This release corrects a minor bug in s2Member's IPN Proxy Key generation that was causing problems in just a few edge cases. This bug may have impacted your site if you had a domain name being accessed with a `Host:` header containing mixed caSe. Not normal behavior, but there are a few edge cases where it's important for s2Member to get this right in order to avoid an "Unable to verify $_POST vars." error. See [this GitHub issue](https://github.com/websharks/s2member/issues/590) if you'd like additional details.

- (s2Member/s2Member Pro) **Password Reset Layout:** This release improves the layout/CSS applied to the WordPress password reset form in order to better separate the password strength indicator from the instructions provided by WordPress. See [this GitHub issue](https://github.com/websharks/s2member/issues/585) if you'd like additional details. Props to @patdumond, @BugRat, and @raamdev for discovering this.

- (s2Member) **Back-end UI Quick Links:** This release resolves an overlap in the display of the quick links atop each menu page in the Dashboard. This bug impacted the lite version only. If you'd like additional details, please see [this GitHub issue](https://github.com/websharks/s2member/issues/589). Props to @raamdev for discovering this.

- (s2Member Pro) **Username Compat.:** This release updates s2Member's own validation against usernames in order to bring it inline with the most recent versions of WordPress core; i.e., we now allow whitespace in usernames. This release was updated so that usernames are validated only by the WordPress core function: `sanitize_user()`, which does allow single whitespace characters in usernames. Please see [this GitHub issue](https://github.com/websharks/s2member/issues/566) if you'd like additional details.

- (s2Member Pro) **Message After Modification:** This release improves the default response that a customer who is upgrading/downgrading receives after having completed checkout using a Pro-Form. Instead of asking the customer to "log back in", s2Member simply says, "Thank you. Your account has been updated.". There is no reason for a customer to log back in; i.e., this is not necessary, and that message was leading to some confusion. Note also that with Pro-Forms you can provide a Custom Return URL on Success using the `success=""` shortcode attribute. Thus, this message is simply a default. We suggest that you customize in all cases. See [this GitHub issue](https://github.com/websharks/s2member/issues/580) if you'd like additional details. Props to @patdumond for reporting this important issue.

- (s2Member Pro) **Documentation Update:** This releases improves the documentation for the `rrt=""` shortcode attribute in all Pro-Form implementations; e.g., PayPal Pro-Forms, Authorize.Net Pro-Forms, and Stripe Pro-Forms. The `rrt=""` attribute can be somewhat misleading, so we added the following: **IMPORTANT NOTE:** If you don't offer a trial period; i.e., the first charge occurs when a customer completes checkout, you should set this to the number of additional payments, and NOT to the total number. For instance, if I want to charge the customer a total of 3 times, and one of those charges occurs when they complete checkout, I set should this to `rrt="2"` for a grand total of three all together.

- (s2Member/s2Member Pro) **Bug Fix:** This release corrects an issue with EOT calculations under a specific circumstance. If a customer registered on the site for free, and later made a purchase that included a free trial period, and they canceled within the trial period, the EOT was being incorrectly calculated based on the user's WordPress registration time instead of being based on the time that their trial began. This resulted in an immediate EOT (due to it being a date in the past), instead of being set to the end of the trial. Fixed in this release.

- (s2Member/s2Member Pro) **Documentation Update:** This release replaces a specific symbol that has been used throughout the Dashboard with s2Member. Instead of the `⥱` symbol we are now using the more compatible `→` symbol instead. This is used to indicate a Dashboard path.

- (s2Member/s2Member) **E_NOTICE:** Several `E_NOTICE`-level warnings were resolved in this release. Note that `E_NOTICE`-level warnings only show up in `WP_DEBUG` mode for developers, but they are frustrating nonetheless. Props to @raamdev for reporting some of these.

- (s2Member Pro) **Bug Fix:** PayPal Pro-Forms selling to customers who choose a Maestro/Solo card may experience problems in some circumstances. GBP currency conversion was partially failing due to a change in the underlying API that s2Member calls upon. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/605) if you'd like additional details.

- (s2Member/s2Member Pro) **Opt-In Bug Fix:** This release of s2Member corrects a bug that was causing members to be automatically unsubscribed from your mailing list whenever you choose to hide the Double Opt-In Box. A customer updating their profile later without this box, was being unsubscribed inadvertently. Fixed in this release. Props to @raamdev for his work in reproducing and reporting this bug. See [this GitHub issue](https://github.com/websharks/s2member/issues/633) if you'd like additional details.

- (s2Member Pro) **Stripe Bug Workaround:** It came to our attention that some Stripe API calls that simply update the `name`, `address_state`, `address_zip`, and `address_country` for tax reporting purposes were resulting in a card decline even after Stripe approved the transaction. We suspect this is a bug in the Stripe API. It has been reported to Stripe. For now though, we are working around this issue by failing gracefully in such a scenario. This simple update is there only for tax reporting purposes, so if it fails it does not warrant a refusal to complete the transaction.m It is simply logged by s2Member for analysis. Please see [this GitHub issue](https://github.com/websharks/s2member/issues/535) where a deeper investigation is underway for our next maintenance release.

- (s2Member Pro) **Stripe API Update:** This release of s2Member takes advantage of the latest Stripe API version. Moving from `v2015-02-18` to `v2015-07-13`. See [this article at Stripe](https://stripe.com/docs/upgrades#api-changelog) if you'd like additional details. _Remember that s2Member's API calls to Stripe will always use this specific version of their API (`v2015-07-13`), even if your Stripe account is configured with an older default version. This is to ensure that s2Member works as intended for all site owners._

- (s2Member Pro) **Stripe Prepaid Cards:** This release makes it possible for site owners to reject prepaid cards if they choose to do so. Stripe has the ability to determine if a credit/debit card is backed by a prepaid funding source. If it is, you can choose to reject or allow this type of card. The default behavior is to accept it. See: **Dashboard → s2Member → Stripe Options → Account Details → Reject or Allow Prepaid Cards** for further details. See also: [this GiHub issue](https://github.com/websharks/s2member/issues/505) if you'd like more information. Props to @raamdev for determining the feasibility of this feature.

- (s2Member Pro) **Bug Fix:** `Notice: Undefined index: password1` in `paypal-registration-in.inc.php`. This was another `E_NOTICE`-level warning that was cleaned up in this release. Props to @raamdev See [this GitHub issue](https://github.com/websharks/s2member/issues/634) if you'd like additional details.

- (s2Member Pro) **Stripe Bug Fix:** This release fixes a bug in Stripe Pro-Forms where upon a customer applying a 100%-off coupon code, the customer is met with an erroneous error regarding a missing state/zipcode--and only when a site owner has defined a tax configuration file also. Fixed in this release. See also [this GitHub issue](https://github.com/websharks/s2member/issues/548) if you'd like additional details.

- (s2Member Pro) **Automatic Update Compat.:** [Automatic Background Updates](https://codex.wordpress.org/Configuring_Automatic_Background_Updates) were introduced in WordPress v3.7 and while by default only WordPress core updates are updated automatically in this special way, it's still possible to enable automatic background updates for everything; including themes and plugins. For instance, some web hosting companies enable automatic/background plugin updates in an attempt to improve overall security.

  That's fine. However, when s2Member Pro is installed, it works as an add-on for the s2Member Framework plugin, and any update of the Framework plugin requires a manual or interactive update of the Pro add-on. Otherwise your site is left with only a portion of its original functionality until you complete the update. For that reason, starting with this release of s2Member, automatic background updates of the s2Member Framework are disabled automatically when you are also running s2Member Pro.

  Props to @raamdev for addressing this issue and providing the source code which made this enhancement possible. See also [this GitHub issue](https://github.com/websharks/s2member/issues/523) if you'd like additional details.

  _See also: [Instructions for Updating s2Member and s2Member Pro](https://s2member.com/updating/)_
- (s2Member Pro) **`[s2Member-Login /]` Shortcode:** This release includes a new shortcode that allows you to display a login box on any Post/Page that you create with WordPress. It can also double as a way to display a user's profile summary; including their avatar. See: [`[s2Member-Login /]` Shortcode Documentation](http://s2member.com/kb-article/s2member-login-shortcode-documentation/) for further details.

- (s2Member Pro) **`[s2Member-Summary /]` Shortcode:** This release includes a new shortcode that allows you to display a user's profile summary (including avatar) in any Post/Page that you create with WordPress. It can also double as a way to display a login box in case the user is not logged in yet (optional). See: [`[s2Member-Summary /]` Shortcode Documentation](http://s2member.com/kb-article/s2member-summary-shortcode-documentation/) for further details. Props to @patdumond for her ideas and feedback on this new feature.

- (s2Member/s2Member Pro) **Avatar via Shortcode:** The `[s2Get /]` shortcode has been updated in support of user avatars, to make it easier for site owners to include a member's avatar in any WordPress Post/Page of their choosing; e.g., `[s2Get user_field="avatar" size="96" /]` produces an `<img />` tag with the user's avatar. See also: [`[s2Get /]` Shortcode Documentation](http://s2member.com/kb-article/s2get-shortcode-documentation/) for further details/examples. Props to @patdumond for her ideas and feedback on this feature.

- (s2Member/s2Member Pro) **`[s2Get date_format="" /]` Now Possible:** The `[s2Get /]` shortcode was updated to support date formats whenever the `user_field=""` key that you want to display ends with `_time`; e.g., `[s2Get user_field="s2member_last_payment_time" date_format="M jS, Y, g:i a T" /]` produces: `Mar 5th, 2022, 12:00 am UTC` instead of a UNIX timestamp. See also: [`[s2Get /]` Shortcode Documentation](http://s2member.com/kb-article/s2get-shortcode-documentation/) for further details/examples, including PHP equivalents.

  _See also: [New `[s2Eot /]` Shortcode](http://s2member.com/kb-article/s2eot-shortcode-documentation/) with EOT-specific date/time functionality enhancements._

- (s2Member/s2Member Pro) **WordPress v4.3-beta Compat.:** This release was tested against WordPress v4.2+, including WordPress v4.3-beta. A few minor adjustments were made to improve support in the upcoming release of WordPress v4.3 based on beta releases made available to us.

- (s2Member/s2Member Pro) **goo.gl URL Shortener:** This release addresses a problem with the Google URL Shortening API. Google now requires that you configure an API key. Otherwise, API calls will fail often and s2Member reverts back to tinyURL instead. Starting with this release, if you enable the Google URL Shortener, you will need to supply an API key for it to work as expected. See: **s2Member → General Options → URL Shortening Service Preference** for further details. See also [this GitHub issue](https://github.com/websharks/s2member/issues/587) if you'd like additional details. Props to @bridgeport for reproducing and reporting this bug.

- (s2Member/s2Member Pro) **Bitly URL Shortener:** This release adds support for Bitly to be used as your preferred URL Shortening service. Bitly has become very popular for many reasons. One reason to choose Bitly over others is that you can configure your Bitly account to use a custom domain of your choosing; i.e., shortened URLs may contain [a domain that you configure](https://bitly.com/a/settings/advanced). See: **s2Member → General Options → URL Shortening Service Preference** for further details.

- (s2Member Pro) **Other Gateways:** Starting with this release, when you install the s2Member Pro add-on for the first time, there are two Pro gateways enabled by default. When you first install s2Member Pro (first-time users only), both the Stripe and PayPal Pro payment gateways will already be enabled for you. This is to help site owners avoid confusion. In addition, first-time users will be greeted by s2Member Pro with a reminder to configure your "Other Gateways". See also [this GitHub issue](https://github.com/websharks/s2member/issues/528) if you'd like additional details. Props to @raamdev for identifying this usability issue and providing feedback/suggestions.

- (s2Member Pro) **Stats Collection:** Starting w/ this release of s2Member Pro, we are now collecting important/anonymous server details that will help us better understand which versions of PHP/MySQL are most widely used by site owners running the pro version of our software. For further details, please see: [What anonymous information does s2Member Pro report to WebSharks, and why?](http://s2member.com/kb-article/what-information-does-s2member-pro-report-to-websharks/)

= v150311 =

- (s2Member/s2Member) **Bug Fix:** The list of users in the WordPress Dashboard was going blank in a particular scenario where a search was attempted in concert with a sortable s2Member column. Fixed in this release. Props to @bridgeport for finding this. See also [this GitHub issue](https://github.com/websharks/s2member/issues/496#issuecomment-76821470) if you'd like technical details.
- (s2Member Pro) **`[s2Member-List /]` Bug Fix:** This release resolves an issue with pagination in the `[s2Member-List /]` shortcode after recent changes in the `WP_User_Query` class. See [this GitHub issue](https://github.com/websharks/s2member/issues/493) if you'd like additional details.
- (s2Member Pro) **Remote Operations API (Bug Fix):** If a remote API call was made to find a user by `user_login`, and that username was all numeric, the `WP_User` class treated it like a user ID instead of as an actual username. Resolved in this release by calling `new WP_User(0, [user login])` as the second argument to the constructor. Thereby forcing `WP_User` to consider it a username. See also [this GitHub issue](https://github.com/websharks/s2member/issues/498) if you'd like technical details.
- (s2Member Pro) **Stripe Bug Fix:** Stripe Pro-Forms for Specific Post/Page Access should not disable the email address field for logged-in users. Resolved in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/500) if you'd like technical details.
- (s2Member Pro) **Stripe Pro-Forms:** This release corrects a bug first introduced in the last release that prevented custom templates for Stripe Pro-Forms from working as intended. Please see [this GitHub issue](https://github.com/websharks/s2member/issues/510) if you'd like additional details.
- (s2Member Pro) **Bug Fix for Gift/Redemption Codes:** This release of s2Member corrects a bug that impacted the generation of Gift/Redemption Codes whenever they were sold with Specific Post/Page Access. See also [this GitHub issue](https://github.com/websharks/s2member/issues/512) if you'd like additional details.

= v150225 =

- (s2Member Pro) **Accept Bitcoin via Stripe!** This release of s2Member Pro comes integrated with the latest version of the Stripe API, where it is now possible to accept Bitcoin right along with most major credit cards—made possible by [Stripe's latest update to support Bitcoin](https://stripe.com/bitcoin). It's as easy as flipping a switch :-) Please see: `Dashboard → s2Member Pro → Stripe Options → Account Details → Accept Bitcoin`. Referencing [this GitHub issue](https://github.com/websharks/s2member/issues/482); i.e., the original feature request.
- (s2Member Pro) **Stripe API Upgrade:** This release of s2Member Pro updates the Stripe SDK and Stripe API to the latest version (Stripe API version: `2015-02-18`). In addition, this release forces a specific version of the Stripe API in all communication between Stripe and s2Member; thereby avoiding a scenario where the Stripe API could be updated again in the future, in ways that might prevent s2Member Pro from operating as intended. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/484) if you'd like technical details. Props to @pauloz1890 for reporting this.
- (s2Member/s2Member Pro) **Security Badge Sizes:** This release of s2Member corrects an issue with the `[s2Member-Security-Badge v="1" /]` shortcode. If you set `v="2"` or `v="3"`, the dimensions were miscalculated. Props to @Mizagorn See [this GitHub issue](https://github.com/websharks/s2member/pull/466) if you'd like additional details.
- (s2Member Pro) **Bug Fix:** Opt-in checkbox state (and some custom fields) were losing state when switching from one type of Pro Form to another—whenever Pro Form Checkout Options were in use. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/468) if you'd like additional details. Props to @zenzoidman for finding this!
- (s2Member) **Bug Fix:** Alt. View Restrictions stopped working on navigation menu items in the previous release of s2Member v150203 due to a default argument value being misinterpreted by a sub-routine. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/475) if you'd like further details.
- (s2Member/s2Member Pro) **Bug Fix:** Some site owners reported "paying" customers being left with a Membership Level of `0` at seemingly random times that may have occurred only once in every 300+ transactions. The issue was related to a regular expression being performed against encrypted binary data with an ungreedy `?` in the regex pattern. Certain characters in the binary output would be lost when specific character sequences were encountered; resulting in a random failure to decrypt cookies set by s2Member. In short, the underlying cause was identified and corrected in this release. Thanks to all who reported this. Our appreciation goes out to everyone who helped to test for this elusive bug. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/397) if you'd like additional technical details.
- (s2Member/s2Member Pro) **UI Enhancements:** This release includes an enhanced UI, along with many subtle improvements to the inline documentation/instructions provided within the WordPress Dashboard.
- (s2Member Pro) **Retiring Google Wallet:** Google [announced that they are retiring Google Wallet for Digital Goods](https://support.google.com/wallet/business/answer/6107573). s2Member Pro continues to support Google Wallet, but this release updates the "Other Gateways" section in the Dashboard to make it clear that Google Wallet will not be supported in future versions of s2Member Pro. In fact, Google Wallet for Digital Goods will [close March 2nd, 2015](https://support.google.com/wallet/business/answer/6107573).
- (s2Member/s2Member) **bbPress Bug Fix:** This release resolves a security issue when running a Multisite Network with bbPress + s2Member. Level 0 access was being granted by the bbPress plugin across all sites in a network. That behavior is fine for bbPress, but is unexpected when s2Member is running in a Network environment. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/465) if you'd like additional details. **IMPORTANT TIP:** If you experienced this issue, please read through [these important comments](https://github.com/websharks/s2member/issues/465#issuecomment-76039842) about bbPress Participants needing to be removed from child blogs in order to fully rid yourself of this problem; i.e., once you complete the update of s2Member, you should [also read this please](https://github.com/websharks/s2member/issues/465#issuecomment-76039842).
- (s2Member/s2Member Pro) **404 / Alt. Views Bug Fix:** This release of s2Member corrects a rare issue where the Membership Options Page (or other pages) can produce random 404 errors whenever s2Member's Alt. View Restrictions are enabled, and there is another plugin installed which runs a DB query using the `WP_Query` class _before_ the Main WP Query has been run. Resolved through the use of `->is_main_query()` instead of tracking it statically via `$initial_query`. See also [this GitHub issue](https://github.com/websharks/s2member/issues/481) if you'd like additional technical details.

= v150203 =

- (s2Member Pro) **Gift/Redemption Codes:** This release adds a powerful new shortcode: `[s2Member-Gift-Codes /]`. This makes it easy to generate and sell access to gift codes (i.e., gift certificates) and/or to a list of redemption codes. For instance,  where a single team leader might like to purchase multiple accounts they can distribute to others on a team, or in a group. Video demo here: http://s2member.com/r/giftredemption-codes-video/ ~ See also: [this GitHub issue](https://github.com/websharks/s2member/issues/386) for additional technical details.
- (s2Member Pro) **User-Specific Coupon Codes:** This release of s2Member makes it possible to configure Pro-Form Coupon Codes that are connected (i.e., only valid) when entered by specific Users/Members who are logged into the site. See: `Dashboard → s2Member → Pro Coupon Codes`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/403) for additional technical details.
- (s2Member Pro) **Coupon Code Max Uses:** This release of s2Member Pro adds the ability to set a maximum number of times that a Coupon Code can be used. This makes it easy to create Coupon Codes that are designed to be used only one time, for instance; or for X number of times. After a Coupon Code is used X number of times, it will expire automatically. See: `Dashboard → s2Member → Pro Coupon Codes`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/285) for technical details.
- (s2Member Pro) **Coupon Code Usage Tracking:** This release of s2Member Pro adds the ability to track the number of times that each of your Coupon Codes have been used. It is also possible to alter the number of uses, and/or set a maximum number of uses. See: `Dashboard → s2Member → Pro Coupon Codes`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/285) for technical details.
- (s2Member Pro) **Coupon Code Active/Expires Dates:** This release of s2Member Pro makes it possible to establish both a start and end time for each of your Pro Coupon Codes. In previous versions of s2Member, it was only possible to set an expiration date. You can now create Coupon Codes that will become active at some point in the future automatically. See: `Dashboard → s2Member → Pro Coupon Codes`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/285) for technical details.
- (s2Member Pro) **Coupon Code UI Enhancements:** This release of s2Member Pro comes with an updated UI that makes it easier to manage your Pro Coupon Codes. See: `Dashboard → s2Member → Pro Coupon Codes`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/285) for technical details.
- (s2Member Pro) **Store Coupon Codes for Each User:** s2Member Pro now stores a list of all coupon codes that a customer has used on your site. See: `Dashboard → Users → Choose User [Edit]`. Scrolling down to the set of s2-related fields will reveal a new list of coupon codes. This list will be filled for new customers only; i.e., s2Member does not have this data for past purchases; only for new customers that you acquire after updating to the latest release. See also [this GitHub issue](https://github.com/websharks/s2member/issues/462) if you'd additional details.
- (s2Member/s2Member Pro) **EOT Custom Value:** In this release of s2Member, the `get_user_option('s2member_custom')` value is preserved after an EOT has taken place, making it possible for site owners to continue to read this value (along with any custom pipe-delimited values they have injected there), even after an EOT has taken place. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/449).
- (s2Member/s2Member Pro) **JW Player Broken Links:** This release corrects some broken links referenced by the inline documentation for s2Member in the WordPress Dashboard. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/448) if you'd like further details.
- (s2Member/s2Member Pro) **Security:** This release of s2Member checks for the existence of the WordPress PHP Constant: `WPINC` instead of looking for the less reliable `$_SERVER['SCRIPT_FILENAME']`. Some site owners reported this was causing trouble in a localhost environment during testing, or when running s2Member on some hosts that are missing the `SCRIPT_FILENAME` environment variable; e.g., some Windows servers. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/454) if you'd like additional details.
- (s2Member Pro) **Advanced Import/Export Compat:** This release of s2Member Pro includes compatibility and a bug fix when running on WordPress v4.1+. Three PHP notices during importation, along with some quirky behavior associated with the `role` CSV column have been corrected. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/455) for technical details.
- (s2Member Pro) **`[s2Member-List /]` Bug Fix:** This release resolves an issue with pagination in the `[s2Member-List /]` shortcode after recent improvements to the search functionality. See [this GitHub issue](https://github.com/websharks/s2member/issues/155#issuecomment-69403120) if you'd like additional details.
- (s2Member Pro) **`[s2Member-List /]` Enhancement:** This release improves search functionality in the `[s2Member-List /]` shortcode, making it so that all searches default to `*[query]*`; i.e., are automatically wrapped by wildcards. If a user enters a wildcard explicitly (or a double quote), this default behavior is overridden and the search query is taken as given in such a scenario. This makes the search functionality easier for end-users to work with, since it no longer requires an exact match. Default behavior is now a fuzzy match. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/394) if you'd like further details.
- (s2Member/s2Member Pro) **AWS v4 Authentication:** This release of s2Member adds AWS v4 Authentication support for Amazon Web Service Regions that only accept the AWS v4 authentication scheme. If you had trouble in the recent past when attempting to integrate s2Member with S3 Buckets (or with CloudFront) in regions outside the USA, this release should resolve those issues for you. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/440) if you'd like additional technical details.
- (s2Member Pro) **Bug Fix:** Pro-Form Checkout Options not working in all cases whenever they are used together with Free Registration Forms. Resolved in this release.

= v150102 =

- (s2Member/s2Member Pro) **Custom Field Mapping:** This release of s2Member adds an internal mapping from s2Member's Custom Field values for each user, to the `get_user_option()` function in the WordPress core. This makes it possible to retrieve user custom field values like always via `get_user_field()` or now through the native `get_user_option()` function also. The benefit of this is that s2Member's custom fields are now more compatible with other themes/plugins for WordPress.
- (s2Member Pro) **[s2Member-List /] Shortcode:** It is now possible to search through custom fields created with s2Member using the `search_columns=""` attribute; e.g., `search_columns="user_login,user_email,s2member_custom_field_MYFIELDID"`; where `MYFIELDID` can be replaced with a field ID that you generate with s2Member via `Dashboard → s2Member → General Options → Registration/Profile Fields`. See also: [this KB article](http://s2member.com/kb-article/s2member-list-shortcode-documentation/) for further details. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/155) for details regarding this improvement.
- (s2Member/s2Member Pro) **MailChimp Bug Fix** This release fixes a bug first introduced in the previous release, which was causing Interest Groups configured w/ s2Member to not be added properly. Resolved in this release. Props to @ethanpil Thanks!
- (s2Member Pro) **ccBill Buttons** This release updates all ccBill button graphics. The MasterCard logo has been removed, and a new set of buttons was created to improve upon the set provided in previous versions of s2Member Pro. See: [this GitHub issue](https://github.com/websharks/s2member/issues/392) if you'd like further details.
- (s2Member Pro) **Authorize.Net** The `AUD` currency code is now supported by Authorize.Net, and thus, s2Member Pro has been updated to support the `AUD` currency code for Pro-Forms integrated with Authorize.Net. See [this GitHub issue](https://github.com/websharks/s2member/issues/383) if you'd like further details.
- (s2Member Pro) **Subscr. CID for Stripe** This release corrects a bug which made it impossible to update the Subscr. CID value (for Stripe) using the user edit form in the Dashboard. For further details, please see [this GitHub issue](https://github.com/websharks/s2member/issues/380).
- (s2Member/s2Member Pro) **Bug fix** s2Member's membership access times log was failing to collect all required access times under certain scenarios where multiple CCAPS were being added or removed in succession within the same process, but across multiple function calls. This resulted in unexpected behaviors (in rare cases) when attempting to use the `[s2Drip /]` shortcode. Fixed in this release. See [this GitHub issue](https://github.com/websharks/s2member/issues/406) for technical details.
- (s2Member/s2Member Pro) **Compatibility** This release includes a fix for s2Member's Multisite Network patches applied to the `wp-admin/user-new.php` file whenever you configure s2Member on a Multisite Network. This change makes s2Member compatible with the coming release of WordPress v4.1 and v4.2-beta as it exists now. See: [this GitHub issue](https://github.com/websharks/s2member/issues/410) if you'd like additional details.
- (s2Member Pro) **Bug Fix:** A feature that was previously introduced in v140816, which made it possible for site owners to set a failed payment threshold (in s2Member's Authorize.Net integration), was suffering from an off-by-one issue during total failed payment calculations. Fixed in this release. See also [this GitHub issue](https://github.com/websharks/s2member/issues/416) if you'd like further details.
- (s2Member Pro) **Feature Enhancement:** Whenever a failed payment threshold is reached (in s2Member's Authorize.Net integration), not only will s2Member terminate on-site access, but now the underlying ARB (Automated Recurring Profile) is cancelled at the same exact time. This way future billing attempts on the Authorize.Net side will not be possible; i.e., it ensures that a failed payment threshold will always terminate both on-site access and the ARB itself together at the same time, as opposed to allowing the ARB termination to occur automatically via Authorize.Net, _whenever_. See also [this GitHub issue](https://github.com/websharks/s2member/issues/416) if you'd like further details.
- (s2Member Pro) **ClickBank Disclaimer:** This release of s2Member adds a default Auto-Return Header Template (customizable from `s2Member → ClickBank Options` in the Dashboard) which includes a disclaimer that ClickBank requires of most merchants before final approval.

  _This default template should help to reduce the time it takes new merchants to receive final approval from ClickBank when first starting out in the ClickBank network. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/412) if you'd like further details._
- (s2Member Pro) **Bug Fix:** PayPal Pro-Forms for Specific Post/Page Access, and configured with `accept="paypal"` (i.e., to accept PayPal only) were not hiding the entire Billing Method section as intended. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/399) if you'd like further details.
- (s2Member Pro) **Bug Fix:** PayPal Pro-Forms using Express Checkout for Billing Agreements under a non-native currency (i.e., under a different currency than their own PayPal account) were failing under some scenarios (notably with the `BRL` currency code). Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/414) if you'd like technical details.
- (s2Member Pro) **Stripe API:** s2Member's Stripe integration has been updated to use the new `statement_descriptor` field in favor of the now deprecated `statement_description`. See [this GitHub issue](https://github.com/websharks/s2member/issues/422) for technical details.
- (s2Member Pro) **Stripe Bug Fix:** In the case of a global tax rate having been applied to the total cost, there were certain scenarios where s2Member Pro would kick back an error message, "Invalid Parameters to Stripe". Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/425) if you'd like technical details.
- (s2Member/s2Member Pro) **WP Core Compat.:** This version of s2Member forces the `wptexturize` filter off in WordPress, due to a bug that was introduced in recent versions of the WordPress core; which results in broken shortcodes in some scenarios. Until the underlying bug is fixed in the WP core, the `wptexturize` filter must be disabled to prevent corruption of any WordPress shortcode that may contain `<` or `>` symbols.

   See [this GitHub issue](https://github.com/websharks/s2member/issues/349) for further technical details. Also referencing: [this WordPress core bug report](https://core.trac.wordpress.org/ticket/29608).
- (s2Member/s2Member Pro) **Alt. Views:** This release fixes a bug that caused `wp_list_pages()` not to be filtered properly under certain scenarios. A symptom of this bug was to apply s2Member's Alt. View protection for "Pages", but for this not work properly in all cases. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/372) if you'd like technical details.
- (s2Member/s2Member Pro) **Currency Code/Symbol:** All email templates, API Notifications (except cancellation/EOT notifications), and all Custom Return URLs on Success; across all payment gateways; now support two additional Replacement Codes: `%%currency%%` and `%%currency_symbol%%`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/314) if you'd like additional details.
- (s2Member Pro) **Coupon Codes:** All transaction-related email templates now support three additional Replacement Codes: `%%full_coupon_code%%`, `%%coupon_code%%`, and `%%coupon_affiliate_id%%`. These have been documented in your Dashboard in places where transaction-related email templates are configured. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/384) if you'd like additional details.
- (s2Member Pro) **Stripe Tax Info:** s2Member now attaches metadata to Stripe Charges and to Stripe Subscriptions which includes a JSON-encoded object containing two or more properties when tax applies.

  These metadata properties can be found in your Stripe Dashboard with the metadata key: `tax_info`; which contains the following JSON-encoded properties: `tax` (total tax that was or will be charged on the regular amount), `tax_per` (tax percentage rate that was applied based on your configuration of s2Member Pro); along with `trial_tax` and `trial_tax_per` in the case of a Stripe Subscription that includes an initial/trial period that requires payment; i.e., the tax applied (if any) to an initial/trial payment on a Subscription.

  We hope this additional information being recorded by s2Member and stored securely within your Stripe account will make it easier for you to maintain accurate bookkeeping records moving forward. This additional metadata is generated for new customers only. It will not be backfilled for any past transactions.

- (s2Member Pro) **Stripe Tax Info:** s2Member now passes the tax location; i.e., `address_state`, `address_zip`, and `address_country` to each Stripe Card object associated with a Stripe Customer.

  We hope this additional information being recorded by s2Member and stored securely within your Stripe account will make it easier for you to maintain accurate bookkeeping records moving forward. This additional cardholder data is collected and stored for new customers only; it will not be backfilled for any past transactions.

- (s2Member Pro) **Stripe IP Address:** s2Member now attaches the customer's IP address (as detected via `$_SERVER['REMOTE_ADDR']` on your server) into each Stripe Customer object, along with the customer's full name. These metadata properties can be found in your Stripe Dashboard with the metadata keys: `name` and `ip`.

- (s2Member Pro) **Stripe Coupon Code:** s2Member now attaches metadata w/ a coupon code used by your customer (if applicable) to each Stripe Charge and/or Stripe Subscription object.

  This metadata property can be found in your Stripe Dashboard with the metadata key: `coupon`; which contains the following JSON-encoded property: `code` i.e., the full coupon code used by your customer. This additional metadata is generated for new customers only. It will not be backfilled for any past transactions. Filled only for transactions that use a coupon code.
- (s2Member Pro) **Stripe Invoice:** This release corrects a bug in s2Member's Stripe integration whereby `subscr-signup-as-subscr-payment` was not always being forced into the core gateway processor; resulting in a miscalculation of the `last_payment_time` under certain scenarios. Fixed in this release. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/396) if you'd like additional details.

= v141007 =

- (s2Member Pro) **ClickBank IPN v6:** This release enables a new integration option for site owners integrated with ClickBank. You may now choose to integrate with v6 of ClickBank's IPN service, since all previous versions are slowly being phased out by ClickBank. Please see: `Dashboard → s2Member → ClickBank Options → IPN Integration` for v6 config. options. See also [this GitHub issue](https://github.com/websharks/s2member/issues/256) if you'd like further details regarding this topic. See also: [this article @ ClickBank](https://support.clickbank.com/entries/22803622-instant-notification-service).
- (s2Member/s2Member Pro) **AWeber API Integration:** This release of s2Member adds a new option for site owners using AWeber. It is now possible to integrate with the new [s2Member App](http://s2member.com/r/aweber-api-key) for AWeber; i.e., via the AWeber API instead of via email-based communication. For further details, please see: `Dashboard → s2Member → API / List Servers → AWeber Integration`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/303) if you'd like additional details.
- (s2Member/s2Member Pro) **Bug Fix:** The EOT Behavior option for `refunds,partial_refunds,reversals` was not being accepted by s2Member. Fixed in this release. Please see [this GitHub issue](https://github.com/websharks/s2member/issues/345) if you'd like further details.
- (s2Member/s2Member Pro) **MailChimp API Wrapper:** This release of s2Member comes with an updated API wrapper class for MailChimp integration. No change in functionality, just a smoother, slightly faster, and more bug-free interaction with the MailChimp API. Please see [this GitHub issue](https://github.com/websharks/s2member/issues/303) if you'd like further details regarding this improvement. See also: [the official MailChimp API class](https://bitbucket.org/mailchimp/mailchimp-api-php); i.e., what s2Member has been updated to in this release.
- (s2Member/s2Member Pro) **URI Restrictions caSe-insensitive (Security Fix)** This release of s2Member changes the way URI Restrictions work. All URI Restrictions are now caSe-insensitive (i.e., `/some-path/` is now the same as `/some-Path/`), allowing s2Member to automatically pick up different variations used in attempts to exploit the behavior of certain slugs within the WordPress core. You can also change this new default behavior, if you prefer. Please see: `Dashboard → s2Member → Restriction Options → URI Restrictions`. See also: [this GitHub issue](https://github.com/websharks/s2member/issues/354) for the details about why this was changed in the most recent copy of s2Member.
- (s2Member/s2Member) **AWeber Role-Based Emails:** In this release we're adding a note in the s2Member UI regarding role-based email addresses being rejected by AWeber. AWeber does not allow role-based emails like: `admin@` or `webmaster@` to be subscribed. It is suggested that you enable s2Member's config. option: "Force Personal Emails" if you intend to integrate with AWeber. Please see: `Dashboard → s2Member → General Options → Registration/Profile Fields`; where you can tell s2Member for force personal email addresses when someone registers on-site. This will prevent a potential subscriber from entering something like `admin@example.com` as their email address.

For older Changelog entries, please see the [CHANGELOG.md](https://github.com/wpsharks/s2member/blob/master/CHANGELOG.md) file.
