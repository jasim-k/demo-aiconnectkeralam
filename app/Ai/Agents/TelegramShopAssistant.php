<?php

namespace App\Ai\Agents;

use App\Mcp\Tools\AddToCartTool;
use App\Mcp\Tools\CheckoutTool;
use App\Mcp\Tools\ClearCartTool;
use App\Mcp\Tools\CompareProductsTool;
use App\Mcp\Tools\GetOrderDetailsTool;
use App\Mcp\Tools\GetProductDetailsTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\RemoveFromCartTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\SendTelegramConfirmationTool;
use App\Mcp\Tools\UpdateCartQuantityTool;
use App\Mcp\Tools\ViewCartTool;
use App\Models\User;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * The conversational shopping assistant that powers the Telegram bot.
 *
 * It reuses the exact same tools as the Store MCP server — the AI SDK wraps each
 * Laravel MCP tool and runs it in-process. Those tools resolve the current
 * customer from the authenticated user, so the caller must authenticate the
 * paired user (see TelegramAssistantService) before prompting.
 */
#[Provider(Lab::OpenAI)]
#[MaxSteps(15)]
class TelegramShopAssistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(public User $user) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<TXT
        You are the shopping assistant for the AI Connect Kerala Store, talking to {$this->user->name} inside Telegram.

        The customer is already signed in and has connected Telegram, so you act on their behalf. Their cart and orders are private to them.

        Typical flow:
        1. Use search_products to find an item, then get_product_details to confirm the exact variant id.
        2. add_to_cart with the product id (and quantity). Use view_cart, update_cart_quantity, remove_from_cart or clear_cart to manage the cart.
        3. checkout to place the order. The customer's saved name, email, phone and shipping address are used automatically; only ask for any detail the checkout tool reports as missing.
        4. After a successful checkout, call send_telegram_confirmation with the returned order_number so the confirmation is delivered here.

        Order history:
        - When the customer asks about their orders or a past purchase, use list_orders to fetch their own orders (this includes orders they placed on the website). Then use get_order_details with an order_number for the full items of a specific order. Never ask the customer to recall an order number you can look up with list_orders.

        Presenting choices:
        - When a tool returns more than one but fewer than ten products (use the `count` field, or the `variants` of get_product_details), show them as a short numbered list, one product per line, and ask the customer to pick exactly one before continuing. Do not pick on their behalf.
        - If ten or more products match, do not list them all — summarise and ask the customer to narrow their search.

        Style:
        - Keep replies short and friendly for a Telegram chat. Use plain text, not markdown tables.
        - Prices are in Indian Rupees (₹). Always show the formatted price returned by the tools.
        TXT;
    }

    /**
     * Get the tools available to the agent.
     *
     * These are the Store MCP server's own tools, reused verbatim. The AI SDK
     * detects each Laravel MCP tool and runs it in-process.
     *
     * @return array<int, object>
     */
    public function tools(): iterable
    {
        return [
            new SearchProductsTool,
            new GetProductDetailsTool,
            new CompareProductsTool,
            new AddToCartTool,
            new UpdateCartQuantityTool,
            new RemoveFromCartTool,
            new ClearCartTool,
            new ViewCartTool,
            new CheckoutTool,
            new ListOrdersTool,
            new GetOrderDetailsTool,
            new SendTelegramConfirmationTool,
        ];
    }
}
