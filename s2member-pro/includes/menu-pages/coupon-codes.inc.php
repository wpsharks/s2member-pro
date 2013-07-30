<?php
/**
* Menu page for s2Member Pro (Coupon Codes page).
*
* Copyright: © 2009-2011
* {@link http://www.websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* This WordPress® plugin (s2Member Pro) is comprised of two parts:
*
* o (1) Its PHP code is licensed under the GPL license, as is WordPress®.
* 	You should have received a copy of the GNU General Public License,
* 	along with this software. In the main directory, see: /licensing/
* 	If not, see: {@link http://www.gnu.org/licenses/}.
*
* o (2) All other parts of (s2Member Pro); including, but not limited to:
* 	the CSS code, some JavaScript code, images, and design;
* 	are licensed according to the license purchased.
* 	See: {@link http://www.s2member.com/prices/}
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
* @package s2Member\Menu_Pages
* @since 1.5
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_menu_page_coupon_codes"))
	{
		/**
		* Menu page for s2Member Pro (Coupon Codes page).
		*
		* @package s2Member\Menu_Pages
		* @since 110531
		*/
		class c_ws_plugin__s2member_pro_menu_page_coupon_codes
			{
				public function __construct ()
					{
						echo '<div class="wrap ws-menu-page">' . "\n";

						echo '<div id="icon-plugins" class="icon32"><br /></div>' . "\n";
						echo '<h2>s2Member® Pro Form (Coupon Codes)</h2>' . "\n";

						echo '<table class="ws-menu-page-table">' . "\n";
						echo '<tbody class="ws-menu-page-table-tbody">' . "\n";
						echo '<tr class="ws-menu-page-table-tr">' . "\n";
						echo '<td class="ws-menu-page-table-l">' . "\n";

						echo '<form method="post" name="ws_plugin__s2member_pro_options_form" id="ws-plugin--s2member-pro-options-form">' . "\n";
						echo '<input type="hidden" name="ws_plugin__s2member_options_save" id="ws-plugin--s2member-options-save" value="' . esc_attr (wp_create_nonce ("ws-plugin--s2member-options-save")) . '" />' . "\n";

						echo '<div class="ws-menu-page-group" title="(Pro Form) Coupon Code Configuration" default-state="open">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-coupon-codes-section">' . "\n";
						echo '<h3>Coupon Code Configuration File (optional, to provide discounts)</h3>' . "\n";
						echo '<p>Currently, this is ONLY compatible with Pro Forms for PayPal® Pro and Authorize.Net®, enabled by the s2Member Pro Module. Coupon Codes allow you to provide discounts <em>(through a special promotion)</em>. A Customer may enter a Coupon Code at checkout, and depending on the Code they enter, a discount may be applied <em>(based on your configuration below)</em>.</p>' . "\n";
						echo '<p>You can have an unlimited number of Coupon Codes. Coupon Codes can be configured to provide a flat-rate discount, or a percentage-based discount. It is possible to force specific Coupon Codes to expire automatically, on a particular date in the future. It is possible to specify which charge(s) a specific Coupon Code applies to <em>(e.g. the Initial/Trial Amount only, the Regular Amount only, or both; including all Recurring fees)</em>. In addition, it is also possible to limit the use of a particular Coupon Code, to a particular Post or Page ID, where a particular Pro Form Shortcode is made available to Customers. You\'ll find several configuration examples below.</p>' . "\n";
						echo '<p><strong>Prerequisites:</strong> In order to display a "Coupon Code" field on your Checkout Form, you MUST add this special Shortcode Attribute to your s2Member Pro Form Shortcode(s): <code>accept_coupons="1"</code>. If you would like to force-feed a default Coupon Code <em>(optional)</em>, you can add this special Shortcode attribute: <code>coupon="[your default code]"</code>. Also optional, instead of <code>coupon="[your default code]"</code>, you could pass <code>?s2p-coupon=[your default code]</code> in the query string of a URL leading to a Checkout Form.</p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";

						echo '<textarea name="ws_plugin__s2member_pro_coupon_codes" id="ws-plugin--s2member-pro-coupon-codes" rows="10" wrap="off" spellcheck="false" style="width:99%;">' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_coupon_codes"]) . '</textarea><br />' . "\n";

						echo 'One Coupon Code per line please, using a pipe (<code>|</code>) delimitation as seen below.' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo 'Here are a few basic Coupon Code examples you can follow:<br />' . "\n";

						echo '<ul>' . "\n"; // Explaining Coupon Codes by example.
						echo '<li><code>SAVE-10|10%</code> <em>(saves the Customer 10%)</em></li>' . "\n";
						echo '<li><code>SAVE-20|20%</code> <em>(saves the Customer 20%)</em></li>' . "\n";
						echo '<li><code>2$OFF|2.00</code> <em>($2.00 off the normal price)</em></li>' . "\n";
						echo '<li><code>EASTER|5.00</code> <em>($5.00 off the normal price)</em></li>' . "\n";
						echo '<li><code>CHRISTMAS|5.00|12/31/2020</code> <em>($5.00 off, expires Dec 31st, 2020)</em></li>' . "\n";
						echo '<li><code>CHRISTMAS-25|25%|01/01/2021</code> <em>(25% off, expires Jan 1st, 2021)</em></li>' . "\n";
						echo '<li><code>100%OFF|100%</code> <em>(100% FREE access @ <strong>$0.00</strong>)</em></li>' . "\n";
						echo '</ul>' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo '<em>By default, s2Member will apply the discount to ALL amounts, including any Regular/Recurring fees.<br />' . "\n";
						echo '* However, you may configure Coupon Codes that will ONLY apply to (ta) Trial Amounts, or (ra) Regular Amounts.</em>' . "\n";

						echo '<ul>' . "\n"; // Explaining this by example.
						echo '<li><code>SAVE-10|10%||ta-only</code> <em>(10% off an Initial/Trial Amount; the ta="" attribute in your Shortcode)</em></li>' . "\n";
						echo '<li><code>SAVE-15|15%||ra-only</code> <em>(15% off the Regular Amount(s); the ra="" attribute in your Shortcode)</em></li>' . "\n";
						echo '<li><code>XMAS|5.00|12/31/2021|ra-only</code> <em>($5 off Regular Amount(s); the ra="" attribute in your Shortcode)</em></li>' . "\n";
						echo '<li><code>5PER|5%|12/31/2021|all</code> <em>(5% off All Amounts; this is the default behavior "all")</em></li>' . "\n";
						echo '</ul>' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo '<em>By default, s2Member accepts Coupon Codes on any Pro Form with Shortcode Attribute: <code>accept_coupons="1"</code>.<br />' . "\n";
						echo '* However, you may configure Coupon Codes that ONLY work on specific Post or Page IDs, as seen below.</em>' . "\n";

						echo '<ul>' . "\n"; // Explaining this by example.
						echo '<li><code>SAVE-10|10%|||123</code> <em>(10% off; works only on Post or Page ID #<code>123</code>)</em></li>' . "\n";
						echo '<li><code>SAVE-15|15%||ra-only|123</code> <em>(15% off Regular Amount(s); works only on Post or Page ID #<code>123</code>)</em></li>' . "\n";
						echo '<li><code>XMAS|5.00|12/31/2021|ra-only|123,456</code> <em>($5 off Regular Amount(s); works only on Post or Page IDs <code>123</code>,<code>456</code>)</em></li>' . "\n";
						echo '<li><code>5PER|5%|12/31/2021|all|all</code> <em>(5% off All Amounts; works on all Posts/Pages; this is the default behavior "all")</em></li>' . "\n";
						echo '</ul>' . "\n";

						echo '<em>Remember, you still need a Pro Form with Shortcode Attribute: <code>accept_coupons="1"</code></em><br />' . "\n";
						echo '<em>* s2Member ONLY accepts Coupons on Pro Forms with Shortcode Attribute: <code>accept_coupons="1"</code></em>' . "\n";

						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-group" title="(Pro Form) Affiliate Coupon Codes">' . "\n";

						echo '<div class="ws-menu-page-section ws-plugin--s2member-pro-affiliate-coupon-codes-section">' . "\n";
						echo '<h3>Affiliate Coupon Codes (optional, for affiliate tracking systems)</h3>' . "\n";
						echo '<p>Currently, this is ONLY compatible with Pro Forms for PayPal® Pro and Authorize.Net®, which are enabled by the s2Member Pro Module. Coupon Codes allow you to provide discounts <em>(through a special promotion)</em>. <strong>Affiliate Coupon Codes</strong> make it possible for your affiliates to receive credit for sales they refer, using one of your Coupon Codes <em>(which you may have configured in the section above)</em>.</p>' . "\n";
						echo '<p>Here\'s how it works. You tell your affiliates about one or more of the Coupon Codes that you accept <em>(which you may have configured in the section above)</em>. Each of your affiliates can add their affiliate ID onto the end of any valid Coupon Code, like this: <code>COUPON-CODE' . esc_html ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_affiliate_coupon_code_suffix_chars"]) . '123</code>; where <code>COUPON-CODE</code> is the valid Coupon Code that you\'ve configured, and <code>123</code> is the affiliate\'s ID <em>(see also, Suffix Chars below)</em>. If a Customer comes to your site, and they use a Coupon Code with an affiliate ID on the end of it; your affiliate will be <strong>tracked</strong> automatically by s2Member. If this Customer actually completes the sale, the referring affiliate will be credited with whatever commission your affiliate program offers.</p>' . "\n";
						echo '<p><strong>Prerequisites.</strong> Your affiliate tracking system MUST provide you with a Tracking URL that s2Member can connect to silently behind-the-scene <em>(sometimes referred to as a cURL Tracking Logger)</em>. The Standard edition of <a href="http://www.s2member.com/idev-affiliate" target="_blank" rel="external">iDevAffiliate®</a> meets this requirement, and it\'s our recommendation to use the cURL Tracking Logger provided by iDevAffiliate®. Or, if you\'re using another affiliate system that offers a URL s2Member can load as a 1px IMG in the Customer\'s browser after a Coupon Code is applied, that\'s fine too. In either case, this URL should ONLY <strong>track</strong> a potential Customer upon entering a Coupon Code, and NOT credit an affiliate with a commission. Credit is given to an affiliate through other forms of integration which you may or may not have configured yet. Please see: <code>s2Member -› API Tracking</code>.</p>' . "\n";
						echo '<p><strong><a href="http://www.s2member.com/idev-affiliate" target="_blank" rel="external">iDevAffiliate®</a> <em>(recommended)</em>:</strong> You can obtain an Affiliate Tracking URL inside your iDevAffiliate® dashboard. See: <code>iDevAffiliate® -› Setup &amp; Tools -› Advanced Developer Tools -› Custom Functions -› cURL Tracking Log</code>. s2Member only needs the Tracking URL itself <em>(and NOT all of the PHP code that iDevAffiliate® provides)</em>. Your iDevAffiliate® Tracking URL <em>( including the <code>SILENT-PHP|</code> prefix described below)</em> should contain s2Member\'s <code><em class="ws-menu-page-hilite">%%</em></code> Replacement Codes, like this: <code>SILENT-PHP|http://example.com/idevaffiliate.php?ip_address=<em class="ws-menu-page-hilite">%%user_ip%%</em>&id=<em class="ws-menu-page-hilite">%%coupon_affiliate_id%%</em></code></p>' . "\n";
						echo '<p><strong><a href="http://www.s2member.com/shareasale" target="_blank" rel="external">ShareASale®</a>:</strong> Use this ShareASale® URL <em>(including the <code>IMG-1PX|</code> prefix described below)</em>, and modify it just a bit to match your product: <code>IMG-1PX|https://www.shareasale.com/r.cfm?u=<em class="ws-menu-page-hilite">%%coupon_affiliate_id%%</em>&b=<em class="ws-menu-page-hilite">BBBBBB</em>&m=<em class="ws-menu-page-hilite">MMMMMM</em>&urllink=about%3Ablank&afftrack=<em class="ws-menu-page-hilite">%%full_coupon_code%%</em></code>. Be sure to replace <code><em class="ws-menu-page-hilite">BBBBBB</em></code> with a specific ShareASale® Banner/Creative ID that you make available to your affiliates. Replace <code><em class="ws-menu-page-hilite">MMMMMM</em></code> with your ShareASale® Merchant ID. The other <code><em class="ws-menu-page-hilite">%%</em></code> Replacement Codes should remain as they are, these are documented below.</p>' . "\n";

						echo '<table class="form-table">' . "\n";
						echo '<tbody>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-pro-affiliate-coupon-code-suffix-chars">' . "\n";
						echo 'Affiliate Suffix Chars (indicating an Affiliate ID):' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo '<input type="text" autocomplete="off" name="ws_plugin__s2member_pro_affiliate_coupon_code_suffix_chars" id="ws-plugin--s2member-pro-affiliate-coupon-code-suffix-chars" value="' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_affiliate_coupon_code_suffix_chars"]) . '" /><br />' . "\n";
						echo 'Characters that s2Member will use to identify an Affiliate\'s ID in Coupon Codes.<br />' . "\n";
						echo '<em>Example: <code>COUPON-CODE' . esc_html ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_affiliate_coupon_code_suffix_chars"]) . '123</code>. Coupon Code is <code>COUPON-CODE</code>. Affiliate ID is <code>123</code>.</em>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<th>' . "\n";
						echo '<label for="ws-plugin--s2member-pro-affiliate-coupon-code-tracking-urls">' . "\n";
						echo 'Affiliate Tracking URLs (one per line):' . "\n";
						echo '</label>' . "\n";
						echo '</th>' . "\n";

						echo '</tr>' . "\n";
						echo '<tr>' . "\n";

						echo '<td>' . "\n";
						echo 'You can input multiple Tracking URLs by inserting one per line.<br />' . "\n";
						echo 'Each line must include a prefix. One of: <code>SILENT-PHP|</code> or <code>IMG-1PX|</code> (details below)<br />' . "\n";
						echo '<textarea name="ws_plugin__s2member_pro_affiliate_coupon_code_tracking_urls" id="ws-plugin--s2member-pro-affiliate-coupon-code-tracking-urls" rows="3" wrap="off">' . format_to_edit ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_affiliate_coupon_code_tracking_urls"]) . '</textarea><br />' . "\n";
						echo '<em>These are ONLY for <strong>tracking</strong> a potential Customer via Affiliate Coupon Codes. You\'re NOT crediting affiliates.</em><br />' . "\n";
						echo '<em>To actually credit your affiliates, please check your Dashboard here: <code>s2Member -› API / Tracking (or API / Notifications)</code>.</em><br /><br />' . "\n";
						echo '<strong>You can use these special Replacement Codes in your Tracking URL(s), as needed.</strong>' . "\n";
						echo '<ul>' . "\n";
						echo '<li><code>%%full_coupon_code%%</code> = The full Affiliate Coupon Code accepted by s2Member.</li>' . "\n";
						echo '<li><code>%%coupon_code%%</code> = The actual Coupon Code accepted by your configuration of s2Member.</li>' . "\n";
						echo '<li><code>%%coupon_affiliate_id%%</code> = This is the end of the Affiliate Coupon Code <em>(i.e. the referring affiliate\'s ID)</em>.</li>' . "\n";
						echo '<li><code>%%affiliate_id%%</code> = Deprecated. This will be removed in a future release. Please use <code>%%coupon_affiliate_id%%</code> instead.</li>' . "\n";
						echo '<li><code>%%user_ip%%</code> = The Customer\'s IP Address, detected during checkout via <code>$_SERVER["REMOTE_ADDR"]</code>.</li>' . "\n";
						echo '</ul>' . "\n";
						echo '<strong>Each Tracking URL must include a prefix. One of: <code>SILENT-PHP|</code> or <code>IMG-1PX|</code></strong>' . "\n";
						echo '<ul>' . "\n";
						echo '<li><code>SILENT-PHP|URL goes here</code> = Coupon Code Tracking for a potential Customer takes place silently behind-the-scene via PHP, using an HTTP connection. This method is the most reliable. This method requires an affiliate tracking system like <a href="http://www.s2member.com/idev-affiliate" target="_blank" rel="external">iDevAffiliate®</a>, that can track by IP address only, when it needs to. If you\'re running iDevAffiliate®, please see the example above.</li>' . "\n";
						echo '<li><code>IMG-1PX|URL goes here</code> = Coupon Code Tracking takes place in a potential Customer\'s browser, through a 1px IMG tag, usually with Cookies set by your affiliate tracking system. This is the most compatible across various affiliate software applications. You give s2Member the Tracking URL, and s2Member will load the 1px IMG tag at the appropriate time.</li>' . "\n";
						echo '</ul>' . "\n";
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";
						echo '</div>' . "\n";

						echo '</div>' . "\n";

						echo '<div class="ws-menu-page-hr"></div>' . "\n";

						echo '<p class="submit"><input type="submit" class="button-primary" value="Save All Changes" /></p>' . "\n";

						echo '</form>' . "\n";

						echo '</td>' . "\n";

						echo '<td class="ws-menu-page-table-r">' . "\n";
						c_ws_plugin__s2member_menu_pages_rs::display ();
						echo '</td>' . "\n";

						echo '</tr>' . "\n";
						echo '</tbody>' . "\n";
						echo '</table>' . "\n";

						echo '</div>' . "\n";
					}
			}
	}

new c_ws_plugin__s2member_pro_menu_page_coupon_codes ();
?>