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

class PaymenterioProcessModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $gatewayUrl = Paymenterio::PAYMENTERIO_MAIN_URL;
        // Get Cart data and verify basic data.
        $cart = $this->context->cart;
        $amount = (float)$cart->getOrderTotal(true, Cart::BOTH);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect($this->context->link->getPageLink('order'));
        }

        // Get Customer data and verify object.
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink('order'));
        }

        // Verify that current payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'paymenterio') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->module->l('Metoda płatności Paymenterio nie jest już dostępna.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);
        $currency = $this->context->currency;
        $this->module->validateOrder(
            $cart->id, Configuration::get('PAYMENTERIO_PAYMENT_NEW_STATUS'),
            $amount,
            $this->module->displayName, null, null,
            (int)$currency->id, false,
            $customer->secure_key
        );

        $apiKey = Configuration::get('API_KEY');

        $system = 1; // full gateway
        $shopID = Configuration::get('SHOP_ID');
        $orderID = $this->module->currentOrder;

        $name = $this->l("Zamówienie #") . Order::getUniqReferenceOf($orderID) . ' ('. Configuration::get('PS_SHOP_NAME') . ')';
        $successURL = $this->context->link->getModuleLink('paymenterio', 'success', array(), true);
        $failURL = $this->context->link->getModuleLink('paymenterio', 'fail', array(), true);
        $notifyURL = $this->context->link->getModuleLink('paymenterio', 'notify', array('hash' => sha1(Order::getUniqReferenceOf($orderID) . '|' . $orderID . '|' . $shopID)), true);

        $payload = array(
            "system" => $system,
            "shop" => $shopID,
            "order" => $this->module->currentOrder,
            "amount" => $amount,
            "currency" => $currency->iso_code,
            "name" => $name,
            "success_url" => $successURL,
            "fail_url" => $failURL,
            "notify_url" => $notifyURL
        );

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_URL => $gatewayUrl,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ));
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                "apiKey: $apiKey"
            ));
        $out = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
            $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            http_response_code(400);
            exit('An error occured, code: ' . $resCode . ' Response: ' . $out);
        }
        curl_close($ch);

        $newPayment = json_decode($out);

        Tools::redirect($newPayment->payment_link);
    }
}