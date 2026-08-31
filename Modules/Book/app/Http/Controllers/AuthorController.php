<?php

namespace Modules\Book\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Book\Models\Author;
use OpenApi\Attributes as OA;

class AuthorController extends Controller
{
    #[OA\Get(
        path: "/authors",
        summary: "استعراض كافة المؤلفين",
        tags: ["Authors"],
        responses: [
            new OA\Response(
                response: 200,
                description: "قائمة المؤلفين",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "نجيب محفوظ"),
                            new OA\Property(property: "bio", type: "string", example: "روائي مصري حائز على جائزة نوبل في الأدب."),
                            new OA\Property(property: "created_at", type: "string", format: "date-time"),
                            new OA\Property(property: "updated_at", type: "string", format: "date-time")
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
        return response()->json(Author::all(), 200);
    }

    #[OA\Post(
        path: "/authors",
        summary: "إضافة مؤلف جديد",
        tags: ["Authors"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "نجيب محفوظ"),
                    new OA\Property(property: "bio", type: "string", example: "روائي مصري حائز على جائزة نوبل في الأدب.")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "تمت إضافة المؤلف بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "نجيب محفوظ"),
                        new OA\Property(property: "bio", type: "string", example: "روائي مصري حائز على جائزة نوبل في الأدب."),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح (Unauthorized)"),
            new OA\Response(response: 422, description: "الاسم مطلوب")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $author = Author::create($validated);

        return response()->json($author, 201);
    }

    #[OA\Get(
        path: "/authors/{id}",
        summary: "عرض تفاصيل مؤلف محدد",
        tags: ["Authors"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف المؤلف",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "تفاصيل المؤلف",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "نجيب محفوظ"),
                        new OA\Property(property: "bio", type: "string", example: "روائي مصري حائز على جائزة نوبل في الأدب."),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "المؤلف غير موجود")
        ]
    )]
    public function show($id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json(['message' => 'المؤلف غير موجود'], 404);
        }

        return response()->json($author, 200);
    }

    #[OA\Put(
        path: "/authors/{id}",
        summary: "تحديث معلومات مؤلف",
        tags: ["Authors"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف المؤلف المراد تحديثه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "طه حسين"),
                    new OA\Property(property: "bio", type: "string", example: "عميد الأدب العربي.")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم تحديث بيانات المؤلف بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "طه حسين"),
                        new OA\Property(property: "bio", type: "string", example: "عميد الأدب العربي."),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 404, description: "المؤلف غير موجود"),
            new OA\Response(response: 422, description: "بيانات التحقق غير صالحة")
        ]
    )]
    public function update(Request $request, $id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json(['message' => 'المؤلف غير موجود'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $author->update($validated);

        return response()->json($author, 200);
    }

    #[OA\Delete(
        path: "/authors/{id}",
        summary: "حذف مؤلف",
        tags: ["Authors"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف المؤلف المراد حذفه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "تم حذف المؤلف بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم حذف المؤلف بنجاح")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 404, description: "المؤلف غير موجود")
        ]
    )]
    public function destroy($id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json(['message' => 'المؤلف غير موجود'], 404);
        }

        $author->delete();

        return response()->json(['message' => 'تم حذف المؤلف بنجاح'], 200);
    }
}
