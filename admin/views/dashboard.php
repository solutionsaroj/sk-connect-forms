<?php
/**
 * Dashboard template view.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * @package SKConnectForms
 */

defined('ABSPATH') || exit;

global $wpdb;
$sk_connect_forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
$sk_connect_subs_table  = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

// Fetch global stats — these are real-time counters; caching would return stale counts.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sk_connect_total_unread = $wpdb->get_var("SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE is_read = 0");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sk_connect_total_all_time = $wpdb->get_var("SELECT COUNT(*) FROM {$sk_connect_subs_table}");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sk_connect_total_today = $wpdb->get_var("SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE DATE(submitted_at) = CURDATE()");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sk_connect_total_forms = $wpdb->get_var("SELECT COUNT(*) FROM {$sk_connect_forms_table}");

// Settings values (fallbacks for defaults when building new forms)
$sk_connect_recipient_email = get_option('sk_connect_forms_recipient_email', get_option('admin_email'));
$sk_connect_success_msg = get_option('sk_connect_forms_success_message', 'Thank you! Your message has been sent successfully.');
$sk_connect_primary_color = get_option('sk_connect_forms_primary_color', '#6366f1');

// Chart data generation (Submissions in last 7 days)
$sk_connect_chart_data = array();
for ($sk_connect_i = 6; $sk_connect_i >= 0; $sk_connect_i--) {
    $sk_connect_date = wp_date('Y-m-d', strtotime("-$sk_connect_i days"));
    $sk_connect_date_label = wp_date('D (d M)', strtotime("-$sk_connect_i days"));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $sk_connect_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sk_connect_subs_table} WHERE DATE(submitted_at) = %s", $sk_connect_date));
    $sk_connect_chart_data[] = array(
        'label' => $sk_connect_date_label,
        'count' => intval($sk_connect_count)
    );
}

// Fetch the latest 5 submissions overall — table names are escaped via esc_sql(); no user input.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sk_connect_latest_subs = $wpdb->get_results(
    "SELECT s.*, f.title as form_title
     FROM {$sk_connect_subs_table} s
     LEFT JOIN {$sk_connect_forms_table} f ON s.form_id = f.id
     ORDER BY s.submitted_at DESC
     LIMIT 5"
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
?>

