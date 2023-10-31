import asyncio
import socket
from requests_html import AsyncHTMLSession


async def fetch_data(domain):
    results = []
    client = AsyncHTMLSession()

    try:
        ip_address = socket.gethostbyname(domain)
    except socket.gaierror:
        print(f"Unable to resolve domain: {domain}")
        ip_address = None

    if ip_address is None:
        return results

    webscancc_payload = {"domain": ip_address}

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

    # Prepare all the requests
    requests = [
        client.get("https://ipchaxun.com/" + ip_address + "/"),
        client.post("https://webscan.cc/", data=webscancc_payload, headers=webscancc_headers),
        client.get("https://site.ip138.com/" + ip_address + "/"),
        client.get("https://rapiddns.io/s/" + ip_address + "?full=1#result"),
    ]

    # Send all the requests concurrently
    responses = []
    for request in requests:
        try:
            response = await asyncio.wait_for(request, timeout=3)
            responses.append(response)
        except asyncio.TimeoutError:
            print("A request timed out and was skipped.")
            responses.append(None)
        except Exception as e:
            print(f"A request encountered an error: {e}")
            responses.append(None)

    # Process the responses
    for i, response in enumerate(responses):
        if response is None:
            print(f"Request {i} was not successful and was skipped.")
            continue
        if isinstance(response, Exception):
            print(f"Request {i} encountered an error: {response}")
            continue
        if response.status_code == 200:
            if i == 0:  # ipchaxun
                ipchaxun_div = response.html.find("#J_domain", first=True)
                if ipchaxun_div:
                    ipchaxun_domains = ipchaxun_div.absolute_links
                    for ipchaxun_domain in ipchaxun_domains:
                        domain = ipchaxun_domain.replace(
                            "https://ipchaxun.com/", ""
                        ).strip("/")
                        results.append(domain)
            elif i == 1:  # webscancc
                webscancc_a_tags = response.html.find(".domain")
                if webscancc_a_tags:
                    results.extend(
                        [
                            a.attrs["href"].replace("/site_", "").replace("/", "")
                            for a in webscancc_a_tags
                        ]
                    )
            elif i == 2:  # ip138
                ip138_ul_tag = response.html.find("#list", first=True)
                if ip138_ul_tag:
                    ip138_domain_tags = ip138_ul_tag.absolute_links
                    for tag in ip138_domain_tags:
                        domain = tag.replace("https://site.ip138.com/", "").strip("/")
                        results.extend([domain])
            elif i == 3:  # rapiddns
                rapiddns_table = response.html.find(
                    ".table.table-striped.table-bordered", first=True
                )
                if rapiddns_table:
                    rapiddns_tr_tags = rapiddns_table.find("tr")
                    results.extend(
                        [
                            tr.find("td", first=True).text
                            for tr in rapiddns_tr_tags
                            if tr.find("td")
                        ]
                    )

    return list(dict.fromkeys(results))
