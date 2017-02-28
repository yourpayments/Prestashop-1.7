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
class PayuBackModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$message = '';
		if (isset($_GET['err'])) {
			$message = $_GET['err'] .'<br>';
		}

		$result = isset($_GET['result']) ? $_GET['result'] : '';
		switch ($result) {
			case '-1': $message .= 'Вам выставлен счёт в системе Qiwi. Пожалуйста перейдите в личный кабинет Qiwi по адресу http://qiwi.com/ и подтвердите оплату.'; break;
			case '0' : $message .= 'Ваш платёж успешно выполнен. Ваш заказ будет обработан...'; break;
			case '1' : $message .= 'Не удалось обработать ваш платёж. Пожалуйста попробуйте повторить платёж или обратитесь к нашему консультанту...'; break;
			default  : $message .= 'Информация о платеже не доступна.'; break;
		}

		$cart = $this->context->cart;
		if (!$this->module->checkCurrency($cart))
			Tools::redirect('index.php?controller=order');

		$total = sprintf(
			$this->getTranslator()->trans('%1$s (tax incl.)', array(), 'Modules.WirePayment.Shop'),
			Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
		);

		$this->context->smarty->assign(array(
      		'message' => $message,
		));

		$this->setTemplate('module:payu/views/templates/front/back.tpl');
	}
}
