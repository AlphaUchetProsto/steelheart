$(document).ready(function (){
    /*getTree().done(function (response){
        $('.tree-popup-product').html(response);
    });

    getProductTree().done(function (response){
        $('.wp-item-popup-product').html(response);
    });*/

    /*if ($('.out-suppliers').length) {
        getCollectionSupplierOut();
    }*/
});

$(document).on('click', '.add-product', function (){
    return addProductRow().done(function (response){
        $('.products-table > tbody').append(response);
        initSearchArticleBtn();
    });
});

$(document).click(function(event) {
    if (!$(event.target).is(".open-menu")) {
        $('.menu').removeClass('active');
    }
});

$(document).on('click', '.open-menu', function (e){
    $(e.target).find('.menu').toggleClass('shadow').toggleClass('active');
});

$(document).on('click', '.menu > button', function (e){
    $(this).closest('.menu').toggleClass('shadow').toggleClass('active');
});

$(document).on('click', '.copy-row', function (){
    let row = $(this).closest('tr').clone();

    $('.products-table > tbody').append(row);
});

$(document).on('click', '.search-article-btn', function (){
    const input = $(this).closest('.position-relative').find('.article-input');
    articleSearch($(this), input.val());
});

function articleSearch(btn, article){
    $.ajax({
        url: '/vendor-order/bas/search-article' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {'article': article},
        beforeSend: function (){
            btn.html('<i class="spinner-border spinner-border-sm"></i>');
        },
        success: function (response) {
            // window.location.reload();
            btn.html('<i class="fas fa-search text-secondary"></i>');
            try {
                result = JSON.parse(response);
                // console.log(result.name);
                const row = btn.closest('tr.main-row');
                const nameInput = row.find('.product-input[name="name"]');

                nameInput.val(result.name);
            } catch (e) {
                // console.log("Ответ не JSON");
                console.log(response);
            }
        },
    });
}
function initSearchArticleBtn() {
    // document.querySelectorAll('.article-input').forEach(input => {
    //     const searchBtn = input.parentNode.querySelector('.search-article-btn');
    //
    //     input.addEventListener('focus', () => {
    //         searchBtn.style.display = 'block';
    //     });
    //
    //     // input.addEventListener('blur', () => {
    //     //     searchBtn.style.display = 'none';
    //     // });
    // });

    document.querySelectorAll('.position-relative').forEach(container => {
        const input = container.querySelector('.article-input');
        const searchBtn = container.querySelector('.search-article-btn');

        container.addEventListener('focusin', () => {
            // searchBtn.style.display = 'block';
            searchBtn.style.display = 'none';
        });

        container.addEventListener('focusout', (e) => {
            if (!container.contains(e.relatedTarget)) {
                searchBtn.style.display = 'none';
            }
        });
    });
}


$(document).on('click', '.delete-row', function (){
    $(this).closest('tr').remove();

    let rowId = $(this).closest('tr').find('input[name="id"]').val();

    if (rowId > 0) {
        return $.ajax({
            url: 'delete-row' + window.location.search,
            method: 'POST',
            dataType: 'HTML',
            data: {rowId: rowId},
            success: function (response) {
                window.location.reload();
            },
        });
    }
});

$(document).on('click', '.folder', function (){
    let currentFolder = $(this);

    getTree($(this).attr('aria-label')).done(function (response){
        currentFolder.next().find('.card-body').html(response);
    });

    getProductTree($(this).attr('aria-label')).done(function (response){
        $('.wp-item-popup-product').html(response);
    });
});

$(document).on('dblclick', '.product-item', function (){
    return addProductRow($(this).attr('aria-label')).done(function (response){
        $('.products-table > tbody').append(response);
    });
});

function getTree(folderId = '')
{
    return $.ajax({
        url: 'get-category-product' + window.location.search,
        method: 'POST',
        dataType: 'HTML',
        data: {'folderId': folderId},
    });
}

function getProductTree(folderId = '')
{
    return $.ajax({
        url: 'get-products' + window.location.search,
        method: 'POST',
        dataType: 'HTML',
        data: {'folderId': folderId},
    });
}

function addProductRow(productId = null)
{
    return $.ajax({
        url: 'add-row' + window.location.search,
        method: 'POST',
        dataType: 'HTML',
        data: {'productId': productId},
    });
}





