<?php 

class CashBill_Payment extends WC_Payment_Gateway {
  
private $paymentUrl;    
    
function __construct()
{
$this->id = 'cashbill_payment';
$this->icon = plugins_url( 'img/cashbill_100x39.png', __FILE__ );
$this->has_fields = false;
$this->method_title = 'Płatności CashBill';
$this->method_description = 'Płatności CashBill to usługa agregująca wszystkie dostępne na rynku finansowym instrumenty płatnicze. Integrując nasz pakiet płatności ze swoim serwisem zapewniasz, że każdy klient będzie mógł dokonać płatności elektronicznej. ';
$this->init_form_fields();
$this->init_settings();
$this->title = $this->get_option( 'title' );
add_action( 'woocommerce_api_' . $this->id, array( $this, 'cashbill_callback' ) );
add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

}


public function cashbill_callback()
{

     if(md5($_GET['cmd'].$_GET['args'].$this->get_option( 'cashbill_key')) != $_GET['sign'])
    {
      echo 'BLAD SYGNATURY';   
      exit();   
     }
     $this->setPaymentUrl();
    
$signature = SHA1($_GET['args'].$this->get_option( 'cashbill_key'));

$response = wp_remote_get( $this->paymentUrl.'payment/'.$this->get_option( 'cashbill_id' ).'/'.$_GET['args'].'?sign='.$signature);    
$response = json_decode($response['body']);


$order = new WC_Order( $response->additionalData );  
if($response->status == 'PositiveFinish')
{
$order->add_order_note( __( 'Płatności CashBill na kwotę '.$response->amount->value.' '.$response->amount->currencyCode.' została przyjęta.', 'cashbill_payment' ) );                                    
$order->payment_complete(); 
echo 'OK';
exit();
}
if($response->status == 'Abort' || $response->status == 'Fraud' || $response->status == 'NegativeFinish')
{
$order->add_order_note( __('Płatność nie została przyjęta system zwrócił status '.$response->status, 'cashbill_payment' ) );
$order->cancel_order('Płatność nie została przyjęta system zwrócił status '.$response->status);
}
echo 'OK';
exit();
}

 public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'     => __( 'Włączony / Wyłączony', 'cashbill_payment' ),
                'label'     => __( 'Włącz metodę płatności', 'cashbill_payment' ),
                'type'      => 'checkbox',
                'default'   => 'no',
            ),
			'title' => array(
                'title'     => __( 'Tytuł', 'cashbill_payment' ),
                'type'      => 'text',
				'default' => __( 'Płatności CashBill', 'woocommerce' ),
                'desc_tip'  => __( 'Tytuł płatności widoczny dla użytkownika w momencie wybierania metody płatności', 'cashbill_payment' ),
            ),
            'cashbill_id' => array(
                'title'     => __( 'Identyfikator Punktu Płatności', 'cashbill_payment' ),
                'type'      => 'text',
				'default' => __( 'Identyfikator Punktu Płatności', 'woocommerce' ),
                'desc_tip'  => __( 'Identyfikator Punktu Płatności znajdziesz w panelu swojego Punktu Płatności.', 'cashbill_payment' ),
            ),
			'cashbill_key' => array(
                'title'     => __( 'Klucz Punktu Płatności', 'cashbill_payment' ),
                'type'      => 'text',
				'default' => __( 'Klucz Punktu Płatności', 'woocommerce' ),
                'desc_tip'  => __( 'Klucz Punktu Płatności znajdziesz w panelu swojego Punktu Płatności.', 'cashbill_payment' ),
            ),
			'test' => array(
                'title'     => __( 'Tryb Testowy', 'cashbill_payment' ),
				 'desc_tip'  => __( 'Włączając tryb testowy będziesz mógł sprawdzić poprawność wykonywanych transkacji.', 'cashbill_payment' ),
                'type'      => 'checkbox',
                'default'   => 'yes',
            ),
        );      
		
    }

    public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );
        $woocommerce->cart->empty_cart();
        $this->setPaymentUrl();
    $QueryOrder = array(
        'title'=> 'Zamówienie Numer : '.$order_id,
        'amount.value'=>$order->order_total,
        'amount.currencyCode'=>$order->get_order_currency(),
        'description'=>'Płatnośc do zamówienia '.$order_id,
        'additionalData'=>$order_id,
        'referer'=>'WooCommerce',
        'returnUrl'=> $this->get_return_url( $order ),
        'negativeReturnUrl'=>$order->get_cancel_order_url(),
        'personalData.firstName'=>$order->billing_first_name,
        'personalData.surname'=>$order->billing_last_name,
        'personalData.email'=>$order->billing_email,
        'personalData.country'=>$order->billing_country,
        'personalData.city'=>$order->billing_city,
        'personalData.postcode'=>$order->billing_zip,
        'personalData.street'=>$order->billing_address_1,
        'sign'=>SHA1('Zamówienie Numer : '.$order_id.$order->order_total.$order->get_order_currency().$this->get_return_url( $order ).'Płatnośc do zamówienia '.$order_id.$order->get_cancel_order_url().$order_id.'WooCommerce'.$order->billing_first_name.$order->billing_last_name.$order->billing_email.$order->billing_country.$order->billing_city.$order->billing_zip.$order->billing_address_1.$this->get_option( 'cashbill_key' )),
    );
    
    $response = wp_remote_post( $this->paymentUrl.'payment/'.$this->get_option( 'cashbill_id' ), array(
        'method'    => 'POST',
        'timeout'   => 90,
        'body' => $QueryOrder,
        'sslverify' => false,
    ) );    
        $response = json_decode($response['body']);
        $order->add_order_note( __( 'Rozpoczecie płatności CashBill', 'cashbill_payment' ) );
            return array(
                'result'   => 'success',
                'redirect' =>  $response->redirectUrl,
            );
 
    }
    
    public function setPaymentUrl()
    {
        
    if($this->get_option( 'test' ) == 'yes')
    {
    $this->paymentUrl = 'https://pay.cashbill.pl/testws/rest/';    
    return;    
    }
    $this->paymentUrl = 'https://pay.cashbill.pl/ws/rest/';
    return;
    }
    
    
    public function admin_options() {
        ?>
         
                  <h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>
          
                  <?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>
          
          <p><a href="<?php echo plugins_url( 'pdf/Instrukcja instalacji.pdf', __FILE__ ); ?>" target="_blank"><img src="<?php echo plugins_url( 'img/pdf-icon.png', __FILE__ ); ?>" /> Instrukcja Instalacji</a></p>
          
          <div style="margin-bottom:40px;">
          <fieldset>
          <h3>Adres serwerowego potwierdzenia transakcji:</h3>
<input id="woocommerce_cashbill" class="input-text regular-input " type="text" placeholder="" value="<?=  str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'cashbill_payment', home_url( '/' ) ) ) ?>" style="" name="woocommerce_cashbill_connect_url" size="70" readonly/>
</fieldset>
</div>
          
          
                  <table class="form-table">
                  <h3>Panel Administracyjny</h3>
                      <?php $this->generate_settings_html(); ?>
                  </table><?php
             
    }
    
    
    

}