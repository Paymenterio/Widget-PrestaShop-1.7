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

class PaymenterioNotifyModuleFrontController extends ModuleFrontController
{
    /**
     * @throws Exception
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $hash = Tools::getValue('hash');
        $shopID = Configuration::get('SHOP_ID');
        $body = json_decode(file_get_contents("php://input"), true);
        $statusID = $body['status'];
        $orderID = $body['order'];

        if ($hash != sha1(Order::getUniqReferenceOf($orderID) . '|' . $orderID . '|' . $shopID)) {
            http_response_code(400);
            exit('Signature Error');
        }

        $orderObject = new Order($orderID);

        $history = new OrderHistory();
        $history->id_order = $orderID;

        if ($orderObject->current_state != Configuration::get('PS_OS_PAYMENT') && (isset($statusID) && !empty($statusID) && Validate::isLoadedObject($orderObject))) {
            if ($statusID == 5) {
                $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $orderID);
            } elseif ($statusID == 20) {
                $history->changeIdOrderState(Configuration::get('PAYMENTERIO_PAYMENT_ERROR_STATUS'), $orderID);
            } elseif ($statusID >= 40 && $statusID <= 43) {
                $history->changeIdOrderState(Configuration::get('PAYMENTERIO_PAYMENT_BLOCKED_STATUS'), $orderID);
            } else {
                $history->changeIdOrderState(Configuration::get('PS_OS_CANCELED'), $orderID);
            }
            $history->addWithemail(true);
            exit("OK");
        }
        http_response_code(404);
        exit('The payment was not found or was completed successfully.');
    }

}
