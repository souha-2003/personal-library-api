<?php

namespace Modules\Book\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Book\Models\Category;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: "/categories",
        summary: "استعراض كافة التصنيفات",
        tags: ["Categories"],
        responses: [
            new OA\Response(
                response: 200,
                description: "قائمة التصنيفات",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "روايات"),
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
        return response()->json(Category::all(), 200);
    }

    #[OA\Post(
        path: "/categories",
        summary: "إضافة تصنيف جديد",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "روايات")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "تمت إضافة التصنيف بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "روايات"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح (Unauthorized)"),
            new OA\Response(response: 422, description: "اسم التصنيف مكرر أو غير صالح")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    #[OA\Get(
        path: "/categories/{id}",
        summary: "عرض تفاصيل تصنيف محدد",
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف التصنيف",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "تفاصيل التصنيف",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "روايات"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "التصنيف غير موجود")
        ]
    )]
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'التصنيف غير موجود'], 404);
        }

        return response()->json($category, 200);
    }

    #[OA\Put(
        path: "/categories/{id}",
        summary: "تحديث اسم تصنيف",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف التصنيف المراد تحديثه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "تاريخ")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم تحديث التصنيف بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "تاريخ"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 404, description: "التصنيف غير موجود"),
            new OA\Response(response: 422, description: "بيانات التحقق غير صالحة")
        ]
    )]
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'التصنيف غير موجود'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category->update($validated);

        return response()->json($category, 200);
    }

    #[OA\Delete(
        path: "/categories/{id}",
        summary: "حذف تصنيف",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف التصنيف المراد حذفه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "تم حذف التصنيف بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم حذف التصنيف بنجاح")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 404, description: "التصنيف غير موجود")
        ]
    )]
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'التصنيف غير موجود'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'تم حذف التصنيف بنجاح'], 200);
    }
}
