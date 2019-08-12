<?php

# Codinglab 2019. This is free software; you may freely copy, use, modify or adapt as you wish.
# https://codinglab.ch
#
# This is the PHP script that will allow purging airplane traces from the database. It is part of Codinglab's Pi Airmon solution.
# To work properly, it needs to be able to access certain files and directories. See the code for details.
#
# For more info see ads-b-logger here: https://github.com/stephen-hocking/ads-b-logger
# For more info on the Pi Airmon see https://codinglab.ch
#
# The (suid root) C program called purge-priv that does the actual purging needs to execute very simple commands along the following lines:
# dropdb -U postgres PlaneReports && createdb -U postgres PlaneReports
# pg_restore -U postgres -v -d PlaneReports ./db.dump
#
# The db.dump file is a schema-only backed up version of the database. Create it once and for all like this:
# pg_dump -U postgres -v -Fc -s -f db.dump PlaneReports


if (empty($_GET["a"])) {

    setcookie('purge_airmon', rand(5000,10000)*79, time()+300, '/');

    echo '

    <!DOCTYPE html>
    <html>
    <head><title>Purge Airmon</title></head>

    <body>

    <center><h1>Are you sure you want to purge the Airmon database?</h1></center>

    <font size="15" face="Arial">
    <table border="1" align="center" width="60%"><tr><td width="50%"><center><b><a href="?a=yes">YES</a></b></center></td><td width="50%"><center><b><a href="?a=no">NO</a></b></center></td></tr></table>
    </font>

    </center>


    </body></html>

    ';

    exit(0);

}

else if ($_GET["a"] == "yes") {

    header('Content-Encoding: chunked');
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    ini_set('output_buffering', false);
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);

    if (!isset($_COOKIE['purge_airmon'])) {
        echo "Sorry, request timed out. Please try again\n";
    }

    else if ($_COOKIE['purge_airmon'] % 79 != 0) {
        setcookie('purge_airmon', '', -1);
        echo "You sneaky sneaky Buryat\n";
    }
    
    else {

        setcookie('purge_airmon', '', -1);
        echo "OK! Purging Airmon database ... DO NOT CLOSE THIS WINDOW!\nBe patient (1 minute) and check in the log below that there is no error.\n\n";
        
        flush();
        ob_flush();

        // ACTUAL PURGE HERE
        // this is a dirty hack to get the output and not have the program hang; if someone knows how to do it better let me know alfy@codinglab.ch.
        // it still doesn't display the output in the right order. It must have something to do with concurrence. Oh well, it's late, it's not that important, and this is only version 1 after all.
        
        $rblah = rand(9,99999);
        exec("/usr/local/bin/purge-priv 2> /tmp/out.".$rblah.".txt > /tmp/out.".$rblah.".txt &");

        sleep(60);
        echo file_get_contents("/tmp/out.".$rblah.".txt");
        echo "\nAll Done, you can close this window.\n";
        exec("rm -f /tmp/out.".$rblah.".txt");
        
        // END OF PURGE

    }
    
    exit(0);
} 

else if ($_GET["a"] == "no") {

    setcookie('purge_airmon', '', -1);

    echo "<!DOCTYPE html><html><head><title>Not purging Airmon database</title></head><body>";
    echo "<h2>Ok, not purging Airmon database.</h2> <br><a href=\"/\">Back to Airmon</a>";
    echo "</body></html>";
    exit(0);
    
}

else {

    echo "You sneaky sneaky Buryat";
    exit(-1);
    
}

?>

