<?php

namespace App\Services;

use App\Models\{Mission, AgentProgress, MissionAttempt, AgentSeasonState};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class MissionAttemptService
{
    public function __construct(
        private AnswerValidationService $validator,
        private RewardService $rewards
    ) {}

    public function attempt(int $agentId, int $missionId, string $answer): array
    {
        $mission = Mission::with('season')->find($missionId);
        if (!$mission) return $this->err('MISSION_NOT_FOUND','Mission not found',404);

        $seasonId = $mission->season_id;

        $state = AgentSeasonState::where('agent_id',$agentId)->where('season_id',$seasonId)->first();
        if (!$state) return $this->err('CONTENT_NOT_FOUND','Agent not paired for this season',404);

        $key = "attempt:{$agentId}";
        $max = 3;
        $decay = 3600;
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $retry = RateLimiter::availableIn($key);
            return $this->err('RATE_LIMIT','Too many attempts',429, ['retry_after_seconds'=>$retry]);
        }
        RateLimiter::hit($key, $decay);

        $progress = AgentProgress::where('agent_id',$agentId)->where('mission_id',$missionId)->first();
        if (!$progress) return $this->err('CONTENT_NOT_FOUND','Progress row missing',500);

        if ($progress->status === 'locked') {
            return $this->err('LOCKED','Mission is locked',403);
        }

        $validation = $mission->validation ?? [];
        $isCorrect = $this->validator->isCorrect($validation, $answer);

        MissionAttempt::create([
            'agent_id'=>$agentId,
            'mission_id'=>$missionId,
            'success'=>$isCorrect,
            'answer_hash'=>hash('sha256', mb_strtolower(trim($answer))),
            'created_at'=>now(),
        ]);

        $progress->attempts_count += 1;
        $progress->last_attempt_at = now();
        $progress->save();

        if (!$isCorrect) {
            return ['ok'=>true,'data'=>[
                'success'=>false,
                'error_code'=>'WRONG_ANSWER',
                'hint_available'=>true,
            ]];
        }

        return DB::transaction(function () use ($state, $progress, $mission, $agentId, $seasonId) {

            $progress->status = 'completed';
            $progress->completed_at = now();
            $progress->save();

            $next = AgentProgress::where('agent_id',$agentId)
                ->where('status','locked')
                ->whereHas('mission', fn($q)=>$q->where('season_id',$seasonId))
                ->orderBy('mission_id')
                ->first();

            if ($next) {
                $next->status = 'active';
                $next->save();
            }

            $xpGained = (int)$mission->xp_reward;
            $state->xp_total += $xpGained;
            $state->level = max(1, (int)floor($state->xp_total / 100) + 1);
            $state->save();

            $rewardAvailable = $this->rewards->checkAndCreateClaims($state->agent_id, $seasonId);

            $unlocks = [];
            if (!empty($mission->assets['base_asset_id'] ?? null)) $unlocks[] = ['type'=>'asset','id'=>$mission->assets['base_asset_id']];
            if (!empty($mission->assets['lore_id'] ?? null)) $unlocks[] = ['type'=>'lore','id'=>$mission->assets['lore_id']];
            foreach ([100,200,300,400,500,1000] as $m) {
                if ($state->xp_total >= $m && ($state->xp_total - $xpGained) < $m) $unlocks[] = ['type'=>'milestone','xp'=>$m];
            }

            return ['ok'=>true,'data'=>[
                'success'=>true,
                'xp_gained'=>$xpGained,
                'new_xp'=>$state->xp_total,
                'unlocks'=>$unlocks,
                'reward_available'=>$rewardAvailable,
                'next_mission_id'=>$next?->mission_id,
            ]];
        });
    }

    private function err(string $code, string $msg, int $status, array $meta=[]): array
    {
        return ['ok'=>false,'error_code'=>$code,'message'=>$msg,'status'=>$status,'meta'=>$meta];
    }
}
