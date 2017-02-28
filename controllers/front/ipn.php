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

class PayuIpnModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		$payu   = $this->module;
		$option = array(
			'merchant'  => $payu->Payu_getVar("merchant"), 
			'secretkey' => $payu->Payu_getVar("secret_key"), 
		);

		$payansewer = PayuCLS::getInst()->setOptions( $option )->IPN();
		if (!$payansewer) {
			die('Incorrect hash');
		}

		$ord = explode( "_", $_POST["REFNOEXT"]);
		$extraVars = "";
		$order = new Order(intval($ord[0]));

		if (!Validate::isLoadedObject($order) OR !$order->id)
			die('Invalid order');

		if (!$amount = floatval(Tools::getValue('IPN_TOTALGENERAL')) OR $amount != $order->total_paid)
			die($amount.' != '. $order->total_paid.' Incorrect amount');

		$id_order_state = _PS_OS_PAYMENT_;

		$history = new OrderHistory();
		$history->id_order = intval($order->id);
		$history->changeIdOrderState(intval($id_order_state), intval($order->id));
		$history->addWithemail(true, $extraVars);

		die($payansewer);
	}
}