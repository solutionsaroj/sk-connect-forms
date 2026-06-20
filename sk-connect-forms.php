<?php
/**
 * Plugin Name: SK Connect Forms
 * Description: A premium, AJAX-powered contact form builder plugin featuring custom options (text, dropdowns, checkboxes, radios) and an interactive analytics dashboard.
 * Version: 2.1.0
 * Author: khanalsaroj083
 * Author URI: https://sarojkhanal.com
 * Text Domain: sk-connect-forms
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SKConnectForms
 */

defined('ABSPATH') || exit;

define('SK_CONNECT_FORMS_PATH', plugin_dir_path(__FILE__));
define('SK_CONNECT_FORMS_URL', plugin_dir_url(__FILE__));
define('SK_CONNECT_FORMS_VERSION', '2.1.0');

/**
 * Get a prefixed and escaped table name.
 *
 * @param string $table The table key ('forms' or 'submissions').
 * @return string|false The escaped table name, or false if invalid.
 */
function sk_connect_forms_get_table( $table ) {
    global $wpdb;
    $allowed = array( 'forms' => 'sk_connect_forms', 'submissions' => 'sk_connect_submissions' );
    if ( ! isset( $allowed[ $table ] ) ) {
        return false;
    }
    return esc_sql( $wpdb->prefix . $allowed[ $table ] );
}

// Require database installation file
require_once SK_CONNECT_FORMS_PATH . 'includes/database.php';

// Hook activation to create/update tables
register_activation_hook(__FILE__, 'sk_connect_forms_create_db');

/**
 * Check if the database needs to be created or upgraded on plugin load.
 * This ensures tables are created even if the plugin was copied manually without triggering the activation hook.
 */
add_action('plugins_loaded', 'sk_connect_forms_check_db_version');
function sk_connect_forms_check_db_version() {
    if (get_option('sk_connect_forms_db_version') !== SK_CONNECT_FORMS_VERSION) {
        sk_connect_forms_create_db();
    }
}

/**
 * Register settings on admin initialization.
 */
add_action('admin_init', 'sk_connect_forms_register_settings');
function sk_connect_forms_register_settings() {
    register_setting('sk_connect_forms_settings_group', 'sk_connect_forms_recipient_email', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => get_option('admin_email')
    ));
    register_setting('sk_connect_forms_settings_group', 'sk_connect_forms_success_message', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'Thank you! Your message has been sent successfully.'
    ));
    register_setting('sk_connect_forms_settings_group', 'sk_connect_forms_primary_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#6366f1'
    ));
}

/**
 * Add WordPress Dashboard Menu Item.
 */
add_action('admin_menu', 'sk_connect_forms_add_menu');
function sk_connect_forms_add_menu() {
    add_menu_page(
        'SK Connect Forms',
        'SK Connect Forms',
        'manage_options',
        'sk-connect-forms',
        'sk_connect_forms_render_admin_layout',
        'dashicons-feedback',
        30
    );
}

/**
 * Render the Admin Dashboard Main Layout (Includes Navigation Tabs).
 */
function sk_connect_forms_render_admin_layout() {
    $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    
    echo '<div class="wrap" style="margin-top:20px;">';
    
    // Output Pill Navigation Bar
    ?>
    <nav class="sk-connect-nav-bar">
        <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms')); ?>" class="sk-connect-nav-link <?php echo $view === 'dashboard' ? 'active' : ''; ?>">
            📊 General Dashboard
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=form-list')); ?>" class="sk-connect-nav-link <?php echo in_array($view, array('form-list', 'editor')) ? 'active' : ''; ?>">
            📋 Form Builder List
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=submissions')); ?>" class="sk-connect-nav-link <?php echo $view === 'submissions' ? 'active' : ''; ?>">
            📥 Inquiry Entries
        </a>
    </nav>
    <?php

    // Route templates
    switch ($view) {
        case 'form-list':
            require_once SK_CONNECT_FORMS_PATH . 'admin/views/form-list.php';
            break;
        case 'editor':
            require_once SK_CONNECT_FORMS_PATH . 'admin/views/form-editor.php';
            break;
        case 'submissions':
            require_once SK_CONNECT_FORMS_PATH . 'admin/views/submissions.php';
            break;
        case 'dashboard':
        default:
            require_once SK_CONNECT_FORMS_PATH . 'admin/views/dashboard.php';
            break;
    }
    
    echo '</div>';
}

