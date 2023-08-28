(function ($) {
    'use strict';
    var yayDokanScript = function () {
        var self = this;
        self.dokanOrderWrapper = '#order-filter';
        self.dokanWithdrawArea = '.dokan-withdraw-area';
        self.balanceArea = '.dokan-withdraw-area .dokan-panel.dokan-panel-default:first-child .dokan-w8';
        self.earningFromOrderWrapper = '.earning-from-order';

        self.regularPrice = '#_regular_price';
        self.salePrice = '#_sale_price';


        self.init = function () {
            // ORDER TABLE AREA
            self.customDokanOrderTable();

            //WITHDRAW AREA
            self.convertMiniWithdrawAmount();

            // ORDER DETAILS AREA
            self.customDokanEarningByOrder();

            // WITHDRAW AREA
            self.customWithDrawArea();
            if (yay_dokan_data.dokan_pro) {
                // REPORTS STATEMENT AREA
                self.customReportStatementArea();
                // COUPON AREA
                self.customCouponsArea();
            }

            // PRODUCT AREA
            self.addNewProductAction();
            self.customProductArea();
        };

        self.convertMiniWithdrawAmount = function () {
            const is_dokan_withdraw_area = $(self.dokanWithdrawArea);
            if (is_dokan_withdraw_area.length > 0) {
                const mini_withdraw_amount_area = self.balanceArea + ' p';
                $(mini_withdraw_amount_area).find("strong").each(function (index) {
                    if (0 != index) {
                        $(this).html(yay_dokan_data.withdraw_limit_currency);
                    }
                });

            }
        }

        self.customDokanOrderTable = function () {
            const dokan_earnings = $(self.dokanOrderWrapper).find('.dokan-order-earning');
            if (dokan_earnings.length > 0) {
                $(self.dokanOrderWrapper).css('opacity', 0.2);
                dokan_earnings.each(function (index) {
                    const _earning = $(this),
                        _parent = _earning.closest('tr'),
                        _order_total = _parent.find('.dokan-order-total'),
                        _order = _parent.find('.dokan-order-id a');

                    const _order_href = _order.attr('href'),
                        _order_id = self.getValueinParam('order_id', _order_href);
                    $.ajax({
                        url: yay_dokan_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'yay_custom_earning_from_order_table',
                            order_id: _order_id,
                            seller_id: yay_dokan_data.seller_id,
                            _nonce: yay_dokan_data.nonce,
                        },
                        success: function success(res) {
                            setTimeout(() => {
                                $(self.dokanOrderWrapper).css('opacity', 1);
                            }, 500);
                            if (res.success && res.data.earning && res.data.order_total) {
                                _earning.html(res.data.earning)
                                _order_total.html(res.data.order_total)
                            }
                        },
                        complete: function complete() {
                            setTimeout(() => {
                                $(self.dokanOrderWrapper).css('opacity', 1);
                            }, 500);
                        },
                    });
                });
            }
        }

        self.customDokanEarningByOrder = function () {
            if ('yes' === yay_dokan_data.order_details_area) {
                self.customDokanOrderDetails();
                if ($(self.earningFromOrderWrapper).length > 0) {
                    $(self.earningFromOrderWrapper).css('opacity', 0.2);
                    $.ajax({
                        url: yay_dokan_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'yay_custom_earning_from_order_table',
                            order_id: yay_dokan_data.order_id,
                            seller_id: yay_dokan_data.seller_id,
                            _nonce: yay_dokan_data.nonce,
                        },
                        success: function success(res) {
                            $(self.earningFromOrderWrapper).css('opacity', 1);
                            if (res.success && res.data.earning) {
                                $(self.earningFromOrderWrapper).html(yay_dokan_data.earning_from_order_text + ' ' + res.data.earning)
                            }
                        },

                    });
                }

                $(document).on('click', 'button.dokan-btn.yay-currency-alert', function (event) {
                    event.preventDefault();
                    if ($('.yay-dokan-request-refund-wrapper').length > 0) {
                        const alertMessage = $(this).data('message-alert');
                        $('.yay-dokan-request-refund-wrapper').addClass('yay-dokan-show-alert').find('.yay-dokan-request-refund-alert-text').html(alertMessage);
                    }
                    return;
                });

                $(document).on('click', '.yay-dokan-request-refund-close-button,.yay-dokan-request-refund-wrapper.yay-dokan-show-alert .yay-dokan-request-refund-overlay', function (event) {
                    event.preventDefault();
                    if ($('.yay-dokan-request-refund-wrapper').length > 0) {
                        $('.yay-dokan-request-refund-wrapper').removeClass('yay-dokan-show-alert');
                    }
                    return;
                });

            }

        }

        self.customDokanOrderDetails = function () {
            const order_items_list = yay_dokan_data.dokan_pro ? $('#order_line_items') : $('#order_items_list'),
                line_items = order_items_list.find('.item');
            $.ajax({
                url: yay_dokan_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'yay_dokan_custom_order_details',
                    order_id: yay_dokan_data.order_id,
                    seller_id: yay_dokan_data.seller_id,
                    _nonce: yay_dokan_data.nonce,
                },
                beforeSend: function (res) {
                    $('#woocommerce-order-items').css('opacity', 0.4)
                },
                success: function success(res) {
                    $('#woocommerce-order-items').css('opacity', 1)
                    if (res.success && res.data.details) {
                        const details = JSON.parse(res.data.details);
                        line_items.each(function (index) {
                            const order_item_id = $(this).data('order_item_id');
                            $(this).find('.item_cost').html(details[order_item_id].item_cost_html);
                            if (yay_dokan_data.dokan_pro) {
                                $(this).find('.line_cost .view').html(details[order_item_id].line_cost_html);
                                $(this).find('.line_tax .view').html(details[order_item_id].line_tax_html);
                            } else {
                                $(this).find('.line_cost').html(details[order_item_id].line_cost_html);
                                $(this).find('.line_tax').html(details[order_item_id].line_tax_html);
                            }

                        });
                        if (yay_dokan_data.dokan_pro) {

                            // SHIPPING SECTION
                            if (res.data.shipping_totals) {
                                $('#order_shipping_line_items').find('.shipping ').each(function (index) {
                                    const order_item_id = $(this).data('order_item_id');
                                    $(this).find('.line_cost .view').html(res.data.shipping_totals[order_item_id].line_cost);
                                    $(this).find('.line_tax .view').html(res.data.shipping_totals[order_item_id].line_tax);
                                });
                            }

                            // FEE SECTION
                            if (res.data.fee_totals) {

                                $('#order_fee_line_items').find('.fee').each(function (index) {
                                    const order_item_id = $(this).data('order_item_id');
                                    $(this).find('.line_cost .view').html(res.data.fee_totals[order_item_id].line_cost);
                                    $(this).find('.line_tax .view').html(res.data.fee_totals[order_item_id].line_tax);
                                });

                            }

                            $('table.wc-order-totals').find('.total').each(function (index) {
                                $(this).html(res.data.order_totals[index]);
                            });

                            // REFUND SECTION
                            if (res.data.refund_totals) {
                                $('#order_refunds').find('.refund').each(function (index) {
                                    const order_refund_id = $(this).data('order_refund_id');
                                    $(this).find('.line_cost .view').html(res.data.refund_totals[order_refund_id].line_cost);
                                });
                                $('.total.refunded-total').html(res.data.order_total_refunded);
                            }

                        }

                        // Allow refund or no
                        if (!res.data.allow_refund) {
                            if ($('p.add-items button.refund-items').length > 0) {
                                $('p.add-items button.refund-items').addClass('yay-currency-alert').removeClass('refund-items').attr('data-message-alert', res.data.yay_currency_alert_refund);
                            }
                        }
                    }

                    if (res.success && res.data.fee_details) {
                        const fee_details = JSON.parse(res.data.fee_details),
                            fee_line_cost_total = order_items_list.find('.fee .line_cost'),
                            fee_line_tax_total = order_items_list.find('.fee .line_tax');
                        if (fee_line_cost_total.length > 0) {
                            fee_line_cost_total.each(function (index) {
                                const order_item_id = $(this).closest('tr.fee').data('order_item_id'),
                                    input_line_total = $(this).find('input[name="line_total[' + order_item_id + ']"]');
                                input_line_total.val(fee_details[order_item_id].line_cost_total);

                            });
                        }

                        if (fee_line_tax_total.length > 0) {
                            fee_line_tax_total.each(function (index) {
                                const order_item_id = $(this).closest('tr.fee').data('order_item_id'),
                                    input_line_tax = $(this).find('input[name="line_tax[' + order_item_id + ']"]');
                                input_line_tax.val(fee_details[order_item_id].line_tax_total);

                            });
                        }


                    }

                },

            });
        }

        self.customWithDrawArea = function () {
            if (yay_dokan_data.last_payment_details) {
                const withdrawArea = $('.dokan-panel.dokan-panel-default .dokan-w8');
                withdrawArea.each(function (index) {
                    if (1 == index) {
                        $(this).find('p').html(yay_dokan_data.last_payment_details);
                    }
                });

            }

        }

        self.addNewProductAction = function () {
            $(document).on('click', '.dokan-add-new-product', function () {
                self.customProductArea('add-product');
            });
        }

        self.customProductArea = function (_action = 'edit-product') {
            // SIMPLE
            const regularPrice = $(self.regularPrice),
                salePrice = $(self.salePrice);
            if (regularPrice.length > 0 && yay_dokan_data.yay_dokan_regular_price) {
                const _parent = regularPrice.closest('.dokan-input-group'),
                    _regularPriceParent = 'edit-product' === _action ? '.regular-price' : '.content-half-part',
                    regularPriceArea = _parent.closest(_regularPriceParent);
                regularPriceArea.append(yay_dokan_data.yay_dokan_regular_price);

                $(document).on('input', self.regularPrice, function (event) {
                    event.preventDefault();
                    const priceVal = $(this).val();
                    self.customApproximatelyPrice(priceVal, $('.yay-dokan-regular-price-wrapper'));
                });

                $(self.regularPrice).trigger('input');

            }

            if (salePrice.length > 0 && yay_dokan_data.yay_dokan_sale_price) {
                const _parent = salePrice.closest('.dokan-input-group'),
                    salePriceArea = _parent.closest('.sale-price');
                salePriceArea.append(yay_dokan_data.yay_dokan_sale_price);

                $(document).on('input', self.salePrice, function (event) {
                    event.preventDefault();
                    const priceVal = $(this).val();
                    self.customApproximatelyPrice(priceVal, $('.yay-dokan-sale-price-wrapper'));
                });

                $(self.salePrice).trigger('input');
            }

        }

        self.customCouponsArea = function (_action = 'edit-coupon') {
            if (yay_dokan_data.default_symbol) {
                $('label[for="amount"]').append(' (' + yay_dokan_data.default_symbol + ')');
            }

            const couponAmount = '#coupon_amount';
            $(couponAmount).closest('.dokan-w5').append(yay_dokan_data.yay_dokan_coupon_amount)
            $(document).on('input', couponAmount, function (event) {
                event.preventDefault();
                const priceVal = $(this).val();
                self.customApproximatelyPrice(priceVal, $('.yay-dokan-coupon-amount-wrapper'));
            });
            $(couponAmount).trigger('input');
        }

        self.customApproximatelyPrice = function (_price, elem) {
            $.ajax({
                url: yay_dokan_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'yay_dokan_custom_approximately_price',
                    _price: _price,
                    _nonce: yay_dokan_data.nonce,
                },
                beforeSend: function (res) {
                },
                success: function success(res) {
                    res.success ? elem.html(res.data.price_html) : elem.html('');
                }
            });
        }

        self.customReportStatementArea = function () {
            if (yay_dokan_data.yay_dokan_report_statement_page) {
                $.ajax({
                    url: yay_dokan_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'yay_dokan_custom_reports_statement',
                        seller_id: yay_dokan_data.seller_id,
                        start_date: yay_dokan_data.yay_dokan_report_statement_from,
                        end_date: yay_dokan_data.yay_dokan_report_statement_to,
                        opening_balance: yay_dokan_data.yay_dokan_report_statement_opening_balance,
                        _nonce: yay_dokan_data.nonce,
                    },
                    beforeSend: function (res) {
                        $('.dokan-report-wrap').css('opacity', 0.4)
                    },
                    success: function success(res) {
                        $('.dokan-report-wrap').css('opacity', 1)
                        if (res.success && res.data.statements) {
                            const
                                ReportsStatementTable = $('.dokan-report-wrap table.table-striped tbody tr'),
                                totalReports = $('.dokan-report-wrap table.table-striped tbody tr:last td'),
                                length = ReportsStatementTable.length;

                            ReportsStatementTable.each(function (index) {
                                const rows = $(this).find('td');
                                if ('yes' === yay_dokan_data.yay_dokan_report_statement_opening_balance) {
                                    if (0 === index) {
                                        return;
                                    }
                                }
                                if ((length - 1) === index) {
                                    return;
                                }

                                $(rows[4]).html(res.data.statements[index].debit)
                                $(rows[5]).html(res.data.statements[index].credit)
                                $(rows[6]).html(res.data.total_balance)
                            });

                            $(totalReports[4]).find('b').html(res.data.total_debit)
                            $(totalReports[5]).find('b').html(res.data.total_credit)
                            $(totalReports[6]).find('b').html(res.data.total_balance)
                        }
                    },

                });
            }
        }

        self.getValueinParam = function (param, url_string) {
            const url = new URL(url_string);
            return url.searchParams.get(param);
        }

    };

    jQuery(document).ready(function ($) {
        var yayDokanFr = new yayDokanScript();
        yayDokanFr.init();
    });
})(jQuery);