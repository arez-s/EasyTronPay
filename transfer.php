<?php
$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';
require(plugin_dir_path( __FILE__ ) . 'main/vendor/autoload.php');

use TronTool\TronKit;
use TronTool\TronApi;
use TronTool\Credential;
use TronTool\Address;
if(isset($_POST['orders'])){

    $auth = get_option('woocommerce_easytronpay_settings');
    foreach($_POST['orders'] as $order){
        
        global $wpdb;
        $table = $wpdb->prefix.'EasyTronPay';
		$mylink = $wpdb->get_row( "SELECT * FROM $table WHERE order_id = '$order'" );
        $api = TronApi::testNet();
        $credential = Credential::fromPrivateKey($auth['privateKey']);
        $kit = new TronKit($api,$credential);
        
        $from = $credential->address()->base58();
        
        $to = $mylink->Address;
        $ret = $kit->sendTrx($to,1000,$from);
        echo 'result => ' . $ret->result . '<br />';
        
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.shasta.trongrid.io/wallet/freezebalance',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'
        {
             "owner_address": "'.Address::decode($auth['address']).'",
             "frozen_balance": 100000000,
             "frozen_duration": 3,
             "resource": "ENERGY",
             "receiver_address": "'.Address::decode($to).'"
        }
        ',
          CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        $res =  json_decode($response);
        $raw = str_replace('"', '\"', json_encode($res->raw_data));
        $res->txID;
        $res->raw_data_hex;
        
        $resp = $credential->signTx($res);
        $resp->signature[0];
        $resp->txID;
        $resp->raw_data_hex;
        $rawNew = str_replace('"', '\"', json_encode($resp->raw_data));
        

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.shasta.trongrid.io/wallet/broadcasttransaction',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'
        {
             "raw_data": "'.$rawNew.'",
             "raw_data_hex": "'.$resp->raw_data_hex.'",
             "signature": [
                  "'.$resp->signature[0].'"
             ],
             "txID": "'.$resp->txID.'"
        }
        ',
          CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        
        $fromKey = $mylink->privateKey;
        $kit = new TronKit(
          TronApi::testNet(),//Access to the main chain
          Credential::fromPrivateKey($fromKey)//Use the specified private key
        );
        
        $to = $auth['address'];//transfer destination address
        $amount = $mylink->Amount*1000000;//The amount of Trc20 tokens transferred
        $contractAddress ='TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs';//The deployment address of the USDT-TRC20 token contract
        $usdt = $kit->Trc20($contractAddress);//Create Trc20 token contract instance
        $ret = $usdt->transfer($to,$amount);//Transfer Trc20 tokens
        if($ret->result == true){
    	    $q = $wpdb->get_row( "UPDATE $table SET flag=2 WHERE order_id = '$order'" );
        }
    }
    wp_redirect(admin_url( 'admin.php?page=EasyTronPay' ));
}
?>