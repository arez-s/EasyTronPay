<?php
$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';
if(isset($_POST['address'])){
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.shasta.trongrid.io/v1/accounts/'.$_POST['address'].'/transactions/trc20',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json'
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    $re = json_decode($response);
        global $wpdb;
        $table = $wpdb->prefix.'EasyTronPay';
        $order = $_POST['order'];
    	$mylink = $wpdb->get_row( "SELECT * FROM $table WHERE order_id = '$order'" );
    	if($mylink != NULL){
    	    $val = $mylink->Amount;
    	}
    if(sprintf("%.2f", $re->data[0]->value/1000000) == $val && $re->data[0]->token_info->symbol == 'USDT'){
        echo "true";
    	$mylink = $wpdb->get_row( "UPDATE $table SET flag=1 WHERE order_id = '$order'" );
        $order1 = new WC_Order($order);
         
        if (!empty($order1)) {
         
        $order1->update_status( 'completed' );
         
        }
    } else {
        echo "false";
    }
}