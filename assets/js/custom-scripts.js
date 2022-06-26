jQuery(document).ready($=>{

    $('.rpi-wall-like-button').each((i,btn)=>{
        const id = btn.id.replace(/[^\d]*/,'');
        console.log(id);
        $(btn).on('click',e=>{
            $.post(
                wall.ajaxurl,
                {
                    'action': 'rpi_wall_toggle_like',
                    'group_id': id
                },
                function (response) {
                    const data = JSON.parse(response);
                    if(data.success){
                        if(data.is_member){
                            jQuery('#like-group-'+id+' .rpi-wall-like-button').html(wallIcons.group);
                        }else{
                            if(data.is_liker){
                                jQuery('#like-group-'+id+' .rpi-wall-like-button').html(wallIcons.group_sub);
                            }else{
                                jQuery('#like-group-'+id+' .rpi-wall-like-button').html(wallIcons.group_add);
                            }
                        }
                        jQuery('#like-group-'+id+' .rpi-wall-counter').html(data.amount);
                        jQuery('#like-group-'+id+' .rpi-wall-group-likers').html(data.likers);
                        jQuery('#like-group-'+id+' .rpi-wall-group-members').html(data.members);
                    }

                }
            )
        });



    })
})
