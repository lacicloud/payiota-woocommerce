=== PayIOTA.me WooCommerce Plugin ===

Contributors: lacicloud, Dan Darden
Donate link: https://payiota.me/humans.txt
Tags: iota, payment, pay, gateway
Tested on: WordPress 4.8.3 and WooCommerce 3.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Github: https://github.com/lacicloud/payiota-woocommerce

The plugin for the IOTA payment gateway PayIOTA.me, which allows you to accept IOTA payments on your site. It has auto-update and QR code generation functions. 

== Installation ==

You must have a PayIOTA.me account to set-up the plugin. 

1. Install the plugin by uploading the ZIP file or by using the WordPress plugin installer
2. Activate it
3. Configure it under Plugins->IOTA Payment Gateway->Settings, set API key and Verification key. As of v1.0.5 , you are not required to set your IPN URL in the PayIOTA interface page to the same URL that you see on the settings page - custom IPN URL's are now supported by PayIOTA.
4. Test it!

== Compatibility ==

WordFence: The IPN triggers a rule in WordFence. There is an untested compatibility patch, but you must whitelist PayIOTA.me (either IPV4 + IPV6 or domain) in WordFence. If you can remove the 'POST without User-Agent/Referer' rule or disable 'WordFence advanced blocking', that works too.

You should probably do the same for other security plugins as well. At this time, I do not know of any plugins that are not fully compatible with PayIOTA other than WordFence.

== Frequently Asked Questions ==

Q: What is the maximum amount of time an invoice lasts?
A: 1 week. After that our service will not check the address anymore to prevent overloading our server and the node it is checking from. 

Q: How can I use currency different than USD?
A: Add the 3 letter currency code in the plugin's settings. Don't forget to also change your WooCommerce currency settings!