/**
 * Enqueue Frontend Assets.
 */
add_action('wp_enqueue_scripts', 'sk_connect_forms_enqueue_public_assets');
function sk_connect_forms_enqueue_public_assets() {
    wp_enqueue_style('sk-connect-public-style', SK_CONNECT_FORMS_URL . 'public/css/public-style.css', array(), SK_CONNECT_FORMS_VERSION);
    wp_enqueue_script('sk-connect-public-script', SK_CONNECT_FORMS_URL . 'public/js/public-script.js', array(), SK_CONNECT_FORMS_VERSION, true);

    wp_localize_script('sk-connect-public-script', 'sk_connect_form_obj', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('sk_connect_form_submit_nonce')
    ));
}

/**
 * Enqueue Admin Assets.
 */
add_action('admin_enqueue_scripts', 'sk_connect_forms_enqueue_admin_assets');
function sk_connect_forms_enqueue_admin_assets($hook) {
    if ($hook !== 'toplevel_page_sk-connect-forms') {
        return;
    }

    // Load local Chart.js to avoid offloaded scripts block
    wp_enqueue_script('chart-js', SK_CONNECT_FORMS_URL . 'admin/js/chart.min.js', array(), '3.9.1', false);

    wp_enqueue_style('sk-connect-admin-style', SK_CONNECT_FORMS_URL . 'admin/css/admin-style.css', array(), SK_CONNECT_FORMS_VERSION);
    wp_enqueue_script('sk-connect-admin-script', SK_CONNECT_FORMS_URL . 'admin/js/admin-script.js', array('chart-js'), SK_CONNECT_FORMS_VERSION, true);

    // If editor subpage is loaded, enqueue form-builder.js
    $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($view === 'editor') {
        wp_enqueue_script('sk-connect-form-builder', SK_CONNECT_FORMS_URL . 'admin/js/form-builder.js', array(), SK_CONNECT_FORMS_VERSION, true);
    }

    wp_localize_script('sk-connect-admin-script', 'sk_connect_admin_obj', array(
        'ajax_url'      => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('sk_connect_admin_nonce'),
        'form_list_url' => admin_url('admin.php?page=sk-connect-forms&view=form-list'),
        'admin_email'   => get_option('admin_email')
    ));

    if ($view === 'editor') {
        $form_id = isset($_GET['id']) ? intval($_GET['id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $form_fields = '[]';
        $form_settings = '{}';
        if ($form_id > 0) {
            global $wpdb;
            $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $form_row = $wpdb->get_row($wpdb->prepare("SELECT fields, settings FROM {$forms_table} WHERE id = %d", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ($form_row) {
                $form_fields = $form_row->fields;
                $form_settings = $form_row->settings;
            }
        }

        wp_localize_script('sk-connect-form-builder', 'sk_connect_builder_data', array(
            'form_id'  => $form_id,
            'fields'   => json_decode($form_fields, true),
            'settings' => json_decode($form_settings, true)
        ));
    }
}

/**
 * Shortcode callback: [sk_connect_form id="X"]
 */
add_shortcode('sk_connect_form', 'sk_connect_forms_shortcode_callback');
function sk_connect_forms_shortcode_callback($atts) {
    $args = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    $form_id = intval($args['id']);
    if ($form_id <= 0) {
        return '<p style="color:red;">Error: Form ID is missing or invalid.</p>';
    }

    global $wpdb;
    $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $form_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$forms_table} WHERE id = %d", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    if (!$form_row) {
        return '<p style="color:red;">Error: Form template not found.</p>';
    }

    $fields = json_decode($form_row->fields, true);
    $settings = json_decode($form_row->settings, true);

    if (!is_array($fields) || empty($fields)) {
        return '<p style="color:orange;">Error: This form has no fields configured yet.</p>';
    }

    // Settings overrides
    $success_msg = isset($settings['success_message']) ? $settings['success_message'] : 'Message sent successfully.';
    $submit_lbl = isset($settings['submit_label']) ? $settings['submit_label'] : 'Send Message';
    $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#6366f1';
    
    // Inject Custom primary colors dynamically inside form shortcode header
    $primary_rgb = sk_connect_forms_hex2rgb($primary_color);
    $hover_color = sk_connect_forms_adjust_brightness($primary_color, -20);
    
    ob_start();
    ?>
    <style>
        .sk-connect-form-wrapper-<?php echo intval($form_id); ?> {
            --sk-connect-primary: <?php echo esc_html($primary_color); ?>;
            --sk-connect-primary-hover: <?php echo esc_html($hover_color); ?>;
            --sk-connect-primary-glow: rgba(<?php echo esc_html($primary_rgb); ?>, 0.25);
        }
    </style>
    
    <div class="sk-connect-form-wrapper sk-connect-form-wrapper-<?php echo intval($form_id); ?>">
        <!-- Form Fields -->
        <div class="sk-connect-form-body-wrapper">
            <h2 class="sk-connect-form-title"><?php echo esc_html($form_row->title); ?></h2>
            <p class="sk-connect-form-subtitle">Please complete the fields below to send your inquiry.</p>
            
            <form class="sk-connect-contact-form" autocomplete="off" data-form-id="<?php echo intval($form_id); ?>">
                <?php foreach ($fields as $field) : 
                    $req_star = isset($field['required']) && $field['required'] ? 'required' : '';
                    ?>
                    
                    <?php if (in_array($field['type'], array('text', 'email', 'textarea', 'tel', 'number', 'url', 'date'))) : ?>
                        <div class="sk-connect-form-group">
                            <?php if ($field['type'] === 'textarea') : ?>
                                <textarea id="sk-connect-<?php echo esc_attr($field['id']); ?>" 
                                          name="<?php echo esc_attr($field['id']); ?>" 
                                          class="sk-connect-form-input" 
                                          placeholder=" " <?php echo esc_attr($req_star); ?>></textarea>
                            <?php else : ?>
                                <input type="<?php echo esc_attr($field['type']); ?>" 
                                       id="sk-connect-<?php echo esc_attr($field['id']); ?>" 
                                       name="<?php echo esc_attr($field['id']); ?>" 
                                       class="sk-connect-form-input" 
                                       placeholder=" " <?php echo esc_attr($req_star); ?>>
                            <?php endif; ?>
                            <label for="sk-connect-<?php echo esc_attr($field['id']); ?>" class="sk-connect-form-label"><?php echo esc_html($field['label']); ?></label>
                            <div class="sk-connect-error-message"></div>
                        </div>

                    <?php elseif ($field['type'] === 'select') : ?>
                        <div class="sk-connect-form-group">
                            <select id="sk-connect-<?php echo esc_attr($field['id']); ?>" 
                                    name="<?php echo esc_attr($field['id']); ?>" 
                                    class="sk-connect-form-input" <?php echo esc_attr($req_star); ?>>
                                <option value="" disabled selected hidden></option>
                                <?php 
                                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : array();
                                foreach ($options as $opt) : ?>
                                    <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="sk-connect-<?php echo esc_attr($field['id']); ?>" class="sk-connect-form-label"><?php echo esc_html($field['label']); ?></label>
                            <div class="sk-connect-error-message"></div>
                        </div>

                    <?php elseif (in_array($field['type'], array('checkbox', 'radio'))) : ?>
                        <div class="sk-connect-form-group-options" data-required="<?php echo isset($field['required']) && $field['required'] ? '1' : '0'; ?>">
                            <span class="sk-connect-group-legend"><?php echo esc_html($field['label']); ?> <?php echo isset($field['required']) && $field['required'] ? '<span style="color:#ef4444;">*</span>' : ''; ?></span>
                            
                            <div class="sk-connect-options-container">
                                <?php 
                                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : array();
                                foreach ($options as $opt) : 
                                    $input_name = $field['type'] === 'checkbox' ? $field['id'] . '[]' : $field['id'];
                                    ?>
                                    <label class="sk-connect-option-label">
                                        <input type="<?php echo esc_attr($field['type']); ?>" 
                                               name="<?php echo esc_attr($input_name); ?>" 
                                               value="<?php echo esc_attr($opt); ?>" 
                                               class="sk-connect-option-input">
                                        <span class="sk-connect-<?php echo esc_attr($field['type']); ?>-indicator"></span>
                                        <?php echo esc_html($opt); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="sk-connect-error-message" style="margin-left: 0; padding-left: 0;"></div>
                        </div>
                    <?php endif; ?>
                    
                <?php endforeach; ?>

                <button type="submit" class="sk-connect-submit-btn">
                    <span class="sk-connect-spinner"></span>
                    <span class="sk-connect-btn-text"><?php echo esc_html($submit_lbl); ?></span>
                </button>

                <div class="sk-connect-feedback-general"></div>
            </form>
        </div>

        <!-- Success Animation Layer -->
        <div class="sk-connect-success-container">
            <svg class="sk-connect-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="sk-connect-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="sk-connect-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
            <h3 class="sk-connect-success-title">Submission Success!</h3>
            <p class="sk-connect-success-desc"><?php echo esc_html($success_msg); ?></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Handle Frontend AJAX Custom Form Submission.
 */
add_action('wp_ajax_sk_connect_submit_custom_form', 'sk_connect_forms_handle_custom_submission');
add_action('wp_ajax_nopriv_sk_connect_submit_custom_form', 'sk_connect_forms_handle_custom_submission');
function sk_connect_forms_handle_custom_submission() {
    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['security'])), 'sk_connect_form_submit_nonce')) {
        wp_send_json_error('Security validation failed.');
    }

    $form_id = isset($_POST['form_id']) ? intval(wp_unslash($_POST['form_id'])) : 0;
    if ($form_id <= 0) {
        wp_send_json_error('Invalid Form ID.');
    }

    global $wpdb;
    $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $form_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$forms_table} WHERE id = %d", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    if (!$form_row) {
        wp_send_json_error('Form configuration not found.');
    }

    $fields = json_decode($form_row->fields, true);
    $settings = json_decode($form_row->settings, true);

    if (!is_array($fields)) {
        wp_send_json_error('Form fields are invalid.');
    }

    $submission_payload = array();
    $has_errors = false;

    // Loop fields config to sanitize and validate
    foreach ($fields as $field) {
        $field_id = $field['id'];
        $val = isset($_POST[$field_id]) ? wp_unslash($_POST[$field_id]) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // Clean arrays (checkbox option arrays)
        if (is_array($val)) {
            $cleaned_val = array_map('sanitize_text_field', $val);
        } else {
            $cleaned_val = $val !== null ? sanitize_text_field($val) : null;
        }

        // Validate required checks
        if (isset($field['required']) && $field['required']) {
            if ($cleaned_val === null || $cleaned_val === '' || (is_array($cleaned_val) && empty($cleaned_val))) {
                $has_errors = true;
                break;
            }
        }

        // Dynamic typing check
        if ($field['type'] === 'email' && !empty($cleaned_val) && !is_array($cleaned_val)) {
            if (!is_email($cleaned_val)) {
                wp_send_json_error("A valid email address is required for field: {$field['label']}.");
            }
        }

        $submission_payload[$field_id] = $cleaned_val;
    }

    if ($has_errors) {
        wp_send_json_error('Please complete all required fields.');
    }

    // Insert to DB Submissions
    $subs_table = $wpdb->prefix . 'sk_connect_submissions';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $inserted = $wpdb->insert(
        $subs_table,
        array(
            'form_id'         => $form_id,
            'submission_data' => wp_json_encode($submission_payload),
            'submitted_at'    => current_time('mysql'),
            'is_read'         => 0
        ),
        array('%d', '%s', '%s', '%d')
    );

    if ($inserted === false) {
        wp_send_json_error('Failed to save submission.');
    }

    // Dispatch notification email
    $recipient_email = isset($settings['recipient_email']) && is_email($settings['recipient_email']) ? $settings['recipient_email'] : get_option('admin_email');
    $email_subject   = '[SK Connect Forms] Submission: ' . esc_html($form_row->title);
    $headers         = array('Content-Type: text/html; charset=UTF-8');

    $email_body = sk_connect_forms_build_notification_html( $form_row->title, $fields, $submission_payload );

    wp_mail($recipient_email, $email_subject, $email_body, $headers);

    wp_send_json_success();
}

