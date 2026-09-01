# 📚 الدليل الشامل والتعليمي لنظام إدارة المكتبة الشخصية (Personal Library API)
## معمارية الوحدات (Laravel Modules) وتوثيق Swagger (OpenAPI)

---

## 📑 الفهرس
1. [نظرة عامة على المشروع](#1-نظرة-عامة-على-المشروع)
2. [المكتبات المستخدمة وسبب اختيارها](#2-المكتبات-المستخدمة-وسبب-اختيارها)
3. [هيكلية المشروع وتشريح الموديولات (Modules Structure)](#3-هيكلية-المشروع-وتشريح-الموديولات)
4. [شرح الأكواد والمنطق البرمجي لكل موديول](#4-شرح-الأكواد-والمنطق-البرمجي-لكل-موديول)
   - [أ. موديول المصادقة (Modules/Auth)](#أ-موديول-المصادقة-modulesauth)
   - [ب. موديول الكتب والتصنيفات والمؤلفين (Modules/Book)](#ب-موديول-الكتب-والتصنيفات-والمؤلفين-modulesbook)
   - [ج. موديول التقييمات (Modules/Review)](#ج-موديول-التقييمات-modulesreview)
5. [كيف يعمل توثيق Swagger والـ OpenAPI Attributes؟](#5-كيف-يعمل-توثيق-swagger-والـ-openapi-attributes)
6. [أوامر الإعداد والتشغيل](#6-أوامر-الإعداد-والتشغيل)
7. [دليل تجربة الـ API خطوة بخطوة عبر واجهة Swagger](#7-دليل-تجربة-الـ-api-خطوة-بخطوة-عبر-واجهة-swagger)

---

## 1. نظرة عامة على المشروع

هذا المشروع عبارة عن **RESTful API** خلفي متكامل (Back-End Only) مبني بإطار عمل **Laravel**، يهدف إلى إدارة مكتبة كتب شخصية، تصنيفاتها، مؤلفيها، وتقييماتها، مع توثيق احترافي وتفاعلي كامل باستخدام **Swagger UI (OpenAPI 3.0)**.

---

## 2. المكتبات المستخدمة وسبب اختيارها

| المكتبة | الأمر الخاص بتثبيتها | ما هي فائدتها في المشروع؟ |
| :--- | :--- | :--- |
| **`laravel/sanctum`** | `composer require laravel/sanctum` | مسؤولة عن نظام الحماية وإصدار الـ **Bearer Tokens** للمستخدمين عند تسجيل الدخول لتأمين الـ Endpoints. |
| **`nwidart/laravel-modules`** | `composer require nwidart/laravel-modules` | تقسيم النظام إلى موديولات برمجية مستقلة ومعزولة داخل مجلد `Modules/` بدلاً من تجميع كل شيء داخل `app/`. |
| **`darkaonline/l5-swagger`** | `composer require darkaonline/l5-swagger` | قراءة التعليقات والـ Attributes البرمجية وتحويلها إلى واجهة تفاعلية تمكنك من تجربة الـ APIs مباشرة من المتصفح. |

---

## 3. هيكلية المشروع وتشريح الموديولات

كل موديول داخل مجلد `Modules/` يمثل وحدة وظيفية مصغرة ومتكاملة تتبع دورة الطلب في لارافيل:

```text
[ Route المسار ] ──> [ Controller المتحكم ] ──> [ Model النموذج ] ──> [ Database قاعدة البيانات ]
```

### المخطط الهيكلي للملفات:
```text
library-api/
├── app/
│   ├── Http/Controllers/Controller.php       <-- الإعدادات العامة لـ Swagger ومعرف Bearer Token
│   └── Models/User.php                       <-- نموذج المستخدم مع HasApiTokens لـ Sanctum
├── Modules/
│   ├── Auth/                                 <-- موديول إدارة الحسابات
│   │   ├── app/Http/Controllers/AuthController.php
│   │   ├── database/migrations/
│   │   └── routes/api.php
│   ├── Book/                                 <-- موديول الكتب والمؤلفين والتصنيفات
│   │   ├── app/Http/Controllers/
│   │   │   ├── CategoryController.php
│   │   │   ├── AuthorController.php
│   │   │   └── BookController.php
│   │   ├── app/Models/
│   │   │   ├── Category.php
│   │   │   ├── Author.php
│   │   │   └── Book.php
│   │   ├── database/migrations/
│   │   └── routes/api.php
│   └── Review/                               <-- موديول التقييمات
│       ├── app/Http/Controllers/ReviewController.php
│       ├── app/Models/Review.php
│       ├── database/migrations/
│       └── routes/api.php
└── config/l5-swagger.php                     <-- إعدادات مسح الموديولات وتوليد التوثيق
```

---

## 4. شرح الأكواد والمنطق البرمجي لكل موديول

### أ. موديول المصادقة (`Modules/Auth`)

#### 1. المسارات (`Modules/Auth/routes/api.php`):
* `POST /api/register`: تسجيل مستخدم جديد (عام).
* `POST /api/login`: تسجيل دخول واستلام Token (عام).
* `POST /api/logout`: تسجيل خروج وإبطال الـ Token الحالي (محمي بـ `auth:sanctum`).
* `GET /api/me`: استرجاع بيانات الحساب المسجل حالياً (محمي بـ `auth:sanctum`).

#### 2. منطق كود التسجيل وتوليد الـ Token (`AuthController.php`):
```php
public function register(Request $request)
{
    // 1. التحقق من صحة المدخلات (Validation)
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    // 2. إنشاء المستخدم وتشفير كلمة السر بأمان
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    // 3. إصدار الـ Bearer Token الخاص بالمستخدم عبر Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    // 4. إرجاع الرد بصيغة JSON مع كود الحالة 201 Created
    return response()->json([
        'message' => 'تم تسجيل المستخدم بنجاح',
        'token' => $token,
        'user' => $user,
    ], 201);
}
```

---

### ب. موديول الكتب والتصنيفات والمؤلفين (`Modules/Book`)

#### 1. العلاقات بين الجداول (Eloquent Relations):
* في نموذج `Book.php`:
  ```php
  public function category() {
      return $this->belongsTo(Category::class); // الكتاب ينتمي لتصنيف
  }
  public function author() {
      return $this->belongsTo(Author::class); // الكتاب ينتمي لمؤلف
  }
  public function reviews() {
      return $this->hasMany(\Modules\Review\Models\Review::class); // الكتاب له مراجعات متعددة
  }
  ```

#### 2. استعراض الكتب مع البحث والفلترة والترقيم الصفحي (`BookController::index`):
```php
public function index(Request $request)
{
    // تحميل العلاقات المرتبطة مسبقاً لتسريع الاستعلام (Eager Loading)
    $query = Book::with(['category', 'author']);

    // البحث في اسم الكتاب أو الوصف
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // الفلترة حسب رقم التصنيف
    if ($request->has('category_id') && !empty($request->category_id)) {
        $query->where('category_id', $request->category_id);
    }

    // الفلترة حسب رقم المؤلف
    if ($request->has('author_id') && !empty($request->author_id)) {
        $query->where('author_id', $request->author_id);
    }

    // إرجاع النتيجة بالترقيم الصفحي Pagination
    return response()->json($query->paginate($request->get('per_page', 10)), 200);
}
```

#### 3. إضافة كتاب ورفع صورة الغلاف (`BookController::store`):
```php
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
    // إذا قام المستخدم برفع ملف صورة
    if ($request->hasFile('cover_image')) {
        $file = $request->file('cover_image');
        // تخزين الصورة في مجلد covers على القرص العام public
        $path = $file->store('covers', 'public');
        $coverImagePath = 'storage/' . $path;
    }

    $book = Book::create([
        'title' => $validated['title'],
        'description' => $validated['description'] ?? null,
        'cover_image' => $coverImagePath,
        'category_id' => $validated['category_id'],
        'author_id' => $validated['author_id'],
        'user_id' => $request->user()->id, // ربط الكتاب بصاحب الحساب
    ]);

    return response()->json($book, 201);
}
```

---

### ج. موديول التقييمات (`Modules/Review`)

#### 1. حساب متوسط التقييم لكتاب محدد (`ReviewController::index`):
```php
public function index($bookId)
{
    $book = Book::find($bookId);
    if (!$book) {
        return response()->json(['message' => 'الكتاب غير موجود'], 404);
    }

    $reviews = Review::with('user:id,name')->where('book_id', $bookId)->get();

    // حساب المتوسط الحسابي للتقييمات
    $averageRating = $reviews->avg('rating') ?: 0;

    return response()->json([
        'book_title' => $book->title,
        'average_rating' => round($averageRating, 2),
        'total_reviews' => $reviews->count(),
        'reviews' => $reviews
    ], 200);
}
```

#### 2. إضافة تقييم ومنع التقييم المكرر (`ReviewController::store`):
```php
public function store(Request $request, $bookId)
{
    $book = Book::find($bookId);
    if (!$book) {
        return response()->json(['message' => 'الكتاب غير موجود'], 404);
    }

    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5', // التقييم من 1 إلى 5 فقط
        'comment' => 'nullable|string',
    ]);

    // التحقق من عدم وجود تقييم سابق من نفس المستخدم لنفس الكتاب
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

    return response()->json(['message' => 'تم إضافة التقييم بنجاح', 'review' => $review], 201);
}
```

---

## 5. كيف يعمل توثيق Swagger والـ OpenAPI Attributes؟

تعتمد لارافيل الحديثة على ميزة **PHP 8 Attributes** لكتابة توثيق الـ API مباشرة فوق الدوال في الكود:

1. **التعريف العام وزر الأمان (`app/Http/Controllers/Controller.php`):**
   ```php
   #[OA\Info(
       version: "1.0.0",
       title: "نظام إدارة المكتبة الشخصية وتقييم الكتب (Personal Library API)",
       description: "RESTful API متكامل لإدارة الكتب، المؤلفين، التصنيفات، وتقييمات الكتب."
   )]
   #[OA\Server(url: "http://localhost:8000/api", description: "Local Server")]
   #[OA\SecurityScheme(
       securityScheme: "bearerAuth",
       type: "http",
       scheme: "bearer",
       bearerFormat: "JWT"
   )]
   ```
   > هذا الكود يضيف زر **Authorize 🔒** في أعلى واجهة الويب لتقومي بوضع الـ Token مرة واحدة وتجربة جميع الـ Endpoints المحمية.

2. **فوق كل مسار في الـ Controllers:**
   * `#[OA\Get]`, `#[OA\Post]`, `#[OA\Put]`, `#[OA\Delete]`: تحدد نوع الطلب والمسار.
   * `tags`: تجميع المسارات تحت أقسام واضحة (`Auth`, `Categories`, `Authors`, `Books`, `Reviews`).
   * `parameters`: توثيق معاملات الرابط مثل `{id}` أو `search` أو `page`.
   * `requestBody`: توثيق شكل البيانات المرسلة سواء كانت `application/json` أو `multipart/form-data` عند رفع الصور.
   * `responses`: توثيق كل رد ممكن مثل `200 OK` أو `401 Unauthorized` أو `422 Validation Error`.

---

## 6. أوامر الإعداد والتشغيل

| الأمر | الشرح والوظيفة |
| :--- | :--- |
| `php artisan l5-swagger:generate` | مسح الـ Attributes وتوليد ملف التوثيق التفاعلي `storage/api-docs/api-docs.json`. |
| `php artisan migrate` | إنشاء وتحديث الجداول في قاعدة البيانات. |
| `php artisan storage:link` | إنشاء اختصار رمزي لمجلد الصور ليتسنى للمتصفح الوصول للصور المرفوعة عبر الرابط العام. |
| `php artisan serve` | تشغيل خادم التطوير المحلي على العنوان `http://localhost:8000`. |

---

## 7. دليل تجربة الـ API خطوة بخطوة عبر واجهة Swagger

1. افتحي المتصفح وانتقلي إلى:
   🔗 **`http://localhost:8000/api/documentation`**

2. **الخطوة الأولى (إنشاء حساب جديد):**
   * توجهي إلى قسم **Auth** واختر `POST /register`.
   * اضغطي على **Try it out**.
   * املئي الحقول باسم، بريد إلكتروني، وكلمة سر، ثم اضغطي **Execute**.
   * انسخي قيمة الـ `token` الناتج في صندوق الاستجابة.

3. **الخطوة الثانية (تفعيل الأمان Authorize):**
   * اصعدي إلى أعلى الواجهة واضغطي على زر **Authorize 🔒**.
   * الصقي الـ Token الذي نسخته في الحقل.
   * اضغطي على **Authorize** ثم **Close**. (أصبحت الآن مسجلة دخول رسمياً في Swagger).

4. **الخطوة الثالثة (إضافة تصنيف ومؤلف):**
   * من قسم **Categories** نفذي `POST /categories` لإضافة تصنيف مثل: `"روايات"`.
   * من قسم **Authors** نفذي `POST /authors` لإضافة مؤلف مثل: `"نجيب محفوظ"`.

5. **الخطوة الرابعة (إضافة كتاب مع صورة غلاف):**
   * من قسم **Books** نفذي `POST /books`.
   * أدخلي اسم الكتاب، ورقم التصنيف `category_id`، ورقم المؤلف `author_id`، واختاري ملف صورة من جهازك لحقل `cover_image`.

6. **الخطوة الخامسة (إضافة واستعراض التقييمات):**
   * من قسم **Reviews** نفذي `POST /books/{book_id}/reviews` لوضع تقييم 5 نجوم وتعليق.
   * نفذي `GET /books/{book_id}/reviews` لمشاهدة المراجعات مع حساب **متوسط التقييم الإجمالي** للكتاب تلقائياً!
