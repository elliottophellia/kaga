<?php

// Retrieve the selected API version from the cookie or set a default value
$apiVersion = isset($_COOKIE['apiVersion']) ? $_COOKIE['apiVersion'] : '1';
$ThemeS = isset($_COOKIE['ThemeS']) ? $_COOKIE['ThemeS'] : 'lightMode';

// function to generate random user agent
function user_agent()
{
    $user_agent = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Linux; Android 10; STK-LX1 Build/HONORSTK-LX1; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/107.0.5304.114 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; Android 13; SM-G998B Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/107.0.5304.105 Mobile Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 13.0; rv:107.0) Gecko/20100101 Firefox/107.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_0_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/107.0.5304.101 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (iPad; CPU OS 16_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/107.0.5304.101 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows Mobile 10; Android 10.0; Microsoft; Lumia 950XL) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Mobile Safari/537.36 Edge/40.15254.603',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 EdgiOS/107.1418.52 Mobile/15E148 Safari/605.1.15'
    );
    return $user_agent[array_rand($user_agent)];
}

// function to validate IP address and domain name
function checkIP($string)
{
    if (filter_var($string, FILTER_VALIDATE_URL) !== false) {
        $regex = preg_replace('/(http|https):\/\//', '', (string) $string);
        $domain = preg_replace('/\/.*/', '', $regex);
        $getIP = gethostbyname($domain);
        return $getIP;
    } elseif (filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
        return $string;
    } else {
        return false;
    }
}