/**
 * Handle Admin AJAX Form Config Save (New & Updates).
 */
add_action('wp_ajax_sk_connect_save_form_config', 'sk_connect_forms_handle_save_config');
function sk_connect_forms_handle_save_config() {
    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['security'])), 'sk_connect_admin_nonce')) {
        wp_send_json_error('Security validation failed.');
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access.');
    }

    $id       = isset($_POST['id']) ? intval(wp_unslash($_POST['id'])) : 0;
    $title    = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $fields   = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    if (empty($title)) {
        wp_send_json_error('Form Name is required.');
    }

    // Validate fields JSON structure
    $decoded_fields = json_decode( $fields, true );
    if ( ! is_array( $decoded_fields ) ) {
        wp_send_json_error( 'Invalid fields configuration.' );
    }
    foreach ( $decoded_fields as $field_item ) {
        if ( ! isset( $field_item['id'], $field_item['type'], $field_item['label'] ) ) {
            wp_send_json_error( 'Each field must have an id, type, and label.' );
        }
    }

    global $wpdb;
    $forms_table = $wpdb->prefix . 'sk_connect_forms';

    if ($id > 0) {
        // Update Form
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $updated = $wpdb->update(
            $forms_table,
            array(
                'title'    => $title,
                'fields'   => $fields,
                'settings' => $settings
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        if ($updated === false) {
            wp_send_json_error('Database update failed.');
        }
    } else {
        // Create Form
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            $forms_table,
            array(
                'title'      => $title,
                'fields'     => $fields,
                'settings'   => $settings,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        if ($inserted === false) {
            wp_send_json_error('Database insert failed.');
        }
    }

    wp_send_json_success();
}

/**
 * Handle Admin AJAX Read Status Toggle.
 */
add_action('wp_ajax_sk_connect_toggle_submission_read', 'sk_connect_forms_handle_toggle_read');
function sk_connect_forms_handle_toggle_read() {
    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['security'])), 'sk_connect_admin_nonce')) {
        wp_send_json_error('Security validation failed.');
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized access.' );
    }

    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

    if (!$id) {
        wp_send_json_error('Invalid submission ID.');
    }

    global $wpdb;
    $subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

    // Retrieve form ID first to query matching unread stats
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $form_id = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$subs_table} WHERE id = %d", $id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $updated = $wpdb->update(
        $subs_table,
        array('is_read' => $status),
        array('id' => $id),
        array('%d'),
        array('%d')
    );

    if ($updated === false) {
        wp_send_json_error('Failed to update database record.');
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $total_unread = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$subs_table} WHERE form_id = %d AND is_read = 0", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    wp_send_json_success(array('total_unread' => intval($total_unread)));
}

