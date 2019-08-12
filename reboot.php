<?php

# Codinglab 2019. This is free software; you may freely copy, use, modify or adapt as you wish.
# https://codinglab.ch
#
# This is the PHP script that will reboot your Raspberry Pi. It is part of Codinglab's Pi Airmon solution.
#
# For more info on the Pi Airmon see https://codinglab.ch
#
# The (suid root) C program called reboot-priv that does the actual rebooting is trivial: int main() {setuid(0); system("/sbin/shutdown -r now");}



header('Content-Encoding: chunked');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
ini_set('output_buffering', false);
ini_set('implicit_flush', true);
ob_implicit_flush(true);


if (empty($_GET["a"])) {

    setcookie('reboot_airmon', rand(5000,10000)*79, time()+300, '/');

    echo '

    <!DOCTYPE html>
    <html>
    <head><title>Reboot Airmon</title></head>

    <body>

    <center><h1>Are you sure you want to reboot Airmon?</h1></center>

    <font size="15" face="Arial">
    <table border="1" align="center" width="60%"><tr><td width="50%"><center><b><a href="?a=yes">YES</a></b></center></td><td width="50%"><center><b><a href="?a=no">NO</a></b></center></td></tr></table>
    </font>

    </center>


    </body></html>

    ';

    exit(0);

}

else if ($_GET["a"] == "yes") {
    
    if (!isset($_COOKIE['reboot_airmon'])) {
        echo "<!DOCTYPE html><html><head><title>Rebooting Airmon</title></head><body>";
        echo "<h2>Sorry, request timed out. Please <a href=\"/reboot.php\">try again</a>.</h2>";
    }

    else if ($_COOKIE['reboot_airmon'] % 79 != 0) {
        setcookie('reboot_airmon', '', -1);
        echo "You sneaky sneaky Buryat";
    }
    
    else {

        setcookie('reboot_airmon', '', -1);
        echo '<!DOCTYPE html><html><head><title>Rebooting Airmon</title></head><body>';
        echo '<center><h2>OK! Rebooting Airmon ... wait 2 minutes and <a href="/">go back</a>.</h2></center><br>';
        
        flush();
        ob_flush();

        sleep (2);
        
        // ACTUAL REBOOT HERE
        
        exec('/usr/local/bin/reboot-priv > /dev/null 2> /dev/null &');
        
        // END OF ACTUAL REBOOT

    }
    
    echo "</body></html>";
    exit(0);
} 

else if ($_GET["a"] == "no") {

    setcookie('reboot_airmon', '', -1);

    echo "<!DOCTYPE html><html><head><title>Not rebooting Airmon</title></head><body>";
    echo "<h2>Ok, not rebooting Airmon.</h2> <br><a href=\"/\">Back to Airmon</a>";
    echo "</body></html>";
    exit(0);
    
}

else {

    echo "You sneaky sneaky Buryat";
    exit(0);
    
}

?>

