<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendVerificationEmailJob;
use App\Models\ParentContact;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HouseholdEmailController extends Controller
{
    public function setup(Request $request)
    {
        $request->validate([
            'email'=>['required','email'],
            'prefs.weekly_summary'=>['nullable','boolean'],
            'prefs.milestones'=>['nullable','boolean'],
        ]);

        $agent = $request->user();
        $householdId = $agent->household_id;

        $verification = Str::random(48);
        $unsub = Str::random(48);

        $contact = ParentContact::updateOrCreate(
            ['household_id'=>$householdId,'email'=>$request->input('email')],
            [
                'status'=>'pending',
                'verification_token'=>$verification,
                'unsub_token'=>$unsub,
                'prefs'=>$request->input('prefs', ['weekly_summary'=>true,'milestones'=>false]),
                'verified_at'=>null,
            ]
        );

        SendVerificationEmailJob::dispatch($contact->id);

        return response()->json(['ok'=>true]);
    }

    public function verify(Request $request)
    {
        $request->validate(['token'=>['required','string']]);

        $contact = ParentContact::where('verification_token',$request->input('token'))->first();
        if (!$contact) return response()->json(['error_code'=>'EMAIL_TOKEN_INVALID','message'=>'Invalid token'], 400);

        $contact->status = 'active';
        $contact->verified_at = now();
        $contact->verification_token = Str::random(48);
        $contact->save();

        return response()->json(['ok'=>true]);
    }

    public function unsubscribe(Request $request)
    {
        $request->validate(['token'=>['required','string']]);

        $contact = ParentContact::where('unsub_token',$request->input('token'))->first();
        if (!$contact) return response()->json(['error_code'=>'EMAIL_TOKEN_INVALID','message'=>'Invalid token'], 400);

        $contact->status = 'unsubscribed';
        $contact->save();

        return response()->json(['ok'=>true]);
    }
}
