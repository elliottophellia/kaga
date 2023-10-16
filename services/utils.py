import json
import httpx
import socket
from bs4 import BeautifulSoup

async def fetch_data(domain):
    results = []
    async with httpx.AsyncClient(verify=False, timeout=None) as client:

        yougetsignal_headers = {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
        }

        yougetsignal_payload = {
          "remoteAddress": socket.gethostbyname("rei.my.id"),
          "key": "",
          "_": "",
        }

        yougetsignal = await client.request(
          url="https://domains.yougetsignal.com/domains.php",
          method="post",
          data=yougetsignal_payload,
          headers=yougetsignal_headers,
        )

        response_json = json.loads(yougetsignal.text)

        if "domainArray" in response_json:
          for item in response_json["domainArray"]:
           results.append(item[0])

        ipchaxun = await client.request(
          url="https://ipchaxun.com/" + socket.gethostbyname("rei.my.id") + "/", method="get"
        )

        ipchaxun_soup = BeautifulSoup(ipchaxun.text, "html.parser")

        ipchaxun_div = ipchaxun_soup.find("div", id="J_domain")

        ipchaxun_domains = ipchaxun_div.find_all("a")

        for ipchaxun_domain in ipchaxun_domains:
          results.append(ipchaxun_domain.get("href").strip("/"))

        webscancc_payload = {"domain": socket.gethostbyname("rei.my.id")}

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

        webscancc = await client.request(
          url="https://webscan.cc/",
          method="post",
          data=webscancc_payload,
          headers=webscancc_headers,
        )

        webscancc_soup = BeautifulSoup(webscancc.text, "html.parser")

        webscancc_a_tags = webscancc_soup.find_all("a", class_="domain")

        results.extend([
          a["href"].replace("/site_", "").replace("/", "") for a in webscancc_a_tags
        ])

        ip138 = await client.request(
          url="https://site.ip138.com/" + socket.gethostbyname("rei.my.id") + "/",
          method="get",
        )

        ip138_soup = BeautifulSoup(ip138.text, "html.parser")

        ip138_ul_tag = ip138_soup.find("ul", id="list")

        ip138_domain_tags = ip138_ul_tag.find_all("a")

        results.extend([tag["href"].strip("/") for tag in ip138_domain_tags])

        rapiddns = await client.request(
          url="https://rapiddns.io/s/" + socket.gethostbyname("rei.my.id") + "?full=1#result",
          method="get",
        )

        rapiddns_soup = BeautifulSoup(rapiddns.text, "html.parser")

        rapiddns_table = rapiddns_soup.find(
          "table", {"class": ["table", "table-striped", "table-bordered"]}
        )

        rapiddns_tr_tags = rapiddns_table.find_all("tr")

        results.extend([
          tr.find_all("td")[0].text for tr in rapiddns_tr_tags if tr.find_all("td")
        ])

    return list(dict.fromkeys(results))