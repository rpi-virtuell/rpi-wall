jQuery(document).ready($ => {

    $('.rpi-wall-like-button').each((i, btn) => {
        const id = btn.id.replace(/[^\d]*/, '');
        console.log(id);
        $(btn).on('click', e => {
            $.post(
                wall.ajaxurl,
                {
                    'action': 'rpi_wall_toggle_like',
                    'group_id': id
                },
                function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        if (data.is_member) {
                            jQuery('#like-group-' + id + ' .rpi-wall-like-button').html(wallIcons.group);
                        } else {
                            if (data.is_liker) {
                                jQuery('#like-group-' + id + ' .rpi-wall-like-button').html(wallIcons.group_sub);
                            } else {
                                jQuery('#like-group-' + id + ' .rpi-wall-like-button').html(wallIcons.group_add);
                            }
                        }
                        jQuery('#like-group-' + id + ' .rpi-wall-counter').html(data.amount);
                        jQuery('#like-group-' + id + ' .rpi-wall-group-likers').html(data.likers);
                        jQuery('#like-group-' + id + ' .rpi-wall-group-members').html(data.members);
                    }

                }
            )
        });
    })

    site_match = location.pathname.match(/^\/member\//);
    if (match) {

        $('#messages').ready(function () {
            tab_match = location.search.match(/tab=([\w-]+)/)
            if (tab_match) {
                tab = tab_match[1];
            }
            if (tab === 'messages') {
                $.post(
                    wall.ajaxurl,
                    {
                        'action': 'rpi_post_user_messages',
                        'paged': 1
                    },
                    rpi_wall_print_messages
                )
            }

        })


        $('label[for="tab-messages"]').on('click', e => {
            location.search = '?tab=messages'
        })
    }

    function rpi_wall_print_messages(response) {
        $('#user-messages').html(response);
        mark_message_as_read();
        $('a.page-numbers').each(function (i, elem) {
            const href = $(elem).attr('href');
            if (typeof href != 'undefined') {
                //link zerstören
                const match = href.match(/paged=(\d*)/);
                const page = match ? match[1] : 1;
                const data = {
                    'action': 'rpi_post_user_messages',
                    'paged': page
                };
                $(elem).attr('href', '#page_' + page);
                $(elem).unbind();

                $(elem).on('click', e => {
                    $.post(
                        wall.ajaxurl,
                        data,
                        rpi_wall_print_messages
                    )
                })
            }
        })
    }


function mark_message_as_read() {

    $('.message').each((i, msg) => {
        console.log(msg);
        const id = msg.id.replace('message-', '');
        $(msg).on('click', e => {
            console.log(id);
            $.post(
                wall.ajaxurl,
                {
                    'action': 'rpi_toggle_message_read',
                    'message_id': id
                },
                function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $(msg).find('.entry-title').removeClass('unread')
                    }
                }
            )
        });
    })
}


    $('.rpi-wall-watch-button').each((i, btn) => {
        const id = btn.id.replace(/[^\d]*/, '');
        console.log(id);
        $(btn).on('click', e => {
            $.post(
                wall.ajaxurl,
                {
                    'action': 'rpi_wall_toggle_watch',
                    'group_id': id
                },
                function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        if (data.is_watcher) {
                            jQuery('#watch-group-' + id + ' .rpi-wall-watch-button').html(wallIcons.pin);
                        } else {
                            jQuery('#watch-group-' + id + ' .rpi-wall-watch-button').html(wallIcons.watch);

                        }
                        jQuery('#like-group-' + id + ' .rpi-wall-counter').html(data.amount);
                    }

                }
            )
        });
    })
})
