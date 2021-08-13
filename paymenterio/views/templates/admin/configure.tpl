{*
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
*  @author    PrestaShop SA < contact@prestashop.com >
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
    <div class="paymenterio-config">
        <h3>{l s='Informacja' mod='paymenterio'}</h3>
        <a href="http://www.paymenterio.pl" target="_blank" title="www.paymenterio.pl"><img src="{$moduleDirectory}views/img/logo.svg" height="50px" border="0" /></a>
        {if $properlyConfigured}
            <div class="alert alert-success" style="margin-top: 20px;">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Moduł jest aktywny. ' mod='paymenterio'}</h2>
                <br />
                <p style="color: #D27C82;"><b>{if $oldVersion}{l s='Please update your PrestaShop installation to the latest version if you want to use the newest features!' mod='paymenterio'}{/if}</b></p>
            </div>
        {else}
                <div class="alert alert-danger" style="margin-top: 20px;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Moduł nie jest aktywny, sprawdź ustawienia.' mod='paymenterio'}</h2>
                    <br />
                    {if $requiredDataNotFilled }
                        <p style="color: #555;"><b>{l s='Dodaj Identyfikator lub Hash sklepu oraz klucz API, aby móc przyjmować płatności.' mod='paymenterio'}</b></p>
                    {else}
                        <p style="color: #555;"><strong>{l s='Moduł jest w pełni skonfigurowany, ale nie został włączony. Włącz moduł, aby móc korzystać z bramki płatniczej Paymenterio.' mod='paymenterio'}</strong></p>
                    {/if}
                    <br />
                </div>
        {/if}

        <p>{l s='Jedynymi danymi potrzebnymi do integracji są: Identyfikator sklepu (hash) i klucz API.' mod='paymenterio'}</p>
        <p>{l s='Identyfikator bądź Hash sklepu dostępny jest w panelu użytkownika. W celu uzyskania klucza API prosimy o ' mod='paymenterio'} <a href="https://paymenterio.pl/contact" target="_blank">{l s='kontakt.'  mod='paymenterio'}</a> </p>
        {if !$isSSL}
            <p><b style="color: brown;">{l s='Twój sklep nie korzysta z protokołu HTTPS. Zalecamy włączenie protokołu HTTPS, aby zapewnić lepszą ochronę.' mod='paymenterio'}</b></p>
        {/if}
        <br />
        <h2>{l s='Zapoznaj się z instrukcją instalacji: '  mod='paymenterio'}<a href="https://paymenterio.com" Title="{l s='Zobacz dokumentację' mod='paymenterio'}" target="_blank"> {l s='Zobacz dokumentację' mod='paymenterio'}</a></h2>
    </div>
</div>