function getProductRow()
{
    let data = [];

    $('.products-table tbody .main-row').each(function (){
        let dataRow = {};

        $(this).find('input, select').each(function (){
            dataRow[$(this).attr('name')] = $(this).val();
        });

        dataRow['dealId'] = $('.deal-input').val();

        let variations = [];

        $(this).next().find('.product-variations > tbody > tr').each(function (){
            let variation = {};

            $(this).find('input, select').each(function () {
                if ($(this).attr('name') == 'brand'){
                    variation[$(this).attr('name')] = $(this).find('option:selected').text();
                }else{
                    variation[$(this).attr('name')] = $(this).val();
                }
            });

            variations.push(variation);
        });

        dataRow['variations'] = variations;

        data.push(dataRow);
    });

    console.log(data);

    return data;
}

// function getProductRow()
// {
//     let data = [];
//
//     $('.products-table tbody .main-row').each(function (){
//         let dataRow = {};
//
//         $(this).find('input, select').each(function (){
//             dataRow[$(this).attr('name')] = $(this).val();
//         });
//
//         dataRow['dealId'] = $('.deal-input').val();
//
//         let variations = [];
//
//         $(this).next().find('.product-variations > tbody > tr').each(function (){
//             let variation = {};
//
//             $(this).find('input, select').each(function () {
//                 variation[$(this).attr('name')] = $(this).val();
//             });
//
//             variations.push(variation);
//         });
//
//         dataRow['variations'] = variations;
//
//         data.push(dataRow);
//     });
//
//     console.log(data);
//
//     return data;
// }

$(document).on('click', '.save-product', function (){
    let data = JSON.stringify(getProductRow());
    let btn = $(this);

    console.log(data);

    return $.ajax({
        url: 'save-rows' + window.location.search,
        method: 'POST',
        dataType: 'json',
        data: data,
        beforeSend: function (){
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохраняю');
        },
        success: function (response) {
            window.location.reload();
        },
    });
});

$(document).on('click', '.open-subtable', function (){
    $(this).closest('tr').next().toggleClass('d-none');
});

// $(document).on('click', '.search-supplier', function (){
//     let btn = $(this);
//
//     $.ajax({
//         url: '/vendor-order/bas/search-supplier' + window.location.search,
//         method: 'POST',
//         dataType: 'html',
//         data: {dealId: $('.deal-input').val()},
//         beforeSend: function (){
//             btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ищу...');
//         },
//         success: function (response) {
//             window.location.reload();
//         },
//     });
// });

$(document).on('click', '.search-supplier', async function () {
    const btn = $(this);
    const originalBtnText = btn.html();

    // Шаг 1: Выполняем поиск товаров по артикулам для пустых строк
    let hasFoundProducts = false;

    // Проходим по всем строкам таблицы
    $('.main-row').each(function() {
        const $row = $(this);
        const nameInput = $row.find('.product-input[name="name"]');
        const articleInput = $row.find('.article-input[name="article"]');
        const article = articleInput.val().trim();

        // Если поле названия пустое, а артикул заполнен
        if (!nameInput.val().trim() && article) {
            const searchBtn = $row.find('.search-article-btn');

            // Выполняем поиск по артикулу
            if (searchBtn.length) {
                hasFoundProducts = true;
                articleSearch(searchBtn, article);
            }
        }
    });


    if (hasFoundProducts) {
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Заполняю товары...');

        await new Promise(resolve => setTimeout(resolve, 2000));
    }

    // Шаг 2: Сохраняем данные
    if (hasFoundProducts) {
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохраняю...');

        try {
            const saveResult = await saveProducts();
            console.log('Сохранено:', saveResult);
        } catch (error) {
            console.error('Ошибка сохранения:', error);
        }
    }

    // Шаг 3: Выполняем поиск поставщиков
    btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ищу поставщиков...');

    $.ajax({
        url: '/vendor-order/bas/search-supplier' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: { dealId: $('.deal-input').val() },
        beforeSend: function() {
            // Текст уже установлен выше
        },
        success: function(response) {
            window.location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Ошибка поиска поставщиков:', error);
            btn.html(originalBtnText);
            // alert('Произошла ошибка при поиске поставщиков');
        }
    });
});

// Функция для сохранения товаров
function saveProducts() {
    return new Promise((resolve, reject) => {
        const data = JSON.stringify(getProductRow());
        console.log('Данные для сохранения:', data);

        $.ajax({
            url: 'save-rows' + window.location.search,
            method: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                console.log('Сохранено успешно');
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Ошибка сохранения:', error);
                reject(error);
            }
        });
    });
}


