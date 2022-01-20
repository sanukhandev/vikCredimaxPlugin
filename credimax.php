<?php

/**
 * @package     VikCrediMax
 * @subpackage  core
 * @author     	Sanu Khan <sanulgebello@gmail.com>
 */

defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.payment.payment');


abstract class AbstractCrediMaxPayment extends JPayment{
	
	
	protected function buildAdminParameters() {
		return array(	
            
            'merchantid' => array(
                'label' => __('Merchant ID','vikbooking'),
                'type' => 'text'
            ),
            'password' => array(
                'label' => __('Password','vikbooking'),
                'type' => 'text'
            ),
			'testmode' => array(
				'label' => __('Test Mode','vikbooking'),
				'type' => 'select',
				'options' => array(
					'0' => __('No','vikbooking'),
					'1' => __('Yes','vikbooking')
				)
			),
        );
	}
	
	public function __construct($alias, $order, $params = array()) {
		parent::__construct($alias, $order, $params);
	}
	
	protected function beginTransaction() {
		$merchantId = $this->getParam('merchantid');
		$password = $this->getParam('password');
		$transactionName = $this->get('transaction_name');
		$uniq_id = $this->get('sid')."-".$this->get('ts');
		$amount = $this->get('total_to_pay');
		$res = $this->callPaymentApi($merchantId, $password, $transactionName, $uniq_id, $amount);
	
		if(!$res) {
			return false;
		}
		if($res->result == 'SUCCESS')  {
			$sessionId = $res->session->id;

			echo '<div class="vbo-booking-details-head vbo-booking-details-head-cancelled retry-button hide " style="cursor:pointer">
			<h4 style="color:white">Payment Cancelled Retry</h4>
			</div>';

			echo "<script type='text/javascript'>
			var _jQ = jQuery.noConflict();

			function errorCallback(error) {
				_jQ('.retry-button').removeClass('hide').addClass('show');
				console.log(JSON.stringify(error));
		  }

		  _jQ('.retry-button').click(function() {
			location.reload();
		});
		  function cancelCallback() {
			_jQ('.retry-button').removeClass('hide').addClass('show');
				console.log('Payment cancelled');
		  }
				
			Checkout.configure({
				session: { 
				  id: '".$sessionId."'
					 },
				interaction: {
					  merchant: {
						  name: 'Tulip Hotel',
						  address: {
							  line1: '200 Sample St',
							  line2: '1234 Example Town'            
						  }    
					  }
				 }
			  });

			  Checkout.showLightbox();
				</script>";

		}



	}
	
	protected function validateTransaction(JPaymentStatus &$status) {
		/** See the code below to build this method */
		return array();

	}

	protected function complete($esit = 0) {
		/** See the code below to build this method */
	}



	private function callPaymentApi($merchantId, $password, $transactionName, $uniq_id, $amount) {
		$curl = curl_init();

			curl_setopt_array($curl, array(
			CURLOPT_URL => "https://credimax.gateway.mastercard.com/api/rest/version/62/merchant/".$merchantId."/session",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
				"apiOperation": "CREATE_CHECKOUT_SESSION",
				"interaction": {
					"operation": "PURCHASE"
				},
				"order": {
					"id": "'.$uniq_id.'",
					"currency": "BHD",
					"description": "'.$transactionName.'",
					"amount": '.$amount.'
				}
			} ',
			CURLOPT_HTTPHEADER => $this->prepareHeaders($merchantId, $password),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			return json_decode($response);
	}

	private function prepareHeaders($merchantId, $password) {
		$headers = array(
			"Content-Type: application/json",
			"Accept: application/json",
			"Authorization: Basic ".base64_encode("merchant.".$merchantId.":".$password)
		);
		return $headers;
	}
}
?>