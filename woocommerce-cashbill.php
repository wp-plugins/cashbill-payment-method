<?php
/*
Plugin Name: CashBill.pl - Płatności WooCommerce 
Plugin URI: http://cashbill.pl
Description: Płatności CashBill to usługa agregująca wszystkie dostępne na rynku finansowym instrumenty płatnicze. Integrując nasz pakiet płatności ze swoim serwisem zapewniasz, że każdy klient będzie mógł dokonać płatności elektronicznej.
Version: 1.3.3
Author: CashBill S.A.
Author URI: http://cashbill.pl
*/
 
add_action( 'plugins_loaded', 'cashbill_payment_init');
function cashbill_payment_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
    include_once( 'woocommerce-cashbill-payment.php' );
 
    add_filter( 'woocommerce_payment_gateways', 'cashbill_payment_load_class');
    function cashbill_payment_load_class( $methods ) {
        $methods[] = 'CashBill_Payment';
        return $methods;
    }
}
 
add_action( 'admin_menu', 'add_admin_menu' );

function add_admin_menu(){
    add_menu_page( 'Płatności CashBill', 'Płatności CashBill', 'manage_options','admin.php?page=wc-settings&tab=checkout&section=cashbill_payment', '', plugins_url( 'img/cashbill_50x50.png', __FILE__ ), 56 );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cashbill_payment_links' );
function cashbill_payment_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cashbill_payment' ) . '">' . __( 'Ustawienia', 'cashbill_payment' ) . '</a>',
    );
 
    return array_merge( $plugin_links, $links );    
}