function getCollectionSupplierOut()
{
    $('.main-row').each(function (){
        let elementAppend = $(this).closest('tr').next().find('td:last-child');
        let productRowId = $(this).closest('tr').find('input[name="id"]').val();
        let productName = $(this).closest('tr').find('input[name="name"]').val();

        if ($(this).closest('tr').next().hasClass('additional-row')) {
            $.ajax({
                url: 'get-collection-supplier' + window.location.search,
                method: 'POST',
                dataType: 'html',
                data: {productRowId: productRowId, productName: productName},
                success: function (response) {
                    elementAppend.html(response);
                },
            });
        }
    });
}

function getCollectionSupplierBitrix()
{
    $('.main-row').each(function (){
        let elementAppend = $(this).closest('tr').next().find('td:last-child');
        let productRowId = $(this).closest('tr').find('input[name="id"]').val();

        if ($(this).closest('tr').next().hasClass('additional-row')) {
            $.ajax({
                url: 'get-collection-supplier' + window.location.search,
                method: 'POST',
                dataType: 'html',
                data: {productRowId: productRowId},
                success: function (response) {
                    elementAppend.html(response);
                },
            });
        }
    });
}

$(document).on('change', '.select-supplier', function (){
    let ssupliersJson = $(this).closest('.bitrix-suppliers').prev().find('input[name="ssuppliers"]').val();
    let collectionSelectedSsuplier = JSON.parse(ssupliersJson);

    let selectedSupplier = $(this).closest('tr').find('input[name="id"]').val();

    if (!$(this).prop('checked')) {
        collectionSelectedSsuplier = collectionSelectedSsuplier.filter(function (item) {
            return item != selectedSupplier;
        });
    } else {
        if (collectionSelectedSsuplier.indexOf(selectedSupplier) == -1) {
            collectionSelectedSsuplier.push(selectedSupplier);
        }
    }

    $(this).closest('.bitrix-suppliers').prev().find('input[name="ssuppliers"]').val(JSON.stringify(collectionSelectedSsuplier));

    return true;
});

$(document).on('click', '.wpc-row-content-item', function (e){
    $(this).addClass('wpc-row-content-item-active');

    let productId = $(this).find('input[name="productId"]').val();
    let productRowIndex = $(this).closest('.popup-product').attr('aria-label');

    return addProductRow(productId).done(function (response){
        let rowId = $('.products-table > tbody > tr').eq(Number(productRowIndex)).find('input[name="id"]').val();

        let row = $(response);
        row.find('input[name="id"]').val(rowId);

        $('.products-table > tbody > tr').eq(Number(productRowIndex)).html(row.html());
        $('.popup-product').remove()
    });
});


$(document).on('click', '.wpc-row-content-item-supplier', function (e){
    let supplierName = $(this).find('h4').text().trim();

    if (activeSupplierInput) {
        activeSupplierInput.val(supplierName);
        activeSupplierInput = null;
    }

    $('#itemsList').closest('.popup-product').remove();
});

// $(document).on('click', '.wpc-row-content-item-supplier', function (e){
//     let supplierName = $(this).find('h4').text().trim();
//     // let supplierId = $(this).find('input[name="supplierId"]').val();
//     let currentInputId = $('#itemsList').data('current-input');
//     if (currentInputId && currentInputId != 0) {
//         $('input[data-itemid="' + currentInputId + '"]').val(supplierName);
//     }else if (activeSupplierInput) {
//         activeSupplierInput.val(supplierName);
//         activeSupplierInput = null;
//     }
//
//     $('#itemsList').closest('.popup-product').remove();
// });

function makePopupDom(inputDom)
{
    let popupBlock = $('<div>', {
        html: '<div class="popup-product-content h-100"><div class="h-100 d-flex align-items-center justify-content-center wp-loader"><span class="loader"></span></div></div>',
        class: 'popup-product card',
        css: {
            top: inputDom.offset().top + inputDom.outerHeight() + 8 + 'px',
            left: inputDom.offset().left + 'px',
            width: inputDom.outerWidth() + 'px',
        },
        'aria-label': inputDom.closest('tr').index(),
    });

    $('body').append(popupBlock);
}

$(document).on('click', '#popup-import-supplier .btn-primary', function (){
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
                $('#popup-import-supplier .invalid-feedback').html(response.file[0])
                $('#importform-file').toggleClass('is-invalid')
            }else {
                $('.products-table > tbody').append(response);
                $('#popup-import-supplier').modal('toggle');
            }
        },
    });
});

