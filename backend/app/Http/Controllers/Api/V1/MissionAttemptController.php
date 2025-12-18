<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionAttemptRequest;
use App\Services\MissionAttemptService;

class MissionAttemptController extends Controller
{
    public function __construct(private MissionAttemptService $service) {}

    public function attempt(int $missionId, MissionAttemptRequest $request)
    {
        $agent = $request->user();
        $answer = (string)$request->input('answer');

        $res = $this->service->attempt($agent->id, $missionId, $answer);

        if (!$res['ok']) {
            return response()->json(['error_code'=>$res['error_code'],'message'=>$res['message']] + ($res['meta'] ?? []), $res['status']);
        }

        return response()->json($res['data']);
    }
}
