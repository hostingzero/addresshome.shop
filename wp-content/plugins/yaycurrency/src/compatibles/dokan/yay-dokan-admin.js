(function ($) {
    'use strict';
    var yayDokanScriptAdmin = function () {
        var self = this;
        self.DokanPage = 'admin.php?page=dokan#/';
        self.DokanLiteArgs = {
            dashboard: self.DokanPage,
            withdraw: self.DokanPage + 'withdraw?status=pending',
            reverseWithdrawal: self.DokanPage + 'reverse-withdrawal',
            vendors: self.DokanPage + 'vendors'
        };
        if (yay_dokan_admin_data.dokan_pro) {
            self.DokanLiteArgs.reports = self.DokanPage + 'reports';
            self.DokanLiteArgs.reportsByDay = self.DokanPage + 'reports?tab=report&type=by-day';
            self.DokanLiteArgs.reportsByVendor = self.DokanPage + 'reports?tab=report&type=by-vendor';
            self.DokanLiteArgs.reportsByYear = self.DokanPage + 'reports?tab=report&type=by-year';
            self.DokanLiteArgs.refundPending = self.DokanPage + 'refund?status=pending';
            self.DokanLiteArgs.refundApproved = self.DokanPage + 'refund?status=completed';
            self.DokanLiteArgs.refundCancelled = self.DokanPage + 'refund?status=cancelled';
        }
        self.atAGalanceAreaDashBoard = '.postbox.dokan-postbox.dokan-status';
        self.atAGalanceSaleAreaDashBoard = self.atAGalanceAreaDashBoard + ' li.sale a';
        self.atAGalanceCommissionAreaDashBoard = self.atAGalanceAreaDashBoard + ' li.commission a';
        self.atAGalanceOverviewChartAreaDashBoard = '.dokan-dashboard .overview-chart';
        self.atAGalanceYayDokanAlertChartAreaDashBoard = '.yay-currency-dokan-modal-alert';
        self.yayDokanAlertArea = '<div class="yay-currency-dokan-modal-alert"><div class="yay-currency-dokan-modal-alert-wrapper"><div class="yay-currency-dokan-locked-modal"><svg width="32" height="32" xmlns="http://www.w3.org/2000/svg"><path d="M16 21.915v2.594a.5.5 0 0 0 1 0v-2.594a1.5 1.5 0 1 0-1 0zM9 14v-3.5a7.5 7.5 0 1 1 15 0V14c1.66.005 3 1.35 3 3.01v9.98A3.002 3.002 0 0 1 23.991 30H9.01A3.008 3.008 0 0 1 6 26.99v-9.98A3.002 3.002 0 0 1 9 14zm3 0v-3.5C12 8.01 14.015 6 16.5 6c2.48 0 4.5 2.015 4.5 4.5V14h-9z" fill="#c2cdd4" fill-rule="evenodd"></path></svg><p>YayCurrency is not support for this feature</p></div></div></div>';
        self.refundWrapperArea = '.dokan-refund-wrapper';
        self.refundArrays = ['refundPending', 'refundApproved', 'refundCancelled'];

        self.init = function () {
            // Init Load
            const currentUrl = window.location.href,
                getUrl = currentUrl.replace(yay_dokan_admin_data.admin_url, ''),
                currentPage = self.getKeyByValue(self.DokanLiteArgs, getUrl);

            self.YayDokanAction(currentPage);
            // Click Submenu
            $(document).on('click', '.wp-submenu.wp-submenu-wrap li', function (event) {
                const _this = $(this),
                    getCurrentUrl = _this.find('a').attr('href');
                const findKey = self.getKeyByValue(self.DokanLiteArgs, getCurrentUrl);
                self.YayDokanAction(findKey);
            });
            // Click Report Submenu --- Dokan Pro
            $(document).on('click', '.dokan-report-sub li', function (event) {
                const _this = $(this),
                    findUrl = self.DokanPage + _this.find('a').attr('href').replace('#/', ''),
                    findKeyInReport = self.getKeyByValue(self.DokanLiteArgs, findUrl);
                self.YayDoKanReports(findKeyInReport, true);
            });

            // Click Submenu Refunds
            $(document).on('click', self.refundWrapperArea + ' ul.subsubsub li', function (event) {
                const _this = $(this),
                    findUrl = self.DokanPage + _this.find('a').attr('href').replace('#/', ''),
                    findKeyInRefund = self.getKeyByValue(self.DokanLiteArgs, findUrl);
                self.YayDokanRefund(findKeyInRefund);
            });

        };

        self.YayDokanAction = function (findKey) {
            self.YayDoKanDashBoard(findKey);
            if (yay_dokan_admin_data.dokan_pro) {
                self.YayDoKanReports(findKey);
                self.YayDokanRefund(findKey);
            }
        }

        self.getKeyByValue = function (object, value) {
            return Object.keys(object).find(key => object[key] === value);
        };

        self.YayDoKanGetDataThisMonth = function () {
            $.ajax({
                url: yay_dokan_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'yay_dokan_admin_custom_dashboard',
                    _nonce: yay_dokan_admin_data.nonce,
                },
                beforeSend: function (res) {
                    $(self.atAGalanceAreaDashBoard).css('opacity', 0.2);
                },
                success: function success(res) {

                    if (res.success && res.data && res.data.report_data) {
                        self.customDokanAtAGalanceArea(res.data.report_data);
                    }

                }
            });
        }

        self.YayDoKanDashBoard = function (findKey) {
            if (findKey && 'dashboard' === findKey) {
                self.YayDoKanGetDataThisMonth();
            }
        }

        self.customDokanAtAGalanceArea = function (reportData, actionClick = false) {
            $(self.atAGalanceAreaDashBoard).css('opacity', 0.2);
            $(self.atAGalanceAreaDashBoard).css('opacity', 0.2);
            var intervalTime = setInterval(function () {
                if ($(self.atAGalanceSaleAreaDashBoard).length > 0) {
                    clearInterval(intervalTime);
                }

                $(self.atAGalanceAreaDashBoard).css('opacity', 1);
                if ($(self.atAGalanceSaleAreaDashBoard).length > 0) {
                    const salesThisMonth = actionClick ? reportData.sales.this_period : reportData.sales.this_month;
                    $(self.atAGalanceSaleAreaDashBoard).find('strong').html(salesThisMonth);
                    if ($(self.atAGalanceSaleAreaDashBoard).find('.up').length > 0) {
                        $(self.atAGalanceSaleAreaDashBoard).find('.up').html(reportData.sales.parcent);
                    }
                    if ($(self.atAGalanceSaleAreaDashBoard).find('.down').length > 0) {
                        $(self.atAGalanceSaleAreaDashBoard).find('.down').html(reportData.sales.parcent);
                    }
                }

                if ($(self.atAGalanceCommissionAreaDashBoard).length > 0) {
                    const earningThisMonth = actionClick ? reportData.earning.this_period : reportData.earning.this_month;
                    $(self.atAGalanceCommissionAreaDashBoard).find('strong').html(earningThisMonth);
                    if ($(self.atAGalanceCommissionAreaDashBoard).find('.up').length > 0) {
                        $(self.atAGalanceCommissionAreaDashBoard).find('.up').html(reportData.earning.parcent);
                    }
                    if ($(self.atAGalanceCommissionAreaDashBoard).find('.down').length > 0) {
                        $(self.atAGalanceCommissionAreaDashBoard).find('.down').html(reportData.earning.parcent);
                    }
                }

                if (!$(self.atAGalanceOverviewChartAreaDashBoard).find(self.atAGalanceYayDokanAlertChartAreaDashBoard).length) {
                    $(self.atAGalanceOverviewChartAreaDashBoard).append(self.yayDokanAlertArea);
                }
            }, 500);
        }
        // DOKAN PRO
        self.changeDokanAtAGalanceAreaHTML = function (findKey) {

            let inputData = [];
            switch (findKey) {
                case "reportsByYear":
                    const year = $(".form-inline.report-filter").find('select.dokan-input').val();
                    self.YayDoKanReportsByYear(year);
                    break;
                case 'reportsByVendor':
                    $(".form-inline.report-filter").find('.form-group input.dokan-input').each(function (index) {
                        inputData.push($(this).val());
                    });
                default:
                    $(".form-inline.report-filter").find('input.dokan-input').each(function (index) {
                        inputData.push($(this).val());
                    });
                    break;
            }
            const sellerId = $('.multiselect__option--selected').length > 0 ? $('.multiselect__option--selected').text() : '';

            if (inputData.length > 0) {
                $.ajax({
                    url: yay_dokan_admin_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'yay_dokan_admin_custom_reports',
                        from: inputData[0],
                        to: inputData[1],
                        seller_id: sellerId,
                        _nonce: yay_dokan_admin_data.nonce,
                    },
                    beforeSend: function (res) {
                        $(self.atAGalanceAreaDashBoard).css('opacity', 0.2);
                    },
                    success: function success(res) {

                        if (res.success && res.data && res.data.report_data) {
                            self.customDokanAtAGalanceArea(res.data.report_data, true);
                        }

                    }
                });
            }
        }

        self.YayDoKanReports = function (findKey, actionClick = false) {
            const data_reports = ['reports', 'reportsByDay', 'reportsByVendor', 'reportsByYear'];
            if (findKey && $.inArray(findKey, data_reports) != -1) {
                if (!actionClick) {
                    self.YayDoKanGetDataThisMonth();
                } else {
                    self.changeDokanAtAGalanceAreaHTML(findKey)
                }

                $(document).on('click', '.form-inline.report-filter button[type="submit"]', function (event) {
                    self.changeDokanAtAGalanceAreaHTML(findKey);
                });

            }
        }

        self.YayDoKanReportsByYear = function (year) {

            $.ajax({
                url: yay_dokan_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'yay_dokan_admin_reports_by_year',
                    _year: year,
                    _nonce: yay_dokan_admin_data.nonce,
                },
                beforeSend: function (res) {
                    $(self.atAGalanceAreaDashBoard).css('opacity', 0.2);
                },
                success: function success(res) {

                    if (res.success && res.data && res.data.report_data) {
                        self.customDokanAtAGalanceArea(res.data.report_data, true);
                    }

                }
            });
        }

        self.YayDokanRefund = function (findKey) {
            // 0 : pending, 1: approved, 2: cancel
            if (self.refundArrays.includes(findKey)) {

                var intervalTime = setInterval(function () {
                    if (!$('.table-loading .table-loader').length) {
                        clearInterval(intervalTime);
                    }
                    const listRowOrderIds = $('.dokan-refund-wrapper table.wp-list-table tbody tr td.order_id');
                    if (listRowOrderIds.length > 0) {
                        let orderIds = [];
                        listRowOrderIds.each(function (index) {
                            const orderId = $(this).find('a strong').text().replace('#', '');
                            $(this).closest('tr').find('td.amount').attr('data-order_id', orderId);
                            orderIds.push(orderId);
                        });
                        let refundStatus = 0;
                        if ('refundApproved' === findKey) {
                            refundStatus = 1;
                        } else if ('refundCancelled' === findKey) {
                            refundStatus = 2;
                        }

                        $.ajax({
                            url: yay_dokan_admin_data.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'yay_dokan_admin_custom_refund_request',
                                orderIds: orderIds,
                                status: refundStatus,
                                _nonce: yay_dokan_admin_data.nonce,
                            },
                            beforeSend: function (res) {
                                $(self.refundWrapperArea).css('opacity', 0.2);
                            },
                            success: function success(res) {
                                $(self.refundWrapperArea).css('opacity', 1);

                                if (res.success && res.data.refunds) {
                                    $('.dokan-refund-wrapper table.wp-list-table tbody tr td.amount').each(function (index) {
                                        $(this).html(res.data.refunds[$(this).data('order_id')]);
                                    });
                                }

                            }
                        });
                    }

                }, 500);

            }
        }

        self.getValueinParam = function (param, url_string) {
            const url = new URL(url_string);
            return url.searchParams.get(param);
        }
    };

    jQuery(document).ready(function ($) {
        var yayDokanAd = new yayDokanScriptAdmin();
        yayDokanAd.init();
    });
})(jQuery);