<?php

/**
 * Plugin Name: EADI Plugin
 * Plugin URI: https://www.byvex.com/
 * Description: Custom plugin.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested Up To: 6.0
 * Author: Byvex Team
 * Author URI: https://www.byvex.com/
 * Text Domain: eadi-plugin
 */

/**
 * Testing url
 * https://example.com/wp-admin/admin-ajax.php?action=blf_ajax_server&handle=submit-form
 */

class EadiPlugin {

    protected $plugin_name;
    protected $plugin_version;
    protected $plugin_slug;
    protected $plugin_dir_url;
    protected $submissions_table;
    protected $offers_table;

    function __construct(){
        $this->plugin_name = 'Eadi Plugin';
        $this->plugin_version = '1.0.0';
        $this->plugin_slug = 'eadi_plugin';
        $this->plugin_dir_url = plugin_dir_url(__FILE__);
        $this->submissions_table = 'eadi_submissions';
        $this->offers_table = 'eadi_offers';

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        add_action('admin_menu', array($this, 'add_plugin_menu_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        add_shortcode('eadi-loan-form', array($this, 'show_loan_form_shortcode'));
        add_shortcode('eadi-loan-success', array($this, 'show_loan_success_shortcode'));
        add_shortcode('eadi-loan-rejected', array($this, 'show_loan_rejected_shortcode'));
        add_shortcode('eadi-loan-error', array($this, 'show_loan_error_shortcode'));

        add_action( 'admin_init', array($this, 'register_settings'));

        add_action('wp_ajax_blf_ajax_server', [$this, 'blf_ajax_server']);
        add_action('wp_ajax_nopriv_blf_ajax_server', [$this, 'blf_ajax_server']);

        add_action('wp_ajax_eadi_callback', array($this, 'eadi_callback'));
        add_action('wp_ajax_nopriv_eadi_callback', array($this, 'eadi_callback'));

        register_activation_hook( __FILE__, array($this, 'activate'));
        register_deactivation_hook( __FILE__, array($this, 'deactivate'));
    }

    function add_action_links($links){
        $my_links = array('<a href="' . admin_url('admin.php?page=' . $this->plugin_slug . '_page') . '">Settings</a>');
        return array_merge($links, $my_links);
    }

    function activate(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // create submissions table start
        $table_name = $wpdb->prefix . $this->submissions_table;
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            amount varchar(100) NULL,
            email varchar(255) NULL,
            organization_number varchar(100) NULL,
            group_name varchar(100) NULL,
            entity_type varchar(100) NULL,
            yrs_business varchar(100) NULL,
            revenue varchar(100) NULL,
            industry_type varchar(100) NULL,
            loan_purpose varchar(100) NULL,
            business_address varchar(255) NULL,
            postcode varchar(100) NULL,
            city varchar(100) NULL,
            first_name varchar(255) NULL,
            last_name varchar(255) NULL,
            personal_id varchar(100) NULL,
            client_city varchar(100) NULL,
            client_address varchar(255) NULL,
            client_postcode varchar(100) NULL,
            consent_direct_marketing varchar(100) NULL,
            ip_address varchar(255) NULL,
            submitted_at varchar(100) NULL,
            eadi_id INT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        $wpdb->query($sql);
        // create submissions table end

        // create offers table start
        $table_name = $wpdb->prefix . $this->offers_table;
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            eadi_id INT NULL,
            organization_number varchar(100) NULL,
            status varchar(100) NULL,
            description varchar(255) NULL,
            event varchar(100) NULL,
            amount varchar(100) NULL,
            amortize_length varchar(100) NULL,
            monthly_cost varchar(100) NULL,
            reason varchar(255) NULL,
            product varchar(100) NULL,
            interest varchar(100) NULL,
            setup_fee varchar(100) NULL,
            admin_fee varchar(100) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        $wpdb->query($sql);
        // create offers table end

    }

    function deactivate(){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }

    function add_plugin_menu_page(){
        add_menu_page(
            esc_html($this->plugin_name),
            esc_html($this->plugin_name),
            'manage_options',
            $this->plugin_slug . '_page',
            '',
            'dashicons-shortcode',
            9,
        );
        add_submenu_page(
            $this->plugin_slug . '_page',
            __('Settings', 'eadi-plugin'),
            __('Settings', 'eadi-plugin'),
            'manage_options',
            $this->plugin_slug . '_page',
            array($this, 'show_plugin_menu_page'),
        );
        add_submenu_page(
            $this->plugin_slug . '_page',
            __('Submissions', 'eadi-plugin'),
            __('Submissions', 'eadi-plugin'),
            'manage_options',
            $this->plugin_slug . '_submissions_page',
            array($this, 'show_submissions_page'),
        );
        add_submenu_page(
            $this->plugin_slug . '_page',
            __('Offers', 'eadi-plugin'),
            __('Offers', 'eadi-plugin'),
            'manage_options',
            $this->plugin_slug . '_offers_page',
            array($this, 'show_offers_page'),
        );
        add_submenu_page(
            $this->plugin_slug . '_page',
            __('Instructions', 'eadi-plugin'),
            __('Instructions', 'eadi-plugin'),
            'manage_options',
            $this->plugin_slug . '_instructions_page',
            array($this, 'show_instructions_page'),
        );
    }

    function register_settings(){

        add_settings_section('eadi_settings_section', null, null, $this->plugin_slug . '_page');

        // API Username
        add_settings_field(
            'eadi_api_username', // name of input
            'API Username', // label of input
            array($this, 'setting_api_username' ), // function for displaying html
            $this->plugin_slug . '_page', // slug of settings page
            'eadi_settings_section' // settings section name
        );
        register_setting(
            'eadi_plugin_settings',
            'eadi_api_username',
            array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => 'capiq' )
        );

        // API Password
        add_settings_field('eadi_api_password', 'API Password', array($this, 'setting_api_password' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_api_password', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => 'We4iHZGbkEzr9gV0ITSjX8AKpv7BPYny' ) );

        // API Host
        add_settings_field('eadi_api_host', 'API Host', array($this, 'setting_api_host' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_api_host', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => 'stage-kss-se-1i3kdsw.ks-295ferr.com' ) );

        // API Key
        add_settings_field('eadi_api_key', 'API Key', array($this, 'setting_api_key' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_api_key', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => 'SxzOXJU1roiB9ajG3TF7REeuCkHwDbZv' ) );

        // Success Page Slug
        add_settings_field('eadi_success_slug', 'SUCCESS Page Slug', array($this, 'setting_success_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_success_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-success' ) );

        // Pending Status Slug
        add_settings_field('eadi_pending_slug', 'PENDING Status Slug', array($this, 'setting_pending_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_pending_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-success' ) );

        // Approved Status Slug
        add_settings_field('eadi_approved_slug', 'APPROVED Status Slug', array($this, 'setting_approved_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_approved_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-success' ) );

        // Sold Status Slug
        add_settings_field('eadi_sold_slug', 'SOLD Status Slug', array($this, 'setting_sold_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_sold_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-success' ) );

        // Error Page Slug
        add_settings_field('eadi_error_slug', 'ERROR Page Slug', array($this, 'setting_error_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_error_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-error' ) );

        // Rejected Page Slug
        add_settings_field('eadi_rejected_slug', 'REJECTED Page Slug', array($this, 'setting_rejected_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_rejected_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-rejected' ) );

        // Closed Page Slug
        add_settings_field('eadi_closed_slug', 'CLOSED Page Slug', array($this, 'setting_closed_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_closed_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-error' ) );

        // Withdrawn Page Slug
        add_settings_field('eadi_withdrawn_slug', 'WITHDRAWN Page Slug', array($this, 'setting_withdrawn_slug' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_withdrawn_slug', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => '/loan-error' ) );

        // Callback Username
        add_settings_field('eadi_callback_username', 'Callback Username', array($this, 'setting_callback_username' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_callback_username', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => 'eadi_capiq' ) );

        // Callback Password
        add_settings_field('eadi_callback_password', 'Callback Password', array($this, 'setting_callback_password' ), $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_callback_password', array( 'sanitize_callback'=> 'sanitize_text_field', 'default' => 'hYn6d3ia9ems7es9sx83lsa' ) );

        // email for success
        add_settings_field('eadi_email_success', 'Email Success', [$this, 'setting_email_success'], $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_email_success', ['default' => ''] );

        // email for fail
        add_settings_field('eadi_email_fail', 'Email Fail', [$this, 'setting_email_fail'], $this->plugin_slug . '_page', 'eadi_settings_section');
        register_setting('eadi_plugin_settings', 'eadi_email_fail', ['default' => ''] );

    }

    function setting_api_username(){
        ?><input type="text" name="eadi_api_username" value="<?php echo esc_attr(get_option('eadi_api_username')); ?>" /><?php
    }

    function setting_api_password(){
        ?><input type="text" name="eadi_api_password" value="<?php echo esc_attr(get_option('eadi_api_password')); ?>" /><?php
    }

    function setting_api_host(){
        ?><input type="text" name="eadi_api_host" value="<?php echo esc_attr(get_option('eadi_api_host')); ?>" /><?php
    }

    function setting_api_key(){
        ?><input type="text" name="eadi_api_key" value="<?php echo esc_attr(get_option('eadi_api_key')); ?>" /><?php
    }

    function setting_callback_username(){
        ?><input type="text" name="eadi_callback_username" value="<?php echo esc_attr(get_option('eadi_callback_username')); ?>" /><?php
    }

    function setting_callback_password(){
        ?><input type="text" name="eadi_callback_password" value="<?php echo esc_attr(get_option('eadi_callback_password')); ?>" /><?php
    }

    function setting_success_slug(){
        ?><input type="text" name="eadi_success_slug" value="<?php echo esc_attr(get_option('eadi_success_slug')); ?>" /><?php
    }

    function setting_error_slug(){
        ?><input type="text" name="eadi_error_slug" value="<?php echo esc_attr(get_option('eadi_error_slug')); ?>" /><?php
    }

    function setting_pending_slug(){
        ?><input type="text" name="eadi_pending_slug" value="<?php echo esc_attr(get_option('eadi_pending_slug')); ?>" /><?php
    }

    function setting_approved_slug(){
        ?><input type="text" name="eadi_approved_slug" value="<?php echo esc_attr(get_option('eadi_approved_slug')); ?>" /><?php
    }

    function setting_sold_slug(){
        ?><input type="text" name="eadi_sold_slug" value="<?php echo esc_attr(get_option('eadi_sold_slug')); ?>" /><?php
    }

    function setting_rejected_slug(){
        ?><input type="text" name="eadi_rejected_slug" value="<?php echo esc_attr(get_option('eadi_rejected_slug')); ?>" /><?php
    }

    function setting_closed_slug(){
        ?><input type="text" name="eadi_closed_slug" value="<?php echo esc_attr(get_option('eadi_closed_slug')); ?>" /><?php
    }

    function setting_withdrawn_slug(){
        ?><input type="text" name="eadi_withdrawn_slug" value="<?php echo esc_attr(get_option('eadi_withdrawn_slug')); ?>" /><?php
    }

    function setting_email_success(){
        $content = get_option('eadi_email_success');
        wp_editor($content, 'eadi_email_success', [
            'textarea_name'=> 'eadi_email_success',
            'media_buttons'=> false,
        ]);
    }

    function setting_email_fail(){
        $content = get_option('eadi_email_fail');
        wp_editor($content, 'eadi_email_fail', [
            'textarea_name'=> 'eadi_email_fail',
            'media_buttons'=> false,
        ]);
    }

    function dbi_plugin_setting_api_key() {
        $options = get_option( 'dbi_example_plugin_options' );
        echo "<input id='dbi_plugin_setting_api_key' name='dbi_example_plugin_options[api_key]' type='text' value='" . esc_attr( $options['api_key'] ) . "' />";
    }

    function dbi_plugin_setting_results_limit() {
        $options = get_option( 'dbi_example_plugin_options' );
        echo "<input id='dbi_plugin_setting_results_limit' name='dbi_example_plugin_options[results_limit]' type='text' value='" . esc_attr( $options['results_limit'] ) . "' />";
    }

    function dbi_plugin_setting_start_date() {
        $options = get_option( 'dbi_example_plugin_options' );
        echo "<input id='dbi_plugin_setting_start_date' name='dbi_example_plugin_options[start_date]' type='text' value='" . esc_attr( $options['start_date'] ) . "' />";
    }

    function enqueue_assets(){
        wp_register_script($this->plugin_slug . '-main-js', $this->plugin_dir_url . 'js/main.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/main.js'));
        wp_register_script($this->plugin_slug . '-alpine-js', $this->plugin_dir_url . 'js/alpine.min.js', array($this->plugin_slug . '-main-js'), '3.10.5');
        wp_register_style($this->plugin_slug . '-styles-css', $this->plugin_dir_url . 'css/style.css', array(), filemtime(plugin_dir_path(__FILE__) . 'css/style.css'));
    }

    function show_loan_form_shortcode($atts = array(), $content = null, $tag = ''){
        wp_enqueue_script($this->plugin_slug . '-main-js');
        wp_enqueue_script($this->plugin_slug . '-alpine-js');
        wp_enqueue_style($this->plugin_slug . '-styles-css');


        function get_client_ip() {
            $ipaddress = '';
            if (isset($_SERVER['HTTP_CLIENT_IP']))
                $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
            else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else if(isset($_SERVER['HTTP_X_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
            else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
            else if(isset($_SERVER['HTTP_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_FORWARDED'];
            else if(isset($_SERVER['REMOTE_ADDR']))
                $ipaddress = $_SERVER['REMOTE_ADDR'];
            else
                $ipaddress = 'UNKNOWN';
            return $ipaddress;
        }

        ob_start();
        ?>
        <div id="blf" x-data="blf">
            <div class="blf-form">
                <ul class="blf-form-steps">
                    <template x-for="step in steps">
                        <li :class="[step.number === current_step ? 'active' : '', step.number < steps.length ? 'flex-grow-1' : 'flex-grow-0']">
                            <div class="text-center">
                                <span x-text="step.number"></span>
                                <h6 x-text="step.title"></h6>
                            </div>
                            <span x-show="step.number < steps.length" class="blf-form-step-line"></span>
                        </li>
                    </template>
                </ul>
                <!-- step 1 start -->
                <div x-show="current_step === 1" class="blf-form-content">
                    <div class="blf-input-group">
                        <label for="user_amount" class="blf-range-label">
                            <span class="blf-range-label-title">Önskat lånebelopp</span>
                            <span class="blf-range-label-value">
                                <span x-text="parseInt(user_data.amount).toLocaleString()"></span>
                                SEK
                            </span>
                        </label>
                        <div>
                            <input id="user_amount" type="range" :min="amount_min" :max="amount_max" step="1" x-model="user_data.amount" :style="{'--range-fill': (( (user_data.amount - amount_min) / (amount_max - amount_min) ) * 100) + '%'}" class="blf-input-range custom-range-js" />
                            <div class="blf-input-range-min-max">
                                <span>
                                    <span x-text="amount_min.toLocaleString()"></span> SEK
                                </span>
                                <span>
                                    <span x-text="amount_max.toLocaleString()"></span> SEK
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="blf-input-group">
                        <label for="user_email">Email</label>
                        <input id="user_email" type="email" x-model="user_data.email" placeholder="din@epost.se" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_phone">Telefon</label>
                        <input id="user_phone" type="text" x-model="user_data.phone" placeholder="+46701 234 567" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_organization_number">Organisationsnummer</label>
                        <input id="user_organization_number" type="text" x-model="user_data.organization_number" placeholder="555555-1234" />
                    </div>
                    <div class="blf-form-step-nav">
                        <div></div>
                        <button type="button" @click="goToStep(2)" class="blf-btn">Nästa</button>
                    </div>
                </div>
                <!-- step 1 end -->
                <!-- step 2 start -->
                <div x-show="current_step === 2" class="blf-form-content">
                    <div class="blf-input-group">
                        <label for="user_groupName">Företagsnamn</label>
                        <input id="user_groupName" type="text" x-model="user_data.groupName" placeholder="Företagsnamn AB" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_entityType">Bolagsform</label>
                        <select id="user_entityType" x-model="user_data.entityType" class="blf-input-select">
                            <template x-for="item in entityTypeArr">
                                <option :value="item.value" x-text="item.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="blf-input-group">
                        <label for="user_yrsBusiness">Antal verksamma år</label>
                        <select id="user_yrsBusiness" x-model="user_data.yrsBusiness" class="blf-input-select">
                            <template x-for="item in yrsBusinessArr">
                                <option :value="item.value" x-text="item.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="blf-input-group">
                        <label for="user_revenue">Omsättning</label>
                        <input id="user_revenue" type="number" x-model="user_data.revenue" placeholder="500000" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_industryType">Bransch</label>
                        <select id="user_industryType" x-model="user_data.industryType" class="blf-input-select">
                            <template x-for="item in industryTypeArr">
                                <option :value="item.value" x-text="item.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="blf-input-group">
                        <label for="user_loanPurpose">Syfte med lånet</label>
                        <select id="user_loanPurpose" x-model="user_data.loanPurpose" class="blf-input-select">
                            <template x-for="item in loanPurposeArr">
                                <option :value="item.value" x-text="item.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="blf-input-group">
                        <label for="user_businessAddress">Företagets postadress</label>
                        <input id="user_businessAddress" type="text" x-model="user_data.businessAddress" placeholder="Företagsvägen 123" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_postcode">Postnummer</label>
                        <input id="user_postcode" type="text" x-model="user_data.postcode" placeholder="12345" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_city">Postort</label>
                        <input id="user_city" type="text" x-model="user_data.city" placeholder="Företagsstaden" />
                    </div>
                    <div class="blf-form-step-nav">
                        <button type="button" @click="goToStep(1)" class="blf-btn">Föregående</button>
                        <button type="button" @click="goToStep(3)" class="blf-btn">Nästa</button>
                    </div>
                </div>
                <!-- step 2 end -->
                <!-- step 3 start -->
                <div x-show="current_step === 3" class="blf-form-content">
                    <div class="blf-input-group">
                        <label for="user_firstName">Förnamn</label>
                        <input id="user_firstName" type="text" x-model="user_data.firstName" placeholder="Johan" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_lastName">Efternamn</label>
                        <input id="user_lastName" type="text" x-model="user_data.lastName" placeholder="Svensson" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_personal_id">Personnummer</label>
                        <input id="user_personal_id" type="text" x-model="user_data.personal_id" placeholder="YYDDMM-XXXX" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_clientAddress">Postadress</label>
                        <input id="user_clientAddress" type="text" x-model="user_data.clientAddress" placeholder="Svenssonsväg 123" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_clientPostcode">Postnummer</label>
                        <input id="user_clientPostcode" type="text" x-model="user_data.clientPostcode" placeholder="123 45" />
                    </div>
                    <div class="blf-input-group">
                        <label for="user_clientCity">Postort</label>
                        <input id="user_clientCity" type="text" x-model="user_data.clientCity" placeholder="Johanstad" />
                    </div>
                    <div class="blf-input-group">
                        <div class="blf-input-checkbox-group">
                            <input type="checkbox" name="agree_terms" value="true" x-model="user_data.consentDirectMarketing" id="user_consentDirectMarketing" class="blf-input-checkbox" />
                            <label for="user_consentDirectMarketing">Genom att ansöka om företagskrediten är jag införstådd med, och accepterar gällande <a href="<?php echo home_url('/lan-allmanna-villkor'); ?>" target="_blank" rel="noopener noreferrer nofollow">villkor</a>.</label>
                        </div>
                    </div>
                    <div class="blf-form-step-nav">
                        <button type="button" @click="goToStep(2)" class="blf-btn">Föregående</button>
                        <button type="button" @click="goToStep(99)" class="blf-btn">Ansök</button>
                    </div>
                </div>
                <!-- step 3 end -->
                <input type="hidden" name="ip_address" value="<?php echo get_client_ip(); ?>" />
                <!-- step final start -->
                <div x-show="current_step === 99" class="blf-form-content">
                    <div class="text-center">
                        <div class="lds-ellipsis">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                </div>
                <!-- step final end -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    function show_loan_success_shortcode($atts = array(), $content = null, $tag = ''){
        ob_start();
        ?>
        <section>
            <div class="section application-process-section">
                <div class="container">
                    <div class="heading-warp">
                        <h2>Tack för din ansökan</h2>
                    </div>
                    <div class="small-desc-wrap">
                        <p>Vi har mottagit er ansökan och den hanteras inom kort. Oftast så får du ett svar inom 30 minuter helgfria vardagar. Så fort vi har ett förhandsbesked till er så skickas ett mail till er.</p>
                    </div>
                    <div class="application-step-wrapper">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="step-content-wrap">
                                    <div class="step-number">1</div>
                                    <div class="step-icon">
                                        <picture>
                                            <source srcset="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-1.png.webp" type="image/webp">
                                            <img src="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-1.png" alt="step-icon-1" class="webpexpress-processed">
                                        </picture>
                                    </div>
                                    <div class="step-title">
                                        <h4>Ansök</h4>
                                    </div>
                                    <div class="step-desc">
                                        <p>Fyll i önskad limit och dina företagsuppgifter.<br> Anmärkningar behöver inte vara ett hinder.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="step-content-wrap">
                                    <div class="step-number">2</div>
                                    <div class="step-icon">
                                        <picture>
                                            <source srcset="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-2.png.webp" type="image/webp">
                                            <img src="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-2.png" alt="step-icon" class="webpexpress-processed">
                                        </picture>
                                    </div>
                                    <div class="step-title">
                                        <h4>Limitbesked</h4>
                                    </div>
                                    <div class="step-desc">
                                        <p>Efter vi granskat din ansökan kommer du att få ditt kreditbesked. <br> Vi skickar ut ett digitalt avtal som signeras med mobilt BankID.<br></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="step-content-wrap">
                                    <div class="step-number">3</div>
                                    <div class="step-icon">
                                        <picture>
                                            <source srcset="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-3.png.webp" type="image/webp">
                                            <img src="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-3.png" alt="step-icon" class="webpexpress-processed">
                                        </picture>
                                    </div>
                                    <div class="step-title">
                                        <h4>Utbetalning</h4>
                                    </div>
                                    <div class="step-desc">
                                        <p>Så fort din ansökan är klar kommer du få ett låneerbjudande, skulle vi behöva mer information så kommer en handläggare att kontakta er. Sedan betalas beviljat lån ut till ert företagskonto.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    function show_loan_rejected_shortcode($atts = array(), $content = null, $tag = ''){
        ob_start();
        ?>
        <section>
            <div class="section application-process-section">
                <div class="container">
                    <div class="heading-warp">
                        <h2>Tack för din ansökan</h2>
                    </div>
                    <div class="small-desc-wrap">
                        <p>Tyvärr så blev inte er önskade kredit beviljad.</p>
                        <p>Behöver ni frigöra likviditet så är ni välkommen att ansöka om att sälja<br />
                            eller belåna er fakturor.<br />
                            För att ansöka om att använda våra fakturatjänster klicka <a href="https://app.capiq.se" target="_blank" rel="noopener noreferrer nofollow"><strong>här</strong>.</a><br />
                            <a href="https://app.capiq.se" target="_blank" rel="noopener noreferrer nofollow" class="btn btn-success rounded-pill">Ansök om fakturatjänster</a>
                        </p>
                        <p>Önskar ni att ansöka om kredit från andra kreditgivare?<br />
                            Gå då in på länken nedan och skicka en ansökan.<br />
                            <strong>Ni kan se ert låneutrymme hos Krea kostnadsfritt och utan UC:</strong><br />
                            <a href="https://krea.se/krea_capiq/?utm_source=capiq&utm_medium=partner" target="_blank" rel="noopener noreferrer nofollow" class="btn btn-success rounded-pill">Vidare till Krea</a>
                        </p>
                        <p>Ni får svar direkt och kan sedan bestämma er om ni vill fortsätta göra en ansökan.</p>
                    </div>
                    <div class="application-step-wrapper">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="step-content-wrap">
                                    <div class="step-number">1</div>
                                    <div class="step-icon">
                                        <picture>
                                            <source srcset="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-1.png.webp" type="image/webp">
                                            <img src="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-1.png" alt="step-icon-1" class="webpexpress-processed">
                                        </picture>
                                    </div>
                                    <div class="step-title">
                                        <h4>Ansök</h4>
                                    </div>
                                    <div class="step-desc">
                                        <p>Fyll i önskad limit och dina företagsuppgifter.<br> Anmärkningar behöver inte vara ett hinder.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="step-content-wrap">
                                    <div class="step-number">2</div>
                                    <div class="step-icon">
                                        <picture>
                                            <source srcset="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-2.png.webp" type="image/webp">
                                            <img src="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-2.png" alt="step-icon" class="webpexpress-processed">
                                        </picture>
                                    </div>
                                    <div class="step-title">
                                        <h4>Limitbesked</h4>
                                    </div>
                                    <div class="step-desc">
                                        <p>Efter vi granskat din ansökan kommer du att få ditt kreditbesked. <br> Vi skickar ut ett digitalt avtal som signeras med mobilt BankID.<br></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="step-content-wrap">
                                    <div class="step-number">3</div>
                                    <div class="step-icon">
                                        <picture>
                                            <source srcset="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-3.png.webp" type="image/webp">
                                            <img src="https://stage.capiq.se/wp-content/uploads/2018/04/step-icon-3.png" alt="step-icon" class="webpexpress-processed">
                                        </picture>
                                    </div>
                                    <div class="step-title">
                                        <h4>Utbetalning</h4>
                                    </div>
                                    <div class="step-desc">
                                        <p>Så fort din ansökan är klar kommer du få ett låneerbjudande, skulle vi behöva mer information så kommer en handläggare att kontakta er. Sedan betalas beviljat lån ut till ert företagskonto.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <style type="text/css">
            .btn-success{border-color:#89C53F !important;background-color:#89C53F !important}
            .rounded-pill {border-radius:99px !important}
        </style>
        <?php
        return ob_get_clean();
    }

    function show_loan_error_shortcode($atts = array(), $content = null, $tag = ''){
        ob_start();
        ?>
        <div>
            <div class="text_wrapper text-center">
                <p>
                    Något verkar ha gått fel.<br /><br />
                    Vänligen testa igen om ett par minuter.
                    Stöter du då på samma ber vi er kontakta oss på epost <a href="mailto:kontakt@capiq.se">kontakt@capiq.se</a>
                </p>
                <a href="https://stage.capiq.se" class="eadi-loadn-error-btn">Testa igen</a>
            </div>
        </div>
        <style type="text/css">
            .eadi-loadn-error-btn {
                display: block;
                width: fit-content;
                margin: auto;
                font-size: 18px;
                border: 0;
                background: #89c53f;
                color: #ffffff;
                border-radius: 50px;
                padding: 10px 50px;
                text-decoration: none;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    function blf_ajax_server(){
        function encrypt($data, $key, $options = 0){
            $ivlen = openssl_cipher_iv_length('AES-256-CBC');
            $iv = openssl_random_pseudo_bytes($ivlen);
            $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, $options, $iv );
            return $iv . $encrypted;
        }

        if( isset($_GET['handle']) && $_GET['handle'] === 'submit-form' ){
            // if you change the labels here,
            // then also change the labes at $wpdb->insert();
            $data = array(
                'action' => 'APPLY',
                'firstName' => $_POST['firstName'],
                'middleName' => '',
                'lastName' => $_POST['lastName'],
                'clientAddress' => $_POST['clientAddress'],
                'clientPostcode' => $_POST['clientPostcode'],
                'clientCity' => $_POST['clientCity'],
                'country' => 'Sweden',
                'emailAddress' => $_POST['email'],
                'website' => 'www.test.com',
                'personal_id' => $_POST['personal_id'],
                'bankAccount' => 'FI0913303000141575',
                'birthDate' => '1982-11-18',
                'loanAmount' => $_POST['amount'],
                'loanPurpose' => $_POST['loanPurpose'],
                'groupName' => $_POST['groupName'],
                'entityType' => $_POST['entityType'],
                'business_id' => $_POST['organization_number'],
                'mobilePhone1' => $_POST['phone'],
                'businessAddress' => $_POST['businessAddress'],
                'postcode' => $_POST['postcode'],
                'city' => $_POST['city'],
                'industryType' => $_POST['industryType'],
                'yrsBusiness' => $_POST['yrsBusiness'],
                'consentDirectMarketing' => 'No',
                'revenue' => $_POST['revenue'],
            );
            $ip_address = isset($_POST['ip_address']) ? $_POST['ip_address'] : 'UNKNOWN';
            $data_json = json_encode($data);
            $api_username = get_option('eadi_api_username');
            $api_password = get_option('eadi_api_password');
            $api_key = get_option('eadi_api_key');
            $api_host = get_option('eadi_api_host');
            $api_token = sha1($api_key . $data_json);
            $api_url = 'https://' . $api_host . '/EADI/' . $api_username . '/' . $api_token . '/';

            $data_encrypted = encrypt($data_json, $api_key);
            $data_encoded = base64_encode($data_encrypted);
            $data_url_friendly = urlencode($data_encoded);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, "data={$data_url_friendly}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $api_username . ":" . $api_password);

            $res_data = curl_exec($ch);
            $res_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $res_info = curl_getinfo($ch);
            curl_close($ch);

            if ( curl_error($ch) ){
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Error occured with api.',
                    'data' => curl_error($ch),
                ));
                exit(0);
            }

            // make json readable
            $res_data = json_decode($res_data, true);

            global $wpdb;
            $table_name = $wpdb->prefix . $this->submissions_table;
            $log_status = $wpdb->insert($table_name, array(
                'amount' => $data['loanAmount'],
                'email' => $data['emailAddress'],
                'organization_number' => $data['business_id'],
                'group_name' => $data['groupName'],
                'entity_type' => $data['entityType'],
                'yrs_business' => $data['yrsBusiness'],
                'revenue' => $data['revenue'],
                'industry_type' => $data['industryType'],
                'loan_purpose' => $data['loanPurpose'],
                'business_address' => $data['businessAddress'],
                'postcode' => $data['postcode'],
                'city' => $data['city'],
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'personal_id' => $data['personal_id'],
                'client_city' => $data['clientCity'],
                'client_address' => $data['clientAddress'],
                'client_postcode' => $data['clientPostcode'],
                'consent_direct_marketing' => $data['consentDirectMarketing'],
                'ip_address' => $ip_address,
                'submitted_at' => strtotime('now'),
                'eadi_id' => $res_data['payload']['id'],
            ));

            $redirect_url = get_option('eadi_' . strtolower($res_data['status']) . '_slug');
            $redirect_url = $redirect_url ? $redirect_url : '/loan-error';

            echo json_encode(array(
                'status' => true,
                'message' => 'Success',
                'data' => $res_data,
                'api_status' => $res_data['status'],
                'code' => $res_code,
                'info' => $res_info,
                'log_status' => $log_status,
                'redirect_url' => home_url() . $redirect_url,
            ));
            exit(0);
        } else if ( isset($_GET['handle']) && ($_GET['handle'] === 'accept-offer' || $_GET['handle'] === 'reject-offer') && isset($_GET['id']) && isset($_GET['product']) ){
            if( $_GET['handle'] === 'accept-offer' ){
                $offer_accept = true;
                $data = [
                    'action'=> 'UPDATE',
                    'id'=> $_GET['id'],
                    'product'=> $_GET['product'],
                    'status'=> 'SELECTED',
                ];
            } else if( $_GET['handle'] === 'reject-offer' ){
                $offer_accept = false;
                $data = [
                    'action'=> 'UPDATE',
                    'id'=> $_GET['id'],
                    'product'=> $_GET['product'],
                    'status'=> 'WITHDRAWN',
                ];
            }
            $data_json = json_encode($data);
            $api_username = get_option('eadi_api_username');
            $api_password = get_option('eadi_api_password');
            $api_key = get_option('eadi_api_key');
            $api_host = get_option('eadi_api_host');
            $api_token = sha1($api_key . $data_json);
            $api_url = 'https://' . $api_host . '/EADI/' . $api_username . '/' . $api_token . '/';

            $data_encrypted = encrypt($data_json, $api_key);
            $data_encoded = base64_encode($data_encrypted);
            $data_url_friendly = urlencode($data_encoded);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, "data={$data_url_friendly}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $api_username . ":" . $api_password);

            $res_data = curl_exec($ch);
            $res_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $res_info = curl_getinfo($ch);
            curl_close($ch);
            $real_res_data = $res_data;
            $res_data = json_decode($res_data, true);
            $res_data = trim($res_data);
            if( strlen($res_data) < 10 ){
                if($offer_accept){
                    $redirect_url = home_url('/offer-accepted/');
                } else {
                    $redirect_url = home_url('/offer-rejected/');
                }
                // echo $redirect_url;
                if(isset($_GET['dev']) && $_GET['dev']){
                    var_dump($res_code);
                    var_dump($real_res_data);
                } else {
                    if($res_code !== 400){
                        header('Location: ' . $redirect_url);
                    } else {
                        var_dump($real_res_data);
                    }
                }
            } else {
                var_dump($res_data);
            }
            exit();
        } else if( isset($_GET['handle']) && $_GET['handle'] === 'get-offer-details' ){
            $data = [
                'action'=> 'OFFERS',
                'id'=> $_GET['id'],
            ];
            $data_json = json_encode($data);
            $api_username = get_option('eadi_api_username');
            $api_password = get_option('eadi_api_password');
            $api_key = get_option('eadi_api_key');
            $api_host = get_option('eadi_api_host');
            $api_token = sha1($api_key . $data_json);
            $api_url = 'https://' . $api_host . '/EADI/' . $api_username . '/' . $api_token . '/';

            $data_encrypted = encrypt($data_json, $api_key);
            $data_encoded = base64_encode($data_encrypted);
            $data_url_friendly = urlencode($data_encoded);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, "data={$data_url_friendly}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $api_username . ":" . $api_password);

            $res_data = curl_exec($ch);
            $res_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $res_info = curl_getinfo($ch);
            curl_close($ch);
            $res_data = json_decode($res_data, true);
            var_dump($res_data);
        } else {
            echo json_encode(array(
                'status' => false,
                'message' => 'Unknown request.',
                'data' => $_POST,
            ));
            exit(0);
        }
    }

    function show_plugin_menu_page(){
        ?>
        <div class="wrap">
            <h1><?php echo $this->plugin_name; ?></h1>
            <form action="options.php" method="post">
                <?php
                    settings_fields('eadi_plugin_settings');
                    do_settings_sections($this->plugin_slug . '_page');
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    function show_submissions_page(){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        if(isset($_POST['action']) && $_POST['action'] === 'truncate_submissions' ){
            $sql = "TRUNCATE TABLE $table_name";
            $wpdb->query($sql);
            $results = false;
        } else {
            $sql = "SELECT * FROM $table_name";
            $results = $wpdb->get_results($sql);
        }
        ?>
        <div class="wrap">
            <h1>Submissions</h1>
            <p>These are the submission values.</p>
            <form action="" method="POST">
                <input type="hidden" name="action" value="truncate_submissions" />
                <button type="submit" class="button">Truncate Table (Delete All Entries)</button>
            </form>
            <br />
            <?php
            if($results){
                ?>
                    <table class="striped widefat wp-list-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">EADI Id</th>
                                <th scope="col">FirstName</th>
                                <th scope="col">LastName</th>
                                <th scope="col">Email</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Organization No</th>
                                <th scope="col">Group Name</th>
                                <th scope="col">Entity Type</th>
                                <th scope="col">Business Yrs</th>
                                <th scope="col">Revenue</th>
                                <th scope="col">Industry Type</th>
                                <th scope="col">Loan Purpose</th>
                                <th scope="col">Date Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach( $results as $key => $row ) {
                                ?>
                                    <tr>
                                        <td><?php echo $row->id; ?></td>
                                        <td><?php echo $row->eadi_id; ?></td>
                                        <td><?php echo $row->first_name; ?></td>
                                        <td><?php echo $row->last_name; ?></td>
                                        <td><?php echo $row->email; ?></td>
                                        <td><?php echo $row->amount; ?></td>
                                        <td><?php echo $row->organization_number; ?></td>
                                        <td><?php echo $row->group_name; ?></td>
                                        <td><?php echo $row->entity_type; ?></td>
                                        <td><?php echo $row->yrs_business; ?></td>
                                        <td><?php echo $row->revenue; ?></td>
                                        <td><?php echo $row->industry_type; ?></td>
                                        <td><?php echo $row->loan_purpose; ?></td>
                                        <td><?php echo date('Y-m-d h:i A', $row->submitted_at); ?></td>
                                    </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                <?php
            } else {
                ?>
                <p><strong>No Submissions</strong></p>
                <?php
            }
            ?>
        </div>
        <?php
    }

    function show_offers_page(){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->offers_table;
        if(isset($_POST['action']) && $_POST['action'] === 'truncate_offers' ){
            $sql = "TRUNCATE TABLE $table_name";
            $wpdb->query($sql);
            $results = false;
        } else {
            $sql = "SELECT * FROM $table_name";
            $results = $wpdb->get_results($sql);
        }
        ?>
        <div class="wrap">
            <h1>Offers</h1>
            <?php if( $results ){ ?>
                <div style="overflow:auto;">
                    <table class="striped widefat wp-list-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">EADI Id</th>
                                    <th scope="col">Organization No</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Event</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Amortize Length</th>
                                    <th scope="col">Monthly Cost</th>
                                    <th scope="col">Reason</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Interest</th>
                                    <th scope="col">Setup Fee</th>
                                    <th scope="col">Admin Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach( $results as $key => $row ) {
                                    ?>
                                        <tr>
                                            <td><?php echo $row->id; ?></td>
                                            <td><?php echo $row->eadi_id; ?></td>
                                            <td><?php echo $row->organization_number; ?></td>
                                            <td><?php echo $row->status; ?></td>
                                            <td><?php echo $row->description; ?></td>
                                            <td><?php echo $row->event; ?></td>
                                            <td><?php echo $row->amount; ?></td>
                                            <td><?php echo $row->amortize_length; ?></td>
                                            <td><?php echo $row->monthly_cost; ?></td>
                                            <td><?php echo $row->reason; ?></td>
                                            <td><?php echo $row->product; ?></td>
                                            <td><?php echo $row->interest; ?></td>
                                            <td><?php echo $row->setup_fee; ?></td>
                                            <td><?php echo $row->admin_fee; ?></td>
                                        </tr>
                                    <?php
                                }
                                ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p>No Offers.</p>
            <?php } ?>
        </div>
        <?php
    }

    function eadi_callback(){
        // http://stage.capiq.se/wp-admin/admin-ajax.php?action=eadi_callback

        $auth_username = $_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : '';
        $auth_password = $_SERVER['PHP_AUTH_PW'] ? $_SERVER['PHP_AUTH_PW'] : '';

        $api_username = get_option('eadi_callback_username');
        $api_password = get_option('eadi_callback_password');

        if( $auth_username == $api_username && $auth_password == $api_password ){

            $data = json_decode(file_get_contents('php://input'), true);
            $event = isset($data['event']) ? $data['event'] : '';

            $to = $body = $subject ='';
            global $wpdb;

            if ( $event === 'pre_offer_created' ) {
                $table_name = $wpdb->prefix . $this->submissions_table;
                $eadi_id = $data['payload']['id'];
                $sql = "SELECT * FROM $table_name WHERE eadi_id=$eadi_id";
                $client = $wpdb->get_results($sql);
                $client = $client[0];
                $business_id = $client->organization_number ? $client->organization_number : '';

                $to = $client->email;
                $subject = 'Förhandsbesked Låneansökan';

                $content = wpautop(get_option('eadi_email_success'));
                $offer_block_start = strrpos($content, '[offer_start]');
                $offer_block_end = strrpos($content, '[offer_end]');
                $body = substr($content, 0, $offer_block_start);

                if($offer_block_start > 0 && $offer_block_end > 0) {
                    $offer_text = substr($content, $offer_block_start);
                    $offer_text = str_replace('[offer_start]', '', $offer_text);
                    $offer_text =  explode('[offer_end]', $offer_text)[0];

                    foreach ($data['offers'] as $key => $offer) {
                        $offer_text = str_replace('[business_id]', $offer['business_id'], $offer_text);
                        $offer_text = str_replace('[amount]', number_format($offer['amount'], 0,'', ' '), $offer_text);
                        $offer_text = str_replace('[amortizeLength]', number_format($offer['amortizeLength'], 0,'', ' '), $offer_text);
                        $offer_text = str_replace('[monthlyCost]', number_format($offer['monthlyCost'], 0, '',' '), $offer_text);
                        $offer_text = str_replace('[accept_offer]', admin_url('admin-ajax.php?action=blf_ajax_server&handle=accept-offer&id=' . $eadi_id . '&product=' . $offer['product']), $offer_text);
                        $offer_text = str_replace('[reject_offer]', admin_url('admin-ajax.php?action=blf_ajax_server&handle=reject-offer&id=' . $eadi_id . '&product=' . $offer['product']), $offer_text);

                        $body .= $offer_text;
                    }
                }

                $end_text = explode('[offer_end]', $content);
                $body .= $end_text[1];

            } else if ( $event === 'pre_application_rejected' ) {
                $table_name = $wpdb->prefix . $this->submissions_table;
                $eadi_id = $data['payload']['id'];
                $sql = "SELECT * FROM $table_name WHERE eadi_id=$eadi_id";
                $client = $wpdb->get_results($sql);
                $client = $client[0];
                $business_id = $client->organization_number ? $client->organization_number : '';

                $to = $client->email;
                $subject = 'Förhandsbesked Låneansökan';

                /*
                $content = wpautop(get_option('eadi_email_fail'));
                if(strrpos($content, '[eadi_id]')){
                    $body = str_replace('[eadi_id]', $eadi_id, $content);
                } else {
                    $body = $content;
                }
                */

                $body .= '<p>Hej</p>';
                $body .= '<p>Tack för er förfrågan.<br />';
                $body .= 'Er kreditansökan med ansökningsnummer ' . $eadi_id . ' blev dessvärre inte beviljad. <br />';
                $body .= 'Men det kan finnas alternativ. <br />';
                $body .= 'Vänligen se alternativen nedan.</p>';
                $body .= '<p>Behöver ni frigöra likviditet så är ni välkommen att ansöka om att sälja eller belåna er fakturor.<br />';
                $body .= 'För att ansöka om att använda våra fakturatjänster klicka <strong>här</strong>.<br />';
                $body .= '<a href="https://app.capiq.se">Ansök om fakturatjänster</a> </p>';
                $body .= '<p>Önskar ni att ansöka om kredit från andra kreditgivare?<br />';
                $body .= 'Gå då in på länken nedan och skicka en ansökan.<br />';
                $body .= '<strong>Ni kan se ert låneutrymme hos Krea kostnadsfritt och utan UC:</strong><br />';
                $body .= '<a href="https://krea.se/krea_capiq/?utm_source=capiq&utm_medium=partner">Vidare till Krea</a></p>';
                $body .= '<p>Ni får svar direkt och kan sedan bestämma er om ni vill fortsätta göra en ansökan.</p>';
            } else {
                $eadi_id = $data['payload']['id'];
                $to = 'capiqse@gmail.com';
                $subject = 'Error Response';
                $body .= '<p>This is the response other than "pre_offer_created" and "pre_application_rejected":</p>';
                $body .= json_encode(array('data' => $data));
            }

            $body .= '<p>Bästa hälsningar</p>';
            $body .= '<p>CapIQ <br />';
            $body .= 'Box 10072,  434 21 Kungsbacka <br />';
            $body .= '+46(0)10 750 07 47 <br />';
            $body .= '<a href="https://www.capiq.se">www.capiq.se</a> <br />';
            $body .= '<a href="https://facebook.com/CapIQFinance/">facebook.com/CapIQFinance/</a></p>';


            $headers = array('Content-Type: text/html; charset=UTF-8', 'Bcc: capiqse@gmail.com, pawansachin06@gmail.com, byvexarvind@gmail.com');
            wp_mail($to, $subject, $body, $headers);


            // common values
            $table_name = $wpdb->prefix . $this->offers_table;

            $eadi_id = $data['payload']['id'];
            $organization_number = isset($business_id) ? $business_id : '';
            $status = isset($data['status']) ? $data['status'] : '';
            $description = '';
            $event = isset($event) ? $event : '';
            $reason = isset($reason) ? $reason : '';

            if( isset($data['offers']) && count($data['offers']) ) {
                foreach ($data['offers'] as $key => $offer) {
                    $db_data = array(
                        'eadi_id' => $eadi_id,
                        'organization_number' => $organization_number,
                        'status' => $status,
                        'description' => $description,
                        'event' => $event,
                        'reason' => $reason,
                        'product' => $offer['product'],
                        'amortize_length' => $offer['amortizeLength'],
                        'amount' => $offer['amount'],
                        'monthly_cost' => $offer['monthlyCost'],
                        'interest' => $offer['interest'],
                        'setup_fee' => $offer['setupFee'],
                        'admin_fee' => $offer['adminFee'],
                    );
                    $wpdb->insert($table_name, $db_data);
                }
            } else {
                $db_data = array(
                    'eadi_id' => $eadi_id,
                    'organization_number' => $organization_number,
                    'status' => $status,
                    'description' => $description,
                    'event' => $event,
                    'reason' => $reason,
                    'product' => '', // this is offer related value
                    'amortize_length' => '', // this is offer related value
                    'amount' => '', // this is offer related value
                    'monthly_cost' => '', // this is offer related value
                    'interest' => '', // this is offer related value
                    'setup_fee' => '', // this is offer related value
                    'admin_fee' => '', // this is offer related value
                );
                $wpdb->insert($table_name, $db_data);
            }

            header("Connection: close");
            echo 'OK';
            header("Content-Encoding: none");
            header("Content-type: text/plain");
            http_response_code(200);
        }
        exit(0);
    }

    function show_instructions_page(){
        ?>
        <div class="wrap">
            <h1>Instructions</h1>
            <p>The usage of this plugin is based on 4 shortcodes, namely:</p>
            <ol>
                <li>
                    <p><strong>[eadi-loan-form]</strong> : This shortcode shows the multistep form for the visitor to enter details.</p>
                </li>
                <li>
                    <p><strong>[eadi-loan-success]</strong> : This shortcode shows the content after success request.</p>
                </li>
                <li>
                    <p><strong>[eadi-loan-rejected]</strong> : This shortcode shows the content after rejected request.</p>
                </li>
                <li>
                    <p><strong>[eadi-loan-error]</strong> : This shortcode shows the content after fail request.</p>
                </li>
            </ol>
            <p>Make sure to check the settings page for changing values related to username, password, host, etc.</p>
        </div>
        <?php
    }
}

$eadi_plugin = new EadiPlugin();