/**
 * Handle Admin AJAX Delete Submission.
 */
add_action('wp_ajax_sk_connect_delete_submission', 'sk_connect_forms_handle_delete_submission');
function sk_connect_forms_handle_delete_submission() {
    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['security'])), 'sk_connect_admin_nonce')) {
        wp_send_json_error('Security validation failed.');
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized access.' );
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error('Invalid submission ID.');
    }

    global $wpdb;
    $subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $form_id = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$subs_table} WHERE id = %d", $id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $deleted = $wpdb->delete($subs_table, array('id' => $id), array('%d'));

    if ($deleted === false) {
        wp_send_json_error('Failed to delete database record.');
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $total_all_time = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$subs_table} WHERE form_id = %d", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $total_unread   = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$subs_table} WHERE form_id = %d AND is_read = 0", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    wp_send_json_success(array(
        'total_all_time' => intval($total_all_time),
        'total_unread'   => intval($total_unread)
    ));
}

/**
 * Handle Admin AJAX Delete Form Template and its associated Submissions.
 */
add_action('wp_ajax_sk_connect_delete_form_template', 'sk_connect_forms_handle_delete_form');
function sk_connect_forms_handle_delete_form() {
    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['security'])), 'sk_connect_admin_nonce')) {
        wp_send_json_error('Security validation failed.');
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized access.' );
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error('Invalid form ID.');
    }

    global $wpdb;
    $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    $subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

    // 1. Delete associated submissions
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->delete($subs_table, array('form_id' => $id), array('%d'));

    // 2. Delete the form template
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $deleted = $wpdb->delete($forms_table, array('id' => $id), array('%d'));

    if ($deleted === false) {
        wp_send_json_error('Failed to delete database form record.');
    }

    wp_send_json_success();
}

