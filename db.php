<?php

# Codinglab 2019. This is free software; you may freely copy, use, modify or adapt as you wish.
# https://codinglab.ch
#
# This is the PHP script that will allow searching airplane traces in the database. It is part of Codinglab's Pi Airmon solution.
# To work properly, it needs to be able to access certain files and directories. See the code for details.
#
# For more info see ads-b-logger here: https://github.com/stephen-hocking/ads-b-logger
# For more info on the Pi Airmon see https://codinglab.ch
#
# Here is the command line to log all aircraft traces in the Postgres DB (with GIS extensions):
# /root/ads-b-logger/planelogger.py -y /root/ads-b-logger/creds.yaml --debug -c -1 -u http://127.0.0.1/dump1090/data/aircraft.json
#
# Here is the command line to log only aircraft with altitude 0-4000m (Flight Level 130, i.e. 13'000 feet):
# /root/ads-b-logger/planelogger.py --max-altitude 4000 -y /root/ads-b-logger/creds.yaml --debug -c -1 -u http://127.0.0.1/dump1090/data/aircraft.json
# This is how the planelogger is currently launched automatically in /etc/rc.local. You may change this to log more comprehensively.
# 
# Here is the command line to read everything from the database:
# /root/ads-b-logger/planedbreader.py -y /root/ads-b-logger/creds.yaml
#
# Here is the command line to read from the database traces of aircraft with ICAO24 code 4b448d on August 8th 2019:
# /root/ads-b-logger/planedbreader.py -y /root/ads-b-logger/creds.yaml --hex "4b448d" --start-time "2019-08-08 00:00:01" --end-time "2019-08-08 23:59:59"
#
# Here is the command line to generate a KML file (fichier2.kml) from the output of planedbreader.py (stored in fichier.dat) : 
# /root/ads-b-logger/planekml.py --file /tmp/fichier.dat --output-file /tmp/fichier2.kml --title "4b448d 2019-08-08"


header('Content-Encoding: chunked');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
ini_set('output_buffering', false);
ini_set('implicit_flush', true);
ob_implicit_flush(true);

echo("<!DOCTYPE html>");
echo "<html><head><title>Airmon Database Search</title></head><body>";

if (empty($_GET["icao24"]) && empty($_GET["date"])) {

    echo '

        <form action="/db.php" method="GET">

        <table>

        <tr>
        <td>ICAO24 Identifier: </td>
        <td>&nbsp;&nbsp;<input type="text" name="icao24"></td>
        </tr>

        <tr>
        <td>Date (YYYY-MM-DD): </td>
        <td>&nbsp;&nbsp;<input type="text" name="date"></td>
        </tr>

        <tr>
        <td>&nbsp;</td>
        <td>&nbsp;&nbsp;<input type="submit" value="Search"></td>
        </tr>
        
        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
        
        <tr>
        <td>&nbsp;</td>
        <td>&nbsp;&nbsp;<a href="/history">Previous search results</a></td>
        </tr>
        
        </table>

        </form>

        <br><i>You have '.(disk_free_space("/") / 1024).' MB of free space left <a href="/purge.php">(purge database)</a>.</i>
        
    
    ';

}

else if ((empty($_GET["icao24"]) && !empty($_GET["date"])) || (!empty($_GET["icao24"]) && empty($_GET["date"]))) {
    echo "Please fill-in all the search fields.<br><a href=\"/db.php\">Go back</a>.";
}

else if (!empty($_GET["date"]) && !preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $_GET["date"])  ) {
    echo "Invalid date format.<br><a href=\"/db.php\">Go back</a>.";
}

else if (!empty($_GET["icao24"]) && !preg_match('/^[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]$/', $_GET["icao24"])) {
    echo "Invalid ICAO24 identifier format.<br><a href=\"/db.php\">Go back</a>.";

}

// here is the actual search routine
else {

    echo "Please wait ...<br><br>";
    
    ob_flush();
    flush();
    
    $continue = 0;
    $rnumb = rand(1000,90000);
    $tdat = "/tmp/adsblogger.".$rnumb.".dat";
    $tkml = "/tmp/adsblogger.".$rnumb.".kml";

    // I'm not bothering to escape shell args here because the above regexps are enough input validation
        
    $sstring = "/root/ads-b-logger/planedbreader.py -y /root/ads-b-logger/creds.yaml --hex \"".strtolower($_GET["icao24"])."\" --start-time \"".$_GET["date"]." 00:00:01\" --end-time \"".$_GET["date"]." 23:59:59\" > ".$tdat."";
    $kstring = "/root/ads-b-logger/planekml.py --file \"".$tdat."\" --output-file \"".$tkml."\" --title \"".$_GET["icao24"]." ".$_GET["date"]."\"";

    //echo "Executing this command:<br>".$sstring;
    //echo "<br>Executing this command:<br>".$kstring;

    // executing the search command
    exec($sstring, $output, $return_value);
    
    if ($return_value) {
        echo "Sorry, the search command failed. You may need to reinstall your Airmon.<br>";
    }
    
    else if (!file_exists($tdat)) {
        echo "Sorry, the file ".$tdat." was not created. You may need to reinstall your Airmon.<br>";
    }
    
    else if (filesize($tdat) == 0) {
        echo "The search command returned no result. Please try with different criteria.<br>";
    }
    
    else {
        
        $newrand = rand(100,999);
        $newfile = "/history/".strtolower($_GET["icao24"])."-".$_GET["date"]."-".$newrand.".dat";

        if (copy($tdat, "/var/www/html".$newfile)) {
            echo "The search command was successful. <a href=\"".$newfile."\">Download your results.</a><br><br>";
        }
        else {
            echo "The search command was successful but the data file could not be copied. You may need to reinstall your Airmon.<br>";
        }
        
        $continue = 1;
    
    }
    // end of search command

    // -----

    // executing the KML conversion command
    if ($continue == 1) { 
    
        exec($kstring, $output, $return_value);
    
        if ($return_value) {
            echo "Sorry, the KML conversion command failed. You may need to reinstall your Airmon.<br>";
        }
    
        else if (!file_exists($tkml)) {
            echo "Sorry, the file ".$tkml." was not created. You may need to reinstall your Airmon.<br>";
        }
    
        else if (filesize($tkml) == 0) {
            echo "The converted KML file has a size of zero. You may need to reinstall your Airmon.<br>";
        }
    
        else {
            
            $newkml = "/history/".strtolower($_GET["icao24"])."-".$_GET["date"]."-".$newrand.".kml";

            if (copy($tkml, "/var/www/html".$newkml)) {
                echo "The KML conversion command was successful. <a href=\"".$newkml."\">Download your KML file.</a><br><br>";
            }
            else {
                echo "The search command was successful but the data file could not be copied. You may need to reinstall your Airmon.<br>";
            }
            
        }
        // end of KML conversion
    }


    exec("rm -f ".$tdat." > /dev/null 2> /dev/null");
    exec("rm -f ".$tkml." > /dev/null 2> /dev/null");

}
// end of the actual search routine


echo "<br><hr><a href=\"/\">Back to Airmon</a>";
echo "</body></html>";
exit(0);

?>

