jQuery(document).ready($=>{

    $('.rpi-wall-like-button').each((i,btn)=>{
        const id = btn.id.replace(/[^\d]*/,'');

        $(btn).on('click',e=>{
            $.post(
                rpi_wall.ajaxurl, {
                    'action': 'rpi_wall_toggle_like',
                    'group_id': id
                },
                function (response) {
                    console.log(response);
                }
            )
        });



    })
})
