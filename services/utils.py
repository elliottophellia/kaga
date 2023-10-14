import json
import socket
import httpx
from lxml import html

async def fetch_data(domain):
    async with httpx.AsyncClient(verify=False, timeout=None) as client:
        headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"}
        query = {"remoteAddress": domain, "key": "", "_": ""}
        
        # Consolidate the API calls into a single function
        async def fetch_api_data(url, method="get", data=None, headers=None, json_path=None, text_split=False):
            response = await client.request(method, url, data=data, headers=headers)
            if response.status_code == 200:
                if json_path:
                    return json.loads(response.text).get(json_path, [])
                elif text_split:
                    return response.text.split('\n')
                else:
                    return html.fromstring(response.text).xpath("//p//span[@class='date']/following-sibling::a[starts-with(@href, '/')]/text()")
            else:
                return []

        yougetsignal = await fetch_api_data("https://domains.yougetsignal.com/domains.php", "post", data=query, headers=headers, json_path="domainArray")
        ipchaxun = await fetch_api_data("https://ipchaxun.com/"+ socket.gethostbyname(domain) +"/")
        webscan = await fetch_api_data("https://api.webscan.cc/query/"+ domain +"/", json_path="domain")
        hackertarget = await fetch_api_data("https://api.hackertarget.com/reverseiplookup/?q=" + domain, text_split=True)

        combined_results = list(set(yougetsignal + ipchaxun + webscan + hackertarget))
        return combined_results