/**
 * Handle CSV Export by Form.
 */
add_action('wp_ajax_sk_connect_export_submissions_csv', 'sk_connect_forms_handle_csv_export');
function sk_connect_forms_handle_csv_export() {
    if (!isset($_GET['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['security'])), 'sk_connect_export_csv')) {
        wp_die('Security verification failed.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $form_id = isset($_GET['form_id']) ? intval(wp_unslash($_GET['form_id'])) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if ($form_id <= 0) {
        wp_die('Invalid Form ID.');
    }

    global $wpdb;
    $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    $subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $form_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$forms_table} WHERE id = %d", $form_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if (!$form_row) {
        wp_die('Form template not found.');
    }

    $fields = json_decode($form_row->fields, true);
    if (!is_array($fields)) {
        $fields = array();
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $results = $wpdb->get_results($wpdb->prepare("SELECT id, submission_data, submitted_at, is_read FROM {$subs_table} WHERE form_id = %d ORDER BY submitted_at DESC", $form_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    // Build CSV header rows dynamically matching form field labels
    $headers = array('Submission ID', 'Date', 'Status');
    foreach ($fields as $field) {
        $headers[] = $field['label'];
    }

    $csv_output = '';
    $escape_csv = function($val) {
        $val = str_replace('"', '""', $val);
        if (preg_match('/[,"\r\n\t ]/', $val)) {
            $val = '"' . $val . '"';
        }
        return $val;
    };

    $csv_output .= implode(',', array_map($escape_csv, $headers)) . "\r\n";

    if (!empty($results)) {
        foreach ($results as $row) {
            $sub_data = json_decode($row['submission_data'], true);
            if (!is_array($sub_data)) {
                $sub_data = array();
            }

            $csv_row = array(
                $row['id'],
                $row['submitted_at'],
                $row['is_read'] ? 'Read' : 'Unread'
            );

            foreach ($fields as $field) {
                $val = isset($sub_data[$field['id']]) ? $sub_data[$field['id']] : '';
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $csv_row[] = $val;
            }

            $csv_output .= implode(',', array_map($escape_csv, $csv_row)) . "\r\n";
        }
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sk_connect_submissions_form_' . $form_id . '_' . wp_date('Y-m-d') . '.csv');

    echo $csv_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}

/**
 * Handle Bulk PDF Export by Form.
 */
add_action('wp_ajax_sk_connect_export_submissions_pdf', 'sk_connect_forms_handle_bulk_pdf_export');
function sk_connect_forms_handle_bulk_pdf_export() {
    if (!isset($_GET['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['security'])), 'sk_connect_export_csv')) {
        wp_die('Security verification failed.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $form_id = isset($_GET['form_id']) ? intval(wp_unslash($_GET['form_id'])) : 0;
    if ($form_id <= 0) {
        wp_die('Invalid Form ID.');
    }

    global $wpdb;
    $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    $subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

    $form_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$forms_table} WHERE id = %d", $form_id));
    if (!$form_row) {
        wp_die('Form template not found.');
    }

    $fields = json_decode($form_row->fields, true);
    if (!is_array($fields)) {
        $fields = array();
    }

    $results = $wpdb->get_results($wpdb->prepare("SELECT id, submission_data, submitted_at, is_read FROM {$subs_table} WHERE form_id = %d ORDER BY submitted_at DESC", $form_id), ARRAY_A);

    require_once SK_CONNECT_FORMS_PATH . 'includes/fpdf/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Inquiry Entries: ' . html_entity_decode($form_row->title), 0, 1, 'C');
    $pdf->Ln(5);

    if (!empty($results)) {
        foreach ($results as $row) {
            $sub_data = json_decode($row['submission_data'], true);
            if (!is_array($sub_data)) {
                $sub_data = array();
            }

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(0, 8, 'Submission ID: ' . $row['id'] . ' | Date: ' . $row['submitted_at'], 0, 1, 'L', true);
            
            $pdf->SetFont('Arial', '', 11);
            foreach ($fields as $field) {
                $val = isset($sub_data[$field['id']]) ? $sub_data[$field['id']] : '-';
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(50, 7, html_entity_decode($field['label']) . ':', 0, 0);
                $pdf->SetFont('Arial', '', 11);
                $pdf->MultiCell(0, 7, html_entity_decode((string)$val));
            }
            $pdf->Ln(5);
        }
    } else {
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'No submissions found.', 0, 1, 'C');
    }

    $pdf->Output('D', 'sk_connect_submissions_form_' . $form_id . '_' . wp_date('Y-m-d') . '.pdf');
    exit;
}

/**
 * Handle Single PDF Export by Submission ID.
 */
add_action('wp_ajax_sk_connect_export_single_pdf', 'sk_connect_forms_handle_single_pdf_export');
function sk_connect_forms_handle_single_pdf_export() {
    if (!isset($_GET['security']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['security'])), 'sk_connect_admin_nonce')) {
        wp_die('Security verification failed.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $sub_id = isset($_GET['sub_id']) ? intval(wp_unslash($_GET['sub_id'])) : 0;
    if ($sub_id <= 0) {
        wp_die('Invalid Submission ID.');
    }

    global $wpdb;
    $forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    $subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );

    $sub_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$subs_table} WHERE id = %d", $sub_id));
    if (!$sub_row) {
        wp_die('Submission not found.');
    }

    $form_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$forms_table} WHERE id = %d", $sub_row->form_id));
    if (!$form_row) {
        wp_die('Form template not found.');
    }

    $fields = json_decode($form_row->fields, true);
    if (!is_array($fields)) {
        $fields = array();
    }

    $sub_data = json_decode($sub_row->submission_data, true);
    if (!is_array($sub_data)) {
        $sub_data = array();
    }

    require_once SK_CONNECT_FORMS_PATH . 'includes/fpdf/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'Submission Details', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Form: ' . html_entity_decode($form_row->title), 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 10, ' ID: ' . $sub_row->id . ' | Date: ' . $sub_row->submitted_at, 0, 1, 'L', true);
    $pdf->Ln(5);

    foreach ($fields as $field) {
        $val = isset($sub_data[$field['id']]) ? $sub_data[$field['id']] : '-';
        if (is_array($val)) {
            $val = implode(', ', $val);
        }
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, html_entity_decode($field['label']) . ':', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 8, html_entity_decode((string)$val));
    }

    $pdf->Output('D', 'submission_' . $sub_id . '.pdf');
    exit;
}

/**
 * Helper: Convert HEX color code to RGB string.
 */
function sk_connect_forms_hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

/**
 * Helper: Darken or lighten color for hover states.
 */
function sk_connect_forms_adjust_brightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
    $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
    $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    
    return '#' . $r_hex . $g_hex . $b_hex;
}

