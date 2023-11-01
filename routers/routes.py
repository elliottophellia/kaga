import time
import socket
import validators
from fastapi import APIRouter, Request, Form
from services.utils import fetch_data
from starlette.responses import HTMLResponse

router = APIRouter()

@router.get("/", response_class=HTMLResponse)
async def read_items():
    with open('views/index.html', 'r') as file:
        html_content = file.read()
    return HTMLResponse(content=html_content, status_code=200)

@router.api_route("/api/reverseip", methods=["GET", "POST", "PUT"])
async def reverseapi(request: Request, domain: str = Form(None)):

    domain = domain or request.query_params.get("domain")
    
    if not domain or (not validators.domain(domain) and not validators.ipv4(domain)):
        return {
            "ReqStatus": 500,
            "ReqMethod": request.method,
            "Message": "Invalid domain or IP address"
        }
    
    start_time = time.perf_counter()

    response = await fetch_data(domain)
    
    end_time = time.perf_counter()
    
    req_time = end_time - start_time
    
    if not response:
        return {
            "ReqStatus": 500,
            "ReqMethod": request.method,
            "Message": "Failed to fetch data"
        }
    
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