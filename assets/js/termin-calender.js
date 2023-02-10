jQuery(document).ready($ => {

    $('.dibes-termin-focused').ready($ =>{
        $(document).click(function (){
            $('.dibes-termin-focused').removeClass('dibes-termin-focused');
        });
    })

    $('.dibes-termin-filled').each((i, btn) => {
        $(btn).click(function (event) {
            $('.dibes-termin-details').removeClass('dibes-termin-focused');
            $(btn).find('.dibes-termin-details').addClass('dibes-termin-focused');
            event.stopPropagation();
        });
    })

    $('.termine-join-button').on('click', e => {
        $.post(
            wall.ajaxurl,
            {
                'action': 'rpi_ajax_termin_log_participant_and_redirect',
                'post_id': e.id
            },
            function (response) {


            }
        )
    })

})