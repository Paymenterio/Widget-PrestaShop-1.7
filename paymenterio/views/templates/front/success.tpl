{extends file='page.tpl'}

{block name='content'}
    <div class="container">
        <div class="row">
            <div class="col-12" style="margin: 20px 0 30px 0;text-align: center;">
                <p>
                    <i class="material-icons" style="font-weight: bold; color: #70b580; font-size: 4.0em;">tag_faces</i>
                </p>
                <h1 style="text-align: center">{l s='Płatność została przekazana, dziękujemy.' mod='paymenterio'}</h1>
                <p style="text-align: center">
                    {l s='Twoje zamówienie zostało złożone i oczekuje na potwierdzenie płatności.' mod='paymenterio'}
                    <br />
                    <small>
                        {l s='W razie pytań, zapraszamy do kontaktu.' mod='paymenterio'}
                    </small>
                </p>
            </div>
        </div>
    </div>
{/block}