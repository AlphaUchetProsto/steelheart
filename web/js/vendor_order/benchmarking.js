$(document).on('click', '.benchmarking-variations .fa-square-plus', function (){
    var table = $(this).closest('table');
    var currentRow = $(this).closest('tr');

    $(this).removeClass('fa-square-plus').addClass('fa-square-minus');
    $(this).closest('tr').find('td').removeClass('td-hidden');

    return $.ajax({
        url: '/vendor-order/benchmarking/add-row-variation' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {id: table.attr('data-id')},
        success: function (response) {
            table.find('tbody').append(response);
        },
    });
});

$(document).on('click', '.benchmarking-variations .fa-square-minus', function (){
    var table = $(this).closest('table');

    if (Number($(this).closest('tr').find('input[name="id"]').val()) > 0) {
        $.ajax({
            url: '/vendor-order/benchmarking/delete-variation' + window.location.search,
            method: 'POST',
            dataType: 'html',
            data: {'id': $(this).closest('tr').find('input[name="id"]').val()},
            success: function (response) {
                // window.location.reload();
            },
        });
    }

    $(this).closest('tr').remove();
});

$(document).on('click', '.save-benchmarking', function (){
    let data = JSON.stringify(getProductRowBenchmarking());
    let btn = $(this);

    return $.ajax({
        url: '/vendor-order/benchmarking/save-rows' + window.location.search,
        method: 'POST',
        // dataType: 'json',
        data: {
            dealId: $('.deal-input').val(),
            data: data,
        },
        beforeSend: function (){
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохраняю');
        },
        success: function (response) {
            window.location.reload();
        },
    });
});

// $(document).on('click', '.save-benchmarking', function (){
//     let data = JSON.stringify(getProductRowBenchmarking());
//     let btn = $(this);
//
//     return $.ajax({
//         url: '/vendor-order/benchmarking/save-rows' + window.location.search,
//         method: 'POST',
//         dataType: 'json',
//         data: data,
//         beforeSend: function (){
//             btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохраняю');
//         },
//         success: function (response) {
//             window.location.reload();
//         },
//     });
// });

$(document).on('click', '.complete-benchmarking', function (){
    let btn = $(this);

    $.ajax({
        url: '/vendor-order/benchmarking/search' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {dealId: $('.deal-input').val()},
        beforeSend: function (){
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ищу...');
        },
        success: function (response) {
            window.location.reload();
        },
    });
});



$(document).on('click', '#popup-import-benchmarking .btn-primary', function (){
    let btn = $(this);

    

    $.ajax({
        url: 'import' + window.location.search,
        method: 'POST',
        processData: false,
        contentType: false,
        data: new FormData(document.getElementById('import-form')),
        beforeSend: function (){
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Загружаю...');
        },
        success: function (response) {
            btn.html('Продолжить');

            if (typeof response == 'object') {
                $('#popup-import-benchmarking .invalid-feedback').html(response.file[0])
                $('#importform-file').toggleClass('is-invalid')
            }else {
                window.location.reload();
            }
        },
    });
});

function getProductRowBenchmarking()
{
    let data = [];

    $('.products-table tbody .main-row').each(function (){
        let dataRow = {};

        $(this).find('input, select').each(function (){
            dataRow[$(this).attr('name')] = $(this).val();
        });

        dataRow['dealId'] = $('.deal-input').val();

        let variations = [];

        $(this).next().find('.benchmarking-variations > tbody > tr').each(function (){
            let variation = {};

            $(this).find('input, select').each(function (){
                variation[$(this).attr('name')] = $(this).val();
            });

            variations.push(variation);
        });

        dataRow['variations'] = variations;

        data.push(dataRow);
    });

    return data;
}