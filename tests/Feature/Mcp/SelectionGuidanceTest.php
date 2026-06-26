<?php

use App\Mcp\Servers\StoreServer;
use App\Mcp\Tools\CompareProductsTool;
use App\Mcp\Tools\GetProductDetailsTool;
use App\Mcp\Tools\SearchProductsTool;
use Laravel\Mcp\Server\Attributes\Instructions;

it('tells the assistant to offer a single-choice selection in each list tool description', function (string $tool) {
    $description = app($tool)->description();

    expect($description)
        ->toContain('single-choice')
        ->toContain('selection');
})->with([
    SearchProductsTool::class,
    CompareProductsTool::class,
    GetProductDetailsTool::class,
]);

it('carries the single-selection rule in the server instructions', function () {
    $attribute = (new ReflectionClass(StoreServer::class))
        ->getAttributes(Instructions::class)[0]
        ->newInstance();

    expect($attribute->value)
        ->toContain('single-choice selection')
        ->toContain('fewer than ten');
});
