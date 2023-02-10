<?php

$args =[
    'post_type' => 'termin',
    'meta_key'=>'termin_date',
    'numberposts'=> 1,
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query'=>
        [
            'key' => 'termin_date',
            'compare' => '>=',
            'value' => date('Y-m-d h:i:s'),
        ]
];

$termine = get_posts($args) ;
$next_termin = reset($termine);


?>
<script>
    const year = new Date().getFullYear();
    const month = new Date().getMonth();
    const day = new Date().getDay();

    // countdown
    let timer = setInterval(function () {

        // get today's date
        const today = new Date().getTime();
        const nexttermin = new Date("<?php echo get_post_meta($next_termin->ID, 'termin_date', true)?>").getTime();
        let diff;
        diff = nexttermin - today;
        // math

        if (diff > 0 )
        {
            let days = Math.floor(diff / (1000 * 60 * 60 * 24));
            let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            let seconds = Math.floor((diff % (1000 * 60)) / 1000);

            // display
            document.getElementById("timer").innerHTML =
                "<div class=\"days\"> \
              <div class=\"numbers\">" + days + "</div>Tage</div> \
<div class=\"hours\"> \
  <div class=\"numbers\">" + hours + "</div>Stunden</div> \
<div class=\"minutes\"> \
  <div class=\"numbers\">" + minutes + "</div>Minuten</div> \
<div class=\"seconds\"> \
  <div class=\"numbers\">" + seconds + "</div>Sekunden</div> \
</div>";

        }

    }, 1000);
</script>
<div class="container">
    <div id="timer">
    </div>
</div>
<?php
?>
