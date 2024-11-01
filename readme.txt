=== Shopp Improved ===
Contributors: aaroncampbell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A89J4TGVXMRNQ
Tags: ecommerce, shopp, cart, shop, e-commerce
Requires at least: 2.9
Tested up to: 3.0
Stable tag: 1.0.3

A related posts plugin that works quickly evenShopp Improved is a plugin that extends the popular Shopp eCommerce solution for WordPress. Requires PHP5.

== Description ==

Shopp Improved is a plugin that extends the popular
<a href="http://shopplugin.net/">Shopp eCommerce solution for WordPress</a>.
You <strong>must</strong> have the <a href="https://shopplugin.net/store/">Shopp
Plugin</a> in order to use this plugin.

I recently worked on a site where I used the Shopp WordPress plugin to set up
E-Commerce.  The first big issue that I ran across was allowing for user
customizations of products.  For example, if a product can be engraved with a
name, includes the person's initials, can include a message, etc.  Shopp does
include the ability to add these kinds of user input fields, but it's done
through a template tag.  This means that you either have to apply it to all
items, or you need a developer to set it up on a per-product basis.  My client
has quite a few products that are custom-made, and they really needed to be able
to set up these fields themselves.

In comes Shopp Improved.  It creates a user interface to allow you to easily add
these fields to any product.  The inputs can then be easily integrated into a
shopp product template with just a couple lines of code.

Requires PHP 5+.

== Installation ==

1. Verify that you have PHP5, which is required for this plugin.
1. Upload the whole `shopp-improved` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Do I need the Shopp plugin to use this? =

Yes.  This plugin only extends Shopp, it is not a replacement.

= How do I add this to my Shopp template? =

Just add these lines to your template:
`
<?php
$shoppImproved = shoppImproved::getInstance();
$shoppImproved->get_inputs();
?>
`

== Changelog ==

= 1.0.3 =
* Use "new Product" directly to make sure the product exists
* Upgrade Xavisys Plugin Framework

= 1.0.2 =
* Updated to work with the newer versions of Shopp

= 1.0.0 =
* Released via WordPress.org

= 0.0.1 =
* Original Version
