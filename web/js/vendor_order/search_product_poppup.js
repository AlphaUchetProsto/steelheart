$(document).on('mouseup', function(e){
    e.stopPropagation();
    e.stopImmediatePropagation();

    let popup = $('.popup-product');
    let inputProduct =  $('.product-input');
    let inputArticle =  $('.product-article');

    if (popup.has(e.target).length > 0) {
        return false;
    }

    if (!popup.is(e.target)  && !$(e.target).is(':focus')) {
        popup.remove();
    }else if(!popup.is(e.target)  && $(e.target).is(':focus') && $('.popup-product').length > 0) {
        $('.popup-product').slice(0, $('.popup-product').length - 1).remove();
    }
});

/*$(document).on('focus', '.product-input, .article-input', function (e) {
    e.stopPropagation();
    e.stopImmediatePropagation();

    makePopupDom($(this));

    if ($(this).hasClass('article-input')) {
        return loadPopupProductArticle($(this)).done(function (response){
            $('.popup-product-content').append(response);
        });
    }

    return loadPopupProductTitle($(this)).done(function (response){
        $('.popup-product-content').append(response);
    });
});*/
let activeSupplierInput = null;
$(document).on('input', '.product-input, .article-input, .supplier_input', function (e){
    e.stopPropagation();
    e.stopImmediatePropagation();


    $('.popup-product').remove();

    makePopupDom($(this));

    activeSupplierInput = $(this);

    const input = this;
    if ($(this).hasClass('supplier_input')) {
        if ($(this).val() != ''){
            return loadPopupSuppliers($(this)).done(function (response){
                $('.popup-product-content').html(response);
                $('#itemsList')[0].dataset.currentInput = input.dataset.itemid;
            });
        }else{
            return false;
        }
    }

    if ($(this).hasClass('article-input')) {
        return loadPopupProductArticle($(this)).done(function (response){
            $('.popup-product-content').html(response);
        });
    }

    return loadPopupProductTitle($(this)).done(function (response){
        $('.popup-product-content').html(response);
    });
})

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

function loadPopupSuppliers(productRow)
{
    return $.ajax({
        url: 'popup-content' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {
            supplier : productRow.val(),
        }
    });
}

function loadPopupProductTitle(productRow)
{
    return $.ajax({
        url: 'popup-content' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {
            name : productRow.val(),
        }
    });
}

function loadPopupProductArticle(productRow)
{
    return $.ajax({
        url: 'popup-content' + window.location.search,
        method: 'POST',
        dataType: 'html',
        data: {
            article: productRow.val(),
        }
    });
}