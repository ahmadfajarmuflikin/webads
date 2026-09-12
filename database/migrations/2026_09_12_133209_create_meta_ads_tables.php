<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('meta_account_id')->unique(); // e.g. act_123456789
            $table->string('name');
            $table->string('currency', 3)->default('IDR');
            $table->string('timezone_name')->default('Asia/Jakarta');
            $table->text('access_token')->nullable(); // System user token or long-lived
            $table->decimal('target_roas', 6, 2)->default(2.50);
            $table->decimal('target_cpa', 15, 2)->default(100000.00);
            $table->string('status')->default('ACTIVE'); // ACTIVE, DISABLED
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('meta_campaign_id')->unique();
            $table->string('name');
            $table->string('objective')->default('OUTCOME_SALES');
            $table->string('status')->default('ACTIVE'); // ACTIVE, PAUSED, ARCHIVED
            $table->string('buying_type')->default('AUCTION');
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->decimal('lifetime_budget', 15, 2)->nullable();
            $table->string('bid_strategy')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('meta_adset_id')->unique();
            $table->string('name');
            $table->string('status')->default('ACTIVE'); // ACTIVE, PAUSED, ARCHIVED
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->string('optimization_goal')->default('OFFSITE_CONVERSIONS');
            $table->json('targeting')->nullable();
            $table->timestamps();
        });

        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_set_id')->constrained('ad_sets')->cascadeOnDelete();
            $table->string('meta_ad_id')->unique();
            $table->string('name');
            $table->string('status')->default('ACTIVE');
            $table->json('creative_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_insights_daily', function (Blueprint $table) {
            $table->id();
            $table->string('meta_entity_type'); // CAMPAIGN, ADSET, AD
            $table->string('meta_entity_id');
            $table->date('date');
            $table->decimal('spend', 15, 2)->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('cpc', 10, 2)->default(0);
            $table->decimal('ctr', 6, 3)->default(0);
            $table->decimal('frequency', 6, 2)->default(1.0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('conversion_value', 15, 2)->default(0);
            $table->decimal('roas', 8, 2)->default(0);
            $table->decimal('cpa', 15, 2)->default(0);
            $table->json('raw_metrics')->nullable();
            $table->timestamps();

            $table->index(['meta_entity_type', 'meta_entity_id', 'date']);
        });

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->enum('entity_level', ['CAMPAIGN', 'ADSET', 'AD'])->default('ADSET');
            $table->string('rule_type'); // KILL_SWITCH, SCALE_UP, FATIGUE_WARNING
            $table->json('conditions'); // e.g. {"spend_multiplier": 1.5, "min_conversions": 0}
            $table->string('action'); // PAUSE, INCREASE_BUDGET, NOTIFY
            $table->decimal('action_value', 8, 2)->nullable(); // e.g. 20 (percent)
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id'); // 'hermes', 'openclaw', 'system_automation'
            $table->string('action'); // PAUSE_ADSET, INCREASE_BUDGET, etc.
            $table->string('target_type'); // ADSET, CAMPAIGN
            $table->string('target_id'); // meta ID
            $table->text('reason'); // Reasoning explanation by AI agent
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_audit_logs');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('ad_insights_daily');
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_sets');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('ad_accounts');
    }
};
