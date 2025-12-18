<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email'=>['required','email'], 'password'=>['required','string']]);

        $teacher = Teacher::where('email', $request->input('email'))->first();
        if (!$teacher || !Hash::check($request->input('password'), $teacher->password)) {
            return response()->json(['error_code'=>'INVALID_CREDENTIALS','message'=>'Invalid credentials'], 401);
        }

        $token = $teacher->createToken('teacher-session', ['teacher'])->plainTextToken;

        return response()->json(['session_token'=>$token, 'teacher'=>['id'=>$teacher->id,'email'=>$teacher->email]]);
    }
}
