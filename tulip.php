<?php
#--------------------------------------------------#
#                    E r r o r                     #
#--------------------------------------------------#
error_reporting(0);
#--------------------------------------------------#
#                C o m p o s e r                   #
#--------------------------------------------------#
require __DIR__ . '/vendor/autoload.php';
$ReqClient = new \GuzzleHttp\Client();
#--------------------------------------------------#
#                    C o l o r                     #
#--------------------------------------------------#
$bold = "\e[1m";
$blue = "\e[94m";
$red = "\e[91m";
$reset = "\e[0m";
$green = "\e[92m";
$yellow = "\e[93m";
#--------------------------------------------------#
#                   B a n n e r                    #
#--------------------------------------------------#
echo "
          $GLOBALS[bold] $GLOBALS[yellow]
                  ⡞⠉⠊⢱ ⣀⣀          
               ⣰⠏⠑⢷  ⡸⠋ ⠸⣄         
              ⠘⢅⡀  ⡷⠒⢧⣀⣀⡤⠊         
               ⣠⠞⠛⠉⢇⣀⣸⠁ ⠉⠳⡄        
               ⠓⡄ ⣠⡎ ⠈⢧⣄⣠⠎         
                ⠑⠊⠁⣇⣀⡀⡸            
              ⣀⣀⣀⣀  ⠘⡆             
              ⢟⠲⢄⡀⠉⠲⡄⡇             
              ⠈⠣⡀⠈⠓⢤⡈⣧             
                ⠈⠓⠢⠤⠬⢿⡀            
                       ⡇            
                       ⢸⡀           
                        ⢇           
                        ⢘⠄          
          $GLOBALS[bold] $GLOBALS[green]
                     T u l i p                      
          $GLOBALS[reset] $GLOBALS[bold] 
    Unlimited, fast, and easy Reverse IP Lookup     
          $GLOBALS[reset] $GLOBALS[red]
                  @elliottophellia        
                   $GLOBALS[reset]          
";
#--------------------------------------------------#
#                 F u n c t i o n                  #
#--------------------------------------------------#
function getip($string)
{
    if (filter_var($string, FILTER_VALIDATE_URL) !== false) {
        $regex = preg_replace('/(http|https):\/\//', '', (string) $string);
        return preg_replace('/\/.*/', '', $regex);
    } elseif (filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
        return $string;
    } else {
        echo $GLOBALS['bold'] . $GLOBALS['yellow'];
        echo "
                 Invalid URL or IP                  
                                                   
         You HAVE TO use https:// or http://        
    if you using URL as input, or it will not work  
        ";
        echo $GLOBALS['reset'] . PHP_EOL;
        exit;
    }
}
#--------------------------------------------------#
#                   M a i n                        #
#--------------------------------------------------#
$url = $argv[1] ?? readline("    Website URL/IP  : ");
$url = getip($url);
$ver = $argv[2] ?? readline("    Version 1/2/3/4 : ");
$err = "
            $GLOBALS[bold] $GLOBALS[yellow]
                 Invalid API Version                  
                                                   
          You HAVE TO use 1/2/3/4 as input,       
        empty input will be set to 1 by default
       but if you use 5 or more it will not work
                   $GLOBALS[reset]
";
$ver = $ver == "" ? 1 : ($ver > 4 ? exit($err) : $ver);

$request = new \GuzzleHttp\Psr7\Request('GET', 'https://reverseip.rei.my.id/api?v' . $ver . '=' . $url);
$promise = $ReqClient->sendAsync($request)->then(function ($response) {
    $listDomain = json_decode((string) $response->getBody(), true);
    if ($listDomain['apiCode'] == 200) {
        echo PHP_EOL;
        foreach ($listDomain['listDomain'] as $domain) {
            echo $GLOBALS['bold'] . $GLOBALS['green'];
            echo '    ' . $domain . PHP_EOL;
            echo $GLOBALS['reset'];
        }
        echo PHP_EOL . "    A total of " . $GLOBALS['green'] . count($listDomain['listDomain']) . $GLOBALS['reset'] . ' domains were discovered! ' . PHP_EOL;
        $result = readline("    You want to save the result? (y/n) : ");
        if ($result == "y" || $result == "Y" || $result == "") {
            $name = readline("    Save as : ");
            $name = $name == "" ? "tulip_" . rand() . ".txt" : $name;
            $save = fopen($name, "w");
            foreach ($listDomain['listDomain'] as $domain) {
                fwrite($save, $domain . PHP_EOL);
            }
            fclose($save);
            echo "    Successfully saved the result as " . $GLOBALS['green'] . "$name" . $GLOBALS['reset'] . PHP_EOL;
        } else {
            exit;
        }
        $validate = readline("    You want to validate the result? (y/n) : ");
        echo PHP_EOL;
        if ($validate == "y" || $validate == "Y" || $result == "") {
            $v200 = fopen("200_$name", "w");
            $v301 = fopen("301_$name", "w");
            $v500 = fopen("500_$name", "w");
            $v404 = fopen("404_$name", "w");
            foreach ($listDomain['listDomain'] as $domain) {
                $check = get_headers("http://" . $domain);
                if (strpos((string) $check[0], '200')) {
                    echo "    " . $GLOBALS['green'] . "200" . $GLOBALS['reset'] . ' - ' . $domain . PHP_EOL;
                    fwrite($v200, $domain . PHP_EOL);
                } elseif (strpos((string) $check[0], '301')) {
                    echo "    " . $GLOBALS['blue'] . "301" . $GLOBALS['reset'] . ' - ' . $domain . PHP_EOL;
                    fwrite($v301, $domain . PHP_EOL);
                } elseif (strpos((string) $check[0], '500')) {
                    echo "    " . $GLOBALS['yellow'] . "500" . $GLOBALS['reset'] . ' - ' . $domain . PHP_EOL;
                    fwrite($v500, $domain . PHP_EOL);
                } elseif (strpos((string) $check[0], '404')) {
                    echo "    " . $GLOBALS['red'] . "404" . $GLOBALS['reset'] . ' - ' . $domain . PHP_EOL;
                    fwrite($v404, $domain . PHP_EOL);
                } else {
                    echo "    " . $GLOBALS['red'] . "???" . $GLOBALS['reset'] . ' - ' . $domain . PHP_EOL;
                }
            }
            fclose($v200);
            fclose($v301);
            fclose($v500);
            fclose($v404);
            echo "
    Successfully saved the result as :
    " . $GLOBALS['green'] . "200_$name" . $GLOBALS['reset'] . "
    " . $GLOBALS['blue'] . "301_$name" . $GLOBALS['reset'] . "
    " . $GLOBALS['yellow'] . "500_$name" . $GLOBALS['reset'] . "
    " . $GLOBALS['red'] . "404_$name" . $GLOBALS['reset'] . "
    " . PHP_EOL;
        } else {
            exit;
        }
    } else {
        $err = "
            $GLOBALS[bold] $GLOBALS[red]
                Something Was Wrong

          API Request Return With Code 404
";
        exit($err);
    }
});

$promise->wait();
