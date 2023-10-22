import asyncio
import httpx
import socket
from bs4 import BeautifulSoup

async def fetch_data(domain):
    results = []
    async with httpx.AsyncClient(verify=False, timeout=7.0) as client:
        webscancc_payload = {"domain": socket.gethostbyname(domain)}

        webscancc_headers = {
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.5",
            "Cache-Control": "max-age=0",
            "Content-Type": "application/x-www-form-urlencoded",
            "Origin": "https://webscan.cc",
            "Referer": "https://webscan.cc/",
            "Sec-Ch-Ua": '"Chromium";v="118", "Brave";v="118", "Not=A?Brand";v="99"',
            "Sec-Ch-Ua-Mobile": "?0",
            "Sec-Ch-Ua-Platform": '"Windows"',
            "Sec-Fetch-Dest": "document",
            "Sec-Fetch-Mode": "navigate",
            "Sec-Fetch-Site": "same-origin",
            "Sec-Fetch-User": "?1",
            "Sec-Gpc": "1",
            "Upgrade-Insecure-Requests": "1",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36",
        }

        common_headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36"
        }

        # Prepare all the requests
        requests = [
            client.request(
                url="https://ipchaxun.com/" + socket.gethostbyname(domain) + "/",
                method="get",
                headers=common_headers,
            ),
            client.request(
                url="https://webscan.cc/",
                method="post",
                data=webscancc_payload,
                headers=webscancc_headers,
            ),
            client.request(
                url="https://site.ip138.com/" + socket.gethostbyname(domain) + "/",
                method="get",
                headers=common_headers,
            ),
            client.request(
                url="https://rapiddns.io/s/"
                + socket.gethostbyname(domain)
                + "?full=1#result",
                method="get",
                headers=common_headers,
            ),
        ]

        # Send all the requests concurrently
        try:
            responses = await asyncio.gather(*requests, return_exceptions=True)
        except httpx.ConnectTimeout:
            print("A request timed out.")

        # Process the responses
        for i, response in enumerate(responses):
            if isinstance(response, Exception):
                print(f"Request {i} encountered an error: {response}")
                continue
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, "html.parser")
                if i == 0:  # ipchaxun
                    ipchaxun_div = soup.find("div", id="J_domain")
                    if ipchaxun_div:
                        ipchaxun_domains = ipchaxun_div.find_all("a")
                        for ipchaxun_domain in ipchaxun_domains:
                            results.append(ipchaxun_domain.get("href").strip("/"))
                elif i == 1:  # webscancc
                    webscancc_a_tags = soup.find_all("a", class_="domain")
                    if webscancc_a_tags:
                        results.extend(
                            [
                                a["href"].replace("/site_", "").replace("/", "")
                                for a in webscancc_a_tags
                            ]
                        )
                elif i == 2:  # ip138
                    ip138_ul_tag = soup.find("ul", id="list")
                    if ip138_ul_tag:
                        ip138_domain_tags = ip138_ul_tag.find_all("a")
                        results.extend(
                            [tag["href"].strip("/") for tag in ip138_domain_tags]
                        )
                elif i == 3:  # rapiddns
                    rapiddns_table = soup.find(
                        "table", {"class": ["table", "table-striped", "table-bordered"]}
                    )
                    if rapiddns_table:
                        rapiddns_tr_tags = rapiddns_table.find_all("tr")
                        results.extend(
                            [
                                tr.find_all("td")[0].text
                                for tr in rapiddns_tr_tags
                                if tr.find_all("td")
                            ]
                        )

    return list(dict.fromkeys(results))