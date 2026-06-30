<?php
/**
 * View template: List of created forms.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * @package SKConnectForms
 */

defined('ABSPATH') || exit;

global $wpdb;
$sk_connect_forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
$sk_connect_subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

// Fetch all forms with submission counts — table names are escaped via esc_sql(); no user input interpolated.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sk_connect_forms = $wpdb->get_results(
    "SELECT f.id, f.title, f.created_at, COUNT(s.id) as count_subs 
     FROM {$sk_connect_forms_table} f 
     LEFT JOIN {$sk_connect_subs_table} s ON f.id = s.form_id 
     GROUP BY f.id 
     ORDER BY f.created_at DESC"
);
?>

<div class="sk-connect-admin-wrap">
    <!-- Header Banner -->
    <header class="sk-connect-header">
        <div class="sk-connect-header-title">
            <h1>Form Builder List</h1>
            <p>Manage your custom forms, configure input fields, and copy shortcodes to place them anywhere.</p>
        </div>
        <div class="sk-connect-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=editor')); ?>" class="button-csv" style="background:#ffffff; color:#1e1b4b;">
                <span>➕</span> Create New Form
            </a>
        </div>
    </header>

    <!-- Table Container -->
    <div class="sk-connect-table-container" style="border-radius:18px;">
        <table class="sk-connect-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Form Name</th>
                    <th>Submissions</th>
                    <th>Shortcode</th>
                    <th>Created At</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sk_connect_forms)) : ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 48px; color: #64748b;">
                            No forms created yet. Click <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=editor')); ?>" style="color: #6366f1; text-decoration: underline;">Create New Form</a> to build your first custom options form!
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sk_connect_forms as $sk_connect_form) : ?>
                        <tr id="sk-connect-form-row-<?php echo esc_attr($sk_connect_form->id); ?>">
                            <td><strong>#<?php echo esc_html($sk_connect_form->id); ?></strong></td>
                            <td style="font-weight: 600; color: #0f172a;"><?php echo esc_html($sk_connect_form->title); ?></td>
                            <td>
                                <span class="sk-connect-badge read" style="font-weight:600;">
                                    <?php echo esc_html($sk_connect_form->count_subs); ?> entries
                                </span>
                            </td>
                            <td>
                                <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; color: #4f46e5; font-size: 12px; border: 1px solid #cbd5e1; user-select: all; cursor: pointer;" title="Click to select all">
                                    [sk_connect_form id="<?php echo esc_attr($sk_connect_form->id); ?>"]
                                </code>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sk_connect_form->created_at))); ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=submissions&form_id=' . $sk_connect_form->id)); ?>" 
                                   class="sk-connect-action-btn view" 
                                   title="View Submissions" 
                                   style="text-decoration: none; margin-right: 8px;">📊 Entries</a>

                                <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=editor&id=' . $sk_connect_form->id)); ?>" 
                                   class="sk-connect-action-btn view" 
                                   title="Edit Form" 
                                   style="text-decoration: none; margin-right: 8px;">✏️ Edit</a>
                                
                                <button class="sk-connect-action-btn delete sk-connect-js-delete-form" 
                                        data-id="<?php echo esc_attr($sk_connect_form->id); ?>" 
                                        title="Delete Form">🗑️ Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
