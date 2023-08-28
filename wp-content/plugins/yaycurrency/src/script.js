'use strict';
(function ($) {
  const get_cookie = (cname) => {
    let name = cname + '=';
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return '';
  };
  const currencyID = get_cookie('yay_currency_widget');
  let applyCurrency = null;
  if (window.yayCurrency.converted_currency) {
    window.yayCurrency.converted_currency.forEach((currency) => {
      if (currency.ID == currencyID) {
        applyCurrency = currency;
      }
    });
    if (window.wc_price_calculator_params) {
      let rate_after_fee = parseFloat(applyCurrency.rate);
      if ('percentage' === applyCurrency.fee.type) {
        rate_after_fee =
          parseFloat(applyCurrency.rate) +
          parseFloat(applyCurrency.rate) *
            (parseFloat(applyCurrency.fee.value) / 100);
      } else {
        rate_after_fee =
          parseFloat(applyCurrency.rate) + parseFloat(applyCurrency.fee.value);
      }
      window.wc_price_calculator_params.woocommerce_currency_pos =
        applyCurrency.currencyPosition;
      window.wc_price_calculator_params.woocommerce_price_decimal_sep =
        applyCurrency.decimalSeparator;
      window.wc_price_calculator_params.woocommerce_price_num_decimals =
        applyCurrency.numberDecimal;
      window.wc_price_calculator_params.woocommerce_price_thousand_sep =
        applyCurrency.thousandSeparator;

      window.wc_price_calculator_params.pricing_rules &&
        window.wc_price_calculator_params.pricing_rules.forEach((rule) => {
          rule.price = (parseFloat(rule.price) * rate_after_fee).toString();
          rule.regular_price = (
            parseFloat(rule.regular_price) * rate_after_fee
          ).toString();
          rule.sale_price = (
            parseFloat(rule.sale_price) * rate_after_fee
          ).toString();
        });
    }
  }

  const yay_currency = () => {
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
  };

  jQuery(document).ready(function ($) {
    yay_currency($);
    const { yayCurrency } = window;

    $(document.body).on('update_checkout', function (e) {
      e.stopImmediatePropagation();
    });

    $(document.body).trigger('wc_fragment_refresh');

    if (
      typeof wc_cart_fragments_params === 'undefined' ||
      wc_cart_fragments_params === null
    ) {
    } else {
      sessionStorage.removeItem(wc_cart_fragments_params.fragment_name);
    }

    const switcherUpwards = function () {
      const allSwitcher = $('.yay-currency-single-page-switcher');

      allSwitcher.each(function () {
        const SWITCHER_LIST_HEIGT = 250;

        const offsetTop =
          $(this).offset().top + $(this).height() - $(window).scrollTop();
        const offsetBottom =
          $(window).height() -
          $(this).height() -
          $(this).offset().top +
          $(window).scrollTop();

        if (
          offsetBottom < SWITCHER_LIST_HEIGT &&
          offsetTop > SWITCHER_LIST_HEIGT
        ) {
          $(this).find('.yay-currency-custom-options').addClass('upwards');
          $(this).find('.yay-currency-custom-arrow').addClass('upwards');
          $(this)
            .find('.yay-currency-custom-select__trigger')
            .addClass('upwards');
        } else {
          $(this).find('.yay-currency-custom-options').removeClass('upwards');
          $(this).find('.yay-currency-custom-arrow').removeClass('upwards');
          $(this)
            .find('.yay-currency-custom-select__trigger')
            .removeClass('upwards');
        }
      });
    };
    $(window).on('load resize scroll', switcherUpwards);

    $(document).on('click', '.yay-currency-custom-select-wrapper', function () {
      $('.yay-currency-custom-select', this).toggleClass('open');
      $('#slide-out-widget-area')
        .find('.yay-currency-custom-options')
        .toggleClass('overflow-fix');
      $('[id^=footer]').toggleClass('z-index-fix');
      $('.yay-currency-custom-select', this)
        .parents('.handheld-navigation')
        .toggleClass('overflow-fix');
    });

    $(document).on('click', '.yay-currency-custom-option-row', function () {
      const currencyID = $(this).data('value');
      const countryCode = $(this)
        .children('.yay-currency-flag')
        .data('country_code');
      $('.yay-currency-switcher').val(currencyID).change();

      if (!$(this).hasClass('selected')) {
        const clickedSwitcher = $(this).closest('.yay-currency-custom-select');

        $(this)
          .parent()
          .find('.yay-currency-custom-option-row.selected')
          .removeClass('selected');

        $(this).addClass('selected');

        clickedSwitcher.find('.yay-currency-flag.selected').css({
          background: `url(${yayCurrency.yayCurrencyPluginURL}assets/dist/flags/${countryCode}.svg)`,
        });

        clickedSwitcher
          .find(
            '.yay-currency-custom-select__trigger .yay-currency-selected-option'
          )
          .text($(this).text());

        clickedSwitcher.find('.yay-currency-custom-loader').addClass('active');

        clickedSwitcher.find('.yay-currency-custom-arrow').hide();
      }
    });

    window.addEventListener('click', function (e) {
      const selects = document.querySelectorAll('.yay-currency-custom-select');
      selects.forEach((select) => {
        if (!select.contains(e.target)) {
          select.classList.remove('open');
        }
      });
    });
  });
})(jQuery);
