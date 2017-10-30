=== Tumult Hype Animations ===
Author URI: http://www.tumult.com
Plugin URI: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074
Contributors: tumultinc
Tags: Hype,Animations
Requires at least: 4.7
Tested up to: 4.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: Easily embed your Hype animations using a shortcode into posts and pages.

== Description ==

This plugin allows you to upload your Tumult Hype animations to your Wordpress site to easily embed them using shortcodes on posts and pages. You can also copy the embed code to use your Wordpress site as a Tumult Hype animation host. 

**Français** Ce plugin vous permet de télécharger un fichier OAM à partir d'un Hype Tumult et d'intégrer facilement votre animation dans une publication ou une page en utilisant un code court. Insérez facilement vos animations Hype sur votre site Wordpress. Vous pouvez également copier facilement le code intégré pour utiliser votre site Wordpress en tant qu'homme d'animation Tumult Hype. 

For more help, please visit this page: https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074

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

Open the Hype Animations section in the Admin dashboard and upload your animation exported in .OAM format (do not export with any spaces or foreign characters in your export name). In Tumult Hype, choose File > Export as HTML5 > OAM. The plugin will process your file and generate a shortcode. Next, insert the shortcode. You can also use the Hype Animations button on the post edit page. 

* **Français** : Ouvrez la section Hype Animations dans le tableau de bord Admin et téléchargez votre animation exportée au format .OAM (ne pas exporter avec des espaces ou des caractères étrangers dans votre nom d'exportation). Dans Tumult Hype, choisissez Fichier > Exporter en HTML5 > OAM. Le plugin va traiter votre fichier et générer un shortcode. Ensuite, insérez le shortcode. Vous pouvez également utiliser le bouton Hype Animations sur la page d'édition de post.


== Changelog ==

= 1.6 =
* Copy Embed Code from the Animation List
* Embeds are now protocol-agnostic for better SSL support (uses // instead of http://)
* Updated to work with WP 4.8.1
* Maximum server-supported file size shown in upload modal
* Improved Translations
* Removed frames & borders on iframes.
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
* **BUG FIX** Files with . caracter in filename works now.

= 1.0 =
* First public distribution version.
