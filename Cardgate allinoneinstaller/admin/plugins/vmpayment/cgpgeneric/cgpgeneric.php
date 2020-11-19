<?php

/**
 * Virtuemart Card Gate Plus payment extension
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category    VMPayment
 * @package     cgpcreditcard
 * @author      Richard Schoots <support@cardgate.com>
 * @copyright   Copyright (c) 2013 Card Gate Plus B.V. - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

if(!class_exists('VmConfig')) {
	require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
}
VmConfig::loadConfig();

if(!class_exists('vmPSPlugin')) {
	require(VMPATH_PLUGINLIBS . DS . 'vmpsplugin.php');
}

class plgVMPaymentCgpgeneric extends vmPSPlugin {

	public static $_this = false;

	/**
	 * CardGatePlus plugin features
	 *
	 * @var mixed
	 */
	protected $_plugin_version = "3.0.14";

	protected $_url = '';

	/**
	 * Base constructor
	 *
	 * @param type $subject
	 * @param type $config
	 */
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);

		$jlang = JFactory::getLanguage();
		$jlang->load('plg_vmpayment_cgpideal', JPATH_ADMINISTRATOR, NULL, TRUE);
		$this->_loggable = true;
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->_tablepkey = 'id'; // virtuemart_cardgate_id';
		$this->_tableId = 'id'; // 'virtuemart_cardgate_id';

		$varsToPush = array(
			'test_mode' => array(
				'',
				'char'
			),
			'site_id' => array(
				0,
				'int'
			),
			'hash_key' => array(
				'',
				'char'
			),
			'gateway_language' => array(
				'nl',
				'char'
			),
			'payment_currency' => array(
				0,
				'int'
			),
			'debug' => array(
				0,
				'int'
			),
			'log' => array(
				0,
				'int'
			),
			'payment_logos' => array(
				'',
				'char'
			),
			'status_pending' => array(
				'',
				'char'
			),
			'status_success' => array(
				'',
				'char'
			),
			'status_canceled' => array(
				'',
				'char'
			),
			'countries' => array(
				0,
				'char'
			),
			'min_amount' => array(
				0,
				'int'
			),
			'max_amount' => array(
				0,
				'int'
			),
			'cost_per_transaction' => array(
				0,
				'int'
			),
			'cost_percent_total' => array(
				0,
				'int'
			),
			'tax_id' => array(
				0,
				'int'
			)
		);
		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
	}

	/**
	 * Create plugin database
	 *
	 * @return type
	 */
	protected function getVmPluginCreateTableSQL() {
		return $this->createTableSQL('Payment ' . $this->_plugin_name . ' Table');
	}

	/**
	 * Check to see if id field is of the right type
	 *
	 * @return null boolean
	 */
	public function checkFieldID() {
		$db = JFactory::getDBO();

		$query = 'SELECT id FROM `' . $this->_tablename . '`' . ' order by id desc  LIMIT 1';
		$db->setQuery($query);

		$id = $db->loadResult();
		if (isset($id)) {
			// check fieldtype
			$query = 'SHOW COLUMNS FROM ' . $this->_tablename . ' LIKE "id"';
			$db->setQuery($query);
			$aColumn = $db->loadResultArray(1);
			$sType = $aColumn[0];
			if (substr_compare($sType, 'tinyint', 0, 7) == 0) {
				$query = 'ALTER TABLE `' . $this->_tablename . '` MODIFY id  INT(11)';
				$db->setQuery($query);
				$result = $db->loadResult();
			}
		}
		return;
	}

	/**
	 * Fields for plugin database
	 *
	 * @return string
	 */
	public function getTableSQLFields() {
		$SQLfields = array(
			'id' => ' int(11) unsigned NOT NULL AUTO_INCREMENT ',
			'virtuemart_order_id' => ' int(11) UNSIGNED ',
			'order_number' => ' char(32) ',
			'virtuemart_paymentmethod_id' => ' mediumint(1) UNSIGNED ',
			'payment_name' => ' char(255) NOT NULL DEFAULT \'\' ',
			'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
			'payment_currency' => 'char(3) ',
			'cost_per_transaction' => ' decimal(10,2) ',
			'cost_percent_total' => ' decimal(10,2) ',
			'tax_id' => ' smallint(1) ',
			'cgp_custom' => ' varchar(255)  ',
			'cgp_response_amount' => ' int(11) ',
			'cgp_response_currency' => ' char(3) ',
			'cgp_response_transaction_id' => ' int(11) ',
			'cgp_response_ref' => ' varchar(255) ',
			'cgp_response_transaction_fee' => ' int(11) ',
			'cgp_response_billing_option' => ' varchar(32) ',
			'cgpresponse_raw' => ' longtext '
		);

		return $SQLfields;
	}

	/**
	 * Redirects customer to payment page
	 *
	 * @param type $cart
	 * @param type $order
	 * @return null|boolean
	 */
	public function plgVmConfirmedOrder($cart, $order) {
		$iPaymentmethodId = $order['details']['BT']->virtuemart_paymentmethod_id;
		if (! ($method = $this->getVmPluginMethod($iPaymentmethodId))) {
			return null;
		}

		if (! $this->selectedThisElement($method->payment_element)) {
			return false;
		}

		$this->set_url($method->test_mode == 'test');

		$cartitems = array();
		$products = $cart->products;

		foreach ($products as $product) {
			$item = array();
			$item['quantity'] = $product->quantity;
			$item['sku'] = $product->product_sku;
			$item['name'] = $product->product_name;
			$prices = $product->prices;

			$item['price'] = round($prices['salesPrice'] * 100, 0);
			$item['vat_amount'] = round($prices['taxAmount'] * 100, 0);
			$item['vat_inc'] = 1;
			$item['type'] = 1;
			$cartitems[] = $item;
		}

		$orderDetails = $cart->orderDetails['details']['BT'];

		if ($orderDetails->order_shipment > 0) {
			$item = array();
			$item['quantity'] = 1;
			$item['sku'] = $orderDetails->virtuemart_shipmentmethod_id;
			$item['name'] = 'SHIPPING';
			$item['price'] = round(($orderDetails->order_shipment + $orderDetails->order_shipment_tax) * 100, 0);
			$item['vat_amount'] = round($orderDetails->order_shipment_tax * 100, 0);
			$item['vat_inc'] = 1;
			$item['type'] = 2;
			$cartitems[] = $item;
		}
		if ($orderDetails->order_billDiscountAmount < 0) {
			$item = array();
			$item['quantity'] = 1;
			$item['sku'] = 'order_bill_discount';
			$item['name'] = 'OrderBill Discount';
			$item['price'] = round($orderDetails->order_billDiscountAmount * 100, 0);
			$item['vat_amount'] = 0;
			$item['vat_inc'] = 1;
			$item['type'] = 4;
			$cartitems[] = $item;
		}

		if ($orderDetails->order_discountAmount < 0) {
			$item = array();
			$item['quantity'] = 1;
			$item['sku'] = 'order_discount';
			$item['name'] = 'Order Discount';
			$item['price'] = round($orderDetails->order_discountAmount * 100, 0);
			$item['vat_amount'] = 0;
			$item['vat_inc'] = 1;
			$item['type'] = 4;
			$cartitems[] = $item;
		}

		$session = JFactory::getSession();
		$return_context = $session->getId();
		$this->_debug = $method->debug; // enable debug
		$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

		// Load VM models if not already exist
		if (! class_exists('VirtueMartModelOrders')) {
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}

		if (! class_exists('VirtueMartModelCurrency')) {
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
		}

		// Load customer address details
		$new_status = '';
		$usrBT = $order['details']['BT'];
		$address = $order['details']['BT'];

		// Load vendor
		$vendorModel = VmModel::getModel('vendor');
		$vendorModel->setId(1);
		$vendor = $vendorModel->getVendor();
		$this->getPaymentCurrency($method);
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
		$db = &JFactory::getDBO();
		$db->setQuery($q);

		// Obtain order's currency and amount
		$currency_code_3 = $db->loadResult();
		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		$amount = sprintf('%.0f', $paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false) * 100);
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

		// Validate SiteID and HashKey
		$site_id = $method->site_id;
		$hash_key = $method->hash_key;

		if (empty($site_id)) {
			vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_SITE_ID_NOT_SET'));
			return false;
		}

		if (empty($hash_key)) {
			vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_HASH_KEY_NOT_SET'));
			return false;
		}

		$ref = 'O' . time() . $order['details']['BT']->order_number;

		// Create POST variables
		$post_variables = array(
			"siteid" => $site_id,
			"ref" => $ref,
			"amount" => $amount,
			"currency" => $currency_code_3,
			"description" => JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ORDER_DESCRIPTION') . " " . $order['details']['BT']->order_number,
			"first_name" => $address->first_name,
			"last_name" => $address->last_name,
			"email" => $address->email,
			"address" => $address->address_1 . (isset($address->address_2) ? ', ' . $address->address_2 : ''),
			"city" => $address->city,
			"country_code" => ShopFunctions::getCountryByID($address->virtuemart_country_id, 'country_2_code'),
			"postal_code" => $address->zip,
			"phone_number" => $address->phone_1,
			"state" => isset($address->virtuemart_state_id) ? ShopFunctions::getStateByID($address->virtuemart_state_id) : '',
			"language" => $method->gateway_language,
			"return_url" => JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginResponseReceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id),
			"return_url_failed" => JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id),
			"shop_name" => vmVersion::$PRODUCT,
			"shop_version" => vmVersion::$RELEASE,
			"plugin_name" => $this->_plugin_name,
			"plugin_version" => $this->_plugin_version,
			"extra" => $order['details']['BT']->order_number,
			"cartitems" => json_encode($cartitems, JSON_HEX_APOS | JSON_HEX_QUOT)
		);

		switch (substr($this->_plugin_name, 3)) {
			case 'visa':
			case 'mastercard':
			case 'americanexpress':
			case 'maestro':
			case 'vpay':
			case 'creditcard':
				$post_variables['option'] = 'creditcard';
				break;

			case 'sofortbanking':
				$post_variables['option'] = 'directebanking';
				break;

			case 'ideal':
				$post_variables['option'] = 'ideal';
				break;

			case 'idealqr':
				$post_variables['option'] = 'idealqr';
				break;

			case 'mistercash':
				$post_variables['option'] = 'mistercash';
				break;

			case 'paypal':
				$post_variables['option'] = 'paypal';
				break;

			case 'paysafecard':
				$post_variables['option'] = 'paysafecard';
				break;

			case 'paysafecash':
				$post_variables['option'] = 'paysafecash';
				break;

			case 'banktransfer':
				$post_variables['option'] = 'banktransfer';
				break;

			case 'giropay':
				$post_variables['option'] = 'giropay';
				break;

			case 'giftcard':
				$post_variables['option'] = 'giftcard';
				break;

			case 'directdebit':
				$post_variables['option'] = 'directdebit';
				break;

			case 'przelewy24':
				$post_variables['option'] = 'przelewy24';
				break;

			case 'afterpay':
				$post_variables['option'] = 'afterpay';
				break;

			case 'klarna':
				$post_variables['option'] = 'klarna';
				break;

			case 'bitcoin':
				$post_variables['option'] = 'bitcoin';
				break;

			case 'billink':
				$post_variables['option'] = 'billink';
				break;

			case 'onlineueberweisen':
				$post_variables['option'] = 'onlineueberweisen';
				break;
		}

		if ($method->test_mode == 'test') {
			$post_variables['test'] = '1';
			$hash_prefix = 'TEST';
		} else {
			$hash_prefix = '';
		}
		$post_variables['hash'] = md5($hash_prefix . $site_id . $amount . $ref . $hash_key);

		$this->logInfo('plgVmConfirmedOrder Initiating a new transaction', 'message');
		$this->logInfo('plgVmConfirmedOrder Sending customer to CardGatePlus with values: ' . var_export($post_variables, true), 'message');

		// Prepare data that should be stored in the database
		$dbValues = array();
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['payment_name'] = $this->renderPluginName($method, $order);
		$dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
		$dbValues['cgp_custom'] = $return_context;
		$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues['cost_percent_total'] = $method->cost_percent_total;
		$dbValues['payment_currency'] = $method->payment_currency;
		$dbValues['payment_order_total'] = $totalInPaymentCurrency;
		$dbValues['tax_id'] = $method->tax_id;
		$this->storePSPluginInternalData($dbValues);

		// Create HTML FORM
		vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_REDIRECT_NOTIFICATION'));
		$url = $this->getGatewayUrl();
		$html = '<form action="' . $url . '" method="post" name="vm_cgp_form" >';
		foreach ($post_variables as $name => $value) {
			$html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '" />';
		}
		// Add for iDEAL bank options
		if (substr($this->_plugin_name, 3) == "ideal") {
			$aBanks = $this->getBankOptions($iPaymentmethodId);
			if ($aBanks) {
				$html .= '<label for="cgp_ideal_issuer">' . JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_IDEAL_SELECT_BANK_LABEL') . '</label>';
				$html .= '<select id="cgp_ideal_issuer" name="suboption" onchange="selectBank()">';
				$banks[0] = JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_IDEAL_SELECT_BANK_DEFAULT');
				$html .= $this->makeBankOptions($aBanks);
				$html .= '</select>';
			} else {
				$html .= '<label for="cgp_ideal_issuer">' . JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_IDEAL_SELECT_BANK_LABEL') . '</label>';
				$html .= '<select id="cgp_ideal_issuer" name="suboption" onchange="selectBank()">';
				$html .= '    <option value="-" selected="selected">' . JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_IDEAL_SELECT_BANK_DEFAULT') . '</option>';
				$html .= '    <option value="0021">Rabobank</option>';
				$html .= '    <option value="0031">ABN Amro</option>';
				$html .= '    <option value="0091">Friesland Bank</option>';
				$html .= '    <option value="0721">ING</option>';
				$html .= '    <option value="0751">SNS Bank</option>';
				$html .= '    <option value="-">' . JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_IDEAL_ADDITIONAL_BANK') . '</option>';
				$html .= '    <option value="0161">Van Lanschot Bank</option>';
				$html .= '    <option value="0511">Triodos Bank</option>';
				$html .= '    <option value="0761">ASN Bank</option>';
				$html .= '    <option value="0771">SNS Regio Bank</option>';
				$html .= '</select>';
			}
		}
		$html .= '</form>';

		if (substr($this->_plugin_name, 3) != "ideal") {
			$html .= '<script type="text/javascript">';
			$html .= '    document.vm_cgp_form.submit();';
			$html .= '</script>';
		} else {
			$html .= '<script type="text/javascript">';
			$html .= 'function selectBank() {';
			$html .= '    var ideal_bank = document.getElementById("cgp_ideal_issuer").value;';
			$html .= '    if (ideal_bank != "-") {';
			$html .= '        document.vm_cgp_form.submit();';
			$html .= '    } else {';
			$html .= '        alert("' . JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_IDEAL_SELECT_BANK_ALERT') . '");';
			$html .= '    }';
			$html .= '}';
			$html .= '</script>';
		}

		// 2 = don't delete the cart, don't send email and don't redirect
		return $this->processConfirmedOrderPaymentResponse(2, $cart, $order, $html, $new_status);
	}

	/**
	 * Sets VM currency
	 *
	 * @param type $virtuemart_paymentmethod_id
	 * @param type $paymentCurrencyId
	 * @return null|boolean
	 */
	public function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
		if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return null;
		}

		if (! $this->selectedThisElement($method->payment_element)) {
			return false;
		}

		$this->getPaymentCurrency($method);
		$paymentCurrencyId = $method->payment_currency;
	}

	/**
	 * From the plugin page, the user returns to the shop.
	 * The order email is sent, and the cart emptied.
	 *
	 * @param type $html
	 * @return null|boolean
	 */
	public function plgVmOnPaymentResponseReceived(&$html) {

		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);

		if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return null; // Another method was selected, do nothing
		}

		if (! $this->selectedThisElement($method->payment_element)) {
			return false;
		}

		$order_number = JRequest::getVar('on');
		if (! $order_number) {
			return false;
		}

		if (! class_exists('VirtueMartModelOrders')) {
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		if (! class_exists('VirtueMartCart')) {
			require (JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		}

		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
		if ($virtuemart_order_id) {
			$order = new VirtueMartModelOrders();
			$order = $order->getOrder($virtuemart_order_id);
		}

		// Display order info
		$payment_name = $this->renderPluginName($method);
		$html = $this->getPaymentResponseHtml($order, $payment_name);
		vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_SUCCESS'));

		// Get the correct cart / session
		$cart = VirtueMartCart::getCart();
		// Clear cart
		$cart->emptyCart();

		return true;
	}

	/**
	 * From the payment page, the user has cancelled the order.
	 * The order previousy created is deleted.
	 * The cart is not emptied, so the user can reorder if necessary.
	 * then delete the order
	 *
	 * @return boolean|null
	 */
	public function plgVmOnUserPaymentCancel() {
		if (! class_exists('VirtueMartModelOrders')) {
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}

		$order_number = JRequest::getVar('on');
		if (! $order_number) {
			return false;
		}
		$pm = JRequest::getVar('pm');
		if (! $pm) {
			return false;
		}

		$db = JFactory::getDBO();
		$query = 'SELECT #__virtuemart_paymentmethods .`payment_element` FROM #__virtuemart_paymentmethods' . " WHERE  `virtuemart_paymentmethod_id`= '" . $pm . "'";
		$db->setQuery($query);
		$payment_element = $db->loadResult();

		if (! $payment_element) {
			return null;
		}

		$query = 'SELECT #__virtuemart_payment_plg_' . $payment_element . '.`virtuemart_order_id` FROM ' . '#__virtuemart_payment_plg_' . $payment_element . " WHERE  `order_number`= '" . $order_number . "'";

		$db->setQuery($query);
		$virtuemart_order_id = $db->loadResult();

		if (! $virtuemart_order_id) {
			return null;
		}

		$this->handlePaymentUserCancel($virtuemart_order_id);
		return true;
	}

	/**
	 * This event is fired by Offline Payment.
	 * It can be used to validate the payment data as entered by the user.
	 *
	 * @return boolean|null
	 */
	public function plgVmOnPaymentNotification() {
		return null;
	}

	/**
	 * This is custom trigger especially for Card Gate Plus to handle callback
	 * `com_cgp` component needed.
	 *
	 * @param string $option
	 * @param int $status
	 * @return boolean
	 */
	public function plgVmOnCgpCallback($data) {
		// correct for sofortbanking if necessary
		if ($data['billing_option'] == 'directebanking') {
			$data['billing_option'] = 'sofortbanking';
		}

		// Process only correct payment option
		if (substr($this->_plugin_name, 3) == $data['billing_option']) {
			foreach (glob(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . "*.php") as $filename) {
				require_once ($filename);
			}
			if (! class_exists('VirtueMartModelOrders')) {
				require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
			}

			$order_number = $data['extra'];

			$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
			// Send email to Admins if Order was not found
			if (! $virtuemart_order_id) {
				$this->_debug = true; // force debug here
				$this->logInfo('plgVmOnCgpCallback: virtuemart_order_id not found ', 'ERROR');
				// send an email to admin, and ofc not update the order status: exit is fine
				$this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_UNKNOW_ORDER_ID'));
				exit();
			} else {
				$this->logInfo('plgVmOnCgpCallback: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');
			}

			$thisOrder = new VirtueMartModelOrders();
			$thisOrder = $thisOrder->getOrder($virtuemart_order_id);

			$vendorId = 0;
			$payment = $this->getDataByOrderId($virtuemart_order_id);
			$method = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);

			if (! $this->selectedThisElement($method->payment_element)) {
				return false;
			}

			if ($thisOrder[details][BT]->order_status != $method->status_success) {
				// Send email if HASH verification failed
				$hashString = ($method->test_mode == 'test' ? 'TEST' : '') . $data['transaction_id'] . $data['currency'] . $data['amount'] . $data['ref'] . $data['status'] . $method->hash_key;

				if (md5($hashString) != $data['hash']) {
					$this->_debug = true; // force debug here
					$this->logInfo('plgVmOnCgpCallback: invalid hash ', 'ERROR');
					// send an email to admin, and ofc not update the order status: exit is fine
					$this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_INVALID_HASH'));
					exit('invalid hash!');
				}

				$this->_debug = $method->debug;
				if (! $payment) {
					$this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
					return null;
				}
				$this->logInfo('plgVmOnCgpCallback: Receiving callback data: ' . var_export($data, true), 'message');

				// Get all know columns of the table
				$db = JFactory::getDBO();
				$query = 'SHOW COLUMNS FROM `' . $this->_tablename . '` ';
				$db->setQuery($query);
				$columns = $db->loadResultArray(0);
				$post_msg = '';

				// Save all cgp_response_<field> data
				$response_fields = array();
				foreach ($data as $key => $value) {
					$post_msg .= $key . "=" . $value . "<br />";
					$table_key = 'cgp_response_' . $key;
					if (in_array($table_key, $columns)) {
						$response_fields[$table_key] = $value;
					}
				}

				$response_fields['payment_name'] = $this->renderPluginName($method);
				$response_fields['cgpresponse_raw'] = var_export($data, true);
				$response_fields['order_number'] = $order_number;
				$response_fields['virtuemart_order_id'] = $virtuemart_order_id;
				$this->storePSPluginInternalData($response_fields);

				// Update Order status
				switch ($data['status']) {
					case "200":
						$new_status = $method->status_success;
						$comments = JTExt::sprintf('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_SUCCESS', $order_number);
						break;

					case "300":
						$new_status = $method->status_canceled;
						$comments = JTExt::sprintf('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_FAILED', $order_number);
						break;
				}
				$this->logInfo('plgVmOnCgpCallback: return new_status:' . $new_status, 'message');

				// Send the email only if payment has been accepted
				if ($virtuemart_order_id) {
					$modelOrder = VmModel::getModel('orders');
					$order['order_status'] = $new_status;
					$order['virtuemart_order_id'] = $virtuemart_order_id;
					$order['customer_notified'] = 1;
					$order['comments'] = $comments;
					$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
				}

				return true;
			} else {
				exit('payment already processed');
			}
		} else {
			return null;
		}
	}

	/**
	 * Display stored payment data for an order
	 *
	 * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
	 * @param type $virtuemart_order_id
	 * @param type $payment_method_id
	 * @return null|string
	 */
	public function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
		if (! $this->selectedThisByMethodId($payment_method_id)) {
			return null; // Another method was selected, do nothing
		}

		$db = JFactory::getDBO();
		$q = 'SELECT * FROM `' . $this->_tablename . '`' . ' WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
		$db->setQuery($q);
		if (! ($paymentTable = $db->loadObject())) {
			return '';
		}

		$this->getPaymentCurrency($paymentTable);
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->payment_currency . '" ';
		$db = &JFactory::getDBO();
		$db->setQuery($q);
		$currency_code_3 = $db->loadResult();
		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE();
		$html .= $this->getHtmlRowBE(strtoupper($this->_plugin_name) . '_PAYMENT_NAME', $paymentTable->payment_name);
		/*
		 * $code = "cgp_response_";
		 *
		 * foreach ($paymentTable as $key => $value) {
		 * if (substr($key, 0, strlen($code)) == $code) {
		 * $html .= $this->getHtmlRowBE($key, $value);
		 * }
		 * }
		 */
		$html .= '</table>' . "\n";

		return $html;
	}

	/**
	 * Create nice HTML
	 *
	 * @param type $cgp_data
	 * @param type $payment_name
	 * @return string
	 */
	protected function getPaymentResponseHtml($order, $payment_name) {
		$currency_id = $order['details']['BT']->order_currency;
		$q = 'SELECT `currency_symbol` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $currency_id . '" ';
		$db = &JFactory::getDBO();
		$db->setQuery($q);

		// Obtain order's currency and amount
		$currency_symbol = $db->loadResult();
		$total = round($order['details']['BT']->order_total, 2);
		$txt_total = number_format($total, 2);

		$html = '<table>' . "\n";
		$html .= $this->getHtmlRow(strtoupper($this->_plugin_name) . '_PAYMENT_NAME', $payment_name);
		$html .= $this->getHtmlRow(strtoupper($this->_plugin_name) . '_ORDER_NUMBER', $order['details']['BT']->order_number);
		$html .= $this->getHtmlRow(strtoupper($this->_plugin_name) . '_AMOUNT', $currency_symbol . ' ' . $txt_total);
		$html .= '</table>' . "\n";

		return $html;
	}

	/**
	 * Calculate transaction costs
	 *
	 * @param VirtueMartCart $cart
	 * @param type $method
	 * @param type $cart_prices
	 * @return type
	 */
	public function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
		if (preg_match('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr($method->cost_percent_total, 0, - 1);
		} else {
			$cost_percent_total = $method->cost_percent_total;
		}

		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}

	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 *
	 * @param type $cart
	 * @param type $method
	 * @param type $cart_prices
	 * @return boolean true if the conditions are fulfilled, false otherwise
	 */
	protected function checkConditions($cart, $method, $cart_prices) {
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
		$amount = $cart_prices['salesPrice'];
		$amount_cond = ($amount >= $method->min_amount and $amount <= $method->max_amount or ($method->min_amount <= $amount and ($method->max_amount == 0)));

		$countries = array();
		if (! empty($method->countries)) {
			if (! is_array($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		if (! is_array($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (! isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}

		if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
			if ($amount_cond) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return Gateway Url
	 *
	 * @return string
	 */
	protected function getGatewayUrl() {
		return $this->_url;
	}

	/**
	 * *************************************************
	 * We must reimplement this triggers for joomla 1.7
	 * *************************************************
	 */

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 */
	public function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
		return $this->onStoreInstallPluginTable($jplugin_id);
	}

	/**
	 * This event is fired after the payment method has been selected.
	 * It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Valérie isaksen
	 * @param VirtueMartCart $cart:
	 *            the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 */
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
		return $this->OnSelectCheck($cart);
	}

	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for example
	 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *
	 * @param object $cart
	 *            Cart object
	 * @param integer $selected
	 *            ID of the method selected
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
		return $this->displayListFE($cart, $selected, $htmlIn);
	}

	/**
	 * plgVmonSelectedCalculatePricePayment
	 * Calculate the price (value, tax_id) of the selected method
	 * It is called by the calculator
	 * This function does NOT to be reimplemented.
	 * If not reimplemented, then the default values from this function are taken.
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart $cart
	 *            the current cart
	 * @param array $cart_prices
	 *            the new cart prices
	 * @param array $cart_prices_name
	 *            the new cart prices
	 * @return boolean|null if the method was not selected, false if the shipping rate is not valid any more, true otherwise
	 */
	public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}

	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available.
	 * If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param
	 *            VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found, virtuemart_xxx_id if only one plugin is found
	 */
	public function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
		return $this->onCheckAutomaticSelected($cart, $cart_prices);
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id
	 *            The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}

	/**
	 * This event is fired during the checkout process.
	 * It can be used to validate the
	 * method data as entered by the user.
	 *
	 * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
	 * @author Max Milbers
	 *
	 *         public function plgVmOnCheckoutCheckDataPayment($psType, VirtueMartCart $cart) {
	 *         return null;
	 *         }
	 */

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id
	 *            The order ID
	 * @param integer $method_id
	 *            method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	public function plgVmonShowOrderPrintPayment($order_number, $method_id) {
		return $this->onShowOrderPrint($order_number, $method_id);
	}

	/**
	 * Save updated order data to the method specific table
	 *
	 * @param array $_formData
	 *            Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 *         skipped!), or null when this method is not actived.
	 * @author Oscar van Eijk
	 *
	 *         public function plgVmOnUpdateOrderPayment( $_formData) {
	 *         return null;
	 *         }
	 */
	/**
	 * Save updated orderline data to the method specific table
	 *
	 * @param array $_formData
	 *            Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 *         skipped!), or null when this method is not actived.
	 * @author Oscar van Eijk
	 *
	 *         public function plgVmOnUpdateOrderLine( $_formData) {
	 *         return null;
	 *         }
	 */
	/**
	 * plgVmOnEditOrderLineBE
	 * This method is fired when editing the order line details in the backend.
	 * It can be used to add line specific package codes
	 *
	 * @param integer $_orderId
	 *            The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 * @author Oscar van Eijk
	 *
	 *         public function plgVmOnEditOrderLineBE( $_orderId, $_lineId) {
	 *         return null;
	 *         }
	 */

	/**
	 * This method is fired when showing the order details in the frontend, for every orderline.
	 * It can be used to display line specific package codes, e.g. with a link to external tracking and
	 * tracing systems
	 *
	 * @param integer $_orderId
	 *            The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 * @author Oscar van Eijk
	 *
	 *         public function plgVmOnShowOrderLineFE( $_orderId, $_lineId) {
	 *         return null;
	 *         }
	 */
	function plgVmDeclarePluginParamsPaymentVM3(&$data) {
		return $this->declarePluginParams('payment', $data);
	}

	public function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams($name, $id, $table);
	}

	private function getBankOptions($iPaymentmethodId) {
		$this->checkBankOptions($iPaymentmethodId);
		return $this->getIdealParam($iPaymentmethodId,'issuers');
	}

	private function checkBankOptions($iPaymentmethodId) {
		$iIssuerRefresh = $this->getIdealParam($iPaymentmethodId,'issuer_refresh');
		if ($iIssuerRefresh < time()) {
			$this->cacheBankOptions($iPaymentmethodId);
		}
	}

	protected function cacheBankOptions($iPaymentmethodId) {


		if ($this->getIdealParam($iPaymentmethodId,'test_mode')) {
			$sUrl = 'https://secure-staging.curopayments.net/cache/idealDirectoryCUROPayments.dat';
		} else {
			$sUrl = 'https://secure.curopayments.net/cache/idealDirectoryCUROPayments.dat';
		}

		if (! ini_get('allow_url_fopen') || ! function_exists('file_get_contents')) {
			$sIssuers = false;
		} else {
			$sIssuers = file_get_contents($sUrl);
		}

		$db = JFactory::getDBO();
		$query = 'SELECT #__virtuemart_paymentmethods .`payment_params` FROM #__virtuemart_paymentmethods' . " WHERE  `virtuemart_paymentmethod_id`= '" . $iPaymentmethodId . "'";
		$db->setQuery($query);
		$sPaymentParams = $db->loadResult();

		if (! $sPaymentParams) {
			return null;
		}

		$aPaymentParams = explode('|', $sPaymentParams);
		foreach ($aPaymentParams as $key => $value) {
			if (! (strpos($value, 'issuer_refresh="') === false)) {
				unset($aPaymentParams[$key]);
			}
			if (! (strpos($value, 'issuers="') === false)) {
				unset($aPaymentParams[$key]);
			}
		}

		$iCacheTime = 24 * 60 * 60;
		$iIssuerRefresh = time() + $iCacheTime;

		$aPaymentParams[] = "issuer_refresh=\"" . $iIssuerRefresh . '"';
		$aPaymentParams[] = "issuers=\"" . base64_encode($sIssuers);

		$aIssuers = unserialize($sIssuers);
		if ($aIssuers != false && array_key_exists("INGBNL2A", $aIssuers)){
			$sPaymentParams = implode( '|', $aPaymentParams );
			$query          = 'UPDATE #__virtuemart_paymentmethods  SET `payment_params`= \'' . $sPaymentParams . '\' WHERE `virtuemart_paymentmethod_id`= ' . $iPaymentmethodId;
			$db->setQuery( $query );
			$db->execute();
		}
	}

	private function makeBankOptions($aBanks) {
		$html = '';
		foreach ($aBanks as $idBank => $bankName) {
			$html .= '<option value="' . $idBank . '">' . $bankName . '</option>';
		}
		return $html;
	}

	private function set_url($test) {
		if ($test) {
			$this->_url = 'https://secure-staging.curopayments.net/gateway/cardgate/';
		} else {
			$this->_url = 'https://secure.curopayments.net/gateway/cardgate/';
		}
	}

	private function getIdealParam($iPaymentmethodId,$param) {
		$search = $param . '="';
		$method = $this->getVmPluginMethod($iPaymentmethodId);
		if ($method->payment_element != 'cgpideal') {
			return false;
		}

		$aPaymentParams = explode('|', $method->payment_params);

		foreach ($aPaymentParams as $key => $value) {
			if (! (strpos($value, $search) === false)) {
				$output = $value;
			}
		}

		switch ($param) {
			case 'issuer_refresh':
				$output = (int) substr($output, 16, 10);
				break;
			case 'issuers':
				$output = substr($output, 9);
				$output = unserialize(base64_decode($output));
				$output[0] = 'Kies uw bank a.u.b.';
				break;
			case 'test_mode':
				$output = substr($output, 11, 4);
				$output = ($output == 'test' ? true : false);
				break;
		}
		return $output;
	}
}
