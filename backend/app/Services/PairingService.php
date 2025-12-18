<?php

namespace App\Services;

use App\Models\{Series, Book, Season, BookToken, Household, Agent, AgentSeasonState, Mission, AgentProgress};

class PairingService
{
    public function pair(string $seriesSlug, string $bookSlug, int $seasonNo, ?string $bookToken, ?string $deviceAgentId): array
    {
        $series = Series::where('slug',$seriesSlug)->first();
        if (!$series) return $this->err('CONTENT_NOT_FOUND','Series not found',404);

        $book = Book::where('series_id',$series->id)->where('slug',$bookSlug)->first();
        if (!$book) return $this->err('CONTENT_NOT_FOUND','Book not found',404);

        $season = Season::where('book_id',$book->id)->where('season_no',$seasonNo)->first();
        if (!$season) return $this->err('CONTENT_NOT_FOUND','Season not found',404);
        if (!$season->is_active) return $this->err('SEASON_INACTIVE','Season inactive',410);

        $mode = $bookToken ? 'book_token' : 'device';
        $ref  = $bookToken ?: $deviceAgentId;

        if ($mode === 'book_token') {
            $tokenRow = BookToken::where('season_id',$season->id)->where('token',$bookToken)->first();
            if (!$tokenRow) return $this->err('INVALID_BOOK_TOKEN','Invalid token',400);
            if (!$tokenRow->canActivate()) return $this->err('INVALID_BOOK_TOKEN','Token not active',400);
            $tokenRow->increment('activation_count');
        }

        $existingState = AgentSeasonState::where('season_id',$season->id)
            ->where('paired_mode',$mode)->where('paired_ref',$ref)
            ->with('agent.household')
            ->first();

        if ($existingState) {
            $agent = $existingState->agent;
            $token = $agent->createToken('agent-session', ['agent'])->plainTextToken;

            return [
                'ok'=>true,
                'token'=>$token,
                'agent_key'=>$agent->agent_key,
                'agent_state'=>[
                    'xp_total'=>$existingState->xp_total,
                    'level'=>$existingState->level,
                    'next_mission_no'=>$this->nextMissionNo($agent->id, $season->id) ?? 1,
                ],
            ];
        }

        $household = Household::create([
            'settings' => ['daily_cap' => (int)($season->rules['default_daily_cap'] ?? 3), 'open_world' => false],
            'wizard_state' => 'not_started',
        ]);

        $agent = Agent::create([
            'household_id' => $household->id,
            'agent_key' => $this->generateAgentKey(),
        ]);

        AgentSeasonState::create([
            'agent_id'=>$agent->id,
            'season_id'=>$season->id,
            'xp_total'=>0,
            'level'=>1,
            'paired_mode'=>$mode,
            'paired_ref'=>$ref,
        ]);

        $this->initializeProgress($agent->id, $season->id);

        $token = $agent->createToken('agent-session', ['agent'])->plainTextToken;

        return [
            'ok'=>true,
            'token'=>$token,
            'agent_key'=>$agent->agent_key,
            'agent_state'=>['xp_total'=>0,'level'=>1,'next_mission_no'=>1],
        ];
    }

    private function initializeProgress(int $agentId, int $seasonId): void
    {
        $missions = Mission::where('season_id',$seasonId)->orderBy('mission_no')->get();
        foreach ($missions as $i => $m) {
            AgentProgress::create([
                'agent_id'=>$agentId,
                'mission_id'=>$m->id,
                'status'=> $i === 0 ? 'active' : 'locked',
            ]);
        }
    }

    private function nextMissionNo(int $agentId, int $seasonId): ?int
    {
        $row = AgentProgress::query()
            ->where('agent_id',$agentId)
            ->whereHas('mission', fn($q)=>$q->where('season_id',$seasonId))
            ->where('status','active')
            ->with('mission')
            ->first();

        return $row?->mission?->mission_no;
    }

    private function generateAgentKey(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i=0;$i<6;$i++) $s .= $alphabet[random_int(0, strlen($alphabet)-1)];
        return 'AGT_'.$s;
    }

    private function err(string $code, string $msg, int $status): array
    {
        return ['ok'=>false,'error_code'=>$code,'message'=>$msg,'status'=>$status];
    }
}
