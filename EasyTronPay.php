<?php
/*
Plugin Name: Easy Tron Pay
Description: Easy Tron Pay - Best Woocommerce Crypto Payment Method For USDT (TRC20)
Author: Alireza Sabahi
Author URI: https://sabahi.ir
*/

require(plugin_dir_path( __FILE__ ) . 'main/vendor/autoload.php');
require(plugin_dir_path( __FILE__ ) . 'main/phpqrcode/phpqrcode.php');

use TronTool\Credential;
use TronTool\Address;

register_activation_hook( __FILE__, 'EasyTronPay_on_activation' );

function EasyTronPay_on_activation(){
	// create the custom table
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'EasyTronPay';
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
              `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `privateKey` varchar(100) NOT NULL UNIQUE KEY,
              `publicKey` varchar(300) DEFAULT NULL,
              `Address` varchar(300) DEFAULT NULL,
              `Amount` varchar(300) DEFAULT NULL,
              `order_id` varchar(50) NOT NULL UNIQUE KEY,
              `flag` int(1) NOT NULL DEFAULT 0
            ) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (class_exists('WC_Gateway_easytronpay'))
{
    return;
} // Stop if the class already exists

add_action('plugins_loaded', 'init_custom_gateway_class');
function init_custom_gateway_class(){

    class WC_Gateway_easytronpay extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'easytronpay_payment';

            $this->id                 = 'easytronpay';
            $this->icon               = apply_filters('woocommerce_easytronpay_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'EasyTronPay', $this->domain );
            $this->method_description = __( 'Allows payments with TRC20.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->address  = $this->get_option( 'address' );
            $this->privateKey  = $this->get_option( 'privateKey' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
			add_action( 'woocommerce_receipt_easytronpay', array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            // add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            // add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable EasyTronPay Payment', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'EasyTronPay Payment', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'address' => array(
                    'title'       => __( 'Wallet Addres', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method main wallet Address that provides fees.', $this->domain ),
                    'default'     => __('Base Wallet Address', $this->domain),
                    'desc_tip'    => true,
                ),
                'privateKey' => array(
                    'title'       => __( 'Private Key', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method main wallet Private Key that provides fees.', $this->domain ),
                    'default'     => __('Base Wallet Private Key', $this->domain),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }
		function receipt_page($order)
		{   
		    echo '<div style="text-align:center;background: white;padding: 20px; border-radius: 10px;color: black">';
		    echo 'Please transfer your order amount in USDT(TRC-20) to wallet address mentioned below.<br />';
		    echo 'Destination wallet Address is:.<br />';
            global $wpdb;
            $table = $wpdb->prefix.'EasyTronPay';
		    $mylink = $wpdb->get_row( "SELECT * FROM $table WHERE order_id = '$order'" );
		    if($mylink == NULL){
    		    $order_data = wc_get_order( $order );
                $credential = Credential::create();
                $prke = $credential->privateKey();
                $puke = $credential->publicKey();
                $addr = $credential->address();
		        $address = Address::fromPublicKey($puke)->base58();
		        echo '<br />'.$address.'<br />';
                $data = array('privateKey' => $prke,'publicKey' => $puke,'Address' => $address,'order_id' => $order,'Amount' => $order_data->get_total());
                $ot = $order_data->get_total();
                $format = array('%s');
                $wpdb->insert($table,$data,$format);
                $my_id = $wpdb->insert_id;
		        echo '<img src="'.plugins_url( 'main/phpqrcode/qr.php?text='.$addr.'', __FILE__ ).'" width="250px" />';
		    } else {
		        $addr = Address::fromPublicKey($mylink->publicKey);
		        echo '<br />'.$addr.'<br />';
		        echo '<img src="'.plugins_url( 'main/phpqrcode/qr.php?text='.$addr.'', __FILE__ ).'" width="250px" />';
                $ot = $mylink->Amount;
		    }
		    echo '<br /> YOUR ORDER AMOUNT IS:<br />';
		    echo '<br /><h4 style="color:black"> $ '.$ot.'</h4><br />';
		    echo '<br />We are processing your payment in background, please do not close this window.<br />';
		    echo 'Once we get your payment confirmation we will Complete the order and redirect you to your Order.';
		    echo '<h5 style="color:black"><div id="countdown">30:00</div></h5>';
		    echo '</div>';
		    $order1 = wc_get_order( $order );
		    echo '<script>
		    jQuery(document).ready(function(){
                setInterval(function(){ 
                    //code goes here that will be run every 5 seconds.    
                    jQuery.ajax({
                        type: "POST",
                        data: {address: "'.$addr.'", order: "'.$order.'"},
                        url: "'.plugins_url( 'check.php', __FILE__ ).'",
                        success: function(result) {
                            if(result != "false"){
                                window.location.href = "'.$this->get_return_url( $order1 ).'";
                            }
                        }
                    });
                }, 20000);
            });
		    </script>';
       }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'easytronpay' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
			$order = new WC_Order($order_id);
			return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true )); 
		}
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_EasyTronPay_gateway_class' );
function add_EasyTronPay_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_easytronpay'; 
    return $methods;
}



/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );
function custom_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    $o = $order->id;
            global $wpdb;
            $table = $wpdb->prefix.'EasyTronPay';
		    $mylink = $wpdb->get_row( "SELECT * FROM $table WHERE order_id = '$o'" );
    if($method != 'easytronpay')
        return;
        
        
    echo '<p><strong>'.__( 'Wallet: ').':</strong> <a href="https://shasta.tronscan.org/#/address/' . $mylink->Address . '" target="_blank">' . $mylink->Address . '</a></p>';
}





 add_action('wp_enqueue_scripts', 'mslb_public_scripts');

 function mslb_public_scripts(){
   wp_register_script('easytronpay_js', plugins_url('/js/js.js',__FILE__ ), array('jquery'), '', true);
   wp_enqueue_script('easytronpay_js');
 }
 
 
 
 
 
 
     
    //add tinext menu to admin page
    function EasyTronPay_menu() {
    
      add_menu_page( 
          'EasyTronPay', 
          'EasyTronPay', 
          'edit_posts', 
          'EasyTronPay', 
          'EasyTronPay_Order_Data', 
          'dashicons-media-spreadsheet' 
    
         );
    }
    add_action('admin_menu', 'EasyTronPay_menu');
    
    //display table and actions in admin page
    function EasyTronPay_Order_Data(){
        global $wpdb;
        $table = $wpdb->prefix.'EasyTronPay';
        $result = $wpdb->get_results( "SELECT * FROM $table WHERE flag = 1" );
        echo '<form method="post" action="'.plugin_dir_url( __FILE__ ).'transfer.php">';
            echo '<table width="60%" style="border: 1px solid black">';
                echo '<tr style="border: 1px solid black">';
                    echo '<th style="border: 1px solid black">';
                        echo 'Choose';
                    echo '</th>';
                    echo '<th style="border: 1px solid black">';
                        echo 'ID';
                    echo '</th>';
                    echo '<th style="border: 1px solid black">';
                        echo 'Order ID';
                    echo '</th>';
                    echo '<th style="border: 1px solid black">';
                        echo 'Private Key';
                    echo '</th>';
                    echo '<th style="border: 1px solid black">';
                        echo 'Amount';
                    echo '</th>';
                echo '</tr>';
        foreach ($result as $post){
                echo '<tr>';
                    echo '<td style="border: 1px solid black; text-align: center">';
                        echo '<input type="checkbox" id="order" name="orders[]" value="'.$post->order_id.'">';
                    echo '</td>';
                    echo '<td style="border: 1px solid black; text-align: center">';
                        echo $post->ID;
                    echo '</td>';
                    echo '<td style="border: 1px solid black; text-align: center">';
                        echo $post->order_id;
                    echo '</td>';
                    echo '<td style="border: 1px solid black; text-align: center">';
                        echo $post->privateKey;
                    echo '</td>';
                    echo '<td style="border: 1px solid black; text-align: center">';
                        echo $post->Amount;
                    echo '</td>';
                echo '</tr>';
        }
            echo '</table>';
            submit_button('Submit');
        echo '</form>';
    }