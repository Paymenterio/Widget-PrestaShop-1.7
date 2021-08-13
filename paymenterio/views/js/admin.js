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

function apiKeyViewer() {
    $("i#apiKeyViewer").click(function() {
        $('i#eyelook').toggleClass("icon-eye-slash icon-eye");
        var input = $('input#API_KEY');
        if (input.attr("type") == "password") {
            input.attr("type", "text");
            $('i#eyelook').attr('style', 'color : #97224b; cursor : zoom-out;');
        } else {
            input.attr("type", "password");
            $('i#eyelook').attr('style', 'color : #2eacce; cursor : zoom-in;');
        }
    });
}

(function($) {
    $(document).ready(function(){
        $('input#API_KEY').attr("type", "password");
        apiKeyViewer()
    });
})(jQuery);