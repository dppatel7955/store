<?php

namespace App\Imports;

use App\Models\Brand;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BrandsImport implements ToModel, WithHeadingRow
{
    private $importedCount = 0;

    public function model(array $row)
    {
        $name = isset($row['name']) ? trim($row['name']) : '';
        if (!$name) {
            return null;
        }

        $slug = !empty($row['slug']) ? trim($row['slug']) : Str::slug($name);

        $brand = Brand::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'logo' => !empty($row['logo']) ? trim($row['logo']) : 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=100&auto=format&fit=crop',
                'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
            ]
        );

        $this->importedCount++;
        return $brand;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
