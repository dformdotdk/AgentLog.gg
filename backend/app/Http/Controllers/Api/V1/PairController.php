<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PairRequest;
use App\Services\PairingService;

class PairController extends Controller
{
    public function __construct(private PairingService $pairing) {}

    public function pair(PairRequest $request)
    {
        $result = $this->pairing->pair(
            seriesSlug: $request->string('series_slug'),
            bookSlug: $request->string('book_slug'),
            seasonNo: (int)$request->input('season_no'),
            bookToken: $request->input('book_token'),
            deviceAgentId: $request->input('device_agent_id'),
        );

        if (!$result['ok']) {
            return response()->json(['error_code'=>$result['error_code'], 'message'=>$result['message']], $result['status']);
        }

        return response()->json([
            'session_token' => $result['token'],
            'agent_key' => $result['agent_key'],
            'agent_state' => $result['agent_state'],
        ]);
    }
}