function getRowSelectedProduct(){
    let rowsExport = [];

    $('input[name="isExport"]:checked').each(function (){
        let tr = $(this).closest('tr');

        let mainRow = $(this).closest('.additional-row').prev('.main-row');

        typeDetail = tr.find('select[name="typeDetailId"]').find('option:selected').text();
        brand = tr.find('select[name="brand"]').find('option:selected').text();
        let data = {
            name: tr.find('input[name="productName"]').val(),
            article: mainRow.find('.article-input').val(),
            amount: mainRow.find('input[name="quantity"]').val(),
            supplier: tr.find('input[name="supplier"]').val(),
            brand: brand == 'Выбрать' ? '' : brand,
            typeDetail: typeDetail == 'Выбрать' ? '' : typeDetail,
            weight: tr.find('input[name="weight"]').val(),
            deliveryTime: tr.find('input[name="deliveryTime"]').val(),
            price: tr.find('input[name="price"]').val(),
            currency: tr.find('select[name="currency"]').find('option:selected').text()
        }

        rowsExport.push(data);
    });

    return rowsExport;
}

// function getRowSelectedProduct()
// {
//     let rowsExport = [];
//
//     $('input[name="isExport"]:checked').each(function (){
//         let tr = $(this).closest('tr');
//
//         let article = $(this).closest('.additional-row').prev().find('.article-input').val();
//         let amount = $(this).closest('.additional-row').prev().find('input[name="quantity"]').val();
//         let name = tr.find('input[name="productName"]').val();
//
//         let data = [];
//
//         /*tr.find('td').each(function (index){
//             if (index > 1) {
//                 data.push($(this).find('input').val());
//             }
//         });*/
//
//         data.push(name);
//         data.push(article);
//         data.push(amount);
//
//         rowsExport.push(data);
//     });
//
//     return rowsExport;
// }

function getCollectionSelectedProduct()
{
    let rowsExport = [];

    $('input[name="isExport"]:checked').each(function (){
        let tr = $(this).closest('tr');
        let data = {};

        tr.find('td').each(function (index){
            if (index > 1) {
                let field = $(this).find('input, select');

                if (field) {
                    data[field.attr('name')] = field.val();
                }

                if (field.is('select')) {
                    data[field.attr('name') + '_text'] = field.find('option:selected').text();
                }
            }
        });

        data.article = $(this).closest('.additional-row').prev().find('input[name="article"]').val();
        rowsExport.push(data);
    });

    return rowsExport;
}

function getRowProduct(){
    let rowsExport = [];

    $('input[name="isExport"]').each(function (){
        let tr = $(this).closest('tr');

        let mainRow = $(this).closest('.additional-row').prev('.main-row');

        typeDetail = tr.find('select[name="typeDetailId"]').find('option:selected').text();
        brand = tr.find('select[name="brand"]').find('option:selected').text();
        let data = {
            name: tr.find('input[name="productName"]').val(),
            article: mainRow.find('.article-input').val(),
            amount: tr.find('input[name="quantity"]').val(),
            supplier: tr.find('input[name="supplier"]').val(),
            brand: brand == 'Выбрать' ? '' : brand,
            typeDetail: typeDetail == 'Выбрать' ? '' : typeDetail,
            weight: tr.find('input[name="weight"]').val(),
            deliveryTime: tr.find('input[name="deliveryTime"]').val(),
            price: tr.find('input[name="price"]').val(),
            currency: tr.find('select[name="currency"]').find('option:selected').text()
        }

        rowsExport.push(data);
    });

    return rowsExport;
}

$(document).on('click', '.export', function (){
    let btn = $(this);
    let rowsExport = getRowProduct();

    if (rowsExport.length > 0) {
        return $.ajax({
            url: 'export' + window.location.search,
            method: 'POST',
            dataType: 'html',
            beforeSend: function (){
                btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Формирую...');
            },
            data: {rows: rowsExport},
            success: function (result) {
                result = JSON.parse(result);
                window.open(result.result, '_blank');
                btn.html('Экспорт');
            }
        });
    }

    return  false;
});

