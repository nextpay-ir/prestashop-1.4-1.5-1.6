<?php

if (defined('_PS_VERSION_') == FALSE) {

	die('This file cannot be accessed directly');
}

class nextpay extends PaymentModule {

	private $_html = '';
	private $_postErrors = array();

	public function __construct() {

		$this->name             = 'nextpay';
		$this->tab              = 'payments_gateways';
		$this->version          = '3.3';
		$this->author           = 'Nextpay.ir';
		$this->currencies       = TRUE;
		$this->currencies_mode  = 'radio';

		parent::__construct();

		$this->displayName      = 'Nextpay.ir Payment Module';
		$this->description      = 'Online Payment with Nextpay.ir';
		$this->confirmUninstall = 'Are you sure you want to delete your details?';

		if (!sizeof(Currency::checkPaymentCurrencies($this->id))) {

			$this->warning = 'No currency has been set for this module.';
		}

		$config = Configuration::getMultiple(array('nextpay_api'));

		if (!isset($config['nextpay_api'])) {

			$this->warning = 'You have to enter your Nextpay.ir API key key to use Nextpay.ir for your online payments.';
		}
	}

	public function install() {

		if (!parent::install() || !Configuration::updateValue('nextpay_api', '') || !Configuration::updateValue('nextpay_logo', '') || !Configuration::updateValue('nextpay_hash', $this->hash_key()) || !$this->registerHook('payment') || !$this->registerHook('paymentReturn')) {

			return FALSE;

		} else {

			return TRUE;
		}
	}

	public function uninstall() {

		if (!Configuration::deleteByName('nextpay_api') || !Configuration::deleteByName('nextpay_logo') || !Configuration::deleteByName('nextpay_hash') || !parent::uninstall()) {

			return FALSE;

		} else {

			return TRUE;
		}
	}

	public function hash_key() {

		$en = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');

		$one   = rand(1, 26);
		$two   = rand(1, 26);
		$three = rand(1, 26);

		return $hash = $en[$one] . rand(0, 9) . rand(0, 9) . $en[$two] . $en[$tree] . rand(0, 9) . rand(10, 99);
	}

	public function getContent() {

		if (Tools::isSubmit('nextpay_setting')) {

			Configuration::updateValue('nextpay_api', $_POST['nextpay_api']);
			Configuration::updateValue('nextpay_logo', $_POST['nextpay_logo']);

			$this->_html .= '<div class="conf confirm">' . 'Settings Updated' . '</div>';
		}

		$this->_generateForm();

		return $this->_html;
	}

	private function _generateForm() {

		$this->_html .= '<div align="center">';
		$this->_html .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
		$this->_html .= 'Enter Your API Key' . '<br/>';
		$this->_html .= '<input type="text" name="nextpay_api" value="' . Configuration::get('nextpay_api') . '" ><br/><br/>';
		$this->_html .= '<input type="submit" name="nextpay_setting" value="' . 'Save' . '" class="button" />';
		$this->_html .= '</form>';
		$this->_html .= '</div>';
	}
	

	public function do_payment($cart) {

		if (extension_loaded('curl')) {

			$server   = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__;
			$amount   = floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));
			$address  = new Address(intval($cart->id_address_invoice));
			$mobile   = isset($address->phone_mobile) ? $address->phone_mobile : NULL;
			$api_key  = Configuration::get('nextpay_api');
			$currency_id = $cart->id_currency;

			foreach(Currency::getCurrencies() as $key => $currency){
				if ($currency['id_currency'] == $currency_id){
					$currency_iso_code = $currency['iso_code'];
				}
			}
			
			if ($currency_iso_code == 'IRR'){
				$amount = $amount / 10;
			}

			$callback = $server . 'modules/nextpay/process.php?do=call_back&id=' . $cart->id . '&amount=' . $amount.'&currency_id='.$currency_id.'&iso_code='.$currency_iso_code;

		
			$params = array(
				'api_key'       => $api_key,
				'amount'        => $amount,
				'callback_uri'  => urlencode($callback),
				'order_id'      => $cart->id
			);
		
			$result = self::send_request("https://api.nextpay.org/gateway/token.http", $params);
		
			if ($result && isset($result->code) && $result->code == -1) {

				$cookie = new Cookie('order');
				$cookie->setExpire(time() + 20 * 60);
				$cookie->hash = md5($cart->id . $amount . Configuration::get('nextpay_hash'));
				$cookie->write();

				$gateway_url = 'https://api.nextpay.org/gateway/payment/' . $result->trans_id;

				Tools::redirect($gateway_url);

			} else {

				$message = 'در ارتباط با وب سرویس Nextpay.ir خطایی رخ داده است';
				$message = isset($result->message) ? $result->message : $message;

				echo $this->error($message);
			}

		} else {

			echo $this->error('تابع cURL در سرور فعال نمی باشد');
		}
	}

	public function error($str) {

		return '<div class="alert error">' . $str . '</div>';
	}

	public function success($str) {

		echo '<div class="conf confirm">' . $str . '</div>';
	}

	public function hookPayment($params) {

		global $smarty;

		$smarty->assign('nextpay_logo', Configuration::get('nextpay_logo'));

		if ($this->active) {

			return $this->display(__FILE__, 'nextpay.tpl');
		}
	}

	public function hookPaymentReturn($params) {

		if ($this->active) {

			return NULL;
		}
	}

	public function send_request($url, $params)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

		$response = curl_exec($ch);
		$error    = curl_errno($ch);

		curl_close($ch);

		$output = $error ? FALSE : json_decode($response);

		return $output;
	}
}
