<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::insert([
            ['title' => 'Primer Post', 'slug' => 'primer_post' , 'category_id' => 1, 'content' => 'Contenido del primer post', 'user_id' => 1],
            ['title' => 'Segundo Post', 'slug' => 'segundo_post' , 'category_id' => 2, 'content' => 'Contenido del segundo post', 'user_id' => 1],
            ['title' => 'Tercer Post', 'slug' => 'tercer_post' , 'category_id' => 3, 'content' => 'Contenido del tercer post', 'user_id' => 1],
        ]);
    }
}
