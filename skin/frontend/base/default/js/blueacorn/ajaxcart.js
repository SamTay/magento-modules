/**
 * @package     BlueAcorn\AjaxCart
 * @version
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

/**
 * Adds an ajax addItem method to the minicart prototype
 * Observe 'ajax-add-to-cart-complete' to hook into add-to-cart completion
 *
 * @param url
 * @param requestData
 * @dispatches 'ajax-add-to-cart-complete', 'ajax-add-to-cart-before'
 */
Minicart.prototype.addItem = function(url, requestData) {
    $j(document).trigger('ajax-add-to-cart-before');
    var cart = this;
    cart.hideMessage();
    cart.showOverlay();
    $j.extend(requestData, {form_key: cart.formKey});
    $j.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: requestData
    }).done(function(result) {
        cart.hideOverlay();
        if (result.success) {
            cart.updateCartQty(result.qty);
            cart.updateContentOnUpdate(result);
            $j(cart.selectors.countHider).removeClass('no-count');
            $j(document).trigger('ajax-add-to-cart-complete', [result]);
        } else {
            cart.showMessage(result);
        }
    }).error(function() {
        cart.hideOverlay();
        cart.showError(cart.defaultErrorMessage);
    });
};

/**
 * Show minicart and scroll to top of page
 */
Minicart.prototype.showCart = function() {
    $j(this.selectors.toggleVisibility).addClass('skip-active')
    $j('html, body').animate({ scrollTop: 0 }, 'slow');
};

/**
 * Extend Minicart initialization to hook into new ajax events
 */
Minicart.prototype.init = Minicart.prototype.init.wrap(function($super) {
    var cart = this;
    $j.extend(cart.initAfterEvents, {
        attachCartAddListeners: function() {
            $j(document).off('ajax-add-to-cart-hook').on('ajax-add-to-cart-hook', function (event, url, requestData) {
                cart.addItem(url, requestData);
            });
            $j(document).off('ajax-add-to-cart-before').on('ajax-add-to-cart-before', function(event) {
                cart.showCart();
            });
        }
    });
    $j.extend(cart.selectors, {
        toggleVisibility: '#header-cart.skip-content, .skip-link[data-target-element="#header-cart"]',
        countHider: 'a.skip-link.skip-cart'
    });
    $super();
});

/**
 * Detach previous add-to-cart events and hook into new Minicart functionality
 */
(function($) {
    var selectors = {
        form: '#product_addtocart_form',
        cartButtons: '.btn-cart'
    };
    $(document).ready(function() {
        // Set up dispatchers for product view page
        if ($('body').hasClass('catalog-product-view')) {
            $(selectors.cartButtons).removeAttr('onclick').off('click').on('click', function(){
                if (productAddToCartForm.validator.validate()) {
                    var url = $(form).attr('action'),
                        requestData = $(form).serialize();
                    $(document).trigger('ajax-add-to-cart-hook', [url, requestData]);
                }
            });
        // Set up dispatchers for other pages (no product form)
        } else {
            $(selectors.cartButtons).each(function() {
                var onclick = $(this).attr('onclick'),
                    urlRegexMatch = !!onclick ? onclick.match(/setLocation\(\'(.+)\'\)/) : null,
                    url = (urlRegexMatch !== null) ? urlRegexMatch.last() : '';
                if (!url || !url.match(/.+checkout\/cart\/add.+/)) {
                    return; // Nothing to do if this button doesnt add to cart
                }

                $(this).removeAttr('onclick').off('click').on('click', function() {
                    $(document).trigger('ajax-add-to-cart-hook', [url, {}]);
                });
            });
        }
    });
})(jQuery);
