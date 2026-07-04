<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Category::latest('id')->get();
    }

    public function headings(): array
    {
        return ['name', 'slug', 'description', 'image', 'is_active'];
    }

    /**
    * @param Category $category
    */
    public function map($category): array
    {
        return [
            $category->name,
            $category->slug,
            $category->description,
            $category->image,
            $category->is_active ? 1 : 0,
        ];
    }
}
