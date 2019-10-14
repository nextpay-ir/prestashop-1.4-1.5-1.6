<?php

if (isset($_GET['do'])) {

	include (dirname(__FILE__) . '/../../config/config.inc.php');
	include (dirname(__FILE__) . '/../../header.php');
	include (dirname(__FILE__) . '/nextpay.php');

	$nextpay = new nextpay;

	if ($_GET['do'] == 'payment') {

		$nextpay->do_payment($cart);

	} else {

		if (isset($_GET['id']) && isset($_GET['amount']) && isset($_POST['trans_id']) && isset($_POST['order_id']) && isset($_GET['currency_id'])&& isset($_GET['iso_code'])) {

			$order  = htmlspecialchars($_GET['id']);
			$amount = htmlspecialchars($_GET['amount']);
			$currency_id = $_GET['currency_id'];
			$currency_iso_code = $_GET['iso_code'];
			$card_holder = isset($_POST['card_holder']) ? $_POST['card_holder'] : "null" ;
			
			$cookie = new Cookie('order');
			$cookie = $cookie->hash;
			

			if (isset($cookie) && $cookie) {

				$hash = md5($order . $amount . Configuration::get('nextpay_hash'));

				
				if ($hash == $cookie) {
					
                    $trans_id   = htmlspecialchars($_POST['trans_id']);
                    $order_id   = htmlspecialchars($_POST['order_id']);

					if (isset($trans_id) && isset($order_id)) {

						$api_key = Configuration::get('nextpay_api');
						

						$params = array (
							'api_key'	=> $api_key,
                            'order_id'	=> $order_id,
                            'trans_id' 	=> $trans_id,
                            'amount'	=> $amount
						);

						$result = $nextpay->send_request("https://api.nextpay.org/gateway/verify.http", $params);
						

						if ($result && isset($result->code) && $result->code == 0) {

							$card_number = $card_holder != "null" ? htmlspecialchars($card_holder) : $result->card_holder;


                            $customer = new Customer((int)$cart->id_customer);
                            $currency = $context->currency;

                            $message = 'تراکنش شماره ' . $trans_id . ' با موفقیت انجام شد. شماره کارت پرداخت کننده ' . $card_number;
                            
                            if ($currency_iso_code == 'IRR'){
                                $amount = $amount * 10;
                            }

                            $nextpay->validateOrder((int)$order, _PS_OS_PAYMENT_, $amount, $nextpay->displayName, $message, array(), (int)$currency->id, false, $customer->secure_key);
                            
                        
                            Tools::redirect('history.php');


						} else {

							$message = 'در ارتباط با وب سرویس Nextpay.ir و بررسی تراکنش خطایی رخ داده است';
							$message = isset($result->message) ? $result->message : $message;

							echo $nextpay->error($message);
						}
						
					} else {

						$message = $message ? $message : 'تراكنش با خطا مواجه شد و یا توسط پرداخت کننده کنسل شده است';

						echo $nextpay->error($message);
					}

				} else {

					echo $nextpay->error('الگو رمزگذاری تراکنش غیر معتبر است');
				}

			} else {

				echo $nextpay->error('سفارش یافت نشد و یا نشست پرداخت منقضی شده است');
			}

		} else {

			echo $nextpay->error('اطلاعات ارسال شده مربوط به تایید تراکنش ناقص و یا غیر معتبر است');
		}
	}

	include (dirname(__FILE__) . '/../../footer.php');

} else {

	die('Something wrong');
}
