<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdSet;
use App\Models\AgentAuditLog;
use App\Models\Campaign;
use App\Services\Analytics\CampaignHealthEvaluator;
use App\Services\Meta\CampaignActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentGatewayController extends Controller
{
    public function __construct(
        protected CampaignHealthEvaluator $evaluator,
        protected CampaignActionService $actionService
    ) {
    }

    /**
     * Menyediakan tool definitions / OpenAPI function specifications untuk LLM Agent (Hermes, OpenClaw).
     */
    public function getToolDefinitions(): JsonResponse
    {
        $tools = [
            [
                'name' => 'get_adset_health_analysis',
                'description' => 'Menganalisis kesehatan dan efektivitas AdSet Meta Ads berdasarkan ROAS, CPA, CTR, dan frekuensi.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'adset_id' => [
                            'type' => 'string',
                            'description' => 'ID AdSet Meta (misal: 120210001)',
                        ],
                        'timeframe_days' => [
                            'type' => 'integer',
                            'description' => 'Rentang hari yang dianalisis (default: 3)',
                        ],
                    ],
                    'required' => ['adset_id'],
                ],
            ],
            [
                'name' => 'get_all_campaigns_health',
                'description' => 'Mengambil ringkasan kesehatan seluruh adset di akun untuk mendeteksi adset winning atau boncos.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'account_id' => [
                            'type' => 'integer',
                            'description' => 'ID Database AdAccount (default akun pertama)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'update_adset_status',
                'description' => 'Menghentikan (PAUSE) atau mengaktifkan (ACTIVATE) AdSet berdasarkan pertimbangan performa.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'adset_id' => [
                            'type' => 'string',
                            'description' => 'ID AdSet Meta',
                        ],
                        'action' => [
                            'type' => 'string',
                            'enum' => ['PAUSE', 'ACTIVATE'],
                            'description' => 'Aksi pengubahan status',
                        ],
                        'reason' => [
                            'type' => 'string',
                            'description' => 'Penjelasan AI agent mengenai alasan tindakan ini',
                        ],
                    ],
                    'required' => ['adset_id', 'action', 'reason'],
                ],
            ],
            [
                'name' => 'adjust_adset_budget',
                'description' => 'Menaikkan atau menurunkan budget harian AdSet secara persentase (e.g. +20% untuk scaling, -15% untuk efisiensi).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'adset_id' => [
                            'type' => 'string',
                            'description' => 'ID AdSet Meta',
                        ],
                        'percentage_change' => [
                            'type' => 'number',
                            'description' => 'Persentase perubahan budget (contoh: 20 untuk naik 20%, -15 untuk turun 15%)',
                        ],
                        'reason' => [
                            'type' => 'string',
                            'description' => 'Alasan penyesuaian budget',
                        ],
                    ],
                    'required' => ['adset_id', 'percentage_change', 'reason'],
                ],
            ],
            [
                'name' => 'create_new_campaign',
                'description' => 'Membuat campaign iklan Meta baru dengan status PAUSED.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'account_id' => [
                            'type' => 'integer',
                            'description' => 'ID AdAccount database lokal',
                        ],
                        'name' => [
                            'type' => 'string',
                            'description' => 'Nama campaign',
                        ],
                        'objective' => [
                            'type' => 'string',
                            'enum' => ['OUTCOME_SALES', 'OUTCOME_LEADS', 'OUTCOME_TRAFFIC'],
                        ],
                        'daily_budget' => [
                            'type' => 'number',
                            'description' => 'Budget harian dalam mata uang akun (IDR)',
                        ],
                    ],
                    'required' => ['name', 'objective'],
                ],
            ],
            [
                'name' => 'generate_creative_angles',
                'description' => 'Menghasilkan 3 variasi sudut pandang (Angles: FOMO, Social Proof, Direct Offer) untuk materi iklan baru ketika terjadi Creative Fatigue.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'product_name' => ['type' => 'string'],
                        'target_audience' => ['type' => 'string'],
                        'main_benefit' => ['type' => 'string'],
                    ],
                    'required' => ['product_name', 'target_audience', 'main_benefit'],
                ],
            ],
        ];

        return response()->json([
            'protocol' => 'agent_tools_v1',
            'compatible_agents' => ['Hermes', 'OpenClaw', 'AutoGen', 'OpenAI Function Calling'],
            'tools' => $tools,
        ]);
    }

    /**
     * Endpoint eksekusi fungsi tool dari AI Agent.
     */
    public function executeTool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tool_name' => 'required|string',
            'arguments' => 'required|array',
            'agent_id' => 'nullable|string', // e.g. hermes-70b, openclaw-agent
        ]);

        $toolName = $validated['tool_name'];
        $args = $validated['arguments'];
        $agentId = $validated['agent_id'] ?? 'external_agent';
        $result = [];

        switch ($toolName) {
            case 'get_adset_health_analysis':
                $adset = AdSet::where('meta_adset_id', $args['adset_id'])->first();
                if (!$adset) {
                    return response()->json(['error' => "AdSet {$args['adset_id']} not found."], 404);
                }
                $result = $this->evaluator->evaluateAdset($adset, $args['timeframe_days'] ?? 3);
                break;

            case 'get_all_campaigns_health':
                $accountId = $args['account_id'] ?? AdAccount::first()?->id;
                if (!$accountId) {
                    return response()->json(['error' => "No AdAccount found."], 404);
                }
                $result = $this->evaluator->evaluateAllAdsets($accountId, 3);
                break;

            case 'update_adset_status':
                $status = ($args['action'] === 'PAUSE') ? 'PAUSED' : 'ACTIVE';
                $actionResult = $this->actionService->setAdsetStatus($args['adset_id'], $status);

                AgentAuditLog::create([
                    'agent_id' => $agentId,
                    'action' => "UPDATE_STATUS_{$status}",
                    'target_type' => 'ADSET',
                    'target_id' => $args['adset_id'],
                    'reason' => $args['reason'] ?? 'Requested by AI agent',
                    'payload' => $args,
                ]);

                $result = $actionResult;
                break;

            case 'adjust_adset_budget':
                $pct = (float) $args['percentage_change'];
                $budgetResult = $this->actionService->adjustAdsetBudget($args['adset_id'], $pct);

                AgentAuditLog::create([
                    'agent_id' => $agentId,
                    'action' => 'ADJUST_BUDGET',
                    'target_type' => 'ADSET',
                    'target_id' => $args['adset_id'],
                    'reason' => $args['reason'] ?? 'Budget scaling adjustment',
                    'payload' => array_merge($args, $budgetResult),
                ]);

                $result = $budgetResult;
                break;

            case 'create_new_campaign':
                $account = !empty($args['account_id']) 
                    ? AdAccount::find($args['account_id']) 
                    : AdAccount::first();

                if (!$account) {
                    return response()->json(['error' => 'Valid AdAccount is required'], 400);
                }

                $campaignResult = $this->actionService->createCampaign($account, [
                    'name' => $args['name'],
                    'objective' => $args['objective'],
                    'daily_budget' => $args['daily_budget'] ?? null,
                ]);

                AgentAuditLog::create([
                    'agent_id' => $agentId,
                    'action' => 'CREATE_CAMPAIGN',
                    'target_type' => 'CAMPAIGN',
                    'target_id' => $campaignResult['campaign']->meta_campaign_id,
                    'reason' => 'Created new campaign via agent prompt',
                    'payload' => $campaignResult,
                ]);

                $result = $campaignResult;
                break;

            case 'generate_creative_angles':
                $product = $args['product_name'];
                $audience = $args['target_audience'];
                $benefit = $args['main_benefit'];

                $result = [
                    'product' => $product,
                    'angles' => [
                        [
                            'angle_type' => 'FOMO & Pain Relief',
                            'headline' => "Capek dengan kendala {$product}? Ini solusinya.",
                            'primary_text' => "Bagi {$audience} yang ingin {$benefit} tanpa ribet. Dapatkan penawaran terbatas hari ini sebelum kehabisan!",
                            'call_to_action' => 'ORDER_NOW',
                        ],
                        [
                            'angle_type' => 'Social Proof & Transformation',
                            'headline' => "Sudah 10.000+ {$audience} beralih ke {$product}",
                            'primary_text' => "Buktikan sendiri transformasi dan hasil nyata dari {$benefit}. Simak cerita mereka sekarang.",
                            'call_to_action' => 'LEARN_MORE',
                        ],
                        [
                            'angle_type' => 'Direct Urgency / Limited Offer',
                            'headline' => "Spesial Pekan Ini: Diskon Eksklusif {$product}",
                            'primary_text' => "Solusi terbaik untuk {$benefit} dirancang khusus untuk {$audience}. Klik tautan sekarang!",
                            'call_to_action' => 'SHOP_NOW',
                        ],
                    ],
                ];
                break;

            default:
                return response()->json(['error' => "Unknown tool: {$toolName}"], 400);
        }

        return response()->json([
            'status' => 'success',
            'tool_executed' => $toolName,
            'agent_id' => $agentId,
            'result' => $result,
        ]);
    }

    /**
     * List cepat data campaign untuk konteks LLM agent.
     */
    public function getCampaignsSummary(): JsonResponse
    {
        $campaigns = Campaign::with(['adSets.insights' => function ($q) {
            $q->where('date', '>=', now()->subDays(3)->toDateString());
        }])->get();

        return response()->json([
            'total_campaigns' => $campaigns->count(),
            'campaigns' => $campaigns,
        ]);
    }
}
