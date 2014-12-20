=== Plugin Name ===
Contributors: rufein
Donate link: http://funkydrop.net/
Tags: integration, qtranslate, mqtranslate, seo, yoast, meta, sitemaps, sitemap, sitemaps, language, title
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 0.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integration between the popular Wordpress SEO module by Yoast and mqtranslate plugin (a fork of qtranslate that is updated).

== Description ==

Wordpress Seo Integration is a plugin to integrate the Wordpress SEO plugin by Yoast plugin and mqTranslate (a fork of qtranslate) 
to manage the meta fields and sitemaps in a website with different languages.

The plugin is an Alpha version and only has the next features:

* Administration panel to manage the meta fields filtered by language. The plugin make use of the Wordpress SEO functions to measure the 
quality of SEO of every post in the website.>
* Build a sitemap with languages. the plugin build a sitemap from the type of post and from the language. For example, it builds an
xml sitemap called *page-es* and other called *page-en*.


== Installation ==

* Install Wordpress Seo Integration either via the WordPress.org plugin directory, or by uploading the files to your server.
* Make sure the Wordpress SEO by Yoast module and mqtranslated module are active.
* Activate the module.

== Frequently Asked Questions ==

= Whats going on if i deactivate the Wordpress SEO by Yoast plugin or mqtranslate plugin? =

The plugin depends on these modules or plugins for a correct behaviour because the plugin overwrite some classes to add functionality. 
If you deactive one (or both) plugins, the Wordpress SEO integration will try load the plugins, but this could cause unpredictable behaviour.

= How do i contribute to this plugin? =

Ive open a project in my github profile. Feel free to fork and change the code.

== Screenshots ==

1. Panel administration of Wordpress Seo Integration.

== Changelog ==

= 0.1.2 =
* Fix error load css & js
* Add Jquery ui tabs
* Better Sitemap: Support language

= 0.1.1 =
* Correct Jquery when updated title to avoid bug in empty page
* Correct duplicate Bulk actions
* Add tips to warn the users

= 0.1 =
* Realeased Alpha.


== Upgrade Notice ==


== Arbitrary section ==


