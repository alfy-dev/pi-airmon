<?php

# Codinglab 2019. This is free software; you may freely copy, use, modify or adapt as you wish.
# https://codinglab.ch
#
# This is the PHP script that will configure one WiFi network on your Raspberry Pi. It is part of Codinglab's Pi Airmon solution.
#
# For more info on the Pi Airmon see https://codinglab.ch
#
# The (suid root) C program called wifi-priv only needs to copy the temporary wpa_supplicant.conf file (passed in argument) into its proper location (/etc/wpa_supplicant/wpa_supplicant.conf).
# It should also execute "/sbin/wpa_cli -i wlan0 reconfigure" afterwards.

function get_ip($interface) {
    $interface = escapeshellarg($interface);
    $pattern = "/inet (\d+\.\d+\.\d+\.\d+)/";
    $text = shell_exec("/sbin/ifconfig $interface");
    preg_match($pattern, $text, $matches);
    return $matches[1];
}

header('Content-Encoding: chunked');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
ini_set('output_buffering', false);
ini_set('implicit_flush', true);
ob_implicit_flush(true);

echo "<!DOCTYPE html>";
echo "<html><head><title>Airmon WiFi Setup</title></head><body>";

if (empty($_POST["essid"]) && empty($_POST["pass"])) {

    echo '

    Use this simple interface to add one WiFi network to Airmon.<br><br>
    If you wish to add more than one network, or if your WiFi network doesn\'t use WPA-PSK, you cannot use this interface.<br>
    If your ESSID or WiFi password have special/unusual characters, it is also best not to use this interface.<br>
    You can also connect a computer screen to access network manager, or use SSH or VNC.<br><br><hr>

    <form action="/wifi.php" method="POST">
    <table border="0">
    <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;&nbsp;ESSID:&nbsp;&nbsp;</td>
    <td>&nbsp;&nbsp;<input type="text" name="essid">&nbsp;&nbsp;</td></tr>
    <tr><td>&nbsp;&nbsp;Password:&nbsp;&nbsp;</td><td>&nbsp;&nbsp;<input type="text" name="pass">&nbsp;&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td>&nbsp;&nbsp;<input type="submit" value="Add WiFi"></td></tr>
    </table></form><br><hr>
    ';
    
    if (empty($ip=get_ip("eth0"))) {
        echo "<i>eth0 not connected</i><br>";
    } else {
        echo "<i>eth0 connected with IP: ".$ip."</i><br>";
    }
    if (empty($ip=get_ip("wlan0"))) {
        echo "<i>wlan0 not connected</i><br>";
    } else {
        echo "<i>wlan0 connected with IP: ".$ip."</i><br>";
    }

}

else if (empty($_POST["essid"]) || empty($_POST["pass"])) {
    echo "Please fill-in all the fields.<br>";

}

else if (preg_match('/[\'"\|\\\\]/', $_POST["essid"]) || preg_match('/[\'"\|\\\\]/', $_POST["pass"])) {
    echo "You have special/unusual characters in your ESSID and/or your password.<br>Please change the password, or use another method to configure your WiFi.<br>";

}

else {

    $wpa = 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
country=CH

network={
    ssid="'.$_REQUEST["essid"].'"
    psk="'.$_REQUEST["pass"].'"
    key_mgmt=WPA-PSK
}
';
    
    $rnum = rand(10000,99000);
    $tfile = "/tmp/wifi_config.".$rnum.".txt";

    if (!file_put_contents($tfile, $wpa)) {
        echo "Sorry, there was an error creating tempfile. You may have to find another way.<br>";
    }
    
    else {
    
        echo "<h3>All good, attempting to reconfigure your Wi-Fi ...</h3>";
        echo "ESSID: \"".$_REQUEST["essid"]."\"<br>Pass: \"".$_REQUEST["pass"]."\"<br><br>";;
        
        ob_flush();
        flush();
        sleep(2);

        exec("/usr/local/bin/wifi-priv ".$tfile, $woutput, $wreturn);
    
        if ($return_value) {
            echo "There was an error re-configuring your WiFi. You may have to find another way.<br>";
        }
        else {
            echo "Wifi reconfigured successfully.<br>";
        }
    
        exec("rm -f $tfile > /dev/null 2> /dev/null");
    
    }
}


echo "</body></html>";
exit(0);

?>

