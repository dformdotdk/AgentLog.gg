<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{AgentSeasonState, AgentProgress};
use Illuminate\Http\Request;

class AgentStatusController extends Controller
{
    public function show(Request $request)
    {
        $agent = $request->user();
        $seasonId = (int)$request->query('season_id');

        $state = AgentSeasonState::where('agent_id',$agent->id)->where('season_id',$seasonId)->first();
        if (!$state) return response()->json(['error_code'=>'CONTENT_NOT_FOUND','message'=>'Season state not found'],404);

        $progress = AgentProgress::where('agent_id',$agent->id)
            ->whereHas('mission', fn($q)=>$q->where('season_id',$seasonId))
            ->get()
            ->map(fn($p)=>['mission_id'=>$p->mission_id,'status'=>$p->status]);

        $next = AgentProgress::where('agent_id',$agent->id)
            ->whereHas('mission', fn($q)=>$q->where('season_id',$seasonId))
            ->where('status','active')->first();

        return response()->json([
            'xp_total'=>$state->xp_total,
            'level'=>$state->level,
            'next_mission_id'=>$next?->mission_id,
            'milestones_unlocked'=>$this->milestonesUnlocked($state->xp_total),
            'missions'=>$progress,
        ]);
    }

    private function milestonesUnlocked(int $xp): array
    {
        $milestones = [100,200,300,400,500,1000];
        return array_values(array_filter($milestones, fn($m)=>$xp >= $m));
    }
}
