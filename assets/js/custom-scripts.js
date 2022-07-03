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

    if (site_match) {
        $('.tabset').ready(function () {
            tab_match = location.search.match(/tab=([\w-]+)/)
            if (tab_match) {
                tab = tab_match[1];
                action = "rpi_tab_" + tab + "_content";
            }else{
                action = "rpi_tab_bio_content";
            }

            rpi_wall_send_post(action);
        })

    }


    function rpi_wall_send_post(action) {
        $.post(
            wall.ajaxurl,
            {
                'action': action,
                'user_ID': rpi_wall.user_ID,
                'paged': 1
            },
            function (response) {
                rpi_wall_print_content(response, action)
            }
        )
    }

    function rpi_wall_print_content(response, action) {
        console.log(action);
        $('#' + action).html(response);
        if (action === 'rpi_tab_messages_content') {
            mark_message_as_read();
        }


        $('a.page-numbers').each(function (i, elem) {
            const href = $(elem).attr('href');
            if (typeof href != 'undefined') {
                //link zerstören
                const match = href.match(/paged=(\d*)/);
                const page = match ? match[1] : 1;
                const data = {
                    'action': action,
                    'user_ID': rpi_wall.user_ID,
                    'paged': page
                };
                $(elem).attr('href', '#page_' + page);
                $(elem).unbind();

                $(elem).on('click', e => {
                    $.post(
                        wall.ajaxurl,
                        data,
                        function (response) {
                            rpi_wall_print_content(response, action)
                        }
                    )
                })
            }
        })
    }


    function mark_message_as_read() {

        $('.message').each((i, msg) => {
            const id = msg.id.replace('message-', '');
            $(msg).on('click', e => {
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
                            jQuery('#btn-watch-group-' + id).html(wallIcons.watch);
                            jQuery('#btn-watch-group-' + id).addClass('watching');
                        } else {
                            jQuery('#btn-watch-group-' + id).html(wallIcons.unwatch);
                            jQuery('#btn-watch-group-' + id).removeClass('watching');

                        }
                        jQuery('#rpi-wall-counter-' + id).html(data.amount);
                    }

                }
            )
        });
    })
})
