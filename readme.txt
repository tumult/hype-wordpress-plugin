=== Tumult Hype Animations ===
Author URI: https://www.tumult.com
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Contributors: tumultinc, freeben
Tags: Hype, Animation
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: Easily embed your Tumult Hype animations into posts and pages with a shortcode.

== Description ==

This plugin allows you to upload your Tumult Hype animations to your Wordpress site to easily embed them using shortcodes on posts and pages. You can also copy the embed code to use your Wordpress site as a Tumult Hype animation host.

For detailed information and support for this plugin, please visit the [support forum](https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074). If your animation doesn't show up at all, please first read the following [responsive design tips](https://forums.tumult.com/t/tumult-hype-animations-wordpress-plugin/11074#responsive-document-tips-2) on the forums. 

== Usage ==

1. In Tumult Hype Professional, Select File > Export as HTML5 > OAM Widget. Note: Do not export with any spaces in your filename or foreign characters or rename your .oam file after export.
2. In the Hype Animations section in the Admin dashboard, click Upload New Animation and select your .oam file.
3. After successful upload, the plugin will generate a shortcode you can use in posts and pages.

== Changelog ==

= 1.9.14 = 
* Add a note to describe your uploaded animation. Notes are autosaved after half a second. 
* Ensure upload form only appears on the Hype Animations dashboard page. 

= 1.9.13 = 
* File Upload Allowlist now parsed in memory. Better wp_nonce security via recommendations from Patchstack. 

= 1.9.11 = 
* Improve security: OAMs must pass a whitelist before being uploaded. Kudos to Patchstack and the Wordpress Plugin team. 

= 1.9.9 = 
* Shows your server's php.ini upload_max_filesize, post_max_size and memory_limit limit in the upload modal
* Handle exotic values provided by php.ini (e.g. 2M, 2.2G)

= 1.9.8 = 
* Upgrade to dropzone 5.9.3, remove unused code, improve management panel

= 1.9.7 = 
* Improve Code Copying Function to avoid debug log errors

= 1.9.6 = 
* Compatibility with EasyWP Hosting
* Improve translations

= 1.9.5 = 
* Disable Classic Editor Animation button
* Design Improvements

= 1.9.1 =
* Fixes issue with CSS classes not saving

= 1.9.0 = 
* Translate into Spanish (Mexico, Spain), Chinese, Portuguese, Arabic, German & Dutch (Thanks Bendora), Japanese, Italian, Romanian (Thanks ionutilie)
* Update Table sorting + styling

= 1.8.2 = 
* Compatibility with Wordpress 5.3
* Shortcode copying improvement

= 1.8.1 = 
* Wordpress 5.2.1 support
* Ondersteuning vir die Afrikaanse taal. (Afrikaans language support!) Thanks https://profiles.wordpress.org/puvircho/!

= 1.8 = 
* Improved Shortcode copying font & style
* Fully supports Wordpress 5.2

= 1.7.4 = 
* Your most recently-updated Tumult Hype animations will appear first in the admin panel list.
* Fully compatible with Wordpress 5.0.1

= 1.7.3 = 
* Resolves issue where pre formatting overrides other plugins: https://wordpress.org/support/topic/problems-with-prehover-in-wordpress-admin/

= 1.7.2 =
* Disallow periods when inserting class names.

= 1.7.1 =
* Include missing jQuery UI images.

= 1.7 =
* Increased security for file uploads. 

= 1.6 =
* Copy Embed Code from the Animation List
* Embeds are now protocol-agnostic for better SSL support (uses // instead of http://)
* Updated to work with Wordpress 4.8.3
* Maximum server-supported file size shown in upload modal (Adjust this limit by editing php.ini)
* Improved Translations
* Removed frames & borders on iframes
* This plugin was adapted from the work of Eralion: eralion.com which was originally posted at (https://wordpress.org/plugins/hype-animations/).

= 1.5 =
* Changing modal popup.

= 1.4 =
* You can now upload new animation with a drag and drop modal popup.
* You can now upload new animation from Wordpress editor tool box.

= 1.3 =
* You can now add a container around the animation (div or iframe) to add custom CSS classes.

= 1.2 =
* **BUG FIX** Now changes files directory in principal .js file.

= 1.1 =
* Now deletes database and uploaded files on uninstall.
* **BUG FIX** Files with . character in filename works now.

= 1.0 =
* First public distribution version.

== Screenshots ==

1. The plugin displays a list of recently-uploaded animations with controls to update, delete, or copy the embed code to embed elsewhere.
2. Clicking 'Copy Code' in the actions list displays the full embed code to your uploaded Tumult Hype document for usage outside of the Wordpress loop or your Wordpress site.
