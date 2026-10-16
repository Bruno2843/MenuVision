<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Base URL
    |--------------------------------------------------------------------------
    |
    | The origin the QR codes point at. It is written into the PNG itself, so a
    | phone has to be able to reach it: leave it empty and the QR encodes
    | whatever host served the page, which only works when the phone is opening
    | the menu from the same machine (Herd, `127.0.0.1`) and never from a device
    | on the Wi-Fi.
    |
    | To try MenuVision from a phone or a tablet on the same local network,
    | set this to the LAN address of the machine running the server:
    |
    |     QR_BASE_URL=http://192.168.1.50:8000
    |
    | Only the origin goes here, never the `/menu/TOKEN` part: the path and the
    | token are still built from the named route, so the codes already handed
    | out keep working and no row has to change when the address does.
    |
    | The value is ignored when it is empty or is not a usable http(s) origin,
    | in which case the QR falls back to the host that served the request.
    |
    */

    'qr_base_url' => (string) env('QR_BASE_URL', ''),

];
