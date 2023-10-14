
import time
import httpx
import socket
import json
from lxml import html
from fastapi import FastAPI, Request, Form
from fastapi.responses import HTMLResponse

app = FastAPI()

async def fetch_data(domain):
    async with httpx.AsyncClient(verify=False, timeout=None) as client:
        headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"}
        query = {"remoteAddress": domain, "key": "", "_": ""}
        yougetsignal = await client.post("https://domains.yougetsignal.com/domains.php", data=query, headers=headers)
        yougetsignal = json.loads(yougetsignal.text).get("domainArray", [])
        yougetsignal = [domain[0] for domain in yougetsignal]

        ipchaxun = await client.get("https://ipchaxun.com/"+ socket.gethostbyname(domain) +"/")
        ipchaxun = html.fromstring(ipchaxun.text)
        ipchaxun = ipchaxun.xpath("//p//span[@class='date']/following-sibling::a[starts-with(@href, '/')]/text()")

        webscan = await client.get("https://api.webscan.cc/query/"+ domain +"/")
        if webscan.text:
            webscan = json.loads(webscan.text)
            webscan = [domain['domain'] for domain in webscan]
        else:
            webscan = []

        hackertarget = await client.get("https://api.hackertarget.com/reverseiplookup/?q=" + domain)
        hackertarget = hackertarget.text.split('\n')

        combined_results = list(set(yougetsignal + ipchaxun + webscan + hackertarget))

        return combined_results

@app.get("/", response_class=HTMLResponse)
async def read_items():
    return """
<!doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Tulip - Reverse IP Lookup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body>
<div class="bg-gradient-to-r from-pink-300 via-purple-300 to-indigo-400">
  <section class="p-4 flex flex-col justify-center min-h-screen max-w-md mx-auto">
    <div class="p-6 bg-white">
      <div class="flex items-center justify-center m-1 mb-9">
      <h1 class="tracking-wide text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-300 via-purple-300 to-indigo-400">Reverse IP Lookup</h1>
      </div>
      <form action="/api/reverseip" method="GET" class="flex flex-col">
        <div class="mb-4">
          <input class="shadow appearance-none border w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="domain" name="domain" type="text" placeholder="Enter Domain or IP">
        </div>
        <div class="flex items-center justify-center">
          <button class="bg-gradient-to-r from-pink-300 via-purple-300 to-indigo-400 text-white font-bold py-2 px-4 focus:outline-none focus:shadow-outline" type="submit">Submit</button>
        </div>
      </form>
    </div>
    <div class="flex items-center justify-center" id="resultContainer" style="display: none;">
    <textarea class="appearance-none w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="result" name="result" rows="7" readonly></textarea>
    </div>
    <div class="flex justify-between mt-4" id="buttonExtra" style="display: none;">
      <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-1/2 mr-2" id="copyButton">Copy</button>
      <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-1/2 ml-2" id="saveButton">Save</button>
    </div>
  </section>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $('form').on('submit', function(event){
            event.preventDefault();
            var submitButton = $(this).find('button[type="submit"]');
            var originalButtonText = submitButton.text();
            submitButton.text('Wait a minute ;)');

            $.ajax({
                url: '/api/reverseip',
                type: 'GET',
                data: $(this).serialize(),
                success: function(data){
                    $('#result').val(JSON.stringify(data.ReqResult.ResultList, null, 2));
                    $('#resultContainer').show();
                    submitButton.text(originalButtonText);
                },
                error: function() {
                    submitButton.text(originalButtonText);
                }
            });
        });
    });
</script>
</body>
</html>
    """
@app.api_route("/api/reverseip", methods=["GET", "POST", "PUT"])
async def reverseapi(request: Request, domain: str = Form(None)):
    start_time = time.time()
    if request.method == "GET":
        domain = request.query_params.get("domain")
    if not domain:
        return {"ReqStatus": 400}
    response = await fetch_data(domain)
    end_time = time.time()
    req_time = end_time - start_time
    if not response:
        return {"ReqStatus": 500}
    return {
            "ReqTime": req_time,
            "ReqStatus": 200,
            "ReqMethod": request.method,
            "ReqAddress": domain,
            "ReqIpAddress": socket.gethostbyname(domain),
            "ReqResult": {
                "ResultTotal": len(response),
                "ResultList": response,
            },
    }
