jQuery(document).ready($ => {

    const unpaginatedActions = [""];

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

    const site_match_member = location.pathname.match(/^\/member\//);
    const site_match_wall = location.pathname.match(/^\/wall\//);
    window.onhashchange = locationHashChanged;
    locationHashChanged();

    function locationHashChanged() {
        if (site_match_member) {
            $('.tabset').ready(function () {
                if (location.hash && rpi_wall.allowedtabs.includes(location.hash.substring(1))) {
                    hash = location.hash.substring(1);
                } else {
                    hash = "bio";
                }
                action = "rpi_tab_" + hash + "_content";
                $('#tab-' + hash).prop('checked', true);
                rpi_wall_send_post(action);
            })

        }else if(site_match_wall){
            $('.tabset').ready(function () {
                if (location.hash && rpi_wall.allowedtabs.includes(location.hash.substring(1))) {
                    hash = location.hash.substring(1);
                } else {
                    hash = "pin";
                }
                action = "rpi_tab_" + hash + "_content";
                $('#tab-' + hash).prop('checked', true);
                rpi_wall_send_post(action);
            })
        }
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
            mark_and_display_message();
            add_member_message_button_event();
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


    function mark_and_display_message() {

        $('.message').each((i, msg) => {
            const id = msg.id.replace('message-', '');
            $(msg).on('click', e => {
                $.post(
                    wall.ajaxurl,
                    {
                        'action': 'rpi_mark_and_display_message',
                        'message_id': id
                    },
                    function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $(msg).find('.entry-title').removeClass('unread')
                            if (data.content) {
                                $('#member-message-detail-title').html(data.title);
                                $('#member-message-detail-content').html(data.content);
                                $('.member-message-grid').addClass('message-detail');
                                $('.member-message-grid').removeClass('message-list');
                                $('#member-message-button').addClass('member-button-display');
                            }
                        }
                    }
                )
            });
        })
    }

    function add_member_message_button_event() {
        $('#member-message-button').ready($ => {
            $('#member-message-button').on('click', e => {
                $('.member-message-grid').removeClass('message-detail');
                $('.member-message-grid').addClass('message-list');
                $('#member-message-button').removeClass('member-button-display');
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

    /**
     * Modal Window open
     */
    //$("#btn-open-modal").animatedModal();
    /**
     * Modal Window schließen
     */
    $(document).click(function(event) {
        var $target = $(event.target);
        if($target.hasClass('zoomIn') ) {
            $('#btn-close-modal').click();
        }
    });

    $('#close-pin-group-button').on('click', e => {
        if (confirm("Soll die PLG wirklich geschlossen werden?"))
        {

            $.post(
                wall.ajaxurl,
                {
                    'action': 'rpi_wall_close_pin_group',
                    'group_id': id,
                },
                function (response) {
                    rpi_wall_print_content(response, action)
                }
            )
        }else{

        }
    });
})
