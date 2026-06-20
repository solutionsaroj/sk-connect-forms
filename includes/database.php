<?php
/**
 * Database schema and installation handlers.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * @package SKConnectForms
 */

defined('ABSPATH') || exit;

/**
 * Create or upgrade the forms and submissions tables.
 */
function sk_connect_forms_create_db() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Table: Forms
    $sk_connect_forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );
    $sql_forms = "CREATE TABLE $sk_connect_forms_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(200) NOT NULL,
        fields longtext NOT NULL,
        settings text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Table: Submissions (linked to Forms)
    $sk_connect_subs_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );
    $sql_submissions = "CREATE TABLE $sk_connect_subs_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id bigint(20) NOT NULL,
        submission_data longtext NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        is_read tinyint(1) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id),
        KEY form_id (form_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_forms);
    dbDelta($sql_submissions);

    // If no forms exist yet, insert a default Contact Form to help the user get started
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $forms_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sk_connect_forms_table} WHERE 1 = %d", 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if (intval($forms_count) === 0) {
        $default_fields = array(
            array(
                'id' => 'name',
                'type' => 'text',
                'label' => 'Full Name',
                'placeholder' => 'Enter your full name',
                'required' => true
            ),
            array(
                'id' => 'email',
                'type' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter your email',
                'required' => true
            ),
            array(
                'id' => 'subject',
                'type' => 'text',
                'label' => 'Subject',
                'placeholder' => 'What is this regarding?',
                'required' => true
            ),
            array(
                'id' => 'message',
                'type' => 'textarea',
                'label' => 'Your Message',
                'placeholder' => 'Write your message here...',
                'required' => true
            )
        );

        $default_settings = array(
            'recipient_email' => get_option('admin_email'),
            'success_message' => 'Thank you! Your message has been sent successfully.',
            'primary_color'   => '#6366f1',
            'submit_label'    => 'Send Message'
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $sk_connect_forms_table,
            array(
                'title'      => 'Default Contact Form',
                'fields'     => wp_json_encode($default_fields),
                'settings'   => wp_json_encode($default_settings),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    update_option('sk_connect_forms_db_version', '2.1.0');
}
