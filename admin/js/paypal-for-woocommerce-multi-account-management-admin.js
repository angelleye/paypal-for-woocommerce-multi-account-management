jQuery(function ($) {
    'use strict';
    $(".add_micro_account_section").click(function (e) {
        e.preventDefault();
        var html = '<table class="form-table">' + $('#micro_account_fields').html() + '</table><br/>';
        $(".paypal_micro_account_section_add_new").after(html);
    });
});