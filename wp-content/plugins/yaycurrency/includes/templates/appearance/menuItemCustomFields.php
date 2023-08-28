<?php
defined( 'ABSPATH' ) || exit;
$is_show_flag_in_menu_item            = get_option( 'yay_currency_show_flag_in_menu_item', 1 );
$is_show_currency_name_in_menu_item   = get_option( 'yay_currency_show_currency_name_in_menu_item', 1 );
$is_show_currency_symbol_in_menu_item = get_option( 'yay_currency_show_currency_symbol_in_menu_item', 1 );
$is_show_currency_code_in_menu_item   = get_option( 'yay_currency_show_currency_code_in_menu_item', 1 );
$menu_item_size                       = get_option( 'yay_currency_menu_item_size', 'small' );
?>
<div class="yay-currency-menu-item-custom-fields">
	<span class="yay-currency-menu-item-custom-fields__title">Switcher elements:</span>
	<div class="yay-currency-menu-item-custom-fields__field">
		<input class="yay-currency-menu-item-custom-fields__field--checkbox" type="checkbox" id="show-flag" name="show-flag" value="1" <?php echo $is_show_flag_in_menu_item ? 'checked' : null; ?> />
		<label for="show-flag">Show flag</label>
	</div>
	<div class="yay-currency-menu-item-custom-fields__field">
		<input class="yay-currency-menu-item-custom-fields__field--checkbox" type="checkbox" id="show-currency-name" name="show-currency-name" value="1" <?php echo $is_show_currency_name_in_menu_item ? 'checked' : null; ?> />
		<label for="show-currency-name">Show currency name</label>
	</div>
	<div class="yay-currency-menu-item-custom-fields__field">
		<input class="yay-currency-menu-item-custom-fields__field--checkbox" type="checkbox" id="show-currency-symbol" name="show-currency-symbol" value="1" <?php echo $is_show_currency_symbol_in_menu_item ? 'checked' : null; ?> />
		<label for="show-currency-symbol">Show currency symbol</label>
	</div>
	<div class="yay-currency-menu-item-custom-fields__field">
		<input class="yay-currency-menu-item-custom-fields__field--checkbox" type="checkbox" id="show-currency-code" name="show-currency-code" value="1" <?php echo $is_show_currency_code_in_menu_item ? 'checked' : null; ?> />
		<label for="show-currency-code">Show currency code</label>
	</div>
	<div class="yay-currency-menu-item-custom-fields">
		<span class="yay-currency-menu-item-custom-fields__title">Switcher size:</span>
		<div class="yay-currency-menu-item-custom-field__field-group">
			<div class="yay-currency-menu-item-custom-field__field">
				<input class="yay-currency-menu-item-custom-fields__field--radio" type="radio" id="menu-item-size-small" name="menu-item-size" value="small" <?php echo 'small' === $menu_item_size ? 'checked' : null; ?> />
				<label for="menu-item-size">Small</label>
			</div>
			<div class="yay-currency-menu-item-custom-field__field">
				<input class="yay-currency-menu-item-custom-fields__field--radio" type="radio" id="menu-item-size-medium" name="menu-item-size" value="medium" <?php echo 'medium' === $menu_item_size ? 'checked' : null; ?> />
				<label for="menu-item-size">Medium</label>
			</div>
		</div>
	</div>
</div>
