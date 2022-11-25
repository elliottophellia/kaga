<?php
/*
                    Theiamillis Gre Forthorthe
                    ---------------------------
                    Unlimited Reverse IP Lookup
                    ---------------------------
                    Author: @elliottophellia
                    Version: 1.0.0
                    Licence: GPLv2
                    ---------------------------
                    Credit: @secgron
*/


error_reporting(0);
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

// color
$red = "\033[1;31m";
$green = "\033[1;32m";
$yellow = "\033[1;33m";
$blue = "\033[1;34m";
$clear = "\033[0m";

function banner() {
    print $GLOBALS['yellow'];
    print " _____ _          _                 _ _ _ _      \n";
    print $GLOBALS['blue'];
    print "|_   _| |__   ___(_) __ _ _ __ ___ (_) | (_)___  \n";
    print $GLOBALS['clear'];
    print "  | | | '_ \ / _ \ |/ _` | '_ ` _ \| | | | / __| \n";
    print $GLOBALS['blue'];
    print "  | | | | | |  __/ | (_| | | | | | | | | | \__ \ \n";
    print $GLOBALS['yellow'];
    print "  |_| |_| |_|\___|_|\__,_|_| |_| |_|_|_|_|_|___/ \n";
    print $GLOBALS['clear'];
    print "   Author : " . $GLOBALS['yellow'] . "@elliottophellia" . $GLOBALS['clear'] . " | Version : " . $GLOBALS['yellow'] . "1.0.0" . $GLOBALS['clear'] . "   \n";
    print "   --------------------------------------------  \n";
    print "   [ + ] Unlimited Reverse IP Lookup Tool [ + ]  \n";
    print "   --------------------------------------------  \n";
}

function usage() {
    print "Usage: php theiamillis.php [options] [arguments]            \n\n";
    print "Options:                                                      \n";
    print "-h, --help      Show this help message and exit               \n";
    print "-u, --url       URL/IP to scan (with or without http/https)   \n";
    print "-o, --output    Output file (default: tulip.txt)            \n\n";
    print "Example:                                                      \n";
    print "php theiamillis.php -u https://example.com -o tulip.txt       \n";
}

function remove_http_and_path($url) {
    $urldone = preg_replace('/(http|https):\/\//', '', $url);
    $urldone = preg_replace('/\/.*/', '', $urldone);
    return $urldone;
}

function get_ip($url) {
    $ip = gethostbyname($url);
    return $ip;
}

function reverse_ip($ip) {
    $setting = array(
        CURLOPT_URL => 'https://osint.sh/reverseip/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'domain=' . $ip,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.5304.63 Safari/537.36',

    );
    $ch = curl_init();
    curl_setopt_array($ch, $setting);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function die_or_alive($url) {
    $setting = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_NOBODY => true,
    );
    $ch = curl_init();
    curl_setopt_array($ch, $setting);
    curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode >= 200 && $httpcode < 400) {
        return true;
    } else {
        return false;
    }
}



if ($argv[1] == "-h" || $argv[1] == "--help") {
    banner();
    usage();
    exit();
} elseif ($argv[1] == "-u" || $argv[1] == "--url") {
    if (empty($argv[2])) {
        banner();
        print "\nError: No URL/IP specified";
        exit();
    } elseif (file($argv[2])) {
        banner();
        print "\nError: You can't use a file as URL/IP";
        exit();
    } else {
        $domain = $argv[2];
        $domain = remove_http_and_path($domain);
        $domain = htmlspecialchars(get_ip($domain));
    }
    if (empty($argv[3])) {
        if (file_get_contents('tulip.txt')) {
            $output = "tulip_" . rand() . ".txt";
        } else {
            $output = "tulip.txt";
        }
    } else {
        if (file_get_contents($argv[3])) {
            $output = $argv[3] . "_" . rand() . ".txt";
        } else {
            $output = $argv[3];
        }
    }
    banner();
    $result = reverse_ip($domain);
    $list = preg_match_all("/<td data-th=\"Domain\">\s(.*?)<\/td>/i", $result, $listdomain);
    $getdomain = str_replace(' ', '', $listdomain[1]);
    if ($listdomain[1] == true) {
        $counting = count(array_filter($getdomain));
        print "\n";
        print "   [ + ] Total Domain Found: " . $counting . "\n";
        print "   [ + ] Domain Saved as: " . $output . "\n";
        print "\n";
        print "   --------------------------------------------\n";
        print "   [ + ]          Domain List             [ + ]\n";
        print "   --------------------------------------------\n";
        print "\n\n";
        $file = fopen($output, "w");
        foreach ($getdomain as $domain) {
            if(die_or_alive($domain) == "200") {
                print "   [ " . $GLOBALS['green'] . "LIVE" . $GLOBALS['clear'] . " ] " . $domain . "\n";
                fwrite($file, $domain . "\r\n");
            } else {
                print "   [ " . $GLOBALS['red'] . "DEAD" . $GLOBALS['clear'] . " ] " . $domain . "\n";
            }
        }
    } else {
        print "\n";
        print "   [ + ] No Domain Found\n";
        print "\n";
    }
} else {
    banner();
    print "\nInvalid option, see --help for usage\n";
    exit();
}
