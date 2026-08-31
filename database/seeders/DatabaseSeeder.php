<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'أحمد محمد',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // إدخال تصنيفات تجريبية
        $c1 = \Modules\Book\Models\Category::create(['name' => 'روايات']);
        $c2 = \Modules\Book\Models\Category::create(['name' => 'تاريخ']);
        $c3 = \Modules\Book\Models\Category::create(['name' => 'علوم']);

        // إدخال مؤلفين تجريبيين
        $a1 = \Modules\Book\Models\Author::create(['name' => 'نجيب محفوظ', 'bio' => 'كاتب وروائي مصري شهير.']);
        $a2 = \Modules\Book\Models\Author::create(['name' => 'ابن خلدون', 'bio' => 'مؤسس علم الاجتماع ومؤرخ شهير.']);

        // إدخال كتب تجريبية
        \Modules\Book\Models\Book::create([
            'title' => 'مقدمة ابن خلدون',
            'description' => 'كتاب مقدمة ابن خلدون الشهير في التاريخ وعلم الاجتماع.',
            'category_id' => $c2->id,
            'author_id' => $a2->id,
            'user_id' => $user->id,
            'cover_image' => null
        ]);

        \Modules\Book\Models\Book::create([
            'title' => 'بين القصرين',
            'description' => 'الرواية الأولى من ثلاثية نجيب محفوظ الشهيرة.',
            'category_id' => $c1->id,
            'author_id' => $a1->id,
            'user_id' => $user->id,
            'cover_image' => null
        ]);
    }
}
