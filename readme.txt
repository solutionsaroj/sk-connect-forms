=== SK Connect Forms ===
Contributors: khanalsaroj083
Tags: contact form, form builder, ajax form, custom form, email notifications
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 2.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, AJAX-powered WordPress contact form builder with a visual drag-and-drop editor, submission management, and email notifications.

== Description ==

**SK Connect Forms** is a powerful yet lightweight WordPress contact form plugin. It lets you build fully custom forms using a visual editor, embed them anywhere using shortcodes, and manage every submission from a clean admin dashboard — all without writing a single line of code.

**Key Features:**

* **Visual Form Builder** — Drag-and-drop fields including Text, Email, Textarea, Dropdown, Radio Buttons, and Checkboxes.
* **Shortcode Integration** — Embed any form on any page or post using `[sk_connect_form id="X"]`.
* **AJAX Submissions** — Forms submit without any page reload for a smooth user experience.
* **Submission Management** — View, mark as read, and delete entries from a dedicated admin dashboard.
* **CSV Export** — Export all submissions for any form as a CSV file with a single click.
* **PDF Export** — Download a PDF report of all submissions or a single submission.
* **Email Notifications** — Receive a beautifully formatted HTML email notification for every new submission.
* **Per-Form Settings** — Configure a custom success message, submit button label, and accent color for each form individually.
* **Secure by Default** — All AJAX handlers use WordPress nonces, capability checks, and prepared database statements.
* **No Page Bloat** — Frontend assets are loaded only on pages where a form shortcode is present.
* **Clean Uninstall** — All plugin data, database tables, and options are removed upon deletion.

== Installation ==

1. Upload the `sk-connect-forms` folder to the `/wp-content/plugins/` directory, or install it directly through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to the **SK Connect Forms** menu item in the admin panel.
4. Click **New Form** to open the form builder and add your desired fields.
5. Copy the shortcode shown in the Form Builder List (e.g., `[sk_connect_form id="1"]`).
6. Paste the shortcode into any page, post, or widget to display the form.

== Frequently Asked Questions ==

= How do I embed a form on my page? =

Use the shortcode `[sk_connect_form id="X"]` where `X` is the form ID displayed in the Form Builder List. You can add this shortcode to any page, post, or text widget.

= Can I customize the form colors? =

Yes. Each form has its own **Primary Theme Color** setting inside the form editor. The color is applied to the form border, labels, and the submit button, making it easy to match your site's brand.

= How do I export submissions? =

Go to **Inquiry Entries**, select your form from the dropdown, and then click the **Export CSV** button in the page header. A CSV file containing all submissions for that form will be downloaded immediately.

= What field types does the plugin support? =

SK Connect Forms supports the following field types: **Text**, **Email**, **Textarea**, **Dropdown Select**, **Checkboxes**, and **Radio Buttons**. Each field can be marked as required or optional.

= Where is my submission data stored? =

All submission data is stored securely in your WordPress database in a dedicated custom table (`wp_sk_connect_submissions`). No data is sent to any external server.

= Does the plugin work with page builders like Elementor or Divi? =

Yes. Because SK Connect Forms uses standard WordPress shortcodes, the `[sk_connect_form id="X"]` shortcode works in any page builder that supports shortcode rendering.

= Will the plugin data be deleted if I uninstall it? =

Yes. When you delete the plugin from the WordPress Plugins screen (not just deactivate it), all custom database tables and stored options are permanently removed for a clean uninstall.

== Screenshots ==

1. The main SK Connect Forms admin dashboard showing submission statistics.
2. The visual form builder editor with drag-and-drop field ordering.
3. The Inquiry Entries page showing submission cards with read/unread status.
4. A live contact form rendered on the frontend with custom accent color.

== Changelog ==

= 2.1.1 =
* Fix: Resolved all WordPress Plugin Check tool warnings for WordPress.org submission compliance.
* Fix: Normalized line endings (CRLF to LF) in all included FPDF library font files.
* Fix: Added direct file access protection (`ABSPATH` guard) to all FPDF font files.
* Fix: Updated `phpcs:disable`/`phpcs:enable` annotations on multi-line SQL queries to correctly suppress per-line sniff warnings.
* Improvement: Bumped `Tested up to` value to WordPress 7.0.

= 2.1.0 =
* Security: Added capability checks to all admin AJAX handlers.
* Security: Added JSON structure validation for form field configurations.
* Fix: Select dropdown fields now correctly respect the required attribute.
* Fix: Dashboard color picker no longer has duplicate name attributes.
* Fix: Corrected a duplicate label in the success message setting field.
* Improvement: Refactored submission queries into a single query builder.
* Improvement: Added a version constant for consistent asset versioning.
* Improvement: Extracted email notification builder into a reusable helper function.
* Improvement: Added accessibility enhancements (reduced motion support, focus-visible styles, keyboard navigation).
* Improvement: Added an unsaved changes warning in the form builder editor.
* Database: Added an `updated_at` column to the forms table.

= 2.0.0 =
* Upgraded to a dynamic visual form builder.
* Added support for custom option fields (Dropdowns, Checkboxes, Radio Buttons).

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.1.1 =
This release resolves all Plugin Check compliance warnings required for WordPress.org submission. No functional changes — safe to update.

= 2.1.0 =
This release includes important security improvements. All users are strongly encouraged to upgrade immediately.
