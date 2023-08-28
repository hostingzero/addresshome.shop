<?php
//Add Custom scripts in Admin
function wmi_custom_script(){
	wp_enqueue_script( 'wmi-admin-script', WMI_MENU_IMG_URL . '/assets/js/wmi-admin-script.js' );
	wp_localize_script( 'wmi-admin-script', 'deleteimg_ajax', array( 'ajax_url' => admin_url('admin-ajax.php')) );
	wp_localize_script( 'wmi-admin-script', 'editimg', WMI_MENU_IMG_URL . 'assets/images/edit-icon.svg' );
	wp_localize_script( 'wmi-admin-script', 'deleteimg', WMI_MENU_IMG_URL . 'assets/images/delete-icon.svg' );
	wp_enqueue_style( 'wmi-admin-style', WMI_MENU_IMG_URL . '/assets/css/wmi-style.css' );
	if (is_admin ()){
	    wp_enqueue_media ();
	}
}
add_action( 'admin_enqueue_scripts', 'wmi_custom_script' );

//Add Custom scripts in Admin
function wmi_custom_style(){
	wp_enqueue_style( 'wmi-front-style', WMI_MENU_IMG_URL . '/assets/css/wmi-front-style.css' );
}
add_action( 'wp_enqueue_scripts', 'wmi_custom_style' );

//Update menu custom field
add_action( 'wp_update_nav_menu_item', 'wmi_update_custom_img_field', 10, 3 );
function wmi_update_custom_img_field( $menu_id, $menu_item_db_id, $args ) {
	// Verify this came from our screen and with proper authorization.
	if ( ! isset( $_POST['_menu_list_image_nonce_name'] ) || ! wp_verify_nonce( $_POST['_menu_list_image_nonce_name'], 'menu_list_image_nonce' ) ) {
		return $menu_id;
	}
    if ( is_array($_REQUEST['menu-item-image']) || is_array($_REQUEST['menu-item-img-position']) ) {
        $image_value = sanitize_text_field($_REQUEST['menu-item-image'][$menu_item_db_id]);
        $image_position = sanitize_text_field($_REQUEST['menu-item-img-position'][$menu_item_db_id]);
		if ( $image_value ) {
        	update_post_meta( $menu_item_db_id, '_menu_list_image', $image_value );
        	update_post_meta( $menu_item_db_id, '_menu_list_image_position', $image_position );
        }
    }
}

//Add menu custom field
function wmi_customfield_menu_image( $item_id, $item ) {
	wp_nonce_field( 'menu_list_image_nonce', '_menu_list_image_nonce_name' );
	$menu_image = get_post_meta( $item_id, '_menu_list_image', true );
	$menu_image_position = get_post_meta( $item_id, '_menu_list_image_position', true );
	?>
	<div class="field-custom_menu_meta" style="margin: 5px 0;">
		<div class="menu-img">
		    <p><?php _e( 'Image', 'wmi-menu-img' ); ?></p>
			<?php if($menu_image){ ?>
				<div class="menu-img-block menu-block-<?php echo $item_id; ?>">
					<ul class="menu-actions">
						<li><a href="javascript:void(0);" class="edit-btn" id="upload-image-<?php echo $item_id; ?>"  data-id="<?php echo $item_id; ?>"><img src="<?php echo WMI_MENU_IMG_URL . 'assets/images/edit-icon.svg'; ?>" alt="edit"></a></li>
						<li><a href="javascript:void(0);" class="close-btn" data-id="<?php echo $item_id; ?>"><img src="<?php echo WMI_MENU_IMG_URL . 'assets/images/delete-icon.svg'; ?>" alt="delete"></a></li>
					</ul>
				    <img class="menu-image upload-image-<?php echo $item_id; ?>" src="<?php echo $menu_image; ?>" width="120" height="120">
				</div>
			<?php } ?>
			<input class="widefat custom_media_url img_txt-<?php echo $item_id; ?>" id="edit-menu-item-image-<?php echo $item_id; ?>" name="menu-item-image[<?php echo $item_id; ?>]" type="hidden" value="<?php echo $menu_image; ?>">
			<input type="button" class="button upload-image widefat" data-id="<?php echo $item_id; ?>" id="upload-image-<?php echo $item_id; ?>" value="Upload Image" style="margin-top:5px;" />
		</div>
		<div class="img-position" style="margin: 5px 0;">
			<p><?php _e( 'Image Position', 'wmi-menu-img' ); ?></p>
			<label>
				<input type="radio" name="menu-item-img-position[<?php echo $item_id; ?>]" value="before" <?php if($menu_image_position == 'before'){ ?>checked <?php }else{ echo 'checked'; } ?>>
				<span><?php _e( 'Before', 'wmi-menu-img' ); ?></span>
			</label>
			<label>
				<input type="radio" name="menu-item-img-position[<?php echo $item_id; ?>]" value="after" <?php if($menu_image_position == 'after'){ echo 'checked'; } ?>>
				<span><?php _e( 'After', 'wmi-menu-img' ); ?></span>
			</label>
		</div>
	</div>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'wmi_customfield_menu_image', 10, 2 );

//Display Image in Menu
function wmi_display_img_menu( $title, $item, $args, $depth ) {
	$menu_image = get_post_meta( $item->ID, '_menu_list_image', true );
	$menu_image_pos = get_post_meta( $item->ID, '_menu_list_image_position', true );
	if($menu_image){
		if ($menu_image_pos == 'after') {
			$title = '<span>'.$title.'</span><img src="'.$menu_image.'" heigth="25px" width="25px">';
		}else{
    		$title = '<img src="'.$menu_image.'" heigth="25px" width="25px"><span>'.$title.'</span>';
    	}
    }
    return $title;
}
add_filter( 'nav_menu_item_title', 'wmi_display_img_menu', 10, 4 );

//Add class in menu items
add_filter('nav_menu_css_class' , 'wmi_add_custom_class' , 10 , 2);
function wmi_add_custom_class($classes, $item){
	$menu_image_pos = get_post_meta( $item->ID, '_menu_list_image_position', true );
	$classes[] = 'wp-menu-img';
	if($menu_image_pos == 'after'){
		$classes[] = 'wp-menu-img-after';	
	}else{
    	$classes[] = 'wp-menu-img-before';
    }
    return $classes;
}

//Delete Image in Menu
add_action('wp_ajax_del_img', 'wmi_delete_img_menu');
add_action('wp_ajax_nopriv_del_img', 'wmi_delete_img_menu');
function wmi_delete_img_menu(){
	delete_post_meta( sanitize_text_field($_POST['menu_id']), '_menu_list_image' );
	update_post_meta( sanitize_text_field($_POST['menu_id']), '_menu_list_image_position', 'before' );
	exit;
}