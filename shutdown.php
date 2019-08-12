<?php

# Codinglab 2019. This is free software; you may freely copy, use, modify or adapt as you wish.
# https://codinglab.ch
#
# This is the PHP script that will shutdown your Raspberry Pi. It is part of Codinglab's Pi Airmon solution.
#
# For more info on the Pi Airmon see https://codinglab.ch
#
# The (suid root) C program called shutdown-priv that does the actual shutting-down is trivial: int main() {setuid(0); system("/sbin/shutdown -h now");}

header('Content-Encoding: chunked');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
ini_set('output_buffering', false);
ini_set('implicit_flush', true);
ob_implicit_flush(true);


if (empty($_GET["a"])) {

    setcookie('shutdown_airmon', rand(5000,10000)*79, time()+300, '/');

    echo '

    <!DOCTYPE html>
    <html>
    <head><title>Shutdown Airmon</title></head>

    <body>

    <center><h1>Are you sure you want to shutdown Airmon?</h1></center>

    <font size="15" face="Arial">
    <table border="1" align="center" width="60%"><tr><td width="50%"><center><b><a href="?a=yes">YES</a></b></center></td><td width="50%"><center><b><a href="?a=no">NO</a></b></center></td></tr></table>
    </font>

    </center>


    </body></html>

    ';

    exit(0);

}

else if ($_GET["a"] == "yes") {
    
    if (!isset($_COOKIE['shutdown_airmon'])) {
        echo "<!DOCTYPE html><html><head><title>Shutting down Airmon</title></head><body>";
        echo "<h2>Sorry, request timed out. Please <a href=\"/shutdown.php\">try again</a>.</h2>";
    }

    else if ($_COOKIE['shutdown_airmon'] % 79 != 0) {
        setcookie('shutdown_airmon', '', -1);
        echo "You sneaky sneaky Buryat";
    }
    
    else {

        setcookie('shutdown_airmon', '', -1);
        echo "<!DOCTYPE html><html><head><title>Shutting down Airmon</title></head><body>";
        echo '<center><h2>OK! Shutting down Airmon ... you can unplug the device in 20 seconds.</h2></center><br>';
        
        flush();
        ob_flush();

        sleep (2);
        
        // ACTUAL SHUTDOWN HERE
        
        exec('/usr/local/bin/shutdown-priv > /dev/null 2> /dev/null &');
        
        // END OF ACTUAL SHUTDOWN

    }
    
    echo "</body></html>";
    exit(0);
} 

else if ($_GET["a"] == "no") {

    setcookie('shutdown_airmon', '', -1);

    echo '<!DOCTYPE html><html><head><title>Not shutting down Airmon</title></head><body>';
    echo "<h2>Ok, not shutting down Airmon.</h2> <br><a href=\"/\">Back to Airmon</a>";
    echo "</body></html>";
    exit(0);
    
}

else {

    echo "You sneaky sneaky Buryat";
    exit(0);
    
}

?>

