<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\PasswordResetMail;


class AuthController extends Controller
{




    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['login']]);
    // }



    public function resetPassword(Request $request)
    {

        $user = User::where("email", $request->email)->first();
        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }

        $passwordToken = PasswordReset::where([
            ['email', $request->email],
            ['token', $request->token]
        ])->first();
        if (!$passwordToken) {
            return response()->json(['message' => "Invalid Token"], 401);
        }

        //validation
        $validator = Validator::make($request->only('password', 'confirm_password'), [
            'password' => 'required|same:confirm_password',
            'confirm_password' => "required",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json(["errors" => $errors], 400);
        }

        //reset password
        $user->password = Hash::make($request->password);

        $passwordReset = $user->save();

        if ($passwordReset) {

            // token delete after password reset
            $passwordToken->delete();
            return response()->json(['message' => 'Password reset successfully.'], 200);
        } else {

            return response()->json(['message' => 'Something going wrong'], 500);
        }
    }

    public function checkToken(Request $request)
    {


        //  $checkToken = PasswordReset::where('email',$request->email)->where('token',$request->token)->first();
        $checkToken = PasswordReset::where([

            ['email', $request->email],
            ['token', $request->token],
            ['expired', false]

        ])->first();

        if (!$checkToken) {
            return response()->json(["message" => "Invalid token"], 401);
        }
        if ($checkToken) {

            return response()->json(["email" => $request->email, "token" => $request->token], 200);
        } else {

            return response()->json(["message" => "Something went wrong"], 500);
        }
    }


    public function forgetPasswordRequest(Request $request)
    {

        $user = User::where("email", $request->email)->first();
        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }
        $token = Hash::make($user->email);
        $saveToken = PasswordReset::create([
            "email" => $request->email,
            "token" => $token
        ]);
        $clientUrl = env("CLIENT_URL");
        if ($clientUrl == "") {

            return response()->json(["message" => "Client url not defined"]);
        }
        $redirect_url = $clientUrl . "/reset_password?email=" . $request->email . "&token=" . $token;
        $mail = Mail::to($request->email)->send(new PasswordResetMail($redirect_url));


        if (Mail::failures()) {
            return response()->json(["message" => "Something going wrong."], 500);
        } else {
            return response()->json(["message" => "Check your email for password reset link."], 200);
        }
    }

    public function reverifyRequest(Request $request)
    {

        $user = User::where("email", $request->email)->first();
        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }
        if ($user->verified) {
            return response()->json(["message" => "User already Verified"], 401);
        }
        $user->verify_code = rand(111111, 999999);
        $update = $user->save();

        if ($update) {
            Mail::to($request->email)->send(new VerificationMail($user));

            return response()->json(['message' => "Resend verify code"], 200);
        } else {
            return response()->json(['message' => "Something going wrong"], 500);
        }
    }


    public function verify(Request $request)
    {

        $user = User::where("email", $request->email)->first();
        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }

        if ($user->verified) {
            return response()->json(["message" => "User already Verified"], 401);
        }
        if ($user->verify_code !== $request->verify_code) {
            return response()->json(['message' => "invalid code"], 400);
        }

        $update =   User::where("email", $request->email)->update([
            "verified" => 1,
            "verify_code" => null
        ]);

        if ($update) {
            return response()->json(["message" => "Verified successfull"]);
        } else {

            return response()->json(["message" => "Something going wrong!"], 500);
        }
    }



    public function register(Request $request)
    {

        // return $request->email;

        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required|email|unique:users,email",
            "password" => "required",
            "confirm_password" => "required|same:password"
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors()->all();
            return response()->json(["errors" => $errors], 400);
        }


        $email = User::where("email", $request->email)->first();
        if ($email) {
            return response()->json(["message" => "Email already exits!"], 401);
        }

        $verify_code = rand(111111, 999999);


        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "verify_code" => $verify_code,
            "verified" => 0
        ]);
        if ($user) {
            Mail::to($request->email)->send(new VerificationMail($user));
            return response()->json(["message" => "Registered Successfully. We have sent an email for verify your account."], 200);
        } else {
            return response()->json(["error" => "Something going wrong"]);
        }



        return $user;
    }


    public function login(Request $request)
    {

        $user = User::where("email", $request->email)->first();
        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(["message" => "Incorrect Password"], 401);
        }
        if (!$user->verified) {
            return response()->json(["message" => "User not Verified"], 401);
        }
        if ($token = $this->guard()->attempt(["email" => $request->email, "password" => $request->password])) {

            return $this->respondWithToken($token, $user);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }


    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->guard()->user());
    }



    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }


    protected function respondWithToken($token, $user = null)
    {
        return response()->json([
            "user" => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }


    public function guard()
    {
        return Auth::guard();
    }
}