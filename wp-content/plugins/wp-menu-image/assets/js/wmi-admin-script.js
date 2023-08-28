jQuery(document).ready( function($) {
    function wmi_media_upload(button_class) {
        var _custom_media = true,
        _orig_send_attachment = wp.media.editor.send.attachment;
        jQuery('body').on('click', button_class, function(e) {
            var button_id ='#'+jQuery(this).attr('id');
            var self = jQuery(button_id);
            var send_attachment_bkp = wp.media.editor.send.attachment;
            var button = jQuery(button_id);
            var menu_id = jQuery(this).attr('data-id');
            _custom_media = true;
            wp.media.editor.send.attachment = function(props, attachment){         
                if ( _custom_media  ) {
                    jQuery('.content').show();
                    if(jQuery('.menu-block-'+menu_id).length > 0){
                        jQuery('.upload-image-' + menu_id).remove();
                        jQuery('.menu-block-'+menu_id).append('<img class="menu-image upload-image-'+menu_id+'" src="'+attachment.url+'" width="90" height="90">');    
                    }else{
                        jQuery('#upload-image-'+ menu_id).before('<div class="menu-img-block menu-block-'+menu_id+'"><ul class="menu-actions"><li><a href="javascript:void(0);" class="edit-btn" id="upload-image-'+menu_id+' data-id="'+menu_id+'"><img src="'+editimg+'" alt="edit"></a></li> <li><a href="javascript:void(0);" class="close-btn"><img src="'+deleteimg+'" alt="delete"></li></ul> <img class="menu-image upload-image-'+menu_id+'" src="'+attachment.url+'" width="120" height="120"></div>');
                    }
                    jQuery('div.menu-img-block').css('display','block');
                    jQuery('.img_txt-' + menu_id).val(attachment.url);
                    jQuery('.custom_media_url').trigger("change");
                }else {
                    return _orig_send_attachment.apply( button_id, [props, attachment] );
                }
            }
            wp.media.editor.open(button);
                return false;
        });
    }
    wmi_media_upload('.upload-image');
    wmi_media_upload('.edit-btn');

    jQuery('.close-btn').on('click', function(e){
        e.preventDefault();
        // jQuery(this).parents('div.menu-img-block').empty();
        jQuery(this).parent('div.menu-actions').siblings('.menu-image').remove();
        jQuery(this).parents('div.menu-img-block').css('display','none');
        var menu_id = jQuery(this).attr('data-id');
        jQuery('.img_txt-' + menu_id).val('');
        jQuery.ajax({
            type : 'POST',
            url : deleteimg_ajax.ajax_url,
            data : { action : 'del_img', menu_id : menu_id },
            success : function(data){   
            }
        });
    });
});