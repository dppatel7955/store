<?php

namespace App\Exports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BrandsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = '')
    {
        $this->search = $search;
    }

    public function collection()
    {
        return Brand::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
            })
            ->latest('id')
            ->get();
    }

    public function headings(): array
    {
        return ['name', 'slug', 'logo', 'is_active'];
    }

    /**
    * @param Brand $brand
    */
    public function map($brand): array
    {
        return [
            $brand->name,
            $brand->slug,
            $brand->logo,
            $brand->is_active ? 1 : 0,
        ];
    }
}
