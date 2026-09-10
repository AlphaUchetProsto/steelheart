$(document).ready(function() {
    recountTable();

    $('.add-product').click(function () {


        let nextProduct = parseInt($('tbody tr:last').attr('id')) + 1;

        if (isNaN(nextProduct)) {
            nextProduct = 1;
        }

        let currencyHtml = $('.currency-template').html();

        $('tbody').append(
            '<tr id="' + nextProduct + '">\n' +
            '            <td>\n' +
            '                <div class="open-menu" onclick="triggerMenu(this)" type="button">\n' +
            '                    <span></span>\n' +
            '                    <span></span>\n' +
            '                    <span></span>\n' +
            '                    <div class="menu shadow">\n' +
            '                        <button onclick="copyRow(this)">\n' +
            '                            Копировать\n' +
            '                        </button>\n' +
            '                        <button onclick="deleteRow(this)">\n' +
            '                            Удалить\n' +
            '                        </button>\n' +
            '                    </div>\n' +
            '                </div>\n' +
            '            </td>\n' +
            '            <td class="number">\n' +
            '                '+ nextProduct + '.\n' +
            '            </td>\n' +
            '            <td class="column-sm">\n' +
            '                <label>\n' +
            '                    <input type="text" class="form-control partNumber">\n' +
            '                </label>\n' +
            '            </td>\n' +
            '            <td>\n' +
            '                <label>\n' +
            '                    <input type="text" class="form-control product" oninput="checkEmptyProducts(this)">\n' +
            '                </label>\n' +
            '            </td>\n' +
            '            <td class="column-sm">\n' +
            '                <label>\n' +
            '                    <input type="text" class="form-control delivery-time">\n' +
            '                </label>\n' +
            '            </td>\n' +
            '            <td class="column-sm">\n' +
            '                <label>\n' +
            '                    <input type="number" class="form-control price" placeholder="0" oninput="recountSum(this)">\n' +
            '                </label>\n' +
            '            </td>\n' +
            '            <td class="column-sm">\n' +
            '                <label>\n' +
            '                    <input type="number" class="form-control count" value="1" oninput="recountSum(this)">\n' +
            '                </label>\n' +
            '            </td>\n' +
            '            <td class="column-sm">\n' +
            '                <label>\n' +
            '                    <input disabled readonly type="number" class="form-control sum" value="0">\n' +
            '                </label>\n' +
            '            </td>\n' +
            '            <td>\n' +
            '                <select class="form-select currency" aria-label="Default select example" onchange="recountTable()">\n' +
                                currencyHtml +
            '                </select>\n' +
            '            </td>\n' +
            '        </tr>'
        )
    });

    $(document).click(function(event) {
        if (!$(event.target).is(".open-menu")) {
            $('.menu').removeClass('active');
        }
    });

    $('.save').click(function () {
        var products = {};
        var dealId = $('#dealId').val();

        $('tbody > tr').each(function(i) {
            let product = {}

            product['partNumber'] = $(this).find('.partNumber').val();
            product['name'] = $(this).find('.product').val();
            product['delivery-time'] = $(this).find('.delivery-time').val();
            product['price'] = $(this).find('.price').val();
            product['count'] = $(this).find('.count').val();
            product['currency'] = $(this).find('.currency').val();

            products[i] = product;
        });

        let btnElement = $(this);

        $(this).addClass('loading').prop('disabled', true);

        $.ajax({
            url: 'https://firstlan.uchetprosto.ru/web/products/save-products',         /* Куда пойдет запрос */
            method: 'post',             /* Метод передачи (post или get) */
            dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */
            data: {dealId: dealId, products: products},     /* Параметры передаваемые в запросе. */
            success: function () {   /* функция которая будет выполнена после успешного запроса.  */
                btnElement.removeClass('loading').prop('disabled', false);
            },
            error: function () {
            }
        });
    });
});

function triggerMenu(button) {
    let id = $(button).parents().eq(1).attr('id');

    console.log(id);

    $('.menu').not('#' + id +' .menu').removeClass('active');
    $('#' + id +' .menu').toggleClass('active');
}

function deleteRow(button) {
    let id = $(button).parents().eq(3).attr('id');

    $('#' + id).remove();

    recountTable();
}

function copyRow(button) {
    let id = $(button).parents().eq(3).attr('id');

    $('#' + id).clone().insertAfter('#' + id);

    recountTable();
}

function recountSum(input) {
    let id = $(input).parents().eq(2).attr('id');

    let price = parseInt($('#' + id).find('.price').val());
    let count = parseInt($('#' + id).find('.count').val());

    if (isNaN(price)) {
        price = 0;
    }
    if (isNaN(count)) {
        count = 0;
    }

    let sum = price * count;

    $('#' + id).find('.sum').val(sum);

    recountTable();
}

function recountTable()
{
    $('.result-list').empty();

    var sum = {};
    $('.save').removeClass('disabled').prop('disabled', false);

    $('tbody > tr').each(function(i) {
        if ($(this).find('.product').val() == '') {
            $('.save').addClass('disabled').prop('disabled', true);
        }

        $(this).find('.number').html(i + 1 + '.');

        $(this).attr("id",i + 1);

        let currency = $(this).find('.currency').find(':selected').text();
        let price = parseInt($(this).find('.sum').val());

        if (isNaN(price)) {
            price = 0
        }

        if (sum[currency] == undefined) {
            sum[currency] = price;
        } else {
            sum[currency] += price;
        }
    });

    for (let currency in sum) {
        $('.result-list').append(
            '<div class="result">\n' +
            '    <div>Сумма ' + currency + '</div>\n' +
            '    <div class="sum">' + sum[currency] + '</div>\n' +
            '</div>'
        );
    }
}

function checkEmptyProducts()
{
    $('.save').removeClass('disabled').prop('disabled', false);

    $('tbody > tr').each(function(i) {
        if ($(this).find('.product').val() == '') {
            $('.save').addClass('disabled').prop('disabled', true);
        }
    });
}