<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */

include(__DIR__.'/../../payu.scls.php');

class PayuValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		$payu               = $this->module;
		$currency           = $this->context->currency;
		$cart               = $this->context->cart;
		$payu->currentOrder = $payu->currentOrder ?: Order::getOrderByCartId($cart->id);

		if ($currency->iso_code != $payu->Payu_getVar("currency") || $cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
			Tools::redirect('index.php?controller=order&step=1');
		}

		$authorized = false;
		foreach (Module::getPaymentModules() as $module) {
			if ($module['name'] == 'payu') {
				$authorized = true;
				break;
			}
		}

		if (!$authorized) {
			die(Tools::displayError('This payment method is not available.'));
		}

		$customer = new Customer((int)$cart->id_customer);

		if (!Validate::isLoadedObject($customer)) {
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
		}

		$total    = (float) $cart->getOrderTotal(true, Cart::BOTH);
		$discount = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);

		$button = "<div style='position:absolute; top:50%; left:50%; margin:-40px 0px 0px -60px; '>".
		          "<div><img src='/modules/payu/img/payu.png' width='120px' style='margin:0px 5px;'></div>".
		          "<div><img src='/modules/payu/img/loader.gif' width='120px' style='margin:5px 5px;'></div>".
		          "</div>".
		          "<script>
		              setTimeout( subform, 100 );
		              function subform(){ document.getElementById('PayUForm').submit(); }
		          </script>";

		$option  = array(
			'merchant' => $payu->Payu_getVar("merchant"), 
			'secretkey' => $payu->Payu_getVar("secret_key"), 
			'debug' => $payu->Payu_getVar("debug_mode"),
			'button' => $button
		);

		if ($payu->Payu_getVar("system_url") != '') {
			$option['luUrl'] = $payu->Payu_getVar("system_url");
		}

		$forSend = array();
		foreach ($cart->getProducts() as $item) {	
			$price = round($item['price'], 3);

			if ($item['price'] > $price) {
				$price += 0.001;
			}

			$forSend['ORDER_PNAME'][] = $item['name'];
			$forSend['ORDER_PCODE'][] = $item['id_product'];
			$forSend['ORDER_PINFO'][] = $item['description_short'];
			$forSend['ORDER_PRICE'][] = $price;
			$forSend['ORDER_QTY'][]   = $item['quantity'];
			$forSend['ORDER_VAT'][]   = ($item['rate'] != '') ? $item['rate'] : 0;
		}

		if ($payu->Payu_getVar("back_ref") != '') {
			$forSend['BACK_REF'] = $payu->Payu_getVar("back_ref");
		}

		$delivery =  new Address( $cart->id_address_delivery );
		$user     = $delivery->getFields();
		$forSend += array(
			'BILL_FNAME'    => $customer->firstname ?: $user['firstname'],
			'BILL_LNAME'    => $customer->lastname  ?: $user['lastname'],
			'BILL_ADDRESS'  => $user['address1'],
			'BILL_ADDRESS2' => $user['address2'],
			'BILL_ZIPCODE'  => $user['postcode'],
			'BILL_CITY'     => $user['city'],
			'BILL_PHONE'    => $user['phone_mobile'],
			'BILL_EMAIL'    => $customer->email,
			
			'DISCOUNT'      => $discount,
		);

		$mailVars = array();
		if (!$payu->currentOrder) {
			$payu->validateOrder($cart->id, 1, $total, $payu->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
		}


		$order = new Order($payu->currentOrder);
		$orderID = $payu->currentOrder.'_'.$cart->id;

		$forSend += array (
							'ORDER_REF' => $orderID, # Uniqe order 
							'ORDER_SHIPPING' => $cart->getTotalShippingCost(), # Shipping cost
							'PRICES_CURRENCY' => $payu->Payu_getVar("currency"),  # Currency
							'LANGUAGE' => $payu->Payu_getVar("language"),
						  );

		$pay = PayuCLS::getInst()->setOptions( $option )->setData( $forSend )->LU();
		echo $pay;
		
		die;

		// $cart = $this->context->cart;
		// if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
		// 	Tools::redirect('index.php?controller=order&step=1');

		// // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		// $authorized = false;
		// foreach (Module::getPaymentModules() as $module)
		// 	if ($module['name'] == 'ps_wirepayment')
		// 	{
		// 		$authorized = true;
		// 		break;
		// 	}
		// if (!$authorized)
		// 	die($this->module->getTranslator()->trans('This payment method is not available.', array(), 'Modules.WirePayment.Shop'));

		// $customer = new Customer($cart->id_customer);
		// if (!Validate::isLoadedObject($customer))
		// 	Tools::redirect('index.php?controller=order&step=1');

		// $currency = $this->context->currency;
		// $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		// $mailVars = array(
		// 	'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
		// 	'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
		// 	'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
		// );

		// $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
		// Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
