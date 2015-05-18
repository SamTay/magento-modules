/**
 * @package     BlueAcorn\AjaxCart
 * @version
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
$j(document).ready(function() {
    if (!$j('body').attr('class').match(/.+catalog-product-view.+/)) {
        $j('.btn-cart').each(function() {
            // Extract URL from onclick action
            var url = $j(this).attr('onclick').match(/setLocation\(\'(.+)\'\)/).last();

            // If URL is an add to cart URL Ajaxify it
            if(url.match(/.+checkout\/cart\/add\/.+/)) {
                // Detach onclick action
                $j(this).attr('onclick', '');

                // Attach AjaxCart observer to add to cart buttons
                $j(this).bind('click', function () {
                    // Show fancy box with ajax_cart_form
                    $j.fancybox.open({
                        content: '<form action="'+url+'" method="POST" id="ajax_cart_form">' +
                            '<input type="text" name="qty" id="qty" value="1" />' +
                            '<button class="button btn-cart" type="submit" title="Add to Cart">' +
                            '<span><span>Add to Cart</span></span>' +
                            '</button>' +
                            '</form>'
                    });

                    // Bind Ajaxcart observer to submit action
                    $j('#ajax_cart_form').bind('submit', function(e) {
                        // Close fancy box
                        $j.fancybox.close();

                        // Prevent form from submitting
                        e.preventDefault();

                        // Gather data from product_addtocart_form
                        var data = {};
                        $j.each($j(this).serializeArray(), function(i, field) {
                            data[field.name] = field.value;
                        });

                        // Instantiate Ajaxcart with url from button
                        ajaxcart = new Ajaxcart(url);

                        // Let the ajax happen
                        ajaxcart.addToCart(data);
                    });
                });
            }
        });
    } else {
        // Detach onclick action
        $j('.btn-cart').attr('onclick','');

        // Atach AjaxCart observer to add to cart button
        $j('.btn-cart').bind('click', function() {
            // Validate product_addtocart_form before attempting AJAX
            if (productAddToCartForm.validator.validate()) {
                // Instantiate Ajaxcart with url from form action
                ajaxcart = new Ajaxcart($j('#product_addtocart_form').attr('action'));

                // Gather data from product_addtocart_form
                var data = {};
                $j.each($j('#product_addtocart_form').serializeArray(), function(i, field) {
                    data[field.name] = field.value;
                });

                // Let the ajax happen
                ajaxcart.addToCart(data);
            }
        });
    }

    $j(document).bind('rebind-minicart', function(event, data) {
        var skipContents = $j('.skip-content');
        var skipLinks = $j('.skip-link');

        skipLinks.on('click', function (e) {
            e.preventDefault();

            var self = $j(this);
            var target = self.attr('href');

            // Get target element
            var elem = $j(target);

            // Check if stub is open
            var isSkipContentOpen = elem.hasClass('skip-active') ? 1 : 0;

            // Hide all stubs
            skipLinks.removeClass('skip-active');
            skipContents.removeClass('skip-active');

            // Toggle stubs
            if (isSkipContentOpen) {
                self.removeClass('skip-active');
            } else {
                self.addClass('skip-active');
                elem.addClass('skip-active');
            }
        });

        $j('#header-cart').on('click', '.skip-link-close', function(e) {
            var parent = $j(this).parents('.skip-content');
            var link = parent.siblings('.skip-link');

            parent.removeClass('skip-active');
            link.removeClass('skip-active');

            e.preventDefault();
        });

        // Rebind tooltip events
        $j('.truncated').each(function(){
            $j(this).bind('mouseover', function(){
                if ($j(this).children('div.truncated_full_value')) {
                    $j(this).children('div.truncated_full_value').addClass('show')
                }
            });
            $j(this).bind('mouseout', function(){
                if ($j(this).children('div.truncated_full_value')) {
                    $j(this).children('div.truncated_full_value').removeClass('show')
                }
            });

        });
    });
});

function Ajaxcart(url) {
    this.url = url;
    var minicartOptions  = {
        formKey:    $j('#form_key').html()
    }
    this.Mini = new Minicart(minicartOptions);
};

Ajaxcart.prototype = {
    addToCart: function(data) {
        // Append Minicart formKey to the data so form actions function
        data['formKey'] = this.Mini.formKey;

        // Scroll back to the top
//        $j("html, body").animate({ scrollTop: 0 }, "slow");

        // Show minicart w/ loading overlay
        this.showMinicart(true);

        $j.ajax({
            url: this.url,
            dataType: "json",
            context: this,
            data: data
        }).done(function(result) {
            // Replace HTML for all result properties with keys ending in '_html'
            for (var key in result) {
                if (result.hasOwnProperty(key) && key.indexOf('_html') > -1) {
                    var identifier = key.replace('_html', '');
                    if ($j(identifier).length) {
                        $j(identifier).html(result[key]);
                    }
                }
            }

            // Persist minicart visibility
            this.showMinicart(false);

            // Add success message
            this.Mini.showSuccess(result.success);

            // Rebind Minicart actions
            $j(document).trigger('rebind-minicart');

            // Reinit Minicart
            this.Mini.init();
        }).error(function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX Cart Error: ' + errorThrown);
            if (jqXHR.responseJSON.redirect_url) {
                // Use replace so that checkout/cart/add is not in browser history
                location.replace(jqXHR.responseJSON.redirect_url);
            } else {
                location.reload();
            }
        });

    },

    // Shows minicart with loading overlay
    showMinicart: function(loading) {
        $j('.header-minicart a').addClass('skip-active');
        $j('#header-cart').addClass('skip-active');
        if (loading) {
            this.Mini.showOverlay();
        }
        this.Mini.hideMessage();
    }
};

