<?php

namespace App\Imports;

use App\Models\Category;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoriesImport implements ToModel, WithHeadingRow
{
    private $importedCount = 0;

    public function model(array $row)
    {
        $name = isset($row['name']) ? trim($row['name']) : '';
        if (!$name) {
            return null;
        }

        $slug = !empty($row['slug']) ? trim($row['slug']) : Str::slug($name);

        $category = Category::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => isset($row['description']) ? trim($row['description']) : null,
                'image' => !empty($row['image']) ? trim($row['image']) : 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600&auto=format&fit=crop',
                'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
            ]
        );

        $this->importedCount++;
        return $category;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
