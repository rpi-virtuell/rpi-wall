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

    $('#messages').ready(function (){
      $.get(
          wall.ajaxurl,
          {
              'action': 'rpi_post_user_messages',
              'paged' : 1
          },
          rpi_wall_print_messages
      )
    })


    function rpi_wall_print_messages(response){
        $('#user-messages').html(response);
        mark_message_as_read();
        $('a.page-numbers').each(function (i, elem){
            const href = $(elem).attr('href');
            console.log(href);
            if (typeof href != 'undefined')
            {
                //link zerstören
                $(elem).attr('href', '#');

                match = href.match(/paged=(\d*)&/);
                if (match)
                {
                    page =match[1]
                }
                else{
                    page=1
                }
                $(elem).on('click', e=>{
                    $.get(
                        wall.ajaxurl,
                        {
                            'action': 'rpi_post_user_messages',
                            'paged' : page,
                        },
                        rpi_wall_print_messages

                    )
                })
            }
        })
    }

    function mark_message_as_read(){

        $('.message').each((i,msg)=>{
            console.log(msg);
            const id = msg.id.replace('message-','');
            $(msg).on('click',e=>{
                console.log(id);
                $.post(
                    wall.ajaxurl,
                    {
                        'action': 'rpi_toggle_message_read',
                        'message_id': id
                    },
                    function (response) {
                        const data = JSON.parse(response);
                        if(data.success){
                           $(msg).find('.entry-title').removeClass('unread')
                        }
                    }
                )
            });
        })
    }

})
