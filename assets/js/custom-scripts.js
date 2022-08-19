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
                        else
                        {
                          document.location.href = "/login";
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
        if(location.hash.indexOf('page_')<0){
            if (site_match_member && $('.tabset').length>0) {
                $('.tabset').ready(function () {

                    var hash = "bio";
                    var page = 1;
                    for (const tab of rpi_wall.allowedtabs) {
                        var regex = new RegExp( tab, 'g' );
                        if (location.hash && location.hash.match(regex)){
                             hash = tab;
                             var match = location.hash.match(/page(\d+)/);
                             if(match!== null){
                                 page = match[1];
                             }
                            break;
                        }
                        //page?

                    }

                    //location.hash.indexOf(hash)

                    // if (location.hash && rpi_wall.allowedtabs.includes(location.hash.substring(1))) {
                    //     hash = location.hash.substring(1);
                    // } else {
                    //     hash = "bio";
                    // }
                    //var action = "rpi_tab_" + hash + "_content";
                    $('#tab-' + hash).prop('checked', true);
                    rpi_wall_send_post(hash,page);
                })

            }else if(site_match_wall && $('.tabset').length>0){
                $('.tabset').ready(function () {
                    // if (location.hash && rpi_wall.allowedtabs.includes(location.hash.substring(1))) {
                    //     hash = location.hash.substring(1);
                    // } else {
                    //     hash = "pin";
                    // }
                    var page = 1;
                    var hash = "pin";
                    for (const tab of rpi_wall.allowedtabs) {
                        var regex = new RegExp( tab, 'g' );
                        if (location.hash && location.hash.match(regex)){
                            hash = tab;
                            var match = location.hash.match(/page(\d+)/);
                            if(match!== null){
                                page = match[1];
                            }
                            break;
                        }
                    }
                    //action = "rpi_tab_" + hash + "_content";
                    $('#tab-' + hash).prop('checked', true);
                    rpi_wall_send_post(hash, page);
                })
            }
        }
    }


    function rpi_wall_send_post(hash, paged=1) {

        var action = "rpi_tab_" + hash + "_content";

        if (action === 'rpi_tab_logout_content'){
            location.href = "/wp-login.php?action=logout";
            console.log(location.href);
            return;
        }

        $.post(
            wall.ajaxurl,
            {
                'action': action,
                'user_ID': rpi_wall.user_ID,
                'paged': paged
            },
            function (response) {
                rpi_wall_print_content(response, hash)
            }
        )
    }

    function rpi_wall_print_content(response, hash) {

        console.log(hash);

        if(!response || response == ''){
            return;
        }

        var action = "rpi_tab_" + hash + "_content";
        $('#' + hash).html(response);
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
                $(elem).attr('href', '#'+hash+'_page' + page);
                $(elem).unbind();

                $(elem).on('click', e => {
                    $.post(
                        wall.ajaxurl,
                        data,
                        function (response) {
                            rpi_wall_print_content(response, hash)
                        }
                    )
                })
            }
        })
    }


    function mark_and_display_message() {

        $('.message-entry').each((i, msg) => {
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
                                if (data.message_count !== "0")
                                {
                                    $('#message-count').html(data.message_count);
                                }else
                                {
                                    $('#message-count').html("");
                                }
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