/**
 * Helper: Build HTML email notification body for form submissions.
 *
 * @param string $form_title         The form title.
 * @param array  $fields             Array of field configurations.
 * @param array  $submission_payload Key-value submission data.
 * @return string The HTML email body.
 */
function sk_connect_forms_build_notification_html( $form_title, $fields, $submission_payload ) {
    $rows_html = '';
    foreach ( $fields as $field ) {
        $val = isset( $submission_payload[ $field['id'] ] ) ? $submission_payload[ $field['id'] ] : '-';
        if ( is_array( $val ) ) {
            $val = implode( ', ', $val );
        }
        $rows_html .= sprintf(
            '<tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #4a5568; width: 150px;">%s</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #2d3748;">%s</td></tr>',
            esc_html( $field['label'] ),
            nl2br( esc_html( $val ) )
        );
    }

    return sprintf(
        '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px;"><h2 style="color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 12px; margin-top: 0;">New Submission: %s</h2><table style="width: 100%%; border-collapse: collapse; margin-top: 20px;">%s</table><p style="font-size: 11px; color: #a0aec0; margin-top: 25px;">Submitted via SK Connect Forms on %s</p></div>',
        esc_html( $form_title ),
        $rows_html,
        wp_date( 'Y-m-d H:i:s' )
    );
}