function request($ip)
{
    $ch = curl_init();
    curl_setopt(
        $ch,
        CURLOPT_URL,
        $GLOBALS['apiVersion'] == 1 ? 'http://ip.yqie.com/iptodomain.aspx?ip=' . $ip : ($GLOBALS['apiVersion'] == 2 ? 'https://ipchaxun.com/' . $ip . '/' : ($GLOBALS['apiVersion'] == 3 ? 'https://api.webscan.cc/query/' . $ip : ($GLOBALS['apiVersion'] == 4 ? 'https://reverseip.rei.my.id/api?v1=' . $ip : '')))
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, user_agent());
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected API version from the form
    if (isset($_POST['apiVersion'])) {
        $apiVersion = $_POST['apiVersion'];

        // Set the API version in the cookie
        setcookie('apiVersion', $apiVersion, time() + (86400 * 30), '/'); // Cookie expires in 30 days
    }

    // Get the selected background color from the form
    if (isset($_POST['ThemeS'])) {
        $ThemeS = $_POST['ThemeS'];

        // Set the background color in the cookie
        setcookie('ThemeS', $ThemeS, time() + (86400 * 30), '/'); // Cookie expires in 30 days
    }

    if (isset($_POST['ipSubmit'])) {
        $ipSubmit = $_POST['ipSubmit'];

        // Validate the IP address
        $ip = checkIP($ipSubmit);

        if ($ip !== false) {
            // Get the domain names
            $request = request($ip);

            if ($apiVersion == 1) {
                $dom = new DOMDocument();
                $dom->loadHTML($request);
                $xpath = new DOMXPath($dom);

                $tdElements = $xpath->query("//td[contains(concat(' ', normalize-space(@class), ' '), ' blue t_l ')]");
                $domains = [];

                foreach ($tdElements as $tdElement) {
                    $textContent = $tdElement->textContent;
                    if (preg_match('/\b(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}\b/', $textContent, $matches)) {
                        $domains[] = $matches[0];
                    }
                }
                $domains = implode(PHP_EOL, $domains);
            } elseif ($apiVersion == 2) {
                $dom = new DOMDocument();
                $dom->loadHTML($request);

                $xpath = new DOMXPath($dom);
                $query = '//p//span[@class="date"]/following-sibling::a[starts-with(@href, "/")]/text()';

                $nodeList = $xpath->query($query);
                $domainNames = [];

                foreach ($nodeList as $node) {
                    $domainNames[] = $node->nodeValue;
                }
                $domains = implode(PHP_EOL, $domainNames);
            } elseif ($apiVersion == 3) {
                $json = json_decode($request, true);
                $domains = [];
                foreach ($json as $item) {
                    if (isset($item['domain'])) {
                        $domains[] = $item['domain'];
                    }
                }
                $domains = implode(PHP_EOL, $domains);
            } elseif ($apiVersion == 4) {
                $json = json_decode($request, true);
                $domains = [];
                if (isset($json['listDomain'])) {
                    $domains = $json['listDomain'];
                }
                $domains = implode(PHP_EOL, $domains);
            } else {
                $domains = 'Invalid API version';
            }
        } else {
            $domains = 'Invalid IP address or domain name! If you using domain name, please add http:// or https:// before domain name';
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Tulip - Free Unlimited Reverse IP Lookup Tools</title>
    <meta name="description" content="Tulip is a free unlimited reverse IP lookup tool. It will pull up all registered domain names using the same IP address.">
    <meta name="keywords" content="reverse ip lookup, reverse ip, reverse dns, reverse ip domain check, reverse ip lookup tool, reverse ip lookup domain, reverse ip lookup free, reverse ip lookup whois, reverse ip lookup location, reverse ip lookup api, reverse ip lookup domain name, reverse ip lookup domain check, reverse ip lookup domain tool, reverse ip lookup domain free, reverse ip lookup domain name tool, reverse ip lookup domain owner, reverse ip lookup domain ip, reverse ip lookup domain bulk, reverse ip lookup domain list, reverse ip lookup domain tool free, reverse ip lookup domain name free, reverse ip lookup domain name owner, reverse ip lookup domain name location, reverse ip lookup domain name api, reverse ip lookup domain name whois, reverse ip lookup domain name bulk, reverse ip lookup domain name list, reverse ip lookup domain name tool free, reverse ip lookup domain name location free, reverse ip lookup domain name api free, reverse ip lookup domain name whois free, reverse ip lookup domain name bulk free, reverse ip lookup domain name list free, reverse ip lookup domain name tool free">
    <meta name="author" content="elliottophellia">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?php echo $ThemeS; ?>">
    <link rel="icon" href="./assets/img/tulip.ico">
    <link rel="apple-touch-icon" href="./assets/img/tulip.ico">
    <link rel="shortcut icon" href="./assets/img/tulip.ico">
    <style>
        @import url('./assets/css/style.css');
        @import url('./assets/css/bootstrap.min.css');

        body {
            background: var(--<?php echo ($ThemeS == 'darkMode') ? 'dark' : 'light'; ?>);
            color: var(--<?php echo ($ThemeS == 'darkMode') ? 'light' : 'dark'; ?>);
        }

        button {
            background: var(--<?php echo ($ThemeS == 'darkMode') ? 'dark-pink' : 'pink'; ?>);
            border: 3px solid var(--<?php echo ($ThemeS == 'darkMode') ? 'transparent' : 'dark'; ?>);
        }

        dialog {
            border: 5px solid var(--<?php echo ($ThemeS == 'darkMode') ? 'light' : 'dark'; ?>);
        }

        input.IPAddress {
            border: 5px solid var(--<?php echo ($ThemeS == 'darkMode') ? 'light' : 'dark'; ?>);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-transparent sticky-top">
        <button data-open-modal>Settings</button>
        <button data-open-donate>Supports</button>
    </nav>
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="text-center">
                    <img src="./assets/img/tulip.png" />
                    <h1>Tulip</h1>
                    <p>Free Unlimited Reverse IP Lookup Tools</p>
                    <?php
                    if (isset($_POST['ipSubmit'])) { ?>

                        <textarea id="myTextarea" readonly><?php echo $domains; ?></textarea>
                        <br />
                        <button type="button" onclick="window.location.href='?'">Home</button>
                        <button type="button" id="copyButton">Copy</button>
                        <button type="button" id="saveButton">Save</button>


                    <?php } else { ?>
                        <form action="index.php" method="post">
                            <input class="IPAddress" type="text" name="ipSubmit" placeholder="IP Address/Domain..." required><br /><br />
                            <button type="submit">Submit</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <dialog data-donate>
        <h4 class="text-center">Buy me a coffee</h4>
        <table>
            <tbody>
                <tr>
                    <td>Paypal</td>
                    <td>:</td>
                    <td>
                        &nbsp;<a href="https://www.paypal.me/elliottophellia" target="_blank">https://www.paypal.me/elliottophellia</a>
                    </td>
                </tr>
                <tr>
                    <td>Trakteer</td>
                    <td>:</td>
                    <td>
                        &nbsp;<a href="https://trakteer.id/elliottophellia" target="_blank">https://trakteer.id/elliottophellia</a>
                    </td>
                </tr>
                <tr>
                    <td>Saweria</td>
                    <td>:</td>
                    <td>
                        &nbsp;<a href="https://saweria.co/elliottophellia" target="_blank">https://saweria.co/elliottophellia</a>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <div class="text-center">
            <button data-close-donate>Cancel</button>
        </div>
    </dialog>
    <dialog data-modal>
        <form action="index.php" method="post">
            <h4 class="text-center">Settings</h4>
            <table>
                <tbody>
                    <tr>
                        <td>API Version</td>
                        <td>:</td>
                        <td>
                            &nbsp;<input type='radio' name='apiVersion' value='1' <?php if ($apiVersion === '1') echo 'checked'; ?>> V1</input>
                            <input type='radio' name='apiVersion' value='2' <?php if ($apiVersion === '2') echo 'checked'; ?>> V2</input>
                            <input type='radio' name='apiVersion' value='3' <?php if ($apiVersion === '3') echo 'checked'; ?>> V3</input>
                            <input type='radio' name='apiVersion' value='4' <?php if ($apiVersion === '4') echo 'checked'; ?>> V4</input>
                        </td>
                    </tr>
                    <tr>
                        <td>Website Theme</td>
                        <td>:</td>
                        <td>
                            &nbsp;<input type='radio' name='ThemeS' value='lightMode' <?php if ($ThemeS === 'lightMode') echo 'checked'; ?>> Light mode</input>
                            <input type='radio' name='ThemeS' value='darkMode' <?php if ($ThemeS === 'darkMode') echo 'checked'; ?>> Dark mode</input>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br>
            <div class="text-center">
                <button data-close-modal>Cancel</button>
                <button type="submit">Submit</button>
            </div>
        </form>
    </dialog>
    <div class="fixed-bottom text-center font-italic">- elliottophellia -</div>
    <script>
        const dataOpenModal = document.querySelector("[data-open-modal]")
        const dataCloseModal = document.querySelector("[data-close-modal]")
        const modal = document.querySelector("[data-modal]")

        const dataOpenDonate = document.querySelector("[data-open-donate]")
        const dataCloseDonate = document.querySelector("[data-close-donate]")
        const donate = document.querySelector("[data-donate]")

        dataOpenModal.addEventListener("click", () => {
            modal.showModal()
        })

        dataOpenDonate.addEventListener("click", () => {
            donate.showModal()
        })

        dataCloseModal.addEventListener("click", () => {
            modal.close()
        })

        dataCloseDonate.addEventListener("click", () => {
            donate.close()
        })

        modal.addEventListener("click", e => {
            const modalDimensions = modal.getBoundingClientRect()
            if (
                e.clientX < modalDimensions.left ||
                e.clientX > modalDimensions.right ||
                e.clientY < modalDimensions.top ||
                e.clientY > modalDimensions.bottom
            ) {
                modal.close()
            }
        })

        donate.addEventListener("click", e => {
            const donateDimensions = donate.getBoundingClientRect()
            if (
                e.clientX < donateDimensions.left ||
                e.clientX > donateDimensions.right ||
                e.clientY < donateDimensions.top ||
                e.clientY > donateDimensions.bottom
            ) {
                donate.close()
            }
        })

        // Function to copy text to clipboard
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        // Add click event listener to the copy button
        const copyButton = document.getElementById('copyButton');
        copyButton.addEventListener('click', function() {
            const textarea = document.getElementById('myTextarea');
            copyToClipboard(textarea.value);
            alert('Text copied to clipboard!');
        });

        // Function to save text as .txt file
        function saveTextAsFile(text, filename) {
            const blob = new Blob([text], {
                type: 'text/plain'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            a.click();
        }

        // Add click event listener to the save button
        const saveButton = document.getElementById('saveButton');
        saveButton.addEventListener('click', function() {
            const textarea = document.getElementById('myTextarea');
            const text = textarea.value;
            const randomNumber = Math.floor(Math.random() * 100000) + 1;
            const filename = 'elliottophellia_'+randomNumber+'.txt';
            saveTextAsFile(text, filename);
        });
    </script>
</body>

</html>