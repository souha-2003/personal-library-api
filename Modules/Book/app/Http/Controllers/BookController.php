<?php

namespace Modules\Book\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Book\Models\Book;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: "/books",
        summary: "استعراض قائمة الكتب مع الترقيم والبحث والفلترة",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                description: "البحث في اسم الكتاب أو الوصف",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "category_id",
                in: "query",
                required: false,
                description: "الفلترة حسب رقم التصنيف",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "author_id",
                in: "query",
                required: false,
                description: "الفلترة حسب رقم المؤلف",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "رقم الصفحة المراد استعراضها (الترقيم الصفحي)",
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "عدد العناصر في الصفحة الواحدة",
                schema: new OA\Schema(type: "integer", default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "قائمة الكتب المسترجعة بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "title", type: "string", example: "مقدمة ابن خلدون"),
                                    new OA\Property(property: "description", type: "string", example: "كتاب يؤسس لعلم الاجتماع والتاريخ."),
                                    new OA\Property(property: "cover_image", type: "string", example: "storage/covers/abcdef.jpg"),
                                    new OA\Property(property: "category_id", type: "integer", example: 1),
                                    new OA\Property(property: "author_id", type: "integer", example: 1),
                                    new OA\Property(property: "user_id", type: "integer", example: 1),
                                    new OA\Property(
                                        property: "category",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "تاريخ")
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "author",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "ابن خلدون")
                                        ]
                                    )
                                ]
                            )
                        ),
                        new OA\Property(property: "total", type: "integer", example: 50)
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $query = Book::with(['category', 'author']);

        // البحث حسب العنوان أو الوصف
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // الفلترة بالتصنيف
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // الفلترة بالمؤلف
        if ($request->has('author_id') && !empty($request->author_id)) {
            $query->where('author_id', $request->author_id);
        }

        // إرجاع النتيجة بالترقيم الصفحي
        $books = $query->paginate($request->get('per_page', 10));

        return response()->json($books, 200);
    }

    #[OA\Post(
        path: "/books",
        summary: "إضافة كتاب جديد مع إمكانية رفع صورة الغلاف",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["title", "category_id", "author_id"],
                    properties: [
                        new OA\Property(property: "title", type: "string", description: "عنوان الكتاب", example: "مقدمة ابن خلدون"),
                        new OA\Property(property: "description", type: "string", description: "وصف الكتاب", example: "كتاب يؤسس لعلم الاجتماع والتاريخ"),
                        new OA\Property(property: "category_id", type: "integer", description: "معرّف التصنيف", example: 1),
                        new OA\Property(property: "author_id", type: "integer", description: "معرّف المؤلف", example: 1),
                        new OA\Property(property: "cover_image", type: "string", format: "binary", description: "ملف صورة الغلاف (اختياري)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "تمت إضافة الكتاب بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "مقدمة ابن خلدون"),
                        new OA\Property(property: "description", type: "string", example: "كتاب يؤسس لعلم الاجتماع والتاريخ"),
                        new OA\Property(property: "cover_image", type: "string", example: "storage/covers/abcdef.jpg"),
                        new OA\Property(property: "category_id", type: "integer", example: 1),
                        new OA\Property(property: "author_id", type: "integer", example: 1),
                        new OA\Property(property: "user_id", type: "integer", example: 1),
                        new OA\Property(property: "created_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح بالدخول (Bearer Token مفقود أو غير صالح)"),
            new OA\Response(response: 422, description: "خطأ في التحقق من البيانات (مثل تصنيف أو مؤلف غير موجود)")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'author_id' => 'required|exists:authors,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $path = $file->store('covers', 'public');
            $coverImagePath = 'storage/' . $path;
        }

        $book = Book::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'cover_image' => $coverImagePath,
            'category_id' => $validated['category_id'],
            'author_id' => $validated['author_id'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json($book, 201);
    }

    #[OA\Get(
        path: "/books/{id}",
        summary: "عرض تفاصيل كتاب محدد",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف الكتاب",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "تفاصيل الكتاب والمؤلف والتصنيف",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "مقدمة ابن خلدون"),
                        new OA\Property(property: "description", type: "string", example: "كتاب يؤسس لعلم الاجتماع والتاريخ"),
                        new OA\Property(property: "cover_image", type: "string", example: "storage/covers/abcdef.jpg"),
                        new OA\Property(property: "category_id", type: "integer", example: 1),
                        new OA\Property(property: "author_id", type: "integer", example: 1),
                        new OA\Property(property: "user_id", type: "integer", example: 1),
                        new OA\Property(
                            property: "category",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "تاريخ")
                            ]
                        ),
                        new OA\Property(
                            property: "author",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "ابن خلدون")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "الكتاب غير موجود")
        ]
    )]
    public function show($id)
    {
        $book = Book::with(['category', 'author'])->find($id);

        if (!$book) {
            return response()->json(['message' => 'الكتاب غير موجود'], 404);
        }

        return response()->json($book, 200);
    }

    #[OA\Post(
        path: "/books/{id}",
        summary: "تحديث معلومات كتاب (مع دعم رفع صورة جديدة)",
        description: "ملاحظة: بسبب قيود معالجة الملفات في PHP لطلبات PUT، يرجى إرسال هذا الطلب كـ POST وإضافة الحقل `_method` بقيمة `PUT` داخل الـ Form-Data لتحديث صورة الغلاف.",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف الكتاب المراد تحديثه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["title", "category_id", "author_id", "_method"],
                    properties: [
                        new OA\Property(property: "_method", type: "string", example: "PUT", description: "يجب تمرير PUT لتحديث البيانات بالكامل عبر POST"),
                        new OA\Property(property: "title", type: "string", example: "مقدمة ابن خلدون (نسخة منقحة)"),
                        new OA\Property(property: "description", type: "string", example: "تفاصيل منقحة لكتاب ابن خلدون الشهير"),
                        new OA\Property(property: "category_id", type: "integer", example: 1),
                        new OA\Property(property: "author_id", type: "integer", example: 1),
                        new OA\Property(property: "cover_image", type: "string", format: "binary", description: "ملف صورة غلاف جديدة (اختياري)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم التحديث بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "مقدمة ابن خلدون (نسخة منقحة)"),
                        new OA\Property(property: "description", type: "string", example: "تفاصيل منقحة لكتاب ابن خلدون الشهير"),
                        new OA\Property(property: "cover_image", type: "string", example: "storage/covers/new_image.jpg"),
                        new OA\Property(property: "category_id", type: "integer", example: 1),
                        new OA\Property(property: "author_id", type: "integer", example: 1),
                        new OA\Property(property: "user_id", type: "integer", example: 1)
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 403, description: "غير مسموح لك بتحديث هذا الكتاب (ليس من إنشائك)"),
            new OA\Response(response: 404, description: "الكتاب غير موجود"),
            new OA\Response(response: 422, description: "خطأ في التحقق من الحقول")
        ]
    )]
    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'الكتاب غير موجود'], 404);
        }

        // التحقق من أن المستخدم هو من أنشأ الكتاب (أمان إضافي للمكتبة الشخصية)
        if ($book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مسموح لك بتعديل بيانات كتاب لم تقم بإضافته'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'author_id' => 'required|exists:authors,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $dataToUpdate = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'author_id' => $validated['author_id'],
        ];

        if ($request->hasFile('cover_image')) {
            // حذف الصورة القديمة إذا وجدت
            if ($book->cover_image) {
                $oldPath = str_replace('storage/', '', $book->cover_image);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('cover_image');
            $path = $file->store('covers', 'public');
            $dataToUpdate['cover_image'] = 'storage/' . $path;
        }

        $book->update($dataToUpdate);

        return response()->json($book, 200);
    }

    #[OA\Delete(
        path: "/books/{id}",
        summary: "حذف كتاب بالكامل",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "معرّف الكتاب المراد حذفه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "تم حذف الكتاب بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم حذف الكتاب بنجاح")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 403, description: "غير مسموح لك بحذف كتاب لم تقم بإضافته"),
            new OA\Response(response: 404, description: "الكتاب غير موجود")
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'الكتاب غير موجود'], 404);
        }

        if ($book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مسموح لك بحذف كتاب لم تقم بإضافته'], 403);
        }

        // حذف صورة الغلاف من السيرفر
        if ($book->cover_image) {
            $path = str_replace('storage/', '', $book->cover_image);
            Storage::disk('public')->delete($path);
        }

        $book->delete();

        return response()->json(['message' => 'تم حذف الكتاب بنجاح'], 200);
    }
}
