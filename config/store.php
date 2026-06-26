<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP Cart Session
    |--------------------------------------------------------------------------
    |
    | MCP tools have no HTTP session, so the AI-driven cart is keyed by this
    | stable identifier. All cart/checkout tools share this one cart.
    |
    */

    'mcp_cart_session' => env('MCP_CART_SESSION', 'mcp-assistant'),

];
