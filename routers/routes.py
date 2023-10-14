import time
import socket
from fastapi import APIRouter, Request, Form
from services.utils import fetch_data
from starlette.responses import HTMLResponse

router = APIRouter()

@router.get("/", response_class=HTMLResponse)
async def read_items():
    # Use 'with' statement to automatically close the file after reading
    with open('views/index.html', 'r') as file:
        html_content = file.read()
    return HTMLResponse(content=html_content, status_code=200)

@router.api_route("/api/reverseip", methods=["GET", "POST", "PUT"])
async def reverseapi(request: Request, domain: str = Form(None)):
    # Use 'time.perf_counter()' for more accurate timing
    start_time = time.perf_counter()
    
    # Consolidate the domain fetching logic
    domain = domain or request.query_params.get("domain")
    
    if not domain:
        return {"ReqStatus": 400}
    
    response = await fetch_data(domain)
    
    # Use 'time.perf_counter()' for more accurate timing
    end_time = time.perf_counter()
    req_time = end_time - start_time
    
    if not response:
        return {"ReqStatus": 500}
    
    # Consolidate the return statement
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