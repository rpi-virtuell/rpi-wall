jQuery(document).ready($ => {

    add_watch_button_click_event();
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
                            jQuery('#like-group-' + id + ' .rpi-wall-like-button').html(wallIcons.group_sub);
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
                    } else {
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
        if (location.hash.indexOf('page_') < 0) {
            if (site_match_member && $('.tabset').length > 0) {
                $('.tabset').ready(function () {

                    var hash = "bio";
                    var page = 1;
                    for (const tab of rpi_wall.allowedtabs) {
                        var regex = new RegExp(tab, 'g');
                        if (location.hash && location.hash.match(regex)) {
                            hash = tab;
                            var match = location.hash.match(/page(\d+)/);
                            if (match !== null) {
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
                    rpi_wall_send_post(hash, page);
                })

            } else if (site_match_wall && $('.tabset').length > 0) {
                $('.tabset').ready(function () {
                    // if (location.hash && rpi_wall.allowedtabs.includes(location.hash.substring(1))) {
                    //     hash = location.hash.substring(1);
                    // } else {
                    //     hash = "pin";
                    // }
                    var page = 1;
                    var hash = "pin";
                    for (const tab of rpi_wall.allowedtabs) {
                        var regex = new RegExp(tab, 'g');
                        if (location.hash && location.hash.match(regex)) {
                            hash = tab;
                            var match = location.hash.match(/page(\d+)/);
                            if (match !== null) {
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


    function rpi_wall_send_post(hash, paged = 1) {

        var action = "rpi_tab_" + hash + "_content";
        var filter = '';
        if (location.hash.indexOf('unread') > 0 && hash == 'messages') {
            filter = 'unread';
        }

        $.post(
            wall.ajaxurl,
            {
                'action': action,
                'user_ID': rpi_wall.user_ID,
                'paged': paged,
                'filter': filter
            },
            function (response) {

                rpi_wall_print_content(response, hash);


                $('#member-mark-all-read-button').on('click', e => {
                    $.post(
                        wall.ajaxurl,
                        {
                            'action': 'rpi_ajax_mark_all_messages_read',
                        },
                        function (response) {

                            response = JSON.parse(response);
                            if (response.success) {
                                location.reload();
                            }
                        }
                    )
                })

                $('#member-delete-all-read-button').on('click', e => {
                    $.post(
                        wall.ajaxurl,
                        {
                            'action': 'rpi_ajax_delete_all_messages_read',
                            'user_id': rpi_wall.user_ID,

                        },
                        function (response) {

                            response = JSON.parse(response);
                            if (response.success) {
                                location.reload();
                            }
                        }
                    )
                })

                add_watch_button_click_event();
            }
        )
        /*
        if (action === 'rpi_tab_logout_content') {

            setTimeout(() => {
                location.href = '/';
            }, 1000);
            return;
        }
        */
    }

    function rpi_wall_print_content(response, hash) {

        if (!response || response == '') {
            return;
        }


        var action = "rpi_tab_" + hash + "_content";
        $('#' + hash).html(response);
        if (action === 'rpi_tab_messages_content') {
            mark_and_display_message();
            add_member_message_button_event();
        }

        if (action === 'rpi_tab_logout_content') {
            location.href=response;
        }

        $('a.page-numbers').each(function (i, elem) {
            const href = $(elem).attr('href');
            if (typeof href != 'undefined') {
                //link zerstören
                const match = href.match(/paged=(\d*)/);
                const page = match ? match[1] : 1;
                var filter = '';
                if (location.hash.indexOf('unread') > 0) {
                    filter = 'unread';
                }

                const data = {
                    'action': action,
                    'filter': filter,
                    'user_ID': rpi_wall.user_ID,
                    'paged': page
                };
                if (filter !== '')
                    filter = '_' + filter;

                $(elem).attr('href', '#' + hash + filter + '_page' + page);
                $(elem).unbind();

                $(elem).on('click', e => {
                    $.post(
                        wall.ajaxurl,
                        data,
                        function (response) {
                            rpi_wall_print_content(response, hash);
                        }
                    )
                })
            }
        })
    }

    /*'var src = jQuery(".ct-button.message-bell img").attr("src"); ' .
                     'if(mc>0) ' .
                     '  src = src.replace("bell.png", "bell_red.png");' .
                     'else ' .
                     '  src = src.replace("bell_red.png", "bell.png");' .
                     'jQuery(".ct-button.message-bell img").attr("src", src);' .
                     */

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
                            $('.focused').removeClass('focused');
                            $(msg).find('.entry-title').removeClass('unread');
                            $(msg).addClass('focused');
                            if (data.content) {
                                $('.member-message-detail').attr('id', data.message_id);
                                $('#member-message-detail-title').html(data.title);
                                $('#member-message-detail-content').html(data.content);
                                $('#member-message-detail-options').css('display', 'inherit');
                                $('.member-message-grid').addClass('message-detail');
                                $('.member-message-grid').removeClass('message-list');
                                $('#member-message-button').addClass('member-button-display');
                                if (data.message_count !== "0") {
                                    $('#message-count').html(data.message_count);
                                    $(".ct-button.message-bell img").attr("src", rpi_wall_bell_red);

                                } else {
                                    $('#message-count').html("");
                                    $(".ct-button.message-bell img").attr("src", rpi_wall_bell);
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
        $('#member-message-detail-delete').ready($ => {
            $('#member-message-detail-delete').on('click', e => {
                let message_id = $('.member-message-detail').attr('id');

                $.post(
                    wall.ajaxurl,
                    {
                        'action': 'rpi_delete_message',
                        'message_id': message_id,
                        'user_id': rpi_wall.user_ID
                    },
                    function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            location.reload();
                        }
                    }
                )


                $('.member-message-grid').removeClass('message-detail');
                $('.member-message-grid').addClass('message-list');
                $('#member-message-button').removeClass('member-button-display');
            });
        })

    }


    /**
     * Modal Window open
     */
    //$("#btn-open-modal").animatedModal();
    /**
     * Modal Window schließen
     */
    $(document).click(function (event) {
        var $target = $(event.target);
        if ($target.hasClass('zoomIn')) {
            $('#btn-close-modal').click();
        }
    });

    $('#close-pin-group-button').on('click', e => {
        if (confirm("Soll die PLG wirklich geschlossen werden?")) {

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
        } else {

        }
    });

    $(document).on('facetwp-loaded', e => {
        setTimeout(e => {
            //remove load more button if last page has loaded
            if (FWP.settings.pager.page == FWP.settings.pager.total_pages) {
                $('.facetwp-facet-paging').hide();
            } else {
                $('.facetwp-facet-paging').show();
            }

            if ($('.facetwp-selections').html().length > 0) {
                $('button.facetwp-reset.facetwp-hide-empty').show();
                $('.ct-container.rpi-wall-filters summary.button').addClass('active');
            } else {
                $('button.facetwp-reset.facetwp-hide-empty').hide();
                $('.ct-container.rpi-wall-filters summary.button').removeClass('active');
            }
            //remove result page content from facetwp-template if no results
            if (FWP.settings.pager.total_pages === 0) {
                $('.entries.facetwp-template').html('');
                $('.facetwp-facet-paging').hide();
            }


        }, 100);

    });

})

function add_watch_button_click_event() {
    $ = jQuery;
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
}
