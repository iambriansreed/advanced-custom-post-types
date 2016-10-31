=== Advanced Custom Post Types ===
Contributors: iambriansreed
Tags: acf, advanced, custom, field, fields, custom field, custom fields, simple fields, magic fields, more fields, edit, content types, post types, types, content
Requires at least: 3.6.0
Tested up to: 4.6.1
License: GPLv2 or later
Stable tag: trunk

Customise WordPress with powerful, professional and intuitive post types.

== Description ==

Advanced Custom Post Types is the perfect solution for any WordPress website which needs more content types like other Content Management Systems. No additional plugins required!

== Filters ==

**acpt/settings/show_admin** - Determines whether or not the admin menu is shown.

**acpt/settings/capability** - Determines capability is needed to manage custom post types.

**acpt/settings/save_json** - The path to save post types locally. 

== Actions ==

**acpt/init** - Fires before acpt has started loading.

== Frequently Asked Questions ==

None

== Upgrade Notice ==

No special upgrade instructions are needed at this time.

== Screenshots ==

Coming soon!

== Installation ==

1. Upload 'advanced-custom-post-types' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 0.0.1 =
* Lets do this.

= 0.0.2 =
* Refactoring, centralized settings, added filters and action hooks, removed stated support for less than ACF v5

= 0.1.0 =
* Refactored and removed all ACF dependency

= 0.1.1 =
* Fixed advanced tabs toggling, fixed rewrite url, fixed API url, removed redundant taxonomies

= 0.2.0 =
* Massive refactoring and cleanup, added export

= 0.3.0 =
* Added the Local JSON feature which saves post type settings to files within your theme. The idea is similar to caching and both dramatically speeds up ACPT and allows for version control over your post type settings. Removed unused filters and added the save_json filter to determine where post type setting files are saved.

= 0.4.0 =
* Fixes post name issues more refactoring and cleanup, added title placeholder

= 0.4.5 =
* Fixes At a Glance dashboard issues

= 0.5.0 =
* Fixes SVN merge issues, notices updates, adds warning for legacy post type data

= 0.5.5 =
* Fixes post_updated_messages bug

