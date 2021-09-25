window.$ = window.jQuery = global.$ = global.jQuery = require('jquery');
require('bootstrap');

import utils from "./utils";

class Modifiers {
    static modifiers_strings = null;

    static init(container)
    {
        if (container.hasClass('modifier-modal')) {
            if (Modifiers.modifiers_strings == null) {
                utils.postAjax({
                    method: 'GET',
                    url: 'modifiers/strings',
                    dataType: 'JSON',
                    success: function(data) {
                        Modifiers.modifiers_strings = data;
                    }
                });
            }

            container.on('change', 'input:radio', function() {
                /*
                    L'ordine degli elementi usati per costruire l'indice delle stringhe
                    deve combaciare con quello in modifier/edit.blade.php
                */
                var container = $(this).closest('.modifier-modal');
                var model_type = container.attr('data-target-type');
                var arithmetic = container.find('input:radio[name=arithmetic]:checked').val();
                var value = container.find('input:radio[name=value]:checked').val();
                var applies_type = container.find('input:radio[name=applies_type]:checked').val();
                var scale = container.find('input:radio[name=scale]:checked').val();
                var applies_target = container.find('input:radio[name=applies_target]:checked').first().val();
                var distribution_type = container.find('input:radio[name=distribution_type]:checked').val();
                var distribution_type_selection = container.find('.distribution_type_selection');

                if (model_type == 'product') {
                    if (applies_type == 'none') {
                        container.find('input:radio[name=applies_target][value=product]').click();
                        applies_target = 'product';
                    }
                }

                if (value == 'price') {
                    container.find('.arithmetic_type_selection').addClass('d-none').find('input:radio[value=apply]').click();
                    arithmetic = 'apply';
                    container.find('.distribution_type_selection').addClass('d-none').find('input:radio[value=none]').click();
                    distribution_type = 'none';
                }
                else {
                    container.find('.arithmetic_type_selection').removeClass('d-none');

                    if (arithmetic == 'apply') {
                        container.find('.arithmetic_type_selection').find('input:radio[value=sum]').click();
                        arithmetic = 'sum';
                    }

                    if (applies_target != 'order') {
                        distribution_type_selection.addClass('d-none').find('input:radio[value=none]').click();
                        distribution_type = 'none';
                    }
                    else {
                        distribution_type_selection.removeClass('d-none');
                        if (distribution_type == 'none') {
                            distribution_type_selection.find('input:radio[value=quantity]').click();
                            distribution_type = 'quantity';
                        }
                    }
                }

                var key = applies_type + ',' + model_type + ',' + applies_target + ',' + scale + ',' + applies_type + ',' + arithmetic + ',' + applies_target + ',' + value + ',' + distribution_type;
                var labels = Modifiers.modifiers_strings[key];

                var simplified = container.find('.simplified_input');
                var advanced = container.find('.advanced_input');
                simplified.toggleClass('d-none', applies_type != 'none');
                advanced.toggleClass('d-none', applies_type == 'none');

                if (applies_type != 'none') {
                    container.find('input:radio[name=value][value=price]').next('label').removeClass('disabled');
                    var table = advanced.find('.dynamic-table');

                    table.find('tr').each(function() {
                        if ($(this).find('.add-row').length != 0) {
                            return true;
                        }

                        $(this).find('td:nth-child(1) .form-control-plaintext').text(labels[0]);
                        $(this).find('td:nth-child(2) .input-group-text').text(labels[1]);
                        $(this).find('td:nth-child(3) .form-control-plaintext').text(labels[2]);
                        $(this).find('td:nth-child(4) .input-group-text').text(labels[3]);
                        $(this).find('td:nth-child(5) .form-control-plaintext').text(labels[4]);
                    });

                    if (table.find('tbody tr').length == 1) {
                        table.find('.add-row').click();
                    }
                }
                else {
                    var value_price_selection = container.find('input:radio[name=value][value=price]');
                    value_price_selection.next('label').addClass('disabled');
                    if (value_price_selection.prop('checked')) {
                        container.find('input:radio[name=value][value=absolute]').click();
                    }

                    simplified.find('.form-control-static').eq(0).text(labels[2]);
                    simplified.find('.input-group-text').text(labels[3]);
                    simplified.find('.form-control-static').eq(1).text(labels[4]);
                }
            });
        }
    }

    static updateBookingModifiers(dynamic_modifiers, container)
    {
        $('input[name^="modifier-"]', container).each(function() {
            let modid = parseInt($(this).attr('name').split('-')[1]);
            if (modid == 0) {
                return;
            }

            for (let [modifier_id, modifier_meta] of Object.entries(dynamic_modifiers)) {
                if (modid == modifier_id) {
                    $(this).parent().find('span').text(utils.priceRound(modifier_meta.amount));
                    delete dynamic_modifiers[modifier_id];
                    return;
                }
            }

            $(this).closest('.modifier-row').remove();
        });

        for (let [modifier_id, modifier_meta] of Object.entries(dynamic_modifiers)) {
            var template = $('.modifier-row.hidden', container);
            var new_row = template.clone();

            new_row.removeClass('hidden').find('.static-label .name').text(modifier_meta.label);
            if (modifier_meta.variable) {
                new_row.find('.static-label .mutable').removeClass('hidden');
            }

            new_row.find('.static-label').siblings('.float-end').append(utils.detailsButton(modifier_meta.url));
            new_row.find('input[name="modifier-0"]').attr('name', 'modifier-' + modifier_id).parent().find('span').text(utils.priceRound(modifier_meta.amount));
            template.before(new_row);
        }
    }
}

export default Modifiers;
