<?php
/**
 * View template: Submissions grid filtered by Form.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * @package SKConnectForms
 */

defined('ABSPATH') || exit;

global $wpdb;
$sk_connect_forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
$sk_connect_subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_forms_list = $wpdb->get_results("SELECT id, title FROM {$sk_connect_forms_table} ORDER BY title ASC"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

if (empty($sk_connect_forms_list)) {
    echo '<div class="sk-connect-admin-wrap"><div class="sk-connect-card" style="padding:48px; text-align:center;"><p>No forms created yet. Please create a form first.</p></div></div>';
    return;
}

// Select active form filter
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_connect_selected_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : intval($sk_connect_forms_list[0]->id);

// Fetch active form details
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_active_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sk_connect_forms_table} WHERE id = %d", $sk_connect_selected_form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
if (!$sk_connect_active_form) {
    $sk_connect_selected_form_id = intval($sk_connect_forms_list[0]->id);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $sk_connect_active_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sk_connect_forms_table} WHERE id = %d", $sk_connect_selected_form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$sk_connect_form_fields = json_decode($sk_connect_active_form->fields, true);
if (!is_array($sk_connect_form_fields)) {
    $sk_connect_form_fields = array();
}

// Submissions pagination
$sk_connect_limit = 10;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_connect_current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$sk_connect_offset = ($sk_connect_current_page - 1) * $sk_connect_limit;

// Filter and search
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_connect_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_connect_status_filter = isset($_GET['status_filter']) ? sanitize_text_field(wp_unslash($_GET['status_filter'])) : 'all';

// Build dynamic query with filters
$sk_connect_where_parts = array();
$sk_connect_where_values = array( $sk_connect_selected_form_id );

$sk_connect_base_where = 'form_id = %d';

if ( ! empty( $sk_connect_search ) ) {
    $sk_connect_base_where .= ' AND submission_data LIKE %s';
    $sk_connect_where_values[] = '%' . $wpdb->esc_like( $sk_connect_search ) . '%';
}

if ( $sk_connect_status_filter === 'read' ) {
    $sk_connect_base_where .= ' AND is_read = 1';
} elseif ( $sk_connect_status_filter === 'unread' ) {
    $sk_connect_base_where .= ' AND is_read = 0';
}

// Count query
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_total_subs_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE {$sk_connect_base_where}",
        ...$sk_connect_where_values
    )
);

// Results query
$sk_connect_page_values = array_merge( $sk_connect_where_values, array( $sk_connect_limit, $sk_connect_offset ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_submissions = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->prepare(
        "SELECT * FROM {$sk_connect_subs_table} WHERE {$sk_connect_base_where} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
        ...$sk_connect_page_values
    )
);

$sk_connect_num_pages = ceil($sk_connect_total_subs_count / $sk_connect_limit);

// Get stats for this form
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_total_all_time = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE form_id = %d", $sk_connect_selected_form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_total_unread = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE form_id = %d AND is_read = 0", $sk_connect_selected_form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$sk_connect_total_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE form_id = %d AND DATE(submitted_at) = CURDATE()", $sk_connect_selected_form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Identify the first 3 fields of the form to display in table columns
$sk_connect_display_fields = array_slice($sk_connect_form_fields, 0, 3);
?>

<div class="sk-connect-admin-wrap">
    <!-- Header Banner -->
    <header class="sk-connect-header">
        <div class="sk-connect-header-title">
            <h1>Inquiry Entries</h1>
            <p>Review customer submissions for form: <strong><?php echo esc_html($sk_connect_active_form->title); ?></strong>.</p>
        </div>
        <div class="sk-connect-header-actions" style="display: flex; gap: 12px; align-items: center;">
            <!-- Form selector filter dropdown -->
            <form method="get" id="form-selector-form" style="margin:0;">
                <input type="hidden" name="page" value="sk-connect-forms">
                <input type="hidden" name="view" value="submissions">
                <select name="form_id" onchange="document.getElementById('form-selector-form').submit();" style="padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.15); color: #fff; font-family: inherit; font-size: 13px; font-weight:600; cursor: pointer; outline:none;">
                    <?php foreach ($sk_connect_forms_list as $sk_connect_f) : ?>
                        <option value="<?php echo esc_attr($sk_connect_f->id); ?>" <?php selected($sk_connect_selected_form_id, $sk_connect_f->id); ?> style="color:#1e293b;"><?php echo esc_html($sk_connect_f->title); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sk_connect_export_submissions_csv&form_id=' . $sk_connect_selected_form_id . '&security=' . wp_create_nonce('sk_connect_export_csv'))); ?>" class="button-csv">
                <span>📥</span> Export CSV
            </a>
            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sk_connect_export_submissions_pdf&form_id=' . $sk_connect_selected_form_id . '&security=' . wp_create_nonce('sk_connect_export_csv'))); ?>" class="button-csv" style="background:#ef4444; color:#fff; border-color:#dc2626;">
                <span>📄</span> Export PDF
            </a>
        </div>
    </header>

    <!-- Stats Cards Grid -->
    <div class="sk-connect-stats-grid">
        <div class="sk-connect-stat-card">
            <div class="sk-connect-stat-info">
                <h3>Total Submissions</h3>
                <p class="sk-connect-stat-number" id="sk-connect-stat-total"><?php echo esc_html($sk_connect_total_all_time); ?></p>
            </div>
            <div class="sk-connect-stat-icon blue">📬</div>
        </div>
        <div class="sk-connect-stat-card">
            <div class="sk-connect-stat-info">
                <h3>Unread entries</h3>
                <p class="sk-connect-stat-number" id="sk-connect-stat-unread"><?php echo esc_html($sk_connect_total_unread); ?></p>
            </div>
            <div class="sk-connect-stat-icon orange">✉️</div>
        </div>
        <div class="sk-connect-stat-card">
            <div class="sk-connect-stat-info">
                <h3>Received Today</h3>
                <p class="sk-connect-stat-number"><?php echo esc_html($sk_connect_total_today); ?></p>
            </div>
            <div class="sk-connect-stat-icon green">⚡</div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="sk-connect-table-controls">
        <form method="get" class="sk-connect-table-search">
            <input type="hidden" name="page" value="sk-connect-forms">
            <input type="hidden" name="view" value="submissions">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($sk_connect_selected_form_id); ?>">
            <input type="search" name="s" placeholder="Search inquiries..." value="<?php echo esc_attr($sk_connect_search); ?>">
        </form>

        <div class="sk-connect-table-filters">
            <form method="get" id="filter-form">
                <input type="hidden" name="page" value="sk-connect-forms">
                <input type="hidden" name="view" value="submissions">
                <input type="hidden" name="form_id" value="<?php echo esc_attr($sk_connect_selected_form_id); ?>">
                <?php if (!empty($sk_connect_search)) : ?>
                    <input type="hidden" name="s" value="<?php echo esc_attr($sk_connect_search); ?>">
                <?php endif; ?>
                <select name="status_filter" onchange="document.getElementById('filter-form').submit();">
                    <option value="all" <?php selected($sk_connect_status_filter, 'all'); ?>>All Statuses</option>
                    <option value="unread" <?php selected($sk_connect_status_filter, 'unread'); ?>>Unread</option>
                    <option value="read" <?php selected($sk_connect_status_filter, 'read'); ?>>Read</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Table Container -->
    <div class="sk-connect-table-container">
        <table class="sk-connect-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <!-- Dynamic Headers from Form Fields config -->
                    <?php foreach ($sk_connect_display_fields as $sk_connect_field) : ?>
                        <th><?php echo esc_html($sk_connect_field['label']); ?></th>
                    <?php endforeach; ?>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sk_connect_submissions)) : ?>
                    <tr>
                        <td colspan="<?php echo count($sk_connect_display_fields) + 3; ?>" style="text-align: center; padding: 32px; color: #64748b;">
                            No entries found for this form.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sk_connect_submissions as $sk_connect_sub) : 
                        $sk_connect_sub_data = json_decode($sk_connect_sub->submission_data, true);
                        if (!is_array($sk_connect_sub_data)) {
                            $sk_connect_sub_data = array();
                        }
                        ?>
                        <tr id="sk-connect-row-<?php echo esc_attr($sk_connect_sub->id); ?>" class="<?php echo $sk_connect_sub->is_read ? '' : 'sk-connect-row-unread'; ?>">
                            <td>
                                <span class="sk-connect-badge <?php echo $sk_connect_sub->is_read ? 'read' : 'unread'; ?>">
                                    <?php echo $sk_connect_sub->is_read ? 'Read' : 'Unread'; ?>
                                </span>
                            </td>
                            
                            <!-- Dynamic Table Rows -->
                            <?php foreach ($sk_connect_display_fields as $sk_connect_field) : 
                                $sk_connect_val = isset($sk_connect_sub_data[$sk_connect_field['id']]) ? $sk_connect_sub_data[$sk_connect_field['id']] : '-';
                                if (is_array($sk_connect_val)) {
                                    $sk_connect_val = implode(', ', $sk_connect_val);
                                }
                                $sk_connect_truncated = strlen($sk_connect_val) > 40 ? substr($sk_connect_val, 0, 40) . '...' : $sk_connect_val;
                                ?>
                                <td><?php echo esc_html($sk_connect_truncated); ?></td>
                            <?php endforeach; ?>

                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sk_connect_sub->submitted_at))); ?></td>
                            
                            <td style="text-align: right; white-space: nowrap;">
                                <button class="sk-connect-action-btn view sk-connect-js-view-sub" 
                                        data-id="<?php echo esc_attr($sk_connect_sub->id); ?>"
                                        data-date="<?php echo esc_attr($sk_connect_sub->submitted_at); ?>"
                                        data-read="<?php echo esc_attr($sk_connect_sub->is_read); ?>"
                                        data-payload="<?php echo esc_attr(wp_json_encode($sk_connect_sub_data)); ?>"
                                        title="View Details">👁️</button>
                                
                                <button class="sk-connect-action-btn toggle sk-connect-js-toggle-read" 
                                        data-id="<?php echo esc_attr($sk_connect_sub->id); ?>" 
                                        title="Mark as <?php echo $sk_connect_sub->is_read ? 'Unread' : 'Read'; ?>">
                                    <?php echo $sk_connect_sub->is_read ? '📬' : '📖'; ?>
                                </button>
                                
                                <button class="sk-connect-action-btn delete sk-connect-js-delete" 
                                        data-id="<?php echo esc_attr($sk_connect_sub->id); ?>" 
                                        title="Delete Entry">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Table Pagination -->
        <?php if ($sk_connect_num_pages > 1) : ?>
            <div class="sk-connect-pagination">
                <div class="sk-connect-pagination-info">
                    Showing <?php echo esc_html($sk_connect_offset + 1); ?> to <?php echo esc_html(min($sk_connect_offset + $sk_connect_limit, $sk_connect_total_subs_count)); ?> of <?php echo esc_html($sk_connect_total_subs_count); ?> items
                </div>
                <div class="sk-connect-pagination-links">
                    <?php
                    echo wp_kses_post(paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => '&laquo; Prev',
                        'next_text' => 'Next &raquo;',
                        'total'     => $sk_connect_num_pages,
                        'current'   => $sk_connect_current_page,
                        'type'      => 'plain',
                    )));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal view (dynamically populated based on the form's config) -->
    <div class="sk-connect-modal-overlay" id="sk-connect-details-modal">
        <div class="sk-connect-modal">
            <div class="sk-connect-modal-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0;">Dynamic Submission Details</h3>
                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="#" id="sk-connect-modal-export-pdf" class="button-csv" style="background:#ef4444; color:#fff; border-color:#dc2626; padding:4px 10px; font-size:12px; border-radius:6px; text-decoration:none;">
                        <span>📄</span> Export PDF
                    </a>
                    <button class="sk-connect-modal-close" id="sk-connect-close-modal" style="position:static; margin-left:10px;">&times;</button>
                </div>
            </div>
            <div class="sk-connect-modal-body" style="max-height: 450px; overflow-y: auto;">
                <div class="sk-connect-modal-meta-row" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
                    <div class="sk-connect-meta-item">
                        <label>Submission Date</label>
                        <span id="sk-connect-modal-date">-</span>
                    </div>
                </div>
                <div style="border-top: 1px solid #f1f5f9; padding-top: 16px; margin-top: 12px;" id="sk-connect-modal-payload-list">
                    <!-- Javascript will render all key -> value pairs here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notice -->
    <div class="sk-connect-toast" id="sk-connect-toast">
        <span class="sk-connect-toast-icon">✓</span>
        <span class="sk-connect-toast-message">Action processed successfully</span>
    </div>
</div>

<!-- Send Form Fields layout dynamically to client JS modal handler -->
<script type="text/javascript">
    const skConnectFormFieldsConfig = <?php echo wp_json_encode($sk_connect_form_fields); ?>;
</script>
