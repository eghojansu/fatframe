$(document).ready(function() {
    adjustLayout();
    $(window).on('resize', adjustLayout);
    $('.notifier').each(function() {
        var data = $(this).data();
        var message = $.trim($(this).html());
        toastr[data.toastr](message);
    });
    $('[data-confirm]').on('click', function(event) {
        event.preventDefault();
        var target = $(this).prop('href');
        var key = $(this).data('confirm');
        bootbox.confirm({
            title: 'Confirmation',
            message: app.confirm[key]+'?',
            buttons: {
                confirm: {
                    label: '<i class="fa fa-check"></i> OK',
                    className: 'btn-danger'
                },
                cancel: {
                    label: '<i class="fa fa-ban"></i> Cancel',
                    className: 'btn-default'
                }
            },
            callback: function(ya) {
                if (ya) {
                    window.location.href = target;
                }
            }
        });
    });
    $('[data-toggle=tooltip]').each(function() {
        var option = {
            container: 'body'
        };
        $(this).tooltip(option);
    });
    $('select[data-reload]').on('change', function() {
        var name = $(this).data('reload') || $(this).prop('name');
        var value = $(this).val();
        var queries = getQuery();
        queries[name] = value;
        window.location.search = $.param(queries)
    });
    $('form[data-quick-search]').on('submit', function(event) {
        event.preventDefault();
        var queries = getQuery();
        $(this).find('input,select').each(function() {
            queries[this.name] = this.value;
        });
        window.location.search = $.param(queries);
    });
    $('.use-datepicker').each(function() {
        var option = $.extend({
            format: 'dd-mm-yyyy',
            endDate: '0d'
        }, $(this).data(), {});
        $(this).datepicker(option);
    });
    $('.use-colorpicker').spectrum({
        preferredFormat: 'hex'
    });
    $('[data-copier]').each(function() {
        var target = $(this).data('copier').split(',');
        $.each(target, function(i, v) {
            var $t = $(v);
            $t.each(function() {
                var changed = $(this).val().length > 0;
                $(this).data('changed', changed);
                $(this).on('keyup', function() {
                    if (!$(this).data('changed')) {
                        $(this).data('changed', true);
                    }
                });
            });
        });
        $(this).on('keyup', function() {
            var val = $(this).val();
            $.each(target, function(i, v) {
                var $t = $(v);
                $t.val(function() {
                    return $(this).data('changed')?this.value:val;
                });
            });
        });
    });
    $('[data-mask]').each(function() {
        var mask = $(this).data('mask');
        $(this).inputmask(mask);
    });
    $('[data-transform=autonumeric]').autoNumeric({
        aSep: '.',
        aDec: ','
    });
    $('[data-transform=rupiah]').autoNumeric({
        aSep: '.',
        aDec: ',',
        aSign: 'Rp '
    });
    $('[data-transform=prosen]').autoNumeric({
        aSep: '.',
        aDec: ',',
        aSign: '%',
        pSign: 's'
    });
    $('.selectpicker').each(function() {
        var url = $(this).data('url');
        var $that = $(this);
        var $form = $that.closest('form');
        var option = {};
        if (url) {
            option.minimumInputLength = 1;
            option.ajax = {
                url: url,
                dataType: 'jsonp',
                delay: 250,
                data: function (term, page) {
                    var param = {
                        q: term, // search term
                        records: 10,
                        page: page,
                        extras: {}
                    };
                    var extras = ($that.data('extras') || '').split(',');
                    $.each(extras, function(i, v) {
                        var $e = $form.find('[data-extra-id="'+v+'"]');
                        if ($e.length) {
                            param.extras[v] = $e.val();
                        }
                    });

                    return param;
                },
                processResults: function (data, params) {
                    var more = ((params.page || 1) * 10) < data.total;
                    return { results: data.objects, paginaton: {more: more}};
                },
                cache: true
            };
        }
        $(this).select2(option);
    });
    $('form').delegate('.loadnext', 'change', function() {
        var $that = $(this);
        var $form = $that.closest('form');
        var next = $that.data('next');
        var $next = $form.find(next);
        var data = {};
        $form.find('.loadnext').each(function() {
            data[$(this).attr('name')] = $(this).val();
        });
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: data,
            success: function(html) {
                $next.select2('destroy');
                $next.replaceWith(
                    $(html).find(next)
                );
                $form.find(next).select2();
            }
        });
    });
});

function isNotEmpty(v) {
    return (v || v !== "");
}
function getQuery() {
    var text = window.location.search.substr(1).replace(/&/g, '","').replace(/=/g, '":"');

    return text ? $.parseJSON('{"'+text+'"}') : {};
}
function adjustLayout() {
    var navheight = $('.navbar-fixed-top').height();

    $('body').css('padding-top', (navheight+20)+'px');
}
