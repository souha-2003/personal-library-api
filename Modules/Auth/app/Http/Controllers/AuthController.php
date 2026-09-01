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
    // === بداية أكواد التوثيق (Swagger) لدالة التسجيل ===
    #[OA\Post(
        path: "/register", // مسار الرابط الذي سيتم طلبه
        summary: "تسجيل حساب مستخدم جديد", // عنوان قصير يظهر في واجهة Swagger
        tags: ["Auth"], // تجميع هذا المسار تحت تصنيف "Auth" في صفحة التوثيق
        
        // تحديد البيانات التي يجب على المستخدم إرسالها (Request Body)
        requestBody: new OA\RequestBody(
            required: true, // إجبار المستخدم على إرسال بيانات
            content: new OA\JsonContent( // تحديد أن البيانات ستكون بصيغة JSON
                required: ["name", "email", "password", "password_confirmation"], // الحقول الإجبارية
                properties: [ // تفصيل كل حقل مع إعطاء أمثلة وهمية تظهر في التوثيق
                    new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "ahmed@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        
        // تحديد الردود المتوقعة من الخادم (Responses)
        responses: [
            // 1. الرد في حالة النجاح (كود 201)
            new OA\Response(
                response: 201,
                description: "تم إنشاء الحساب بنجاح",
                content: new OA\JsonContent( // شكل البيانات الراجعة عند النجاح
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل المستخدم بنجاح"),
                        new OA\Property(property: "token", type: "string", example: "1|abcdef12345..."),
                        new OA\Property( // بيانات المستخدم الراجعة ككائن (Object)
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
            // 2. الرد في حالة وجود خطأ في البيانات (كود 422)
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

    // === نهاية أكواد التوثيق وبداية الكود البرمجي الفعلي ===
    public function register(Request $request)
    {
        // 1. التحقق من صحة البيانات (توافق ما كتبناه في التوثيق فوق)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // إيميل فريد
            'password' => 'required|string|min:8|confirmed', // كلمة سر متطابقة
        ]);

        // 2. إنشاء المستخدم وتشفير كلمة المرور بـ Hash
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 3. توليد كود Token للمستخدم الجديد
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4. إرجاع النتيجة بصيغة JSON مع كود 201 (يطابق تماماً شكل الرد الذي وصفناه في التوثيق)
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

    // === أكواد التوثيق (Swagger) ===
    #[OA\Post(
        path: "/login",
        summary: "تسجيل الدخول والحصول على Token",
        tags: ["Auth"],
        
        // البيانات المطلوبة: إيميل وباسورد فقط
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
            // حالة النجاح: إرجاع التوكن والبيانات
            new OA\Response(
                response: 200,
                description: "تم تسجيل الدخول بنجاح",
                // ... (نفس تفاصيل بيانات الرد المكتوبة في الكود الأصلي)
            ),
            // حالة الفشل (الباسورد أو الإيميل خطأ): كود 401
            new OA\Response(
                response: 401,
                description: "بيانات الاعتماد غير صحيحة (Unauthorized)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "بيانات الدخول غير صحيحة")
                    ]
                )
            ),
            // خطأ بنقص البيانات المرسلة: كود 422
            new OA\Response(
                response: 422,
                description: "خطأ في التحقق من البيانات"
            )
        ]
    )]
    // === الكود البرمجي الفعلي ===
    public function login(Request $request)
    {
        // 1. التحقق أن المستخدم أرسل الإيميل وكلمة المرور
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 2. البحث عن المستخدم في قاعدة البيانات عن طريق إيميله
        $user = User::where('email', $validated['email'])->first();

        // 3. إذا لم نجد المستخدم، أو كلمة المرور غير مطابقة للمشفرة
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            // نرجع خطأ 401 (غير مصرح)
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        // 4. إذا نجح الدخول، نولد Token جديد
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. نرجع البيانات مع التوكن بكود 200
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
    

    // === أكواد التوثيق (Swagger) ===
    #[OA\Post(
        path: "/logout",
        summary: "تسجيل الخروج وإبطال الـ Token",
        tags: ["Auth"],
        
        // سطر مهم: يخبر Swagger أن هذا الرابط يحتاج إلى إرسال Bearer Token ليعمل (مسار محمي)
        security: [["bearerAuth" => []]], 
        
        responses: [
            // النجاح: كود 200
            new OA\Response(
                response: 200,
                description: "تم تسجيل الخروج بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الخروج بنجاح")
                    ]
                )
            ),
            // الفشل (لم يرسل التوكن): كود 401
            new OA\Response(
                response: 401,
                description: "غير مصرح بالدخول (Unauthorized)"
            )
        ]
    )]

    // === الكود البرمجي الفعلي ===
    public function logout(Request $request)
    {
        // نصل للمستخدم صاحب الطلب، ثم لرمز الدخول الحالي له، ونقوم بحذفه (إبطاله)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ], 200);
    }


    // === أكواد التوثيق (Swagger) ===
    #[OA\Get( // نوع الطلب هنا Get وليس Post
        path: "/me",
        summary: "الحصول على بيانات المستخدم الحالي",
        tags: ["Auth"],
        security: [["bearerAuth" => []]], // مسار محمي يتطلب Token أيضاً
        
        responses: [
            // النجاح: يرجع بيانات المستخدم كود 200
            new OA\Response(
                response: 200,
                description: "بيانات المستخدم الحالي",
                // ... (تفاصيل الرد)
            ),
            new OA\Response(
                response: 401,
                description: "غير مصرح بالدخول (Unauthorized)"
            )
        ]
    )]
    // === الكود البرمجي الفعلي ===
    public function me(Request $request)
    {
        // استخراج بيانات المستخدم المربوطة بالتوكن المرسل وإعادتها فوراً كـ JSON
        return response()->json($request->user(), 200);
    }
}