$(document).on('click', '.btn-create-document', function (){
    let rowsExport = getCollectionSelectedProduct();
    console.log(rowsExport);
    if (rowsExport.length > 0) {
        $('.document-row').remove();
        $('#popup-create-document').modal('show');

        rowsExport.forEach(function (row) {
            let rowHtml = '<div class="row document-row">\n' +
                '                        <div class="col d-flex align-items-center">' + row.productName + '</div>\n' +
                '                        <div class="col-auto" style="width: 150px;">' + '<div class="wp-money_input"> <input class="form-control" type="number" disabled name="main_price" value="' + row.price + '"><span class="currency">' + row.currency_text + '</span> </div></div>' +
                '                        <div class="col-auto" style="width: 150px;">\n' +
                '                            <input class="form-control" type="number" name="price">\n' +
                '                            <input class="form-control" type="hidden" name="name" value="' + row.productName + '">\n' +
                '                            <input class="form-control" type="hidden" name="brand" value="' + row.brand_text + '">\n' +
                '                            <input class="form-control" type="hidden" name="article" value="' + row.article + '">\n' +
                '                            <input class="form-control" type="hidden" name="main_price" value="' + row.price + '">\n' +
                '                            <input class="form-control" type="hidden" name="supplier" value="' + row.supplier + '">\n' +
                '                            <input class="form-control" type="hidden" name="deliveryTime" value="' + row.deliveryTime + '">\n' +
                '                            <input class="form-control" type="hidden" name="quantity" value="' + row.quantity + '">\n' +
                '                        </div>\n' +
                '                    </div>';

            $('.create-document-form').append(rowHtml);
        });
    }

    return false;
});

$(document).on('click', '#popup-create-document .btn-primary', function (){
    let btn = $(this);
    let totalPriceRow = 0;
    let totalPriceProduct = 0;

    let rows = [];

    $('.create-document-form > div').each(function () {
        let dataRow = {};

        $(this).find('input').each(function () {
            dataRow[$(this).attr('name')] = $(this).val();
            if ($(this).attr('name') == 'price' && !$(this).val()) {
                $(this).addClass('is-invalid')
            } else {
                $(this).removeClass('is-invalid')
            }

            if ($(this).attr('name') == 'price') {
                totalPriceRow = totalPriceRow + parseFloat($(this).val());
            }

            if ($(this).attr('name') == 'main_price') {
                totalPriceProduct = totalPriceProduct + parseFloat($(this).val());
            }
        });

        rows.push(dataRow);
    });

    if ($('#popup-create-document input.is-invalid').length == 0) {
        let mappedRow = rows.map((row) => row.name + ' ' + row.article + ' ' + row.price + ' руб.');

        return $.ajax({
            url: 'update-document-field' + window.location.search,
            method: 'POST',
            dataType: 'html',
            data: {
                dealId: $('.deal-input').val(),
                totalPriceRow : totalPriceRow,
                totalPriceProduct: totalPriceProduct,
                rows: mappedRow,
                table: rows,
                mainItem: JSON.stringify(getProductRow())
            },
            beforeSend: function (){
                btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохраняю...');
            },
            success: function (response) {
                window.location.reload();
            },
        });
    }

    return false;
});

$(document).on('click', '.delete-variation', function () {
    $($(this).attr('data-bs-target')).attr('data-id', $(this).closest('tr').find('input[name="id"]').val());
});

$(document).on('click', '#confirm-delete-variation .btn-primary', function () {
    let btn = $(this);
    let variationId = $(this).closest('.modal').attr('data-id');

    return $.ajax({
        url: 'delete-variation' + window.location.search,
        method: 'POST',
        dataType: 'html',
        beforeSend: function (){
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Удаляю...');
        },
        data: {variationId: variationId},
        success: function (result) {
            window.location.reload();
        }
    });
});


$(document).on('click', '.product-variations .fa-square-plus', function (){
    var table = $(this).closest('table');
    var currentRow = $(this).closest('tr');

    $(this).removeClass('fa-square-plus').addClass('fa-square-minus');
    $(this).closest('tr').find('td').removeClass('td-hidden');

    return $.ajax({
        url: '/vendor-order/main/add-row-variation' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {id: table.attr('data-id')},
        success: function (response) {
            table.find('tbody').append(response);
        },
    });
});

$(document).on('click', '.move-to-benchmarking', function (){
    let btn = $(this);

    $.ajax({
        url: '/vendor-order/bas/move-product-benchmarking' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {dealId: $('.deal-input').val()},
        beforeSend: function (){
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Переношу...');
        },
        success: function (response) {
            window.location.reload();
        },
    });
});


