<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "نظام إدارة المكتبة الشخصية وتقييم الكتب (Personal Library API)",
    description: "RESTful API متكامل لإدارة الكتب، المؤلفين، التصنيفات، وتقييمات الكتب مع توثيق كامل باستخدام Swagger."
)]
#[OA\Server(
    url: "http://localhost:8000/api",
    description: "سيرفر التطوير المحلي (Local Development Server)"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "أدخل الـ Token الخاص بك هنا بالصيغة التالية: Bearer {token}"
)]
abstract class Controller
{
    #[OA\Get(
        path: "/test",
        summary: "تجربة الاتصال بالـ API",
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function test()
    {
        return response()->json(['message' => 'API is working']);
    }
}