<div class="sk-connect-admin-wrap">
    <!-- Header Banner -->
    <header class="sk-connect-header">
        <div class="sk-connect-header-title">
            <h1>SK Connect Forms Dashboard</h1>
            <p>Welcome! Review your submission metrics, manage custom forms, and adjust global builder defaults.</p>
        </div>
        <div class="sk-connect-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=editor')); ?>" class="button-csv" style="background:#ffffff; color:#1e1b4b;">
                <span>➕</span> Create New Form
            </a>
        </div>
    </header>

    <!-- Stats Section -->
    <div class="sk-connect-stats-grid">
        <div class="sk-connect-stat-card">
            <div class="sk-connect-stat-info">
                <h3>Total Forms</h3>
                <p class="sk-connect-stat-number"><?php echo esc_html($sk_connect_total_forms); ?></p>
            </div>
            <div class="sk-connect-stat-icon blue">📋</div>
        </div>
        <div class="sk-connect-stat-card">
            <div class="sk-connect-stat-info">
                <h3>Total Submissions</h3>
                <p class="sk-connect-stat-number"><?php echo esc_html($sk_connect_total_all_time); ?></p>
            </div>
            <div class="sk-connect-stat-icon blue">📬</div>
        </div>
        <div class="sk-connect-stat-card">
            <div class="sk-connect-stat-info">
                <h3>Unread Inquiries</h3>
                <p class="sk-connect-stat-number"><?php echo esc_html($sk_connect_total_unread); ?></p>
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

    <!-- Main Content Area: Left Chart/Analytics, Right Settings -->
    <div class="sk-connect-main-grid">
        <!-- Analytics Card -->
        <div class="sk-connect-card">
            <h2 class="sk-connect-card-title">Overall Inquiries Timeline (Last 7 Days)</h2>
            <div style="height: 250px; position: relative;">
                <canvas id="sk-connect-submissions-chart"></canvas>
            </div>
        </div>

        <!-- Default Builder Preferences Card -->
        <div class="sk-connect-card">
            <h2 class="sk-connect-card-title">New Form Defaults</h2>
            <form method="post" action="options.php" class="sk-connect-admin-settings-form">
                <?php settings_fields('sk_connect_forms_settings_group'); ?>
                
                <label for="sk_connect_forms_recipient_email">Default Recipient Email</label>
                <input type="email" id="sk_connect_forms_recipient_email" name="sk_connect_forms_recipient_email" value="<?php echo esc_attr($sk_connect_recipient_email); ?>" required>

                <label for="sk_connect_forms_success_message">Default Success Message</label>
                <input type="text" id="sk_connect_forms_success_message" name="sk_connect_forms_success_message" value="<?php echo esc_attr($sk_connect_success_msg); ?>" required>

                <label for="sk_connect_forms_primary_color">Default Accent/Primary Color</label>
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 16px;">
                    <input type="color" id="sk_connect_forms_primary_color" name="sk_connect_forms_primary_color" value="<?php echo esc_attr($sk_connect_primary_color); ?>" style="border: none; width: 44px; height: 44px; padding: 0; cursor: pointer; border-radius: 8px;">
                    <input type="text" id="sk_connect_forms_primary_color_text" value="<?php echo esc_attr($sk_connect_primary_color); ?>" style="margin-bottom: 0; max-width: 120px;">
                </div>

                <?php submit_button('Save Defaults', 'primary', 'submit', false); ?>
            </form>
        </div>
    </div>

    <!-- Quick Overview Panel: Latest Submissions across all forms -->
    <div class="sk-connect-card" style="margin-top: 24px; padding:0; overflow:hidden;">
        <div style="padding: 24px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <h2 class="sk-connect-card-title" style="margin:0;">Recent Activity</h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=submissions')); ?>" style="color: #6366f1; text-decoration:none; font-weight:600; font-size:13px;">View All Entries &rarr;</a>
        </div>
        <table class="sk-connect-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Form Name</th>
                    <th>Data Summary</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sk_connect_latest_subs)) : ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 32px; color: #64748b;">
                            No submission entries found. Create a form and embed its shortcode to receive submissions.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sk_connect_latest_subs as $sk_connect_sub) : 
                        $sk_connect_payload = json_decode($sk_connect_sub->submission_data, true);
                        if (!is_array($sk_connect_payload)) {
                            $sk_connect_payload = array();
                        }
                        
                        // Pick the first key-value of submission data to display as summary
                        $sk_connect_first_val = !empty($sk_connect_payload) ? reset($sk_connect_payload) : '-';
                        if (is_array($sk_connect_first_val)) {
                            $sk_connect_first_val = implode(', ', $sk_connect_first_val);
                        }
                        $sk_connect_summary = strlen($sk_connect_first_val) > 50 ? substr($sk_connect_first_val, 0, 50) . '...' : $sk_connect_first_val;
                        ?>
                        <tr>
                            <td>
                                <span class="sk-connect-badge <?php echo $sk_connect_sub->is_read ? 'read' : 'unread'; ?>">
                                    <?php echo $sk_connect_sub->is_read ? 'Read' : 'Unread'; ?>
                                </span>
                            </td>
                            <td style="font-weight:600; color:#0f172a;"><?php echo esc_html($sk_connect_sub->form_title ? $sk_connect_sub->form_title : 'Deleted Form'); ?></td>
                            <td><em><?php echo esc_html($sk_connect_summary); ?></em></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sk_connect_sub->submitted_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Securing chart data variables -->
<script type="text/javascript">
    const skConnectChartData = <?php echo wp_json_encode($sk_connect_chart_data); ?>;
</script>
