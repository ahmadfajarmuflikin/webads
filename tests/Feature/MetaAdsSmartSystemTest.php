<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdSet;
use App\Models\AgentAuditLog;
use App\Models\Campaign;
use Database\Seeders\MetaAdsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaAdsSmartSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MetaAdsDemoSeeder::class);
    }

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Smart Evaluation Engine');
        $response->assertSee('SCALING_CANDIDATE');
        $response->assertSee('LOSING_MONEY');
    }

    public function test_agent_tools_definitions_requires_auth_key(): void
    {
        $response = $this->getJson('/api/v1/agent/tools/definitions');
        $response->assertStatus(401);

        $responseWithKey = $this->withHeaders([
            'X-Agent-Key' => config('meta.agent_api_key'),
        ])->getJson('/api/v1/agent/tools/definitions');

        $responseWithKey->assertStatus(200);
        $responseWithKey->assertJsonStructure([
            'protocol',
            'tools' => [
                '*' => ['name', 'description', 'parameters']
            ]
        ]);
    }

    public function test_agent_can_evaluate_boncos_adset_and_receive_kill_recommendation(): void
    {
        $response = $this->withHeaders([
            'X-Agent-Key' => config('meta.agent_api_key'),
        ])->postJson('/api/v1/agent/tools/execute', [
            'agent_id' => 'hermes-70b',
            'tool_name' => 'get_adset_health_analysis',
            'arguments' => [
                'adset_id' => 'adset_boncos_002',
                'timeframe_days' => 3,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'tool_executed' => 'get_adset_health_analysis',
            'result' => [
                'verdict' => 'LOSING_MONEY',
                'risk_level' => 'CRITICAL',
            ]
        ]);
    }

    public function test_agent_can_pause_boncos_adset_and_creates_audit_log(): void
    {
        $response = $this->withHeaders([
            'X-Agent-Key' => config('meta.agent_api_key'),
        ])->postJson('/api/v1/agent/tools/execute', [
            'agent_id' => 'openclaw-agent',
            'tool_name' => 'update_adset_status',
            'arguments' => [
                'adset_id' => 'adset_boncos_002',
                'action' => 'PAUSE',
                'reason' => 'Spend Rp 280.000 dengan 0 penjualan. Menghentikan pemborosan.',
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ad_sets', [
            'meta_adset_id' => 'adset_boncos_002',
            'status' => 'PAUSED',
        ]);

        $this->assertDatabaseHas('agent_audit_logs', [
            'agent_id' => 'openclaw-agent',
            'action' => 'UPDATE_STATUS_PAUSED',
            'target_id' => 'adset_boncos_002',
        ]);
    }

    public function test_agent_can_scale_winning_adset_budget(): void
    {
        $response = $this->withHeaders([
            'X-Agent-Key' => config('meta.agent_api_key'),
        ])->postJson('/api/v1/agent/tools/execute', [
            'agent_id' => 'hermes-core',
            'tool_name' => 'adjust_adset_budget',
            'arguments' => [
                'adset_id' => 'adset_winner_001',
                'percentage_change' => 20,
                'reason' => 'ROAS 3.8x stabil selama 3 hari berturut-turut.',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'result' => [
                'percentage_change' => 20,
            ]
        ]);
    }

    public function test_automation_watchdog_pauses_losing_adsets_automatically(): void
    {
        $this->post(route('automation.watchdog'))
            ->assertRedirect();

        // Adset boncos harus sudah otomatis di-PAUSE oleh watchdog
        $this->assertDatabaseHas('ad_sets', [
            'meta_adset_id' => 'adset_boncos_002',
            'status' => 'PAUSED',
        ]);

        $this->assertDatabaseHas('agent_audit_logs', [
            'agent_id' => 'system_kill_switch',
            'action' => 'PAUSE_ADSET',
            'target_id' => 'adset_boncos_002',
        ]);
    }

    public function test_sync_data_web_route_executes_successfully(): void
    {
        $response = $this->post(route('meta.sync_web'), ['preset' => 'today']);
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
    }
}
