﻿=== Tumult Hype Animations ===
Author URI: http://www.tumult.com
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Contributors: tumultinc, freeben
Tags: Hype, Animation
Requires at least: 4.7
Requires PHP: 5.4
Tested up to: 4.8.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: Easily embed your Hype animations using a shortcode into posts and pages.

== Description ==

This plugin allows you to upload your Tumult Hype animations to your Wordpress site to easily embed them using shortcodes on posts and pages. You can also copy the embed code to use your Wordpress site as a Tumult Hype animation host.

**Français** Ce plugin vous permet de télécharger un fichier OAM à partir d'un Hype Tumult et d'intégrer facilement votre animation dans une publication ou une page en utilisant un code court. Insérez facilement vos animations Hype sur votre site Wordpress. Vous pouvez également copier facilement le code intégré pour utiliser votre site Wordpress en tant qu'homme d'animation Tumult Hype.

For detailed information about this plugin, please visit: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074

This plugin is a fork of Eralion's plugin 'Hype Animations' (https://wordpress.org/plugins/hype-animations/) and extends functionality.

== Installation ==

1. Download Tumult Hype Animations.zip from the "download" link on the web page where you're viewing this.
2. Decompress the file contents.
3. Upload the Tumult Hype Animations folder to your WordPress plugins directory (/wp-content/plugins/).
4. Activate the Tumult Hype Animations plugin from the WordPress plugin page.

**Français**

1. Téléchargez Tumult Hype Animations.zip sur le lien de téléchargement sur cette même page.
2. Décompressez l'archive téléchargée.
3. Mettez en ligne le dossier Tumult Hype dans votre dossier de plugins (/wp-content/plugins/).
4. Activez le plugin Tumult Hype Animations depuis les extentions.

== Usage ==

1. In Tumult Hype Professional, Select File > Export as HTML5 > OAM Widget. Note: Do not export with any spaces in your filename or foreign characters.
2. In the Hype Animations section in the Admin dashboard, click Upload New Animation and select your .oam file.
3. After successful upload, the plugin will generate a shortcode you can use in posts and pages.

You may also use the Hype Animations button on the post and page editor.

* **Français** : Ouvrez la section Hype Animations dans le tableau de bord Admin et téléchargez votre animation exportée au format .OAM (ne pas exporter avec des espaces ou des caractères étrangers dans votre nom d'exportation). Dans Tumult Hype, choisissez Fichier > Exporter en HTML5 > OAM. Le plugin va traiter votre fichier et générer un shortcode. Ensuite, insérez le shortcode. Vous pouvez également utiliser le bouton Hype Animations sur la page d'édition de post.

== Changelog ==

= 1.6 =
* Copy Embed Code from the Animation List
* Embeds are now protocol-agnostic for better SSL support (uses // instead of http://)
* Updated to work with Wordpress 4.8.3
* Maximum server-supported file size shown in upload modal (Adjust this limit by editing php.ini)
* Improved Translations
* Removed frames & borders on iframes
* This plugin was adapted from the work of Eralion: eralion.com.

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
