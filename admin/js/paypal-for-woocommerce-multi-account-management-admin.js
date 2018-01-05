jQuery( function( $ ) {    
        $('#woocommerce_paypal_express_testmode_microprocessing').change(function () {
        var sandbox = $('#woocommerce_paypal_express_sandbox_api_username_microprocessing, #woocommerce_paypal_express_sandbox_api_password_microprocessing, #woocommerce_paypal_express_sandbox_api_signature_microprocessing').closest('tr');
        var production = $('#woocommerce_paypal_express_api_username_microprocessing, #woocommerce_paypal_express_api_password_microprocessing, #woocommerce_paypal_express_api_signature_microprocessing').closest('tr');
        if ($(this).is(':checked')) {
            sandbox.show();
            production.hide();
        } else {
            sandbox.hide();
            production.show();
        }
    }).change();
    
    $('.aewp-product-search').selectWoo({
        ajax: {          
            url: admin_ajax_url,
            dataType: 'json',
            delay: 250,
            data: function (params) {
              return {
                    term:     params.term,
                    action:   'woocommerce_json_search_products_and_variations',
                    security: search_products_nonce,
                    exclude:  $( this ).data( 'exclude' ),
                    include:  $( this ).data( 'include' ),
                    limit:    $( this ).data( 'limit' )
                };
            },
            processResults: function( data ) {
                    var terms = [];
                    if ( data ) {
                            $.each( data, function( id, text ) {
                                    terms.push( { id: id, text: text } );
                            });
                    }
                    return {
                            results: terms
                    };
            },
            cache: true
          },
          escapeMarkup: function (markup) { return markup; },
          minimumInputLength: 3
    });
});