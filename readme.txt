=== CIT Media Sync ===
Contributors: Ooypunk
Donate link: http://www.collectief-it.nl/
Tags: media, media library, sync, attach, synchronization
Requires at least: 2.9
Tested up to: 3.5
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to add files to the Media Library that were previously uploaded by FTP.

== Description ==

Once upon a time there was a website in the auld style, with imagery in an images directory. But then it was said that 
the website should be built anew, but in WordPress, and the images were transported by FTP to the new structure. But 
the new pages and posts needed images, but the images were not in the Library.
This plugin scans the upload directory for images that are not yet in the Library, and provides links for each one to 
be included.


== Installation ==

1. Upload the `cit_media_sync` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why doesn't this plugin have function X? =

Suggestions are welcome here: http://wordpress.org/support/plugin/cit-media-sync

== Changelog ==

= 1.1 =
* Fixed some errors
* Added some checks, to see if the uploads directory is readable and writable

= 1.0 =
* Initial import

== Upgrade Notice ==

