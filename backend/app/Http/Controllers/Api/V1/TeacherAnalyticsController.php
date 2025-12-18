<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Classroom, ClassAgent};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherAnalyticsController extends Controller
{
    public function show(int $classId)
    {
        $class = Classroom::with('season.missions')->findOrFail($classId);

        $agentIds = ClassAgent::where('class_id',$classId)
            ->whereNull('unlinked_at')
            ->pluck('agent_id');

        $missions = $class->season->missions()->orderBy('mission_no')->get();

        if ($agentIds->isEmpty()) {
            return response()->json([
                'class'=>['id'=>$class->id,'name'=>$class->name,'season_id'=>$class->season_id],
                'heatmap'=>[],
                'stuck'=>[],
                'roster'=>[],
            ]);
        }

        $completedCounts = DB::table('agent_progress')
            ->select('mission_id', DB::raw('count(*) as completed'))
            ->whereIn('agent_id',$agentIds)
            ->where('status','completed')
            ->groupBy('mission_id')
            ->pluck('completed','mission_id');

        $total = $agentIds->count();
        $heatmap = $missions->map(function ($m) use ($completedCounts, $total) {
            $c = (int)($completedCounts[$m->id] ?? 0);
            return ['mission_no'=>$m->mission_no, 'completion_rate'=> $total ? round($c/$total, 2) : 0];
        });

        $from = Carbon::now()->subDays(14);
        $failStats = DB::table('mission_attempts')
            ->select('mission_id',
                DB::raw('sum(case when success=false then 1 else 0 end) as fails'),
                DB::raw('count(*) as attempts')
            )
            ->whereIn('agent_id',$agentIds)
            ->where('created_at','>=',$from)
            ->groupBy('mission_id')
            ->get()
            ->keyBy('mission_id');

        $stuck = [];
        foreach ($missions as $m) {
            $row = $failStats->get($m->id);
            if (!$row) continue;

            $attempts = (int)$row->attempts;
            $fails = (int)$row->fails;
            if ($attempts === 0) continue;

            $failRate = $fails / $attempts;
            if ($failRate >= 0.30) {
                $stuck[] = [
                    'mission_no'=>$m->mission_no,
                    'fail_rate'=>round($failRate, 2),
                    'recommendation'=>'Overvej at gennemgå emnet på tavlen.',
                ];
            }
        }

        return response()->json([
            'class'=>['id'=>$class->id,'name'=>$class->name,'season_id'=>$class->season_id],
            'heatmap'=>$heatmap,
            'stuck'=>$stuck,
            'roster'=>[],
        ]);
    }
}
