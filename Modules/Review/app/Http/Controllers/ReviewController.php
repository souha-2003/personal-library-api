<?php

namespace Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Review\Models\Review;
use Modules\Book\Models\Book;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: "/books/{book_id}/reviews",
        summary: "استعراض كافة التقييمات لكتاب محدد وحساب متوسط التقييم",
        tags: ["Reviews"],
        parameters: [
            new OA\Parameter(
                name: "book_id",
                in: "path",
                required: true,
                description: "معرّف الكتاب لجلب تقييماته",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "قائمة التقييمات ومتوسط التقييم الإجمالي",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "book_title", type: "string", example: "مقدمة ابن خلدون"),
                        new OA\Property(property: "average_rating", type: "number", format: "float", example: 4.5),
                        new OA\Property(property: "total_reviews", type: "integer", example: 2),
                        new OA\Property(
                            property: "reviews",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "rating", type: "integer", example: 5),
                                    new OA\Property(property: "comment", type: "string", example: "كتاب رائع جداً"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(
                                        property: "user",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "أحمد محمد")
                                        ]
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "الكتاب غير موجود")
        ]
    )]
    public function index($bookId)
    {
        $book = Book::find($bookId);

        if (!$book) {
            return response()->json(['message' => 'الكتاب غير موجود'], 404);
        }

        $reviews = Review::with('user:id,name')
            ->where('book_id', $bookId)
            ->get();

        $averageRating = $reviews->avg('rating') ?: 0;

        return response()->json([
            'book_title' => $book->title,
            'average_rating' => round($averageRating, 2),
            'total_reviews' => $reviews->count(),
            'reviews' => $reviews
        ], 200);
    }

    #[OA\Post(
        path: "/books/{book_id}/reviews",
        summary: "إضافة تقييم وملاحظة لكتاب محدد",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "book_id",
                in: "path",
                required: true,
                description: "معرّف الكتاب المراد تقييمه",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["rating"],
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, description: "التقييم من 1 إلى 5 نجوم", example: 5),
                    new OA\Property(property: "comment", type: "string", description: "التعليق أو الملاحظة", example: "كتاب رائع أنصح بقراءته.")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "تمت إضافة التقييم بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "تم إضافة التقييم بنجاح"),
                        new OA\Property(
                            property: "review",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "book_id", type: "integer", example: 1),
                                new OA\Property(property: "user_id", type: "integer", example: 1),
                                new OA\Property(property: "rating", type: "integer", example: 5),
                                new OA\Property(property: "comment", type: "string", example: "كتاب رائع أنصح بقراءته."),
                                new OA\Property(property: "created_at", type: "string", format: "date-time")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "لقد قمت بتقييم هذا الكتاب مسبقاً"),
            new OA\Response(response: 401, description: "غير مصرح"),
            new OA\Response(response: 404, description: "الكتاب غير موجود"),
            new OA\Response(response: 422, description: "خطأ في التحقق من البيانات (التقييم ليس بين 1 و 5)")
        ]
    )]
    public function store(Request $request, $bookId)
    {
        $book = Book::find($bookId);

        if (!$book) {
            return response()->json(['message' => 'الكتاب غير موجود'], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // منع التقييم المكرر لنفس الكتاب من نفس المستخدم
        $existing = Review::where('book_id', $bookId)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'لقد قمت بتقييم هذا الكتاب مسبقاً'], 400);
        }

        $review = Review::create([
            'book_id' => $bookId,
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'تم إضافة التقييم بنجاح',
            'review' => $review
        ], 201);
    }
}
