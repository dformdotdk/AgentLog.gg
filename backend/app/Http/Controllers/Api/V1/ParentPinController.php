<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ParentPinController extends Controller
{
    public function set(Request $request)
    {
        $request->validate(['pin'=>['required','string','min:4','max:6']]);
        $agent = $request->user();
        $household = $agent->household;

        if ($household->parent_pin_hash) {
            return response()->json(['error_code'=>'PARENT_PIN_ALREADY_SET','message'=>'PIN already set'], 409);
        }

        $household->parent_pin_hash = Hash::make($request->input('pin'));
        $household->save();

        $token = $agent->createToken('parent-session', ['agent','parent'])->plainTextToken;

        return response()->json(['ok'=>true,'session_token'=>$token]);
    }

    public function verify(Request $request)
    {
        $request->validate(['pin'=>['required','string','min:4','max:6']]);
        $agent = $request->user();
        $household = $agent->household;

        if (!$household->parent_pin_hash) {
            return response()->json(['error_code'=>'PARENT_PIN_REQUIRED','message'=>'PIN not set'], 400);
        }

        if (!Hash::check($request->input('pin'), $household->parent_pin_hash)) {
            return response()->json(['error_code'=>'PARENT_PIN_INVALID','message'=>'Invalid PIN'], 403);
        }

        $token = $agent->createToken('parent-session', ['agent','parent'])->plainTextToken;

        return response()->json(['ok'=>true,'session_token'=>$token]);
    }
}
