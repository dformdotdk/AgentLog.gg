<?php

namespace App\Services;

use App\Models\{Agent, Reward, RewardClaim, AgentSeasonState};

class RewardService
{
    public function checkAndCreateClaims(int $agentId, int $seasonId): bool
    {
        $agent = Agent::with('household')->findOrFail($agentId);
        $householdId = $agent->household_id;

        $state = AgentSeasonState::where('agent_id',$agentId)->where('season_id',$seasonId)->firstOrFail();
        $xp = $state->xp_total;

        $rewards = Reward::where('household_id',$householdId)->where('is_active',true)->get();
        $created = false;

        foreach ($rewards as $reward) {
            if ($xp < $reward->xp_cost) continue;

            $exists = RewardClaim::where('reward_id',$reward->id)
                ->where('agent_id',$agentId)->where('season_id',$seasonId)
                ->whereNull('redeemed_at')
                ->exists();

            if ($exists) continue;

            RewardClaim::create([
                'household_id'=>$householdId,
                'reward_id'=>$reward->id,
                'agent_id'=>$agentId,
                'season_id'=>$seasonId,
                'available_at'=>now(),
            ]);
            $created = true;
        }

        return $created;
    }
}
