<?php
defined('_JEXEC') or die();

/**
 *
 * @package	VirtueMart
 * @subpackage Plugins  - Elements
 * @author Valérie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2011 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: $
 */
/*
 * This class is used by VirtueMart Payment or Shipment Plugins
 * which uses JParameter
 * So It should be an extension of JFormField
 * Those plugins cannot be configured througth the Plugin Manager anyway.
 */
class JFormFieldVmConfiguracaobb extends JFormField {

    /**
     * Element name
     * @access	protected
     * @var		string
     */
    public $type = 'Configuracaobb';

    protected function getInput() {	

		// recupera informacao do pagamento ativo
		if(!class_exists('VirtueMartModelPaymentmethod'))require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'paymentmethod.php');
		$pm = new VirtueMartModelPaymentmethod();
		$pagamento = $pm->getPayment();
		$url_visualiza = JURI::root().'index.php?option=com_virtuemart&view=pluginresponse&task=plugin&tmpl=component&task=pluginresponsereceived&pm='.$pagamento->virtuemart_paymentmethod_id.'&boleto=1';
		$url_atualiza = $url_visualiza.'&atualiza=1';

		$html = '<div style="height: 30px; background: #E6E6E6; padding: 4px; margin: 4px;">'.$url_visualiza.'</div>';
		$html .= '<div><em>Copie esta url acima para <b>visualizar</b> os pagamentos efetuados ou clique no botão abaixo.</em></div>';
		$html .= '<div><input type="button" class="button" value="Visualizar Pagamentos" onclick="javascript:window.open(\''.$url_visualiza.'\',\'\',\'width=700,height=300\')"/></div>';

		$html .= '<div style="height: 30px; background: #E6E6E6; padding: 4px; margin: 4px;">'.$url_atualiza.'</div>';
		$html .= '<div><em>Copie esta url acima para <b>processar</b> os pagamentos efetuados ou clique no botão abaixo.</em></div>';
		$html .= '<div><input type="button" class="button" value="Processar Pagamentos" onclick="javascript:window.open(\''.$url_atualiza.'\',\'\',\'width=700,height=300\')"/></div>';
		return $html;
    }

}