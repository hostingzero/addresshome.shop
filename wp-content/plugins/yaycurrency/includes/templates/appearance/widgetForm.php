<?php
defined( 'ABSPATH' ) || exit;
$is_show_flag_in_widget            = get_option( 'yay_currency_show_flag_in_widget', 1 );
$is_show_currency_name_in_widget   = get_option( 'yay_currency_show_currency_name_in_widget', 1 );
$is_show_currency_symbol_in_widget = get_option( 'yay_currency_show_currency_symbol_in_widget', 1 );
$is_show_currency_code_in_widget   = get_option( 'yay_currency_show_currency_code_in_widget', 1 );
$widget_size                       = get_option( 'yay_currency_widget_size', 'small' );
?>
<div class="yay-currency-widget-custom-fields">
	<span class="yay-currency-widget-custom-fields__title">Switcher elements:</span>
	<div class="yay-currency-widget-custom-fields__field">
		<input class="yay-currency-widget-custom-fields__field--checkbox" type="checkbox" id="show-flag" name="show-flag" value="1" <?php echo $is_show_flag_in_widget ? 'checked' : null; ?> />
		<label for="show-flag">Show flag</label>
	</div>
	<div class="yay-currency-widget-custom-fields__field">
		<input class="yay-currency-widget-custom-fields__field--checkbox" type="checkbox" id="show-currency-name" name="show-currency-name" value="1" <?php echo $is_show_currency_name_in_widget ? 'checked' : null; ?> />
		<label for="show-currency-name">Show currency name</label>
	</div>
	<div class="yay-currency-widget-custom-fields__field">
		<input class="yay-currency-widget-custom-fields__field--checkbox" type="checkbox" id="show-currency-symbol" name="show-currency-symbol" value="1" <?php echo $is_show_currency_symbol_in_widget ? 'checked' : null; ?> />
		<label for="show-currency-symbol">Show currency symbol</label>
	</div>
	<div class="yay-currency-widget-custom-fields__field">
		<input class="yay-currency-widget-custom-fields__field--checkbox" type="checkbox" id="show-currency-code" name="show-currency-code" value="1" <?php echo $is_show_currency_code_in_widget ? 'checked' : null; ?> />
		<label for="show-currency-code">Show currency code</label>
	</div>
	<div class="yay-currency-widget-custom-fields">
		<span class="yay-currency-widget-custom-fields__title">Switcher size:</span>
		<div class="yay-currency-widget-custom-field__field-group">
			<div class="yay-currency-widget-custom-field__field">
				<input class="yay-currency-widget-custom-fields__field--radio" type="radio" id="widget-size-small" name="widget-size" value="small" <?php echo 'small' === $widget_size ? 'checked' : null; ?> />
				<label for="widget-size">Small</label>
			</div>
			<div class="yay-currency-widget-custom-field__field">
				<input class="yay-currency-widget-custom-fields__field--radio" type="radio" id="widget-size-medium" name="widget-size" value="medium" <?php echo 'medium' === $widget_size ? 'checked' : null; ?> />
				<label for="widget-size">Medium</label>
			</div>
		</div>
	</div>
</div>
