<?php
/**
 * View template: Interactive Form Builder.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * @package SKConnectForms
 */

defined('ABSPATH') || exit;

global $wpdb;
$sk_connect_forms_table = esc_sql( $wpdb->prefix . 'sk_connect_forms' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_connect_form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sk_connect_form_title = '';
$sk_connect_form_fields = '[]';
$sk_connect_form_settings = '{}';

if ($sk_connect_form_id > 0) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $sk_connect_form_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sk_connect_forms_table} WHERE id = %d", $sk_connect_form_id));
    if ($sk_connect_form_row) {
        $sk_connect_form_title = $sk_connect_form_row->title;
        $sk_connect_form_fields = $sk_connect_form_row->fields;
        $sk_connect_form_settings = $sk_connect_form_row->settings;
    }
}
?>

<div class="sk-connect-admin-wrap">
    <!-- Header Banner -->
    <header class="sk-connect-header">
        <div class="sk-connect-header-title">
            <h1><?php echo $sk_connect_form_id ? 'Edit Form' : 'Create Custom Form'; ?></h1>
            <p>Customize fields, adjust settings, and configure validations for your options form.</p>
        </div>
        <div class="sk-connect-header-actions" style="display:flex; gap:12px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=sk-connect-forms&view=form-list')); ?>" class="button-csv" style="background:rgba(255,255,255,0.05); color:#fff; border-color:rgba(255,255,255,0.1);">
                Cancel
            </a>
            <button id="sk-connect-save-form-btn" class="button-csv" style="background:#ffffff; color:#1e1b4b;">
                <span>💾</span> Save Form
            </button>
        </div>
    </header>

    <!-- Editor Workspace Grid Layout -->
    <div class="sk-connect-builder-layout">
        
        <!-- Left Sidebar: Toolbox -->
        <div class="sk-connect-builder-panel toolbox">
            <h3 class="panel-heading">Field Toolbox</h3>
            <p class="panel-description">Click an element to add it to your custom form canvas.</p>
            
            <div class="toolbox-buttons">
                <button type="button" class="toolbox-btn" data-type="text">
                    <span class="btn-icon">🔤</span> Single-line Text
                </button>
                <button type="button" class="toolbox-btn" data-type="email">
                    <span class="btn-icon">📧</span> Email Address
                </button>
                <button type="button" class="toolbox-btn" data-type="tel">
                    <span class="btn-icon">📞</span> Phone Number
                </button>
                <button type="button" class="toolbox-btn" data-type="number">
                    <span class="btn-icon">🔢</span> Number
                </button>
                <button type="button" class="toolbox-btn" data-type="url">
                    <span class="btn-icon">🔗</span> Website / URL
                </button>
                <button type="button" class="toolbox-btn" data-type="date">
                    <span class="btn-icon">📅</span> Date Picker
                </button>
                <button type="button" class="toolbox-btn" data-type="textarea">
                    <span class="btn-icon">📝</span> Multi-line Textarea
                </button>
                <button type="button" class="toolbox-btn" data-type="select">
                    <span class="btn-icon">🔽</span> Dropdown Select
                </button>
                <button type="button" class="toolbox-btn" data-type="checkbox">
                    <span class="btn-icon">☑️</span> Checkboxes
                </button>
                <button type="button" class="toolbox-btn" data-type="radio">
                    <span class="btn-icon">🔘</span> Radio Buttons
                </button>
            </div>

            <!-- Form-level Configuration settings nested below -->
            <div style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <h3 class="panel-heading">Form Settings</h3>
                
                <div class="setting-group">
                    <label for="sk-connect-form-title-input">Form Name</label>
                    <input type="text" id="sk-connect-form-title-input" value="<?php echo esc_attr($sk_connect_form_title ? $sk_connect_form_title : 'My Custom Options Form'); ?>" placeholder="e.g. Feedback Form">
                </div>

                <div class="setting-group">
                    <label for="sk-connect-form-email-input">Recipient Notification Email</label>
                    <input type="email" id="sk-connect-form-email-input" placeholder="e.g. admin@domain.com">
                </div>

                <div class="setting-group">
                    <label for="sk-connect-form-success-input">Success Message</label>
                    <input type="text" id="sk-connect-form-success-input">
                </div>

                <div class="setting-group">
                    <label for="sk-connect-form-submit-lbl">Submit Button Label</label>
                    <input type="text" id="sk-connect-form-submit-lbl" placeholder="Send Message">
                </div>

                <div class="setting-group">
                    <label for="sk-connect-form-accent-color">Primary Theme Color</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="color" id="sk-connect-form-accent-color" style="border:none; width:36px; height:36px; padding:0; cursor:pointer; border-radius:8px;">
                        <input type="text" id="sk-connect-form-accent-color-hex" style="margin-bottom:0;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Center Area: Live Canvas Preview -->
        <div class="sk-connect-builder-panel canvas">
            <h3 class="panel-heading">Form Preview Canvas</h3>
            <p class="panel-description">Select and organize fields. Move blocks up/down using order controls.</p>
            
            <div class="canvas-droptone" id="sk-connect-builder-canvas">
                <!-- Dynamic form fields rendered here by form-builder.js -->
            </div>
            
            <div class="canvas-empty-state" id="sk-connect-canvas-empty">
                <div style="font-size: 40px; margin-bottom: 12px;">🎨</div>
                <h4>Your Canvas is Empty</h4>
                <p>Click elements in the Toolbox on the left to build custom fields.</p>
            </div>
        </div>

        <!-- Right Sidebar: Properties Inspector -->
        <div class="sk-connect-builder-panel inspector" id="sk-connect-properties-inspector">
            <h3 class="panel-heading">Field Settings</h3>
            <p class="panel-description" id="inspector-instructions">Select a field on the canvas to edit its validation rules and labels.</p>
            
            <div id="inspector-content" style="display:none;">
                <input type="hidden" id="field-index-ref">
                
                <div class="setting-group">
                    <label for="field-label-input">Field Label</label>
                    <input type="text" id="field-label-input">
                </div>

                <div class="setting-group" id="placeholder-group">
                    <label for="field-placeholder-input">Placeholder text</label>
                    <input type="text" id="field-placeholder-input">
                </div>

                <div class="setting-group" style="flex-direction:row; display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="field-required-input" style="margin:0;">
                    <label for="field-required-input" style="margin:0; cursor:pointer;">Required field</label>
                </div>

                <!-- Option Values Manager (Select, Checkbox, Radio) -->
                <div class="setting-group" id="options-group" style="display:none; border-top:1px solid #e2e8f0; padding-top:16px; margin-top:16px;">
                    <label style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span>Option Items</span>
                        <button type="button" id="sk-connect-add-option-btn" class="button-csv" style="padding:4px 8px; font-size:11px; height:auto; background:#6366f1; color:#fff; border:none;">➕ Add</button>
                    </label>
                    
                    <div id="options-items-list" style="display:flex; flex-direction:column; gap:8px;">
                        <!-- Options items dynamically loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
