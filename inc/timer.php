<?php

global $next_termin;
$t = date('d|m|H',strtotime('H:i',strtotime(get_post_meta($next_termin->ID, 'termin_date', true))));
$time = explode('|', $t);

?>
<script>
    const year = new Date().getFullYear();
    const month = new Date().getMonth();
    const day = new Date().getDay();

    const fourthOfJuly = new Date(year, 1,19,12).getTime();
    const ss = new Date(year + 0, 0, 0).getTime();


    // countdown
    let timer = setInterval(function() {

        // get today's date
        const today = new Date().getTime();

        // get the difference
        let diff;
        if(month > 0) {
            diff = fourthOfJulyNextYear - today;
        } else {
            diff = fourthOfJuly - today;
        }




        // math
        let days = Math.floor(diff / (1000 * 60 * 60 * 24));
        let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((diff % (1000 * 60)) / 1000);

        // display
        document.getElementById("timer").innerHTML =
            "<div class=\"days\"> \
		  <div class=\"numbers\">" + days + "</div>days</div> \
<div class=\"hours\"> \
  <div class=\"numbers\">" + hours + "</div>hours</div> \
<div class=\"minutes\"> \
  <div class=\"numbers\">" + minutes + "</div>minutes</div> \
<div class=\"seconds\"> \
  <div class=\"numbers\">" + seconds + "</div>seconds</div> \
</div>";

    }, 1000);
</script>
<style>
    @import url('https://fonts.googleapis.com/css?family=Lato:400,700|Montserrat:900');

    $font: 'Arial', sans-serif;
    $big: 'verdana', sans-serif;

    $red: #EF2F3C;
    $white: #F6F4F3;
    $blue: #276FBF;
    $dark: #183059;
    $yellow: #F0A202;

    $star: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);

    html {
        display: grid;
        min-height: 100%;
    }

    body {
        display: grid;
        background: $dark;
    }

    .container {
        position: relative;
        margin: auto;
        overflow: hidden;
        width: 650px;
        height: 480px;
    }

    h1 {
        font-family: $font;
        text-align: center;
        margin-top: 2em;
        font-size: 1em;
        text-transform: uppercase;
        letter-spacing: 5px;
        color: $white;
    }

    #timer {
        color: $white;
        text-align: center;
        text-transform: uppercase;
        font-family: $font;
        font-size: .7em;
        letter-spacing: 5px;
        margin-top: 25%;
    }

    .days, .hours, .minutes, .seconds {
        display: inline-block;
        padding: 10px;
        width: 100px;
        border-radius: 5px;
    }

    .days {
        background: $red;
    }

    .hours {
        background: $white;
        color: $dark;
    }

    .minutes {
        background: $blue;
    }

    .seconds {
        background: $yellow;
    .numbers {

    }
    }

    .numbers {
        font-family: $big;
        color: $dark;
        font-size: 4em;
    }

    .white {
        position: absolute;
        background: $white;
        height: 85px;
        width: 75px;
        left: 30%;
        top: 2%;
    .triangle {
        border-bottom: 14px solid $white;
    }
    .string {
        background: $white;
        border: 1px solid $white;
    }
    }

    .red {
        position: absolute;
        background: $red;
        left: 18%;
        top: 9%;
        height: 65px;
        width: 70px;
    .triangle {
        border-bottom: 14px solid $red;
    }
    .string {
        background: $red;
        border: 1px solid $red;
    }
    }

    .blue {
        position: absolute;
        background: $blue;
        height: 80px;
        width: 80px;
        left: 60%;
        top: 5%;
    .triangle {
        border-bottom: 14px solid $blue;
    }
    .string {
        background: $blue;
        border: 1px solid $blue;
    }
    }

    .balloon {
        display:none;
        border: 1px solid #000;
        border-radius: 50% 50% 50% 50%/ 40% 40% 60% 60%;
        z-index: 2;
    }

    .eye {
        position: absolute;
        width: 7px;
        height: 7px;
        top: 40%;
        left: 22%;
        background: #000;
        border-radius: 50%;
    &:after {
         content: '';
         left: 35px;
         width: 7px;
         height: 7px;
         border-radius: 50%;
         background: #000;
         position: absolute;
     }
    }

    .mouth {
        position:absolute;
        top: 45%;
        left: 43%;
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }

    .happy {
        border: 2px solid;
        border-color: transparent #000 #000 transparent;
        transform: rotate(45deg);
    }

    .triangle {
        position: absolute;
        left: 40%;
        bottom: -10%;
        width: 0;
        height: 0;
        border-left: 7px solid transparent;
        border-right: 7px solid transparent;
    }

    .string {
        position: absolute;
        height: 70px;
        width: 1px;
        left: 48%;
        top: 100%;
        z-index: -1;
    }

    .star {
        width: 20px;
        height: 20px;
        background: $white;
        -webkit-clip-path: $star;
        clip-path: $star;
    }

    .star-red {
        width: 30px;
        height: 30px;
        margin-left: 51px;
        margin-top: -5px;
        background: $red;
        -webkit-clip-path: $star;
        clip-path: $star;
    }

    footer {
        position: fixed;
        bottom: 0;
        right: 0;
        text-transform: uppercase;
        padding: 10px;
        font-family: $font;
        font-size: 0.7em;
    p {
        letter-spacing: 3px;
        color: $red;
    }
    a {
        color: $white;
        text-decoration: none;
    &:hover {
         color: $blue;
     }
    }
    }
</style>
<div class="container">
    <div class="balloon white">
        <div class="star-red"></div>
        <div class="face">
            <div class="eye"></div>
            <div class="mouth happy"></div>
        </div>
        <div class="triangle"></div>
        <div class="string"></div>
    </div>

    <div class="balloon red">
        <div class="star"></div>
        <div class="face">
            <div class="eye"></div>
            <div class="mouth happy"></div>
        </div>
        <div class="triangle"></div>
        <div class="string"></div>
    </div>

    <div class="balloon blue">
        <div class="star"></div>
        <div class="face">
            <div class="eye"></div>
            <div class="mouth happy"></div>
        </div>
        <div class="triangle"></div>
        <div class="string"></div>
    </div>
    <div id="timer"></div>
</div>

<?php ?>
