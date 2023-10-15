import json
import httpx
import socket
from lxml import html
from bs4 import BeautifulSoup

async def fetch_data(domain):
    async with httpx.AsyncClient(verify=False, timeout=None) as client:
        headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"}
        query = {"remoteAddress": domain, "key": "", "_": ""}

        async def fetch_api_data(url, method="get", data=None, headers=None, json_path=None, text_split=False, soup_parse=False):
            response = await client.request(method, url, data=data, headers=headers)
            if response.status_code == 200:
                if json_path:
                    return json.loads(response.text).get(json_path, [])
                elif text_split:
                    return response.text.split('\n')
                elif soup_parse:
                    soup = BeautifulSoup(response.text, 'html.parser')
                    ul_tag = soup.find('ul', id='list')
                    if ul_tag is not None:
                         domain_tags = ul_tag.find_all('a')
                         return [tag['href'].strip('/') for tag in domain_tags]
                    else:
                         return []
                else:
                    return html.fromstring(response.text).xpath("//p//span[@class='date']/following-sibling::a[starts-with(@href, '/')]/text()")
            else:
                return []

        yougetsignal = await fetch_api_data("https://domains.yougetsignal.com/domains.php", "post", data=query, headers=headers, json_path="domainArray")
        ipchaxun = await fetch_api_data("https://ipchaxun.com/"+ socket.gethostbyname(domain) +"/")
        webscan = await fetch_api_data("https://api.webscan.cc/query/"+ domain +"/", json_path="domain")
        hackertarget = await fetch_api_data("https://api.hackertarget.com/reverseiplookup/?q=" + domain, text_split=True)
        ip138 = await fetch_api_data("https://site.ip138.com/" + socket.gethostbyname(domain) + "/", soup_parse=True)

        # Ensure all results are lists of strings (domains)
        yougetsignal = [item for sublist in yougetsignal for item in sublist] if yougetsignal and isinstance(yougetsignal[0], list) else yougetsignal
        ipchaxun = [item for sublist in ipchaxun for item in sublist] if ipchaxun and isinstance(ipchaxun[0], list) else ipchaxun
        webscan = [item for sublist in webscan for item in sublist] if webscan and isinstance(webscan[0], list) else webscan
        hackertarget = [item for sublist in hackertarget for item in sublist] if hackertarget and isinstance(hackertarget[0], list) else hackertarget
        ip138 = [item for sublist in ip138 for item in sublist] if ip138 and isinstance(ip138[0], list) else ip138

        # Combine all results into a single list of strings (domains)
        combined_results = []
        combined_results.extend(yougetsignal)
        combined_results.extend(ipchaxun)
        combined_results.extend(webscan)
        combined_results.extend(hackertarget)
        combined_results.extend(ip138)

        # Remove duplicates
        combined_results = list(set(combined_results))

        # Filter out empty strings and not a valid domain
        combined_results = [domain for domain in combined_results if domain and domain.count('.') >= 1]

        return combined_results