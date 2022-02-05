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
					'PURCHASE' => __('No','vikbooking'),
					'NONE' => __('Yes','vikbooking')
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
		$this->set('notify_url', 'https://tuliphotelsuites.com/one-bedroom-apartment/?option=com_vikbooking&task=notifypayment&sid='.$this->get('sid').'&ts='.$this->get('ts'));
		
		$amount = $this->get('total_to_pay');
		$res = $this->callPaymentApi($merchantId, $password, $transactionName, $uniq_id, $amount, $this->getParam('testmode'));
		if(!$res) {
			echo '<div class="vbo-booking-details-head vbo-booking-details-head-cancelled retry-button " style="cursor:pointer">
			<h4 style="color:white">Payment Gateway Error Retry</h4>
			</div>';
		}
		if($res->result == 'SUCCESS')  {
			$sessionId = $res->session->id;

			echo '<div class="vbo-booking-details-head vbo-booking-details-head-cancelled retry-button hide " style="cursor:pointer">
			<h4 style="color:white">Payment Cancelled Retry</h4>
			</div>';
			echo '<div class="vbo-booking-details-head vbo-booking-details-head-completed complete-button hide " style="cursor:pointer">
			<h4 style="color:white">Payment Completed</h4>
			</div>';
?>
<script type='text/javascript'>

	var _jQ = jQuery.noConflict();
	function completeCallback(resultIndicator, sessionVersion) {
		console.log('completeCallback-resultIndicator', resultIndicator);
		console.log('completeCallback-sessionVersion', sessionVersion);

		_jQ('.complete-button').removeClass('hide').addClass('show');

	}
	function errorCallback(error) {
		_jQ('.retry-button').removeClass('hide').addClass('show');
		console.log(JSON.stringify(error));
	}

	_jQ('.retry-button').click(function () {
		location.reload();
	});
	function cancelCallback() {
		_jQ('.retry-button').removeClass('hide').addClass('show');
		console.log('Payment cancelled');
	}

	Checkout.configure({
		session: {
			id: '<?php echo $sessionId; ?>',
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
	console.log("<?php echo $this->get('notify_url') ?>");
</script>

				<?php

		}
	}
	
	protected function validateTransaction(JPaymentStatus &$status) {
		$merchantId = $this->getParam('merchantid');
		$password = $this->getParam('password');
		$uniq_id = $_GET['sid']."-".$_GET['ts'];	
		$validate = $this->verifyPayment($uniq_id,$merchantId, $password);
		if($validate->result == 'SUCCESS' && ( $validate->status == 'CAPTURED' || $validate->status == 'AUTHENTICATED')) {
			$status->verified(); 
			$status->paid( $validate->merchantAmount );
			return true;
		}
		return false;
	
	}

	protected function complete($res) {
		$app = JFactory::getApplication();

		if ($res)
		{
			$url = $this->get('return_url');

			// display successful message
			$app->enqueueMessage(__('Thank you! Payment successfully received.', 'vikcredimax'));
		}
		else
		{
			$url = $this->get('error_url');

			// display error message
			$app->enqueueMessage(__('It was not possible to verify the payment. Please, try again.', 'vikcredimax'));
		}

		JFactory::getApplication()->redirect($url);
		exit;
	}



	private function callPaymentApi($merchantId, $password, $transactionName, $uniq_id, $amount, $opration = 'NONE') {
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
					"operation": "'.$opration.'",
					"returnUrl":"'.$this->get('notify_url').'"
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

	private function verifyPayment($uniq_id, $merchantId, $password) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://credimax.gateway.mastercard.com/api/rest/version/62/merchant/".$merchantId."/order/".$uniq_id,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => $this->prepareHeaders($merchantId, $password),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return json_decode($response);
	}
}
?>