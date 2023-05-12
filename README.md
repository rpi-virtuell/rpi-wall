# rpi-wall Pinnwand für professionelle Lerngemeinschaften

requires:  
* [rpi-virtuell/matrix-php-sdk](https://github.com/rpi-virtuell/php-matrix-sdk) via compaser

 
## Cronjobs ##
Es gibt einige Cronjobs die angelegt werden müssen, um die
fehlerfreie Funktionsweise des Plugins zu gewährleisten.

*Die Zeitabstände des Cronjobs können individuell gewählt werden.
Gewöhnlich wird aber der kleinstmögliche Abstand verwendet.*

-     cron_sync_member_data
    
-     cron_update_join_request
     
-     cron_update_pin_status
-     cron_wall_sync_read_messages
