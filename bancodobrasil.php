<?php

if (!defined('_VALID_MOS') && !defined('_JEXEC'))
    die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

/**
 * @version $Id: bancodobrasil.php,v 1.4 2005/05/27 19:33:57 ei
 *
 * a special type of 'cash on delivey':
 * @author Max Milbers, ValÃ©rie Isaksen, Luiz Weber
 * @version $Id: bancodobrasil.php 5122 2012-02-07 12:00:00Z luizwbr $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentBancodobrasil extends vmPSPlugin {

    // instance of class
    public static $_this = false;

    function __construct(& $subject, $config) {
        //if (self::$_this)
        //   return self::$_this;
        parent::__construct($subject, $config);

        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $varsToPush = $this->getVarsToPush ();        
		// dados de configuração do Banco do Brasil
		/*
			0 - Todas as modalidades contratadas pelo convenente 
			2 - Boleto  bancário 
			21 - 2ª Via de boleto bancário, já gerado anteriormente 
			3 - Débito em Conta via Internet 
			5 - BB Crediário Internet
		*/				
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }
    /**
     * Create the table for this plugin if it does not yet exist.
     * @author ValÃ©rie Isaksen
     */
    protected function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment Bancodobrasil Table');
    }

    /**
     * Fields to create the payment table
     * @return string SQL Fileds
     */
    function getTableSQLFields() {
        $SQLfields = array(
            'id' => 'bigint(15) unsigned NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'cost_per_transaction' => ' decimal(10,2) DEFAULT NULL ',
            'cost_percent_total' => ' decimal(10,2) DEFAULT NULL ',
            'tax_id' => 'smallint(11) DEFAULT NULL'
        );

        return $SQLfields;
    }
    
    function getPluginParams(){
        $db = JFactory::getDbo();
        $sql = "select virtuemart_paymentmethod_id from #__virtuemart_paymentmethods where payment_element = 'bancodobrasil'";
        $db->setQuery($sql);
        $id = (int)$db->loadResult();
        return $this->getVmPluginMethod($id);
    }

    /**
     *
     *
     * @author ValÃ©rie Isaksen
     */
    function plgVmConfirmedOrder($cart, $order) {
	
		$url 	= JURI::root();
		$url_lib 			= $url.DS.'plugins'.DS.'vmpayment'.DS.'bancodobrasil'.DS;
		$url_imagens 	= $url_lib . 'imagens'.DS;

        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }
		// $params = new JParameter($payment->payment_params);
        $lang = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        $vendorId = 0;

        $html = "";

        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        $this->getPaymentCurrency($method);
        // END printing out HTML Form code (Payment Extra Info)
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db = &JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();
        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);


        $this->_virtuemart_paymentmethod_id = $order['details']['BT']->virtuemart_paymentmethod_id;        
        $dbValues['payment_name'] = $this->renderPluginName($method);
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
        $dbValues['cost_percent_total'] = $method->cost_percent_total;
        $dbValues['payment_currency'] = $currency_code_3;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $this->storePSPluginInternalData($dbValues);
    
        JFactory::getApplication()->enqueueMessage(utf8_encode("Seu pedido foi realizado com sucesso. Voc&egrave; ser&aacute; redirecionado para o site do Banco do Brasil, onde efetuar&aacute; o pagamento da sua compra."));

        $html = $this->retornaHtmlPagamento($order, $method, 1);

		$new_status = $method->status_aguardando;
		return $this->processConfirmedOrderPaymentResponse(1, $cart, $order, $html, $dbValues['payment_name'], $new_status);

    }

    function retornaHtmlPagamento( $order, $method, $redir ) {

        $url    = JURI::root();
        $url_lib            = $url.DS.'plugins'.DS.'vmpayment'.DS.'bancodobrasil'.DS;
        $url_imagens    = $url_lib . 'imagens'.DS;

        $app =& JFactory::getApplication();
        if($app->getName() != 'site') {
            return true;
        }
        $lang = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        $vendorId = 0;
        
        $html = '<table>' . "\n";
        $html .= $this->getHtmlRow('STANDARD_PAYMENT_INFO', $dbValues['payment_name']);
        if (!empty($payment_info)) {
            $lang = & JFactory::getLanguage();
            if ($lang->hasKey($method->payment_info)) {
                $payment_info = JTExt::_($method->payment_info);
            } else {
                $payment_info = $method->payment_info;
            }
            $html .= $this->getHtmlRow('STANDARD_PAYMENTINFO', $payment_info);
        }
        if (!class_exists('VirtueMartModelCurrency')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
        }
        if (!class_exists('CurrencyDisplay')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
        }

        $html .= "<tr><td>";

        // campos do usuario
        $zip = $order["details"]["BT"]->zip;
        $replacements = array(" ", ".", ",", "-", ";"); 
        $zip = str_replace($replacements, "", $zip);
        $valor = number_format(round($order['details']['BT']->order_total,2),2,'','');
    
        $html .= '<form name="bancodobrasil" id="bancodobrasil" action="https://mpag.bb.com.br/site/mpag/" method="POST">  ';        
        // $html .= '<form name="bancodobrasil" id="bancodobrasil" action="https://www16.bancodobrasil.com.br/site/mpag/" method="POST">  ';
        $html .= "<input type='hidden' name='nome' value='". $order["details"]["BT"]->first_name . ' ' . $order["details"]["BT"]->last_name."' />";
        $html .= "<input type='hidden' name='endereco' value='". substr($order["details"]["BT"]->address_1 . ',' . $order["details"]["BT"]->address_2,0,60) ."' />";
        $html .= "<input type='hidden' name='cidade' value='". $order["details"]["BT"]->city ."' />";
        $html .= "<input type='hidden' name='uf' value='". ShopFunctions::getStateByID($order["details"]["BT"]->virtuemart_state_id, "state_2_code")  ."' />";
        $html .= "<input type='hidden' name='cep' value='". $zip ."' />";   
        $html .= "<input type='hidden' name='valor' value='". $valor ."' />";
        $html .= "<input type='hidden' name='cpfCnpj' value='". $order["details"]["BT"]->fax."' />";

        // configurações boleto
        /*
            0 - Todas as modalidades contratadas pelo convenente 
            2 - Boleto  bancário 
            21 - 2ª Via de boleto bancário, já gerado anteriormente 
            3 - Débito em Conta via Internet 
            5 - BB Crediário Internet
        */

        if ($redir) {
            $tp_pagamento   = $method->tipo_integracao; 
        } else  {
            $tp_pagamento   = 21;
        }
        $msg_loja       = $method->mensagem_boleto;
        $order_id       = $order['details']['BT']->order_number;
        $virtuemart_order_id    = $order['details']['BT']->virtuemart_order_id;

        if ($method->modo_teste) {
            $IdConv     = $method->convenio_teste;
            $cobranca   = $method->cobranca_teste;
            $dias_vencimento    = $method->dias_vencimento_teste;
        } else {
            $IdConv     = $method->convenio;        
            $cobranca   = $method->cobranca;        
            $dias_vencimento    = $method->dias_vencimento;
        }

        $qtdPontos = "";
        // calcula a data de vencimento do boleto
        $data_vencimento = date('dmY', strtotime($order['details']['BT']->created_on . " +".$dias_vencimento." days"));     

        // $refTran = $cobranca.str_pad($virtuemart_order_id, 10, "0", STR_PAD_LEFT);
        $tamanho_cobranca   = strlen($cobranca);        
        $tamanho_total      = 17 - $tamanho_cobranca;

        $refTran = $cobranca.str_pad($virtuemart_order_id, $tamanho_total, "0", STR_PAD_LEFT);       

        $html .= "<input type='hidden' name='dtVenc' value='".$data_vencimento."' />";  
        $html .= "<input type='hidden' name='tpPagamento' value='".$tp_pagamento."' />";
        $html .= "<input type='hidden' name='refTran' value='".$refTran."' />"; 
        $html .= "<input type='hidden' name='msgLoja' value='".$msg_loja."' />";
        $html .= "<input type='hidden' name='qtdPontos' value='".$qtdPontos."' />";
        $html .= "<input type='hidden' name='idConv' value='".$IdConv."' />";

        $url_informa = JROUTE::_(JURI::root(true) . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&id_pedido='.$order_id) ;
        $html .= "<input type='hidden' name='urlInforma' value='".$url_informa."' />";
        $url_retorno = JROUTE::_(JURI::root(true) . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id).'&id_pedido='.$order_id;
        $html .= "<input type='hidden' name='urlRetorno' value='".$url_retorno."' />";

        /**
            // Desconto do pedido
            if ($db->f("order_discount") != 0.00) {
                echo '<input type="hidden" name="desconto" value="'.$db->f("order_discount").'" />';    
            }
            // Desconto do pedido
            if ($db->f("order_tax") != 0.00) {
                echo '<input type="hidden" name="acrescimo" value="'.$db->f("order_tax").'" />';
            }
        **/

        $url_imagem_pagamento = $url_imagens. 'bancodobrasil_botao.png';        
        if ($redir) {        
            $html .= '<br/><br/>Voc&egrave; ser&aacute; direcionado para a tela de pagamento em 5 segundos, ou ent&atilde;o clique no botão abaixo <br/>:';
            $html .= '<script>setTimeout(\'document.getElementById("bancodobrasil").submit();\',5000);</script>';        
        } 

        $html .= '<input type="image" src="'.$url_imagem_pagamento.'" />';
        $html .= '</form>';

        $html .= "</td></tr>";
        $html .= '</table>';

        return $html;
    }

    /**
     * Display stored payment data for an order
     *
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
        if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
            return null; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` '
                . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);
        if (!($paymentTable = $db->loadObject())) {
            vmWarn(500, $q . " " . $db->getErrorMsg());
            return '';
        }
        $this->getPaymentCurrency($paymentTable);

        $html = '<table class="adminlist">' . "\n";
        $html .=$this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_NAME', $paymentTable->payment_name);
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
        $html .= '</table>' . "\n";
        return $html;
    }

    function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
        if (preg_match('/%$/', $method->cost_percent_total)) {
            $cost_percent_total = substr($method->cost_percent_total, 0, -1);
        } else {
            $cost_percent_total = $method->cost_percent_total;
        }
        return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
    }
       
    function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method, $progressive) {

        if ($method->modo_calculo_desconto == '2') {
            return parent::setCartPrices($cart, $cart_prices, $method, false);
        } else {
            return parent::setCartPrices($cart, $cart_prices, $method, true);
        }
    }



    /**
     * Check if the payment conditions are fulfilled for this payment method
     * @author: Valerie Isaksen
     *
     * @param $cart_prices: cart prices
     * @param $payment
     * @return true: if the conditions are fulfilled, false otherwise
     *
     */
    protected function checkConditions($cart, $method, $cart_prices) {

// 		$params = new JParameter($payment->payment_params);
        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

        $amount = $cart_prices['salesPrice'];
        $amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
                OR
                ($method->min_amount <= $amount AND ($method->max_amount == 0) ));
        if (!$amount_cond) {
            return false;
        }
        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        // probably did not gave his BT:ST address
        if (!is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (!isset($address['virtuemart_country_id']))
            $address['virtuemart_country_id'] = 0;
        if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            return true;
        }

        return false;
    }

    /*
     * We must reimplement this triggers for joomla 1.7
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the bancodobrasil method to create the tables
     * @author ValÃ©rie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author ValÃ©rie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     *
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
        return $this->OnSelectCheck($cart);
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
     *
     * @param object $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /*
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price (value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
     * @author Valerie Isaksen
     * @cart: VirtueMartCart the current cart
     * @cart_prices: array the new cart prices
     * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
     *
     *
     */

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }
        $this->getPaymentCurrency($method);

        $paymentCurrencyId = $method->payment_currency;
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {

        $orderModel = VmModel::getModel('orders');
        $orderDetails = $orderModel->getOrder($virtuemart_order_id);
        if (!($method = $this->getVmPluginMethod($orderDetails['details']['BT']->virtuemart_paymentmethod_id))) {
            return false;
        }

        if (!$this->selectedThisByMethodId ($virtuemart_paymentmethod_id)) {
            return NULL;
        } // Another method was selected, do nothing

        $view = JRequest::getVar('view');
        // somente retorna se estiver como transação pendente
        if ($method->status_aguardando == $orderDetails['details']['BT']->order_status and $view == 'orders') {
            
            // JFactory::getApplication()->enqueueMessage('');
            $redir = 0;
            $html = $this->retornaHtmlPagamento( $orderDetails, $method, $redir );
            echo $html;
        }
    
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);

    }

    /**
     * This event is fired during the checkout process. It can be used to validate the
     * method data as entered by the user.
     *
     * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
     * @author Max Milbers

      public function plgVmOnCheckoutCheckDataPayment(  VirtueMartCart $cart) {
      return null;
      }
     */

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmDeclarePluginParamsPaymentVM3( &$data) {
        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    //Notice: We only need to add the events, which should work for the specific plugin, when an event is doing nothing, it should not be added

    /**
     * Save updated order data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk
     *
      public function plgVmOnUpdateOrderPayment(  $_formData) {
      return null;
      }

      /**
     * Save updated orderline data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk
     *
      public function plgVmOnUpdateOrderLine(  $_formData) {
      return null;
      }

      /**
     * plgVmOnEditOrderLineBE
     * This method is fired when editing the order line details in the backend.
     * It can be used to add line specific package codes
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk
     *
      public function plgVmOnEditOrderLineBEPayment(  $_orderId, $_lineId) {
      return null;
      }

      /**
     * This method is fired when showing the order details in the frontend, for every orderline.
     * It can be used to display line specific package codes, e.g. with a link to external tracking and
     * tracing systems
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk
     *
      public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
      return null;
      }

      /**
     * This event is fired when the  method notifies you when an event occurs that affects the order.
     * Typically,  the events  represents for payment authorizations, Fraud Management Filter actions and other actions,
     * such as refunds, disputes, and chargebacks.
     *
     * NOTE for Plugin developers:
     *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
     *
     * @param $return_context: it was given and sent in the payment form. The notification should return it back.
     * Used to know which cart should be emptied, in case it is still in the session.
     * @param int $virtuemart_order_id : payment  order id
     * @param char $new_status : new_status for this order id.
     * @return mixed Null when this method was not selected, otherwise the true or false
     *
     * @author Valerie Isaksen
     *
     *
      public function plgVmOnPaymentNotification() {
      return null;
      }
	*/
	function plgVmOnPaymentNotification() {
		
		header("Status: 200 OK");
		if (!class_exists('VirtueMartModelOrders'))
			require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		$bancodobrasil_data = $_REQUEST;
		
		
		if (!isset($bancodobrasil_data['refTran'])) {
			return;
		}		
		$order_number = $bancodobrasil_data['id_pedido'];
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
		//$this->logInfo('plgVmOnPaymentNotification: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');

		if (!$virtuemart_order_id) {
			return;
		}
		$vendorId = 0;
		$payment = $this->getDataByOrderId($virtuemart_order_id);
		if($payment->payment_name == '') {
			return false;
		}
		$method = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);
		if (!$this->selectedThisElement($method->payment_element)) {
			return false;
		}
		//$this->_debug = $method->debug;
		if (!$payment) {
			$this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
			return null;
		}
		$this->logInfo('bancodobrasil_data ' . implode('   ', $bancodobrasil_data), 'message');

		// get all know columns of the table
		$db = JFactory::getDBO();
		$query = 'SHOW COLUMNS FROM `' . $this->_tablename . '` ';
		$db->setQuery($query);
		$columns = $db->loadResultArray(0);
		$post_msg = '';
		foreach ($bancodobrasil_data as $key => $value) {
			$post_msg .= $key . "=" . $value . "<br />";
			$table_key = 'bancodobrasil_response_' . $key;
			if (in_array($table_key, $columns)) {
			$response_fields[$table_key] = $value;
			}
		}

		//$response_fields[$this->_tablepkey] = $this->_getTablepkeyValue($virtuemart_order_id);
		//$response_fields['payment_name'] = $this->renderPluginName($method);
		$response_fields['payment_name'] = $payment->payment_name;
		//$response_fields['paypalresponse_raw'] = $post_msg;
		//$return_context = $bancodobrasil_data['custom'];
		$response_fields['order_number'] = $order_number;
		$response_fields['virtuemart_order_id'] = $virtuemart_order_id;

		/*
		$error_msg = $this->_processIPN($bancodobrasil_data, $method);
		$this->logInfo('process IPN ' . $error_msg, 'message');		
		if (!(empty($error_msg) )) {
			$new_status = $method->status_canceled;
			$this->logInfo('process IPN ' . $error_msg . ' ' . $new_status, 'ERROR');
		} else {
			$this->logInfo('process IPN OK', 'message');
		}*/
			/*
			 * https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_IPNandPDTVariables
			 * The status of the payment:
			 * Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.
			 * Completed: The payment has been completed, and the funds have been added successfully to your account balance.
			 * Created: A German ELV payment is made using Express Checkout.
			 * Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.
			 * Expired: This authorization has expired and cannot be captured.
			 * Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.
			 * Pending: The payment is pending. See pending_reason for more information.
			 * Refunded: You refunded the payment.
			 * Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.
			 * Processed: A payment has been accepted.
			 * Voided: This authorization has been voided.
			 *
			 */
			
			// nova forma de validar com o Banco do Brasil ( formulário sonda )
			$params = array(
				"idConv"=> $bancodobrasil_data["idConv"],
				"refTran"=> $bancodobrasil_data["refTran"],		
				"qtdPontos"=>'0',		
				"valorSonda"=> $bancodobrasil_data["valor"],		
				"formato"=>'03'
			);
			$url_request 	= "https://www16.bb.com.br/site/mpag/REC3.jsp";
			$conteudo 		= trim(request($params, $url_request,false));

			$refTran = substr($conteudo,0,17);
			$valor = substr($conteudo,17,15);	
			$IdConv = substr($conteudo,32,6);	
			$TpPagamento = substr($conteudo,38,1);	
			$Situacao = substr($conteudo,39,2);	
			$DataPagamento = substr($conteudo,41,8);	
			$qtdPontos = substr($conteudo,50,15);				

			$bancodobrasil_status = $Situacao;
			$vetor_msg['00'] = 'Pagamento Efetuado com sucesso';
			$vetor_msg['01'] = 'Pagamento não autorizado/transação recusada';
			$vetor_msg['02'] = 'Erro no processamento da consulta';
			$vetor_msg['03'] = 'Pagamento não localizado';
			$vetor_msg['10'] = 'Campo idConv inválido ou nulo';
			$vetor_msg['11'] = 'Valor informado é inválido, nulo ou não confere com o valor registrado';
			$vetor_msg['99'] = 'Operação cancelada pelo cliente';
			
			switch ($bancodobrasil_status) {
				case '00': $new_status = $method->status_aprovado; break;
				case '02': 
				case '10': 
				case '11': 
				case '03': $new_status = $method->status_aguardando; break;
				case '01': 
				case '99': 
				default: $new_status = $method->status_cancelado; break;				
			}

			$this->logInfo('plgVmOnPaymentNotification return new_status:' . $new_status, 'message');

		if ($virtuemart_order_id) {
			// send the email only if payment has been accepted
			if (!class_exists('VirtueMartModelOrders'))
			require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
			$modelOrder = new VirtueMartModelOrders();
			$orderitems = $modelOrder->getOrder($virtuemart_order_id);
			$nb_history = count($orderitems['history']);
			$order['order_status'] = $new_status;
			$order['virtuemart_order_id'] = $virtuemart_order_id;
			$order['comments'] = 'O status do seu pedido '.$order_number.' no Banco do Brasil foi atualizado: '.utf8_encode($bancodobrasil_data['status']);
			if (isset($vetor_msg[$bancodobrasil_status])) {
				$order['comments'] .= "\n Mensagem: ".$vetor_msg[$bancodobrasil_status];
			}

			//JText::sprintf('VMPAYMENT_PAYPAL_PAYMENT_CONFIRMED', $order_number);
			$order['comments'] .= "<br />" . JText::sprintf('VMPAYMENT_PAYPAL_EMAIL_SENT');
			$order['customer_notified'] = 1;

			$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
			if ($nb_history == 1) {
    			if (!class_exists('shopFunctionsF'))
    				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
    			shopFunctionsF::sentOrderConfirmedEmail($orderitems);
    			$this->logInfo('Notification, sentOrderConfirmedEmail ' . $order_number. ' '. $new_status, 'message');
			}
		}
		//// remove vmcart
		$this->emptyCart($return_context);
    }
	
      /**
     * plgVmOnPaymentResponseReceived
     * This event is fired when the  method returns to the shop after the transaction
     *
     *  the method itself should send in the URL the parameters needed
     * NOTE for Plugin developers:
     *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
     *
     * @param int $virtuemart_order_id : should return the virtuemart_order_id
     * @param text $html: the html to display
     * @return mixed Null when this method was not selected, otherwise the true or false
     *
     * @author Valerie Isaksen
     *
     *
      function plgVmOnPaymentResponseReceived(, &$virtuemart_order_id, &$html) {
      return null;
      }
     */
	 // retorno da transação para o pedido específico
	 function plgVmOnPaymentResponseReceived(&$html) {

		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);

		$vendorId = 0;
		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return null; // Another method was selected, do nothing
		}

		if ($method->modo_teste) {
			$idCobranca 		= $method->cobranca_teste;
		} else {
			$idCobranca 		= $method->cobranca;
		}

		// recupera o model do pedido 
		if (!class_exists('VirtueMartModelOrders')) require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		
		$boleto = JRequest::getVar('boleto');
		$atualiza = JRequest::getVar('atualiza');
		if (isset($boleto)) {
			//print_r($method);
			$path = $method->arquivo_retorno;			
			if (!file_exists($path)) {
				die('Arquivo do retorno nao existe: '.$path);
			}

            if ($method->tipo_retorno == 'bbt') {
			    $handle = fopen($path, "rb");
                $contents = '';
                while (!feof($handle)) {
                    $contents .= fread($handle, 8192);
                }
                fclose($handle);          
                $aux = explode("\n",$contents);                
                $modelOrder = new VirtueMartModelOrders();
                $coluna_captura = $method->coluna_captura;

                foreach($aux as $k=>$boleto_pagamento) {
                    $linha = explode(';',$boleto_pagamento);
                    $virtuemart_order_id = (int)(str_replace($idCobranca,'',substr($linha[$coluna_captura],0,-1)));

                    // recupera as informações do pedido
                    $orderitems = $modelOrder->getOrder($virtuemart_order_id);
                    if ( $virtuemart_order_id != 0 )
                    if (is_array($orderitems)) {
                        echo $virtuemart_order_id.' - '. $orderitems['details']['BT']->first_name.' '.$orderitems['details']['BT']->last_name.' - R$ '.$orderitems['details']['BT']->order_total;
                        if (isset($atualiza)) {
                            $status = $orderitems['details']['BT']->order_status;
                            // se o status não estiver aprovado, aprova o pedido
                            if ($status != $method->status_aprovado) {
                                // aprova o pedido                                
                                $order = array();
                                $order['order_status']          = $method->status_aprovado;
                                $order['virtuemart_order_id']   = $virtuemart_order_id;
                                $order['customer_notified']     = 1;
                                $order['comments']              = 'Pagamento do Boleto BB - Status: Confirmado';
                                $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);                           
                                echo ' - ATUALIZADO';
                            }
                        }       
                    } else {
                        echo 'Pedido '.$virtuemart_order_id.' não encontrado.'; 
                    }
                    echo "\n";
                }
                die();
            } else {
          
                /**Exemplo de uso da classe para processamento de arquivo de retorno de cobranças em formato FEBRABAN/CNAB400,
                * testado com arquivo de retorno do Banco do Brasil com convênio de 6 posições.<br/>
                * @copyright GPLv2
                * @package LeituraArquivoRetorno
                * @author Manoel Campos da Silva Filho. http://manoelcampos.com/contato
                * @version 0.4
                */

                //Adiciona a classe strategy RetornoBanco que vincula um objeto de uma sub-classe
                //de RetornoBase, e assim, executa o processamento do arquivo de uma determinada
                //carteira de um banco específico.
                $biblioteca_bb_retorno = JPATH_ROOT.DS.'plugins'.DS.'vmpayment'.DS.'bancodobrasil'.DS.'retorno'.DS;
                require_once($biblioteca_bb_retorno."RetornoBanco.php");
                require_once($biblioteca_bb_retorno."RetornoFactory.php");


                /**Função handler a ser associada ao evento aoProcessarLinha de um objeto da classe
                * RetornoBase. A função será chamada cada vez que o evento for disparado.
                * @param RetornoBase $self Objeto da classe RetornoBase que está processando o arquivo de retorno
                * @param $numLn Número da linha processada.
                * @param $vlinha Vetor contendo a linha processada, contendo os valores da armazenados
                * nas colunas deste vetor. Nesta função o usuário pode fazer o que desejar,
                * como setar um campo em uma tabela do banco de dados, para indicar
                * o pagamento de um boleto de um determinado cliente.
                * @see linhaProcessada1
                */
                define('STATUS_APROVADO',$method->status_aprovado);
                /*
                function linhaProcessada($self, $numLn, $vlinha) {
                    $atualiza = JRequest::getVar('atualiza');
                    if($vlinha) {
                        if($vlinha["registro"] == $self::DETALHE) {
                        printf("%08d: ", $numLn);
                        echo "Nosso N&uacute;mero <b>".$vlinha['nosso_numero']."</b> ".
                           "Data <b>".$vlinha["data_ocorrencia"]."</b> ". 
                           "Valor <b>".$vlinha["valor"]."</b><br/>\n";
                        if (isset($atualiza)) {
                            $modelOrder = new VirtueMartModelOrders();
                            $virtuemart_order_id = ltrim(str_replace($vlinha['convenio'], '', $vlinha['nosso_numero']),'0');
                            // recupera as informações do pedido
                            $orderitems = $modelOrder->getOrder($virtuemart_order_id);
                            if ( $virtuemart_order_id != 0 )
                            if (is_array($orderitems)) {
                                echo $virtuemart_order_id.' - '. $orderitems['details']['BT']->first_name.' '.$orderitems['details']['BT']->last_name.' - R$ '.$orderitems['details']['BT']->order_total;
                                if (isset($atualiza)) {
                                    $status = $orderitems['details']['BT']->order_status;
                                    // se o status não estiver aprovado, aprova o pedido
                                    if ($status != STATUS_APROVADO) {
                                        // aprova o pedido
                                        $order = array();
                                        $order['order_status']          = STATUS_APROVADO;
                                        $order['virtuemart_order_id']   = $virtuemart_order_id;
                                        $order['customer_notified']     = 1;
                                        $order['comments']              = 'Pagamento Banco do Brasil - Status: Confirmado';
                                        $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);                           
                                        echo ' - ATUALIZADO <br />';
                                    }
                                }       
                            } else {
                                echo 'Pedido '.$virtuemart_order_id.' não encontrado. <br />'; 
                            }
                        }     
                    }
                  } else echo "Tipo da linha n&atilde;o identificado<br/>\n";
                }
                */
                /**Outro exemplo de função handler, a ser associada ao evento
                * aoProcessarLinha de um objeto da classe RetornoBase.
                * Neste exemplo, é utilizado um laço foreach para percorrer
                * o vetor associativo $vlinha, mostrando os nomes das chaves
                * e os valores obtidos da linha processada.
                * @see linhaProcessada */
                function linhaProcessada1($self, $numLn, $vlinha) {
                    $atualiza = JRequest::getVar('atualiza');
                    printf("%08d) ", $numLn);
                    if($vlinha) {
                        foreach($vlinha as $nome_indice => $valor) {
                          echo "$nome_indice: <b>$valor</b><br/>\n ";                      
                          if (isset($atualiza)) {
                            // @todo here later                           
                          }
                        }
                        echo "<br/>\n";
                    } else 
                        echo "Tipo da linha n&atilde;o identificado<br/>\n";
                }

                //--------------------------------------INÍCIO DA EXECUÇÃO DO CÓDIGO-----------------------------------------------------
                //Use uma das duas instrucões abaixo (comente uma e descomente a outra)
                //$cnab400 = RetornoFactory::getRetorno($fileName, "linhaProcessada1");

                if ($method->funcao_processamento == '0') {
                    $funcao_processamento = 'linhaProcessada';
                } else {
                    $funcao_processamento = 'linhaProcessada1';
                }

                $cnab400 = RetornoFactory::getRetorno($path, $funcao_processamento);
                $retorno = new RetornoBanco($cnab400);
                $retorno->processar();
                die('fim');                
            }


			
		}
		
		if (!$this->selectedThisElement($method->payment_element)) {
			return false;
		}
		if (!class_exists('VirtueMartCart'))
				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		$payment_data = JRequest::get('post');
		$payment_name = $this->renderPluginName($method);
		$html = $this->_getPaymentResponseHtml($payment_data, $payment_name);

		if (!empty($payment_data)) {
			vmdebug('plgVmOnPaymentResponseReceived', $payment_data);
			$order_number = $payment_data['invoice'];
			$return_context = $payment_data['custom'];
			if (!class_exists('VirtueMartModelOrders'))
			require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

			$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
			$payment_name = $this->renderPluginName($method);
			$html = $this->_getPaymentResponseHtml($payment_data, $payment_name);

			if ($virtuemart_order_id) {

			// send the email ONLY if payment has been accepted
			if (!class_exists('VirtueMartModelOrders'))
				require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

			$modelOrder = new VirtueMartModelOrders();
			$orderitems = $modelOrder->getOrder($virtuemart_order_id);
			$nb_history = count($orderitems['history']);
			//vmdebug('history', $orderitems);
			if (!class_exists('shopFunctionsF'))
				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
    			if ($nb_history == 1) {
    				if (!class_exists('shopFunctionsF'))
    				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
    				shopFunctionsF::sentOrderConfirmedEmail($orderitems);
    				$this->logInfo('plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message');
    				$order['order_status'] = $orderitems['items'][$nb_history - 1]->order_status;
    				$order['virtuemart_order_id'] = $virtuemart_order_id;
    				$order['customer_notified'] = 0;
    				$order['comments'] = JText::sprintf('VMPAYMENT_PAYPAL_EMAIL_SENT');
    				$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
    			}
			}
		}
		$cart = VirtueMartCart::getCart();
		//We delete the old stuff
		// get the correct cart / session
		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
		return true;
		}

    function _getPaymentResponseHtml($paymentTable, $payment_name) {

        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($paymentTable['id_pedido']);

        $modelOrder = new VirtueMartModelOrders();
        $order = $modelOrder->getOrder($virtuemart_order_id);

        $html = '<table>' . "\n";
        $html .= $this->getHtmlRowBE('COM_VIRTUEMART_PAYMENT_NAME', $payment_name);
        //if (!empty($paymentTable)) {
            $html .= $this->getHtmlRowBE('STANDARD_ORDER_NUMBER', $order['details']['BT']->order_number);
            $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_TOTAL_CURRENCY', $order['details']['BT']->order_total);
        //}
        $html .= '</table>' . "\n";

        return $html;
    }       
		 
}

// No closing tag
