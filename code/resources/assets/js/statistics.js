import Chartist from 'chartist';
import utils from "./utils";

class Statistics {
    static init(container)
    {
        setTimeout(() => {
            if ($('#stats-summary-form', container).length != 0) {
                this.runSummaryStats();

                $('#stats-summary-form').submit((event) => {
                    event.preventDefault();
                    this.runSummaryStats();
                });
            }

            if ($('#stats-supplier-form', container).length != 0) {
                this.runSupplierStats();

                $('#stats-supplier-form').submit((event) => {
                    event.preventDefault();
                    this.runSupplierStats();
                });
            }
        }, 500);
    }

    static doEmpty(target)
    {
        $(target).empty().css('height', 'auto').append($('#templates .alert').clone());
    }

    static commonGraphConfig()
    {
        return {
            horizontalBars: true,
            axisX: {
                onlyInteger: true
            },
            axisY: {
                offset: 220
            },
        };
    }

    static doGraph(selector, data)
    {
        if (data.labels.length == 0) {
            this.doEmpty(selector);
        }
        else {
            if ($(selector).length != 0) {
                $(selector).empty().css('height', data.labels.length * 40);
                new Chartist.Bar(selector, data, this.commonGraphConfig());
            }
        }
    }

    static doGraphs(group, data)
    {
        this.doGraph('#stats-' + group + '-expenses', data.expenses);
        this.doGraph('#stats-' + group + '-users', data.users);
        this.doGraph('#stats-' + group + '-categories', data.categories);
    }

    static loadingGraphs(group)
    {
        $('#stats-' + group + '-expenses').empty().append(utils.loadingPlaceholder());
        $('#stats-' + group + '-users').empty().append(utils.loadingPlaceholder());
        $('#stats-' + group + '-categories').empty().append(utils.loadingPlaceholder());
    }

    static runSummaryStats()
    {
        this.loadingGraphs('generic');

        $.getJSON('/stats/summary', {
            start: $('#stats-summary-form input[name=startdate]').val(),
            end: $('#stats-summary-form input[name=enddate]').val(),
            target: $('input[name=stats_target]').val(),
        }, (data) => {
            this.doGraphs('generic', data);
        });
    }

    static runSupplierStats()
    {
        this.loadingGraphs('products');

        $.getJSON('/stats/supplier', {
            supplier: $('#stats-supplier-form select[name=supplier] option:selected').val(),
            start: $('#stats-supplier-form input[name=startdate]').val(),
            end: $('#stats-supplier-form input[name=enddate]').val(),
            target: $('input[name=stats_target]').val(),
        }, (data) => {
            this.doGraphs('products', data);
        });
    }
};

export default Statistics;
