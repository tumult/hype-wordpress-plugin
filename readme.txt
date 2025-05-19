=== Tumult Hype Animations ===
Author URI: https://www.tumult.com
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Contributors: Tumult
Tags: animations, Gutenberg, block editor, shortcode, responsive
Requires at least: 5.8
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: Easily embed your Tumult Hype animations into posts and pages with a shortcode.
Update URI: https://wordpress.org/plugins/tumult-hype-animations/

== Description ==

Tumult Hype Animations plugin allows you to embed animations created with Tumult Hype into your WordPress site. Version 2.0 introduces full Gutenberg block support, making it easier than ever to add animations to your posts and pages. 

**Features:**

* Embed animations using a shortcode or Gutenberg block.
* Animation selector with thumbnails for better usability.
* Block patterns for common layouts.
* Transform support for converting shortcodes into blocks.
* Compatible with WordPress 5.8 and above.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tumult-hype-animations` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Gutenberg block editor or the `[hypeanimations_anim]` shortcode to embed animations.

== Usage ==

1. In Tumult Hype Professional, Select File > Export as HTML5 > OAM Widget. Note: Do not export with any spaces in your filename or foreign characters or rename your .oam file after export.
2. In the Hype Animations section in the Admin dashboard, click Upload New Animation and select your .oam file.
3. After successful upload, the plugin will generate a shortcode you can use in posts and pages.

=== Shortcode Options ===

The basic shortcode format is: `[hypeanimations_anim id="X"]` where X is the ID of your animation. 

You can also use the following optional attributes:

* `width` - Sets the width of the animation. Accepts values in pixels or percentages (e.g., "400px" or "100%"). Default is the animation's original width.
* `height` - Sets the height of the animation. Accepts values in pixels or percentages (e.g., "300px" or "50%"). Default is the animation's original height.
* `embedmode` - Controls how the animation is embedded. Options:
  * `embedmode="div"` - Embeds the animation directly in a div element
  * `embedmode="iframe"` - Embeds the animation in an iframe
  * If omitted, uses the container type set in the admin panel

  Complete example using all options:

  [hypeanimations_anim id="15" width="100%" height="400px" embedmode="iframe"]

  Explanation of the options used:
  - `id="15"`: Specifies the ID of the animation to embed.
  - `width="100%"`: Sets the width of the animation to 100% of its container.
  - `height="400px"`: Sets the height of the animation to 400 pixels.
  - `responsive="1"`: Enables responsive scaling of the animation to fit its container.
  - `auto_height="true"`: Automatically adjusts the height based on the content's aspect ratio.
  - `embedmode="iframe"`: Embeds the animation in an iframe element.

Examples:

```
[hypeanimations_anim id="10"] // this uses the values present in the Hype embed. 
[hypeanimations_anim id="10" width="100%" height="300px"]
[hypeanimations_anim id="10" embedmode="iframe"] // wraps the entire document and all HTML in an iframe. Useful if you require code in the 'head' html. 
```

=== Gutenberg Block Usage ===

After activating the plugin, go to the block editor and search for "Tumult Hype Animation" in the block library. Configure the block settings to select an animation and customize its dimensions.

**Block Editor Options:**

* **Animation** - Select from your uploaded Tumult Hype animations
* **Width** - Specify the width (pixels or percentages)
* **Height** - Specify the height (pixels or percentages)
* **Responsive** - Toggle to enable/disable responsive scaling
* **Auto Height** - Toggle to enable/disable automatic height adjustment

== Changelog ==

= 2.0.0 =
* Added full Gutenberg block support with advanced features.
* Added block patterns and transform support for shortcodes.
* Improved compatibility with WordPress 6.8.
* Enhanced animation selector with thumbnails.
* Added block collection for better organization.

= 1.9.16 =
* Resolves CVE-2024-11082: Authors+ can upload executable files during OAM replacement. Thanks to Wordfence and vgo0 for responsible disclosure. 

= 1.9.15 = 
* Ensure only authors, editors, and admins can access animation information (not subscribers). Thanks to Tieu Pham Trong Nhan for the responsible security disclosure and Wordfence for forwarding the issue. Resolves CVE-2024-10543. 

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

== Frequently Asked Questions ==

= How do I use the Gutenberg block? =

After activating the plugin, go to the block editor and search for "Tumult Hype Animation" in the block library. Configure the block settings to select an animation and customize its dimensions.
/**
 * Options for configuring the block editor:
 * 
 * - **Width**: Specifies the width of the block. Accepts values in pixels (e.g., `400px`) or percentages (e.g., `75%`).
 * - **Height**: Specifies the height of the block. Accepts values in pixels (e.g., `px`) or percentages (e.g., `%`).
 * - **Responsive**: Enables scaling of the animation to fit the container dimensions, ensuring responsiveness.
 * - **Auto Height**: Automatically adjusts the height of the block based on the content's aspect ratio.
 */
 
= Can I still use shortcodes? =

Yes, the plugin fully supports the `[hypeanimations_anim]` shortcode for embedding animations. You can also transform shortcodes into blocks using the block editor. Additionally, to use the Gutenberg block, simply search for "Tumult Hype Animation" in the block library, select it, and configure its settings to choose an animation and customize its dimensions. This provides a more visual and user-friendly way to embed animations.

== Screenshots ==

1. The plugin displays a list of recently-uploaded animations with controls to update, delete, or copy the embed code to embed elsewhere.
2. Clicking 'Copy Code' in the actions list displays the full embed code to your uploaded Tumult Hype document for usage outside of the Wordpress loop or your Wordpress site.
