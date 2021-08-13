<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Paymenterio extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    const PAYMENTERIO_MAIN_URL = "https://api.paymenterio.pl/v1/pay";

    public function __construct()
    {
        $this->name = 'paymenterio';
        $this->tab = 'payments_gateways';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->controllers = array('process', 'notify', 'success', 'fail');
        $this->version = '1.0.1';
        $this->author = 'Paymenterio';
        $this->need_instance = 1;
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Płatności Paymenterio');
        $this->description = $this->l('Moduł umożliwia przyjmowanie płatności przy pomocy bramki płatniczej Paymenterio.');

        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować moduł Paymenterio?');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('Brak walut ustawionych dla tego modułu!');
        }
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('paymentOptions') ||
            !$this->registerHook('paymentReturn') ||
            !Configuration::updateValue('SHOP_ID', '') ||
            !Configuration::updateValue('API_KEY', '') ||
            !Configuration::updateValue('MODULE_ENABLED', false)
        ) {
            return false;
        }

        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('Aby zainstalować ten moduł, musisz włączyć rozszerzenie cURL na swoim serwerze');
            return false;
        }

        if (!$this->addStatuses()) {
            return false;
        }

        return true;
    }

    /**
     * Uninstall the module
     * @return boolean
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookPaymentReturn()
    {
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $isEnabled = Configuration::get('MODULE_ENABLED');
        if (!$isEnabled) {
            return;
        }

        $payment_options = [
            $this->getExternalPaymentOption()
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Szybka i bezpieczna płatność'))
                       ->setAction($this->context->link->getModuleLink($this->name, 'process', array(), true))
                       ->setAdditionalInformation($this->context->smarty->fetch('module:paymenterio/views/templates/front/payment_option_info.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/logo.svg'));

        return $externalOption;
    }
    public function isPropValid($prop)
    {
        return $prop && !empty($prop);
    }


    public function getContent()
    {
        $output = null;
        $this->context->controller->addJS($this->_path.'views/js/admin.js');

        $properlyConfigured = false;
        $requiredDataNotFilled = false;

        $shop_id = Configuration::get('SHOP_ID');
        if (empty($shop_id))
            $shop_id = strval(Tools::getValue('SHOP_ID'));
        $api_key = Configuration::get('API_KEY');
        if (empty($api_key))
            $api_key = strval(Tools::getValue('API_KEY'));
        $isEnabled = Configuration::get('MODULE_ENABLED');
        if (empty($isEnabled))
            $isEnabled = boolval(Tools::getValue('MODULE_ENABLED'));

        if (empty($shop_id) || empty($api_key)) {
            $requiredDataNotFilled = true;
        }
        if (!$requiredDataNotFilled) {
            if ($isEnabled) {
                $properlyConfigured = true;
            }
        }

        $templateData = array(
            'moduleDirectory' => $this->_path,
            'properlyConfigured' => $properlyConfigured,
            'requiredDataNotFilled' => $requiredDataNotFilled,
            'shopID' => $shop_id,
            'apiKey' => $api_key,
            'oldVersion' => !version_compare(_PS_VERSION_, "1.7", ">="),
            'isSSL' => $this->isSSLEnabled(),
        );
        $this->context->smarty->assign($templateData);
        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        if (Tools::isSubmit('submit'.$this->name)) {
            $shop_id = strval(Tools::getValue('SHOP_ID'));
            $api_key = strval(Tools::getValue('API_KEY'));
            $isEnabled = boolval(Tools::getValue('MODULE_ENABLED'));
            if ($this->isPropValid($shop_id) && $this->isPropValid($api_key)) {
                Configuration::updateValue('SHOP_ID', $shop_id);
                Configuration::updateValue('API_KEY', $api_key);
                Configuration::updateValue('MODULE_ENABLED', $isEnabled);
                $output .= $this->displayConfirmation($this->l('Dane zostały zapisane.'));
            } else {
                $output .= $this->displayError($this->l('Wszystkie pola są wymagane, spróbuj ponownie.'));
            }
        }

        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
    
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Ustawienia modułu'),
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Aktywny'),
                    'name' => 'MODULE_ENABLED',
                    'is_bool' => true,
                    'required' => true,
                    'desc' => $this->l('Wybierając "NIE", możesz ukryć moduł płatności Paymenterio bez wcześniejszego odinstalowywania.'),
                    'values' => array(
                        array(
                            'id' => 'enabled_active_on',
                            'value' => true,
                            'label' => $this->l('Włączony')
                        ),array(
                            'id' => 'enabled_active_off',
                            'value' => false,
                            'label' => $this->l('Wyłączony')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'prefix' => '<i class="material-icons" style="font-weight: bold; color: #10279b; font-size: 1.2em;">location_city</i>',
                    'label' => $this->l('ID lub Hash Sklepu'),
                    'hint' => $this->l('Unikalny identyfikator lub Hash sklepu dostępny w panelu administracyjnym.'),
                    'desc' => $this->l('Wprowadź Identyfikator lub Hash Twojego sklepu'),
                    'name' => 'SHOP_ID',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'prefix' => '<i class="icon-key" style="color: #10279b;"></i>',
                    'suffix' => '<i class="icon-eye-slash" id="apiKeyViewer" style="color: #2eacce; cursor : zoom-in;"></i>',
                    'label' => $this->l('Klucz API'),
                    'desc' => $this->l('Wprowadź klucz API, który jest dostępny w panelu administracyjnym.'),
                    'hint' => $this->l('Klucz API jest niezbędny do inicjowania płatności.'),
                    'name' => 'API_KEY',
                    'size' => 20,
                    'maxlength' => 50,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
    
        $helper = new HelperForm();
    
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
    
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
    
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
    
        // Load current value
        $helper->fields_value['SHOP_ID'] = Configuration::get('SHOP_ID');
        $helper->fields_value['API_KEY'] = Configuration::get('API_KEY');
        $helper->fields_value['MODULE_ENABLED'] = Configuration::get('MODULE_ENABLED');
        
        return $helper->generateForm($fields_form);
    }

    public static function getPayment($id)
    {
        $mainUrl = self::PAYMENTERIO_MAIN_URL;
        $shopId = Configuration::get('SHOP_ID');
        $apiKey = Configuration::get('API_KEY');

        $sign = sha1($id . $apiKey);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_URL => "{$mainUrl}/payment/{$shopId}/{$id}?sign={$sign}",
        ));

        $out = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
            curl_close($ch);
            throw new Exception($out);
        }
        curl_close($ch);

        return json_decode($out);
    }

    /**
     * Add all necessary statuses
     * @return boolean
     */
    public function addStatuses(): bool
    {
        if (
            Validate::isInt(Configuration::get('PAYMENTERIO_PAYMENT_NEW_STATUS')) xor ( Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENTERIO_PAYMENT_NEW_STATUS'))))) {
            $order_state_new = new OrderState();
            $order_state_new->name[Language::getIdByIso("pl")] = "Oczekiwanie na potwierdzenie płatności";
            $order_state_new->name[Language::getIdByIso("en")] = "Waiting for the payment confirmation";
            $order_state_new->send_email = false;
            $order_state_new->invoice = false;
            $order_state_new->unremovable = false;
            $order_state_new->color = "lightblue";
            if (!$order_state_new->add()) {
                return false;
            }
            if (!Configuration::updateValue('PAYMENTERIO_PAYMENT_NEW_STATUS', $order_state_new->id)) {
                return false;
            }
        }

        if (
            Validate::isInt(Configuration::get('PAYMENTERIO_PAYMENT_BLOCKED_STATUS')) xor ( Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENTERIO_PAYMENT_BLOCKED_STATUS'))))) {
            $order_state_new = new OrderState();
            $order_state_new->name[Language::getIdByIso("pl")] = "Płatność zablokowana";
            $order_state_new->name[Language::getIdByIso("en")] = "Payment blocked";
            $order_state_new->send_email = false;
            $order_state_new->invoice = false;
            $order_state_new->unremovable = false;
            $order_state_new->color = "red";
            if (!$order_state_new->add()) {
                return false;
            }
            if (!Configuration::updateValue('PAYMENTERIO_PAYMENT_BLOCKED_STATUS', $order_state_new->id)) {
                return false;
            }
        }

        if (
            Validate::isInt(Configuration::get('PAYMENTERIO_PAYMENT_ERROR_STATUS')) xor ( Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENTERIO_PAYMENT_ERROR_STATUS'))))) {
            $order_state_new = new OrderState();
            $order_state_new->name[Language::getIdByIso("pl")] = "Błąd płatności";
            $order_state_new->name[Language::getIdByIso("en")] = "Payment error";
            $order_state_new->send_email = false;
            $order_state_new->invoice = false;
            $order_state_new->unremovable = false;
            $order_state_new->color = "red";
            if (!$order_state_new->add()) {
                return false;
            }
            if (!Configuration::updateValue('PAYMENTERIO_PAYMENT_ERROR_STATUS', $order_state_new->id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check, if SSL is enabled during current connection
     * @return boolean
     */
    public function isSSLEnabled()
    {
        if (isset($_SERVER['HTTPS'])) {
            if (Tools::strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1') {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] === '443')) {
            return true;
        }
        return false;
    }
}
