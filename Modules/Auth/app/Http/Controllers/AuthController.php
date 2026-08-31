<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/register",
        summary: "تسجيل حساب مستخدم جديد",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "ahmed@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "تم إنشاء الحساب بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل المستخدم بنجاح"),
                        new OA\Property(property: "token", type: "string", example: "1|abcdef12345..."),
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                                new OA\Property(property: "email", type: "string", example: "ahmed@example.com")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "خطأ في التحقق من البيانات (Validation Error)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The email has already been taken."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "email",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The email has already been taken.")
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل المستخدم بنجاح',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    #[OA\Post(
        path: "/login",
        summary: "تسجيل الدخول والحصول على Token",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "ahmed@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم تسجيل الدخول بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الدخول بنجاح"),
                        new OA\Property(property: "token", type: "string", example: "1|abcdef12345..."),
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                                new OA\Property(property: "email", type: "string", example: "ahmed@example.com")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "بيانات الاعتماد غير صحيحة (Unauthorized)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "بيانات الدخول غير صحيحة")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "خطأ في التحقق من البيانات"
            )
        ]
    )]
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    #[OA\Post(
        path: "/logout",
        summary: "تسجيل الخروج وإبطال الـ Token",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "تم تسجيل الخروج بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الخروج بنجاح")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "غير مصرح بالدخول (Unauthorized)"
            )
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ], 200);
    }

    #[OA\Get(
        path: "/me",
        summary: "الحصول على بيانات المستخدم الحالي",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "بيانات المستخدم الحالي",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                        new OA\Property(property: "email", type: "string", example: "ahmed@example.com")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "غير مصرح بالدخول (Unauthorized)"
            )
        ]
    )]
    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }
}
