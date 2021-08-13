<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@paymenterio.pl so we can send you a copy immediately.
 *
 * @author    Paymenterio Team <kontakt@paymenterio.pl>
 * @copyright Paymenterio sp. z o.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class PaymenterioFailModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        if (!$this->module->active ||
            !$this->context->cart->id_address_delivery ||
            !$this->context->cart->id_address_invoice) {
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        $customer = $this->context->customer;
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink('order'));
        }
    }

    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('module:paymenterio/views/templates/front/fail.tpl');
    }
}