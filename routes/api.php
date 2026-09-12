<?php

use App\Http\Controllers\Api\AgentGatewayController;
use App\Http\Middleware\VerifyAgentApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| AI Agent Gateway Routes (Hermes, OpenClaw, AutoGen, MCP)
|--------------------------------------------------------------------------
| Protected with X-Agent-Key header / Bearer token
*/
Route::prefix('v1/agent')
    ->middleware(VerifyAgentApiKey::class)
    ->group(function () {
        // Discovery endpoint: JSON tool schemas for LLM tool-calling
        Route::get('/tools/definitions', [AgentGatewayController::class, 'getToolDefinitions']);

        // Execution endpoint: LLM runs tool
        Route::post('/tools/execute', [AgentGatewayController::class, 'executeTool']);

        // Quick snapshot for LLM context window
        Route::get('/campaigns/summary', [AgentGatewayController::class, 'getCampaignsSummary']);
    });
