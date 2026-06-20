=== SK Connect Forms ===
Contributors: Antigravity
Tags: contact form, form builder, custom form, ajax form, forms
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 2.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, AJAX-powered WordPress form builder plugin featuring dynamic field creation and an interactive analytics dashboard.

== Description ==

SK Connect Forms lets you easily build custom forms using various field types (Text, Email, Textarea, Select Dropdowns, Radio buttons, and Checkboxes), place them anywhere using shortcodes, and review entries dynamically in the admin panel.

== Frequently Asked Questions ==

= How do I embed a form on my page? =

Use the shortcode `[sk_connect_form id="X"]` where X is the form ID shown in the Form Builder List.

= Can I customize the form colors? =

Yes! Each form has its own Primary Theme Color setting in the form editor. You can also set global defaults from the Dashboard.

= How do I export submissions? =

Go to Inquiry Entries, select your form, and click the "Export CSV" button in the header.

= What field types are supported? =

SK Connect Forms supports: Text, Email, Textarea, Dropdown Select, Checkboxes, and Radio Buttons.

== Installation ==

1. Upload the `sk-connect-forms` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the 'SK Connect Forms' menu in the admin panel to build custom forms.
4. Copy the shortcode `[sk_connect_form id="X"]` and paste it on any page or post.

== Changelog ==

= 2.1.0 =
* Security: Added capability checks to all admin AJAX handlers.
* Security: Added JSON structure validation for form field configurations.
* Fix: Select dropdown fields now correctly respect the required attribute.
* Fix: Dashboard color picker no longer has duplicate name attributes.
* Fix: Typo "Success Success Message" corrected to "Success Message".
* Improvement: Refactored submission queries into a single query builder.
* Improvement: Added version constant for consistent asset versioning.
* Improvement: Extracted email notification builder into reusable helper.
* Improvement: Added accessibility enhancements (reduced motion, focus-visible, keyboard nav).
* Improvement: Added unsaved changes warning in form builder.
* Database: Added updated_at column to forms table.

= 2.0.0 =
* Upgraded to dynamic visual form builder.
* Added custom option fields.

= 1.0.0 =
* Initial release.
