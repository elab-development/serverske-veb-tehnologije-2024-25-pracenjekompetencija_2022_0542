<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name' => 'Cloud & DevOps',
                'description' => 'Certifikati iz oblasti cloud platformi i DevOps praksi.'
            ],
            [
                'name' => 'Backend Development',
                'description' => 'Backend jezici, framework-ovi i arhitekture.'
            ],
            [
                'name' => 'Data & AI',
                'description' => 'Data science, ML/AI i data engineering sertifikati.'
            ],
            [
                'name' => 'Cybersecurity',
                'description' => 'Bezbednost informacija, mrežna i aplikativna bezbednost.'
            ],
            [
                'name' => 'Project & Product',
                'description' => 'Upravljanje projektima, agilne metodologije.'
            ],
            [
                'name' => 'UX & Design',
                'description' => 'UX istraživanje, UI dizajn i dizajn sistemi.'
            ],
        ];

        foreach ($items as $item) {
            Category::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
