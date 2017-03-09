<?php
if (!defined('_CAN_LOAD_FILES_')) exit;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class payu extends PaymentModule
{
	var $cellArray = array(	'PAYU_MERCHANT', 'PAYU_SECRET_KEY', 'PAYU_SYSTEM_URL', 'PAYU_CURRENCY', 
							'PAYU_VAT', 'PAYU_DEBUG_MODE', 'PAYU_BACK_REF', 'PAYU_LANGUAGE' 
					   		);

	public function __construct()
	{
		$this->name = 'payu';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
		$this->author = 'PayU';
		$this->controllers = array('validation', 'ipn', 'back');
		
 		parent::__construct();
		$this->displayName = $this->l('Платежи PayU');
		$this->description = $this->l('Оплата через PayU');
		$this->confirmUninstall = $this->l('Действительно хотите удалить модуль?');
	}

	public function install()
	{
		if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentOptions'))
			return false;
		return true;
	}

	public function uninstall()
	{	
		foreach ( $this->cellArray as $val) 
			if ( !Configuration::deleteByName($val) ) 
			return false;
		if (!parent::uninstall() ) return false;
		return true;
	}

	public function Payu_getVar( $name )
	{
		return Configuration::get( "PAYU_".strtoupper( $name ) );
	}

	public function Payu_setVar($name, $value)
	{
		return Configuration::set( "PAYU_".strtoupper( $name ), $value );
	}

	private function _displayForm()
	{
		$ipn_url    = 'http://'. $_SERVER['HTTP_HOST'] .'/module/payu/ipn';
		$system_url = $this->Payu_getVar('system_url') ?: 'https://secure.payu.ru/order/lu.php';
		$back_ref   = $this->Payu_getVar('back_ref')   ?: 'http://'. $_SERVER['HTTP_HOST'] .'/module/payu/back';

		$this->_html .=
		'<form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">
			<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Contact details').'</legend>
				<table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
					<tr><td colspan="2">'.$this->l('Please specify the PayU account details for customers').'.<br /><br /></td></tr>

					<tr>
						<td width="130" style="height: 35px;">'.$this->l('Debug mode').'</td>
						<td>
							<select name="debug_mode" style="width: 300px;">
								<option value="0" '. ($this->Payu_getVar('debug_mode') ? '' : 'selected="selected"') .'>'. $this->l('No') .'</option>
								<option value="1" '. ($this->Payu_getVar('debug_mode') ? 'selected="selected"' : '') .'>'. $this->l('Yes') .'</option>
							</select>
						</td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('Merchant').'</td>
						<td><input type="text" name="merchant" value="'.$this->Payu_getVar("merchant").'" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('Secret key').'</td>
						<td><input type="text" name="secret_key" value="'.$this->Payu_getVar("secret_key").'" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('System url').'</td>
						<td><input type="text" name="system_url" value="'. $system_url .'" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('IPN URL').'</td>
						<td><input type="text" name="ipn_url" value="'. $ipn_url .'" readonly="readonly" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('Back refference').'</td>
						<td><input type="text" name="back_ref" value="'. $back_ref .'" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('Currency of payment').'</td>
						<td><input type="text" name="currency" value="'.$this->Payu_getVar("currency").'" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">'.$this->l('Language of payment page').'</td>
						<td><input type="text" name="language" value="'.$this->Payu_getVar("language").'" style="width: 300px;" /></td>
					</tr>
					<tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="'.$this->l('Update settings').'" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';
	}

	private function _displayPayU()
	{
		$this->_html .= '<img src="../modules/payu/img/payu.jpg" style="float:left; margin-right:15px;"><b>'.
						$this->l('This module allows you to accept payments by PayU.').'</b><br /><br />'.
						$this->l('If the client chooses this payment mode, the order will change its status into a \'Waiting for payment\' status.').
						'<br /><br /><br />';
	}

	public function getContent()
	{
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error">'. $err .'</div>';
		}
		else
			$this->_html .= '<br />';
		$this->_displayPayU();
		$this->_displayForm();
		return $this->_html;
	}


	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
		/*$this->_postErrors[] = $this->l('Account details are required.');*/
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{	
			Configuration::updateValue('PAYU_DEBUG_MODE', Tools::getValue('debug_mode'));
			Configuration::updateValue('PAYU_MERCHANT',   Tools::getValue('merchant'));
			Configuration::updateValue('PAYU_SECRET_KEY', Tools::getValue('secret_key'));
			Configuration::updateValue('PAYU_SYSTEM_URL', Tools::getValue('system_url'));
			Configuration::updateValue('PAYU_BACK_REF',   Tools::getValue('back_ref'));
			Configuration::updateValue('PAYU_CURRENCY',   Tools::getValue('currency'));
			Configuration::updateValue('PAYU_LANGUAGE',   Tools::getValue('language'));
		}
		$this->_html .= '<div class="conf confirm"><img src="../img/admin/enabled.gif" alt="'.$this->l('ok').'" /> '.$this->l('Settings updated').'</div>';
	}

	# Display

	public function hookPayment($params)
	{
		if (!$this->active)	return;
		if (!$this->_checkCurrency($params['cart'])) return;

		global $smarty;
		$smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'this_description' => 'Оплата через систему PayU',
			'this_id_cart' => $params['cart']->id,
		));

		return $this->display(__FILE__, 'payu.tpl');
	}

	public function hookPaymentOptions($params)
    {
    	error_reporting(E_ALL);
    	ini_set('display_errors', 1);

        if (!$this->active)	return;
		if (!$this->checkCurrency($params['cart'])) return;

        $this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'this_description' => 'Оплата через систему PayU',
			'this_id_cart' => $params['cart']->id,
		));

		$newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Оплата через систему PayU', array(), 'Modules.Payu.Shop'))
                      ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                      ->setAdditionalInformation($this->fetch('module:payu/views/templates/payu.tpl'));
                      
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency((int)($cart->id_currency));
		$currencies_module = $this->getCurrency((int)$cart->id_currency);
		$currency_default = Configuration::get('PS_CURRENCY_DEFAULT');
		
		if (is_array($currencies_module))
			foreach ($currencies_module AS $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

}
?>