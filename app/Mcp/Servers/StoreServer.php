<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddToCartTool;
use App\Mcp\Tools\CheckoutTool;
use App\Mcp\Tools\ClearCartTool;
use App\Mcp\Tools\CompareProductsTool;
use App\Mcp\Tools\GetOrderDetailsTool;
use App\Mcp\Tools\GetProductDetailsTool;
use App\Mcp\Tools\RemoveFromCartTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\SendTelegramConfirmationTool;
use App\Mcp\Tools\UpdateCartQuantityTool;
use App\Mcp\Tools\ViewCartTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('AI Connect Apple Store')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
This server lets you shop the AI Connect Apple Store on the customer's behalf.

Typical flow:
1. search_products to find an item, then get_product_details to confirm the exact variant id.
2. add_to_cart with the product id (and quantity). Use view_cart, update_cart_quantity, remove_from_cart or clear_cart to manage the cart.
3. checkout with the customer's name, email, phone and shipping address to place the order.
4. send_telegram_confirmation with the returned order_number to notify the customer.

Prices are in Indian Rupees (₹). There is a single shared cart for this assistant.
TXT)]
class StoreServer extends Server
{
    protected array $tools = [
        SearchProductsTool::class,
        GetProductDetailsTool::class,
        CompareProductsTool::class,
        AddToCartTool::class,
        UpdateCartQuantityTool::class,
        RemoveFromCartTool::class,
        ClearCartTool::class,
        ViewCartTool::class,
        CheckoutTool::class,
        GetOrderDetailsTool::class,
        SendTelegramConfirmationTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
