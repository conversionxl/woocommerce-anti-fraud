<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_AF_Settings' ) ) :

function wc_af_add_settings() {
    /**
     * Settings class
     *
     * @since 1.0.0
     */
    class WC_AF_Settings extends WC_Settings_Page {
        
        /**
         * The request response
         * @var array
        */
        private $response = null;

        /**
         * The error message
         * @var string
        */
        private $error_message = '';

        /**
         * Setup settings class
         *
         * @since  1.0
         */

        const SETTINGS_NAMESPACE = 'anti_fraud';

        public function __construct() {
            $this->id    = 'wc_af';
            $this->label = __( 'Anti Fraud', 'wc_af' );
            
            add_filter( 'woocommerce_settings_tabs_array',        array( $this, 'add_settings_page' ), 20 );
            add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
            add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
            add_action( 'woocommerce_sections_' . $this->id,      array( $this, 'output_sections' ) );
        }
        
        /**
         * Get sections
         *
         * @return array
         */
        public function get_sections() {
        
            $sections = array(
                ''         => __( 'General Settings', 'wc_af' ),
                'black_list' => __( 'Blacklist Settings', 'wc_af' ),
                'paypal_settings' => __( 'Paypal Settings', 'wc_af' ),
            );
            
            return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
        }
        
        /**
         * Get settings array
         *
         * @since 1.0.0
         * @param string $current_section Optional. Defaults to empty string.
         * @return array Array of settings
         */
        public function get_settings( $current_section = '' ) {
            
            $score_options = array();
            for ( $i = 100; $i > - 1; $i -- ) {
                if ( ( $i % 5 ) == 0 ) {
                    $score_options[$i] = $i;
                }
            }

            $rule_weight = array();
            for ($i = 20; $i > -1; $i -- ){
            	$rule_weight[$i] = $i;	
            } 

            if ( 'black_list' == $current_section ) {
            
                /**
                 * WCAF Filter Plugin  Blacklist Settings
                 *
                 * @since 1.0.0
                 * @param array $settings Array of the plugin settings
                */
                $settings = apply_filters( 'myplugin_company_file_settings', array(
                    array(
                        'name' => __( 'Blacklist' ),
                        'type' => 'title',
                        'desc' => __( '' ),
                        'id'   => 'wc_af_blacklist_settings', 
                    ),
                    //Enable email blacklist
                    array(
						'title'       => __( 'Enable email blacklist', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => 'wc_af_emial_blacklist',
						'default'     => 'no',
						'desc' => __( '' ),
						'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . 'enable_automatic_email_blacklist', 
					),  
                    //Enable automatic blacklisting
					array(
						'title'       => __( 'Enable automatic blacklisting', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => 'wc_af_email_blacklist',
						'default'     => 'no',
						'desc' => __( '<br/>Add email addresses of orders reported with a high risk of fraud to blacklist automatically' ),
						'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . 'enable_automatic_blacklist', 
					),
					//Block these email addresses
					array(
                        'name'        => __( 'Block these email addresses', 'woocommerce-anti-fraud' ),
                        'type'        => 'textarea',
                        'desc'        => __( "The following email addresses are not safe.", 'woocommerce-anti-fraud '),
                        'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . 'blacklist_emails',
                        'css'         => 'width:100%; height: 100px;',
                        'default'     => '',
                        'class'       => 'wc_af_tags_input'  
                    ), 
                    array(
                        'type' => 'sectionend',
                        'id'   => 'wc_af_blacklist_settings'
                    ),

                ) );
                
            }else if('paypal_settings' == $current_section){
                /**
                 * WCAF Filter Plugin Paypal Settings
                 *
                 * @since 1.0.0
                 * @param array $settings Array of the plugin settings
                */
                $settings = apply_filters( 'wc_af_paypal_settings', array(
                
                    array(
                        'name' => __( 'Antifraud Paypal Settings' ),
                        'type' => 'title',
                        'desc' => __( '' ),
                        'id'   => 'wc_af_paypal_settings', 
                    ),
                    array(
						'title'       => __( 'Enable Paypal Verification', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => 'wc_af_paypal_verification',
						'default'     => 'no',
						'desc' => __( '' ),
						'id'   => 'wc_af_paypal_verification', 
					),  
                    //Prevent downloads if verification failed or still processing
					array(
						'title'       => __( 'Prevent downloads if verification failed or still processing', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => 'wc_af_paypal_verification',
						'default'     => 'no',
						'desc' => __( '' ),
						'id'   => 'wc_af_paypal_prevent_downloads', 
					),
					//Time span before further attempts 
					array(
                        'name'     => __( 'Time span before further attempts' ),
                        'type'     => 'number',
                        'desc'     => __( 'Number of days that have to pass before sending another email if the order is still waiting for verification' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_time_paypal_attempts',
                        'css'         => 'display: block;',
                        'default' => '2',
                        'custom_attributes' => array(
                            'min'  => 1,
                            'step' => 1,
                        ),
                    ),
                    //Time span before the orders are cancelled 
                    array(
                        'name'     => __( 'Time span before the orders are cancelled' ),
                        'type'     => 'number',
                        'desc'     => __( '<br/>Number of days that have to pass before deleting the order if it is not verified' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_day_deleting_paypal_order',
                        'default' => '2',
                        'custom_attributes' => array(
                            'min'  => 1,
                            'step' => 1,
                        ),
                    ),  
                    //Email type
                    array(
                        'name'     => __( 'Email Type', 'woocommerce-anti-fraud' ),
                        'type'     => 'select',
                        'options'  => array(
						    'html'        => __( 'Html', 'woocommerce_antifraud' ),
						    'text'       => __( 'text', 'woocommerce_antifraud' ),
						),
                        'desc'     => __( '<br/>Choose a format for the email.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_email_format',
                        'default' => 'html',
                    ), 
                    //Email subject
                    array(
					    'name'     => __( 'Email Subject', 'woocommerce' ),
					    'desc'     => __( '</br>{site_title} Replaced with the site title' ),
					    'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_email_subject',
					    'type'     => 'text',
					    'placeholder' => '[{site_title}] Confirm your PayPal email address'
					),
					//Email body
					array(
                        'name'        => __( 'Email body', 'woocommerce-anti-fraud' ),
                        'type'        => 'textarea',
                        'desc'        => __( "{site_title} Replaced with the site title<br/>{site_email} Replaced with the site title", 'woocommerce-anti-fraud '),
                        'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_email_body',
                        'css'         => 'width:100%; height: 100px;',
                        'default'     => 'Hi!We have received your order on {site_title}, but to complete we have to verify your PayPal email address.If you havent made or authorized any purchase, please, contact PayPal support service immediately,and email us to {site_email} for having your money back.',
                    ), 
                    //PayPal verified addresses
                    array(
                        'name'        => __( 'Paypal verified address', 'woocommerce-anti-fraud' ),
                        'type'        => 'textarea',
                        'desc'        => __( 'Verified email addresses'),
                        'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_verified_address',
                        'class'         => 'wc_af_tags_input',
                        'default'     => '',
                    ), 
                    array( 
                        'type' => 'sectionend', 
                        'id' => 'wc_af_paypal_settings' 
                    ),
                    
                ) );
            }else {
                
                /**
                 * WCAF Filter Plugin General Settings
                 *
                 * @since 1.0.0
                 * @param array $settings Array of the plugin settings
                 */
                $settings = apply_filters( 'wc_af_general_settings', array(
                
                        array(
                        'name' => __( 'General Settings' ),
                        'type' => 'title',
                        'desc' => '',
                        'id'   => 'wc_af_general_settings' 
                    ),
                    array(
						'title'       => __( 'Admin Email Settings', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '<br/>Send a notification mail to admin showing the outcome of anti-fraud checks.' ),
						'id'	=> 'wc_af_email_notification'
					),    
                    array(
                        'name'     => __( 'Cancel Score', 'woocommerce-anti-fraud' ),
                        'type'     => 'select',
                        'options'  => $score_options,
                        'desc'     => __( 'Orders with a score equal to or greater than this number will be automatically cancelled. Select 0 to disable.', 'woocommerce-anti-fraud' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_cancel_score',
                        'css'         => 'display: block;',
                        'default' => '90',
                    ), 
                    array(
                        'name'     => __( 'On-hold Score', 'woocommerce-anti-fraud' ),
                        'type'     => 'select',
                        'options'  => $score_options,
                        'desc'     => __( 'Orders with a score equal to or greater than this number will be automatically set on hold. Select 0 to disable.', 'woocommerce-anti-fraud' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_hold_score',
                        'css'         => 'display: block;',
                        'default' => '70',
                    ),
                    array(
                        'name'     => __( 'Email Notification Score', 'woocommerce-anti-fraud' ),
                        'type'     => 'select',
                        'options'  => $score_options,
                        'desc'     => __( 'An admin email notification will be sent if an orders scores equal to or greater than this number. Select 0 to disable.', 'woocommerce-anti-fraud' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_email_score',
                        'css'         => 'display: block;',
                        'default' => '50',
                    ),
                    //custom code for send email to other users
                     array(
                        'name'     => __( 'Add More Recipients', 'woocommerce-anti-fraud' ),
                        'type'     => 'text',
                        'options'  => $score_options,
                        'desc'     => __( 'Add multiple email recipients with comma-separated. an email notification will be sent if an order score equal to or greater then Email Notification Score field.', 'woocommerce-anti-fraud' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_custom_email',
                        'css'         => 'display: block;'
                    ),
                    array(
                        'name'        => __( 'Email Whitelist', 'woocommerce-anti-fraud' ),
                        'type'        => 'textarea',
                        'desc'        => __( "Above automated actions don't apply to orders from customers with email addresses entered here. Enter one email address per line.", 'woocommerce-anti-fraud '),
                        'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_whitelist',
                        'css'         => 'width:100%; height: 100px;',
                        'default'     => '',
                    ),   
                    array( 
                        'type' => 'sectionend', 
                        'id' => 'wc_af_general_settings' 
                    ),
                    //thresholds settings
                    array(
                        'name' => __( 'Settings for risk thresholds' ),
                        'type' => 'title',
                        'desc' => '<hr/>',
                        'id'   => 'wc_af_thresholds_settings' 
                    ),
                    array(
                        'name'     => __( 'Medium Risk threshold', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'desc'     => __( '' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_low_risk_threshold',
                        'css'         => 'display: block;',
                        'default' => '25',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'     => __( 'High Risk threshold', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'desc'     => __( '' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_higher_risk_threshold',
                        'css'         => 'display: block;',
                        'default' => '75',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array( 
                        'type' => 'sectionend', 
                        'id' => 'wc_af_thresholds_settings' 
                    ),
                    array(
                        'name' => __( 'Rule Settings' ),
                        'type' => 'title',
                        'desc' => '<hr/>',
                        'id'   => 'wc_af_rule_settings' 
                    ),
                    array(
						'title'       => __( 'Enable first order check', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '<br/>Enable first order check' ),
						'id'	=> 'wc_af_first_order'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_first_order_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),  
                    //custom rule for processing order
                    array(
						'title'       => __( 'Enable first order check for processing order', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'no',
						'desc' => __( '<br/>Enable first order check for in processing order ' ),
						'id'	=> 'wc_af_first_order_custom'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_first_order_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ), 
                    array(
						'title'       => __( 'Enable International order check', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_international_order'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_international_order_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
					array(
						'title'       => __( 'Enable IP geolocation check', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_ip_geolocation_order'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_ip_geolocation_order_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
						'title'       => __( 'Enable Billing and Shipping address check', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_bca_order'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_bca_order_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
						'title'       => __( 'Enable Proxy check', 'woocommerce-anti-fraud' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_proxy_order'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_proxy_order_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
						'title'       => __( 'Enable suspecious domain email check' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '<br/>Enable international order check' ),
						'id'	=> 'wc_af_suspecius_email'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_suspecious_email_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'        => __( 'Suspicious domains', 'woocommerce-anti-fraud', 'woocommerce-anti-fraud' ),
                        'type'        => 'textarea',
                        'desc'        => __( "Email domains consider suspicious.", 'woocommerce-anti-fraud '),
                        'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_suspecious_email_domains',
                        'css'         => 'width:100%; height: 100px;',
                        'default'     => $this->suspicious_domains(),
                        'class'       => 'wc_af_tags_input' 
                    ), 
                    array(
						'title'       => __( 'Enable unsafe countries check' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_unsafe_countries'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_unsafe_countries_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'        => __( 'Define unsafe countries', 'woocommerce-anti-fraud', 'woocommerce-anti-fraud' ),
                        'type'        => 'multiselect',
                        'desc'        => __( "<br>Enter the countries that you consider unsafe.", 'woocommerce-anti-fraud '),
                        'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_define_unsafe_countries_list',
                        'class'        => 'chzn-drop',
                        'options'      => $this->get_countries() 
                    ), 
                    array(
						'title'       => __( 'Enable order amount check (for orders exceeding average order amount)' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_order_avg_amount_check'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_order_avg_amount_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'     => __( 'Average multiplier', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'desc'     => __( 'Total order amount accepted (expressed as multiplier of average order amount).)' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_avg_amount_multiplier',
                        'css'         => 'display: block;',
                        'default' => '2',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                        ),
                    ),
                    array(
						'title'       => __( 'Enable order amount check (for order exceeding the below specified amount)' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_order_amount_check'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_order_amount_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'     => __( 'Amount limit ($)', 'woocommerce-anti-fraud' ),
                        'type'     => 'text',
                        'desc'     => __( 'Total order amount accepted. Set zero for no limit..)' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_amount_limit',
                        'css'         => 'display: block;',
                        'default' => '0',
                    ),
                    array(
						'title'       => __( 'Enable check for attempt count' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_attempt_count_check'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_order_attempt_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'     => __( 'Time span to check' ),
                        'type'     => 'number',
                        'desc'     => __( 'Time span (hours) for check' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_attempt_time_span',
                        'css'         => 'display: block;',
                        'default' => '1',
                    ),
                    array(
                        'name'     => __( 'Maximum number of orders per time span' ),
                        'type'     => 'number',
                        'desc'     => __( 'Maximum number of orders that a user can make in the specified time span' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_max_order_attempt_time_span',
                        'css'         => 'display: block;',
                        'default' => '2',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                        ),
                    ),
					array(
						'title'       => __( 'Enable IP multiple details check' ),
						'type'        => 'checkbox',
						'label'       => '',
						'default'     => 'yes',
						'desc' => __( '' ),
						'id'	=> 'wc_af_ip_multiple_check'
					),
					array(
                        'name'     => __( 'Rule Weight', 'woocommerce-anti-fraud' ),
                        'type'     => 'number',
                        'options'  => $rule_weight,
                        'desc'     => __( 'Weight of the single rule in the total calculation of risk.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_ip_multiple_weight',
                        'css'         => 'display: block;',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                            'max'  => 100
                        ),
                    ),
                    array(
                        'name'     => __( 'Time span (days) to check' ),
                        'type'     => 'number',
                        'desc'     => __( 'Time span (days) to check.' ),
                        'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_ip_multiple_time_span',
                        'css'         => 'display: block;',
                        'default' => '2',
                        'custom_attributes' => array(
                            'min'  => 0,
                            'step' => 1,
                        ),
                    ),
                    array( 
                        'type' => 'sectionend', 
                        'id' => 'wc_af_rule_settings' 
                    ),    
                ) );
                
            }
            
            /**
             * Filter WCAF Settings
             *
             * @since 1.0.0
             * @param array $settings Array of the plugin settings
             */
            return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
            
        }
        
        
        /**
         * Output the settings
         *
         * @since 1.0
         */
        public function output() {
        
            global $current_section;
            
            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::output_fields( $settings );
        }
        
        
        /**
         * Save settings
         *
         * @since 1.0
         */
        public function save() {
        
            global $current_section;
            
            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::save_fields( $settings );
        }

        public function suspicious_domains(){
            $email_domains = array('hotmail',
            'live',
            'gmail',
            'yahoo',
            'mail',
            '123vn',
            'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijk',
            'aaemail.com',
            'webmail.aol',
            'postmaster.info.aol',
            'personal',
            'atgratis',
            'aventuremail',
            'byke',
            'lycos',
            'computermail',
            'dodgeit',
            'thedoghousemail',
            'doramail',
            'e-mailanywhere',
            'eo.yifan',
            'earthlink',
            'emailaccount',
            'zzn',
            'everymail',
            'excite',
            'expatmail',
            'fastmail',
            'flashmail',
            'fuzzmail',
            'galacmail',
            'godmail',
            'gurlmail',
            'howlermonkey',
            'hushmail',
            'icqmail',
            'indiatimes',
            'juno',
            'katchup',
            'kukamail',
            'mail',
            'mail2web',
            'mail2world',
            'mailandnews',
            'mailinator',
            'mauimail',
            'meowmail',
            'merawalaemail',
            'muchomail',
            'MyPersonalEmail',
            'myrealbox',
            'nameplanet',
            'netaddress',
            'nz11',
            'orgoo',
            'phat.co',
            'probemail',
            'prontomail',
            'rediff',
            'returnreceipt',
            'synacor',
            'walkerware',
            'walla',
            'wongfaye',
            'xasamail',
            'zapak',
            'zappo');
            return implode(',', $email_domains);
        }
        
        public function get_countries(){
            $countries_obj   = new WC_Countries();
            $countries       = $countries_obj->__get('countries');
            return $countries;

        }

    }
    $settings[] = new WC_AF_Settings();
     return $settings;
    /*$a =  new WC_AF_Settings();*/
    //$res = $a->get_settings();
   }
add_filter( 'woocommerce_get_settings_pages', 'wc_af_add_settings', 15 );
endif;
