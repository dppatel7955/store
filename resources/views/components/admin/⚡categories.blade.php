<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $isOpen = false;

    // Form fields
    public $categoryId = null;
    public $name = '';
    public $slug = '';
    public $parent_id = null;
    public $description = '';
    public $image = '';
    public $imageFile = null;
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => '']
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedName($value)
    {
        if (!$this->categoryId) {
            $this->slug = Str::slug($value);
        }
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->with('parent')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
            })
            ->latest('id')
            ->paginate(8);
    }

    public function openModal($id = null)
    {
        $this->resetErrorBag();
        $this->imageFile = null;

        if ($id) {
            $category = Category::findOrFail($id);
            $this->categoryId = $category->id;
            $this->name = $category->name;
            $this->slug = $category->slug;
            $this->parent_id = $category->parent_id;
            $this->description = $category->description;
            $this->image = $category->image;
            $this->is_active = (bool) $category->is_active;
        } else {
            $this->resetFields();
        }

        $this->isOpen = true;
    }

    public function resetFields()
    {
        $this->categoryId = null;
        $this->name = '';
        $this->slug = '';
        $this->parent_id = null;
        $this->description = '';
        $this->image = '';
        $this->imageFile = null;
        $this->is_active = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|min:3|max:255',
            'slug' => 'required|max:255|unique:categories,slug,' . ($this->categoryId ?? 'NULL') . ',id',
            'parent_id' => 'nullable|exists:categories,id|different:categoryId',
            'description' => 'nullable|max:1000',
            'imageFile' => 'nullable|image|max:2048',
            'is_active' => 'required|boolean'
        ];

        if (!$this->categoryId && !$this->imageFile) {
            $rules['imageFile'] = 'required|image|max:2048';
        }

        $this->validate($rules);

        if ($this->imageFile) {
            if ($this->categoryId) {
                $oldCategory = Category::find($this->categoryId);
                if ($oldCategory && $oldCategory->image && !str_starts_with($oldCategory->image, 'http')) {
                    $oldPath = public_path(ltrim($oldCategory->image, '/'));
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }
            $this->image = '/uploads/' . $this->imageFile->store('categories', 'custom_public');
        }

        if (empty($this->image)) {
            $this->image = 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?q=80&w=200&auto=format&fit=crop';
        }

        Category::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'parent_id' => $this->parent_id ?: null,
                'description' => $this->description,
                'image' => $this->image,
                'is_active' => $this->is_active
            ]
        );

        $this->dispatch('swal', title: 'Success!', text: $this->categoryId ? 'Category updated successfully.' : 'Category created successfully.', icon: 'success');
        $this->isOpen = false;
        $this->resetFields();
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();
        $this->dispatch('swal', title: 'Success!', text: 'Category status updated successfully.', icon: 'success');
    }

    public function delete($id)
    {
        $category = Category::findOrFail($id);
        
        // Prevent deleting categories that might have products bound to them
        if ($category->products()->count() > 0) {
            $this->dispatch('swal', title: 'Error!', text: "Cannot delete category '{$category->name}' because it contains products.", icon: 'error');
            return;
        }

        if ($category->image && !str_starts_with($category->image, 'http')) {
            $path = public_path(ltrim($category->image, '/'));
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        $category->delete();
        $this->dispatch('swal', title: 'Deleted!', text: 'Category deleted successfully.', icon: 'success');
    }

    public $csvFile;

    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt,xlsx,xls|max:4096',
        ]);

        try {
            $import = new \App\Imports\CategoriesImport;
            \Excel::import($import, $this->csvFile->getRealPath());

            $this->dispatch('swal', title: 'Import Completed!', text: "Successfully imported {$import->getImportedCount()} categories.", icon: 'success');
        } catch (\Exception $e) {
            $this->dispatch('swal', title: 'Import Failed!', text: $e->getMessage(), icon: 'error');
        }
        $this->resetPage();
    }

    public function exportCsv()
    {
        $fileName = 'categories_export_' . now()->format('Y_m_d_His') . '.csv';
        return \Excel::download(new \App\Exports\CategoriesExport, $fileName);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Categories</h1>
            <p class="text-xs text-slate-500 mt-1">Manage catalog categories for your items.</p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <a 
                href="/sample_categories_import.csv" 
                download="sample_categories_import.csv"
                class="rounded-xl bg-white border border-slate-205 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center flex items-center justify-center gap-1.5 shadow-sm"
                title="Download Sample CSV Template"
            >
                <span>Sample CSV</span>
                <svg class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
            </a>
            <button 
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                class="rounded-xl bg-white border border-slate-205 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center flex items-center justify-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                title="Export Categories Catalog to CSV"
            >
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-3.5 w-3.5 text-slate-550" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Exporting...</span>
                </span>
                <svg wire:loading.remove wire:target="exportCsv" class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </button>
            <label 
                wire:loading.class="opacity-60 cursor-not-allowed pointer-events-none" 
                wire:target="csvFile"
                class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition text-center cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
            >
                <span wire:loading.remove wire:target="csvFile">Import CSV</span>
                <span wire:loading wire:target="csvFile" class="flex items-center gap-1.5">
                    <svg class="animate-spin h-3.5 w-3.5 text-slate-550" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Importing...</span>
                </span>
                <input type="file" wire:model="csvFile" class="hidden" accept=".csv" wire:loading.attr="disabled" wire:target="csvFile">
                <svg wire:loading.remove wire:target="csvFile" class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V4a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
            </label>
            <button 
                wire:click="openModal()" 
                class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition text-center"
            >
                Add Category
            </button>
        </div>
    </div>



    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="w-full sm:max-w-xs relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search categories..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
            />
            <span class="absolute left-3 top-2.5 text-slate-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Image</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Parent</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Slug</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Description</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->categories as $category)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <!-- Image -->
                            <td class="p-4">
                                <img src="{{ $category->image }}" alt="{{ $category->name }}" class="h-10 w-10 object-cover rounded-lg border border-slate-200">
                            </td>
                            <!-- Name -->
                            <td class="p-4 text-xs font-bold text-slate-800">{{ $category->name }}</td>
                            <!-- Parent -->
                            <td class="p-4 text-xs text-slate-600 font-semibold">
                                @if($category->parent)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px]">
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-normal">-</span>
                                @endif
                            </td>
                            <!-- Slug -->
                            <td class="p-4 text-xs text-slate-500 font-mono">{{ $category->slug }}</td>
                            <!-- Description -->
                            <td class="p-4 text-xs text-slate-600 max-w-xs truncate">{{ $category->description ?? '-' }}</td>
                            <!-- Status -->
                            <td class="p-4">
                                <button 
                                    wire:click="toggleStatus({{ $category->id }})"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $category->is_active ? 'bg-indigo-600' : 'bg-slate-200' }}"
                                >
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $category->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <!-- Actions -->
                            <td class="p-4 text-right space-x-2">
                                <button 
                                    wire:click="openModal({{ $category->id }})" 
                                    class="text-indigo-605 hover:text-indigo-700 text-xs font-bold transition"
                                >
                                    Edit
                                </button>
                                <button 
                                    wire:confirm="Are you sure you want to delete this category?"
                                    wire:click="delete({{ $category->id }})" 
                                    class="text-rose-605 hover:text-rose-700 text-xs font-bold transition"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-xs text-slate-400 font-semibold">
                                No categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($this->categories->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/40">
                {{ $this->categories->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Slide Over/Dialog) -->
    <div 
        x-data="{ show: @entangle('isOpen') }" 
        x-show="show" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" 
        style="display: none;"
    >
        <!-- Backdrop -->
        <div 
            x-show="show" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="show = false" 
            class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity"
        ></div>

        <!-- Modal panel -->
        <div 
            x-show="show" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-2xl transition-all"
        >
            <div class="px-6 py-6 bg-white border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-base font-extrabold text-slate-900">
                    {{ $categoryId ? 'Edit Category' : 'Create Category' }}
                </h3>
                <button @click="show = false" class="text-slate-500 hover:text-slate-800 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Category Name</label>
                    <input 
                        type="text" 
                        wire:model.live="name" 
                        placeholder="e.g. Smart Home" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    @error('name') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Slug</label>
                    <input 
                        type="text" 
                        wire:model="slug" 
                        placeholder="smart-home" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition font-mono"
                    />
                    @error('slug') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Parent Category -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Parent Category (Optional)</label>
                    <select 
                        wire:model="parent_id"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    >
                        <option value="">-- None (Make Root Category) --</option>
                        @foreach(\App\Models\Category::whereNull('parent_id')->where('id', '!=', $categoryId)->get() as $parentCat)
                            <option value="{{ $parentCat->id }}">{{ $parentCat->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Image File Upload -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Category Image</label>
                    <div class="flex items-center gap-4">
                        @if($imageFile)
                            <div class="relative h-16 w-16 rounded-xl overflow-hidden border border-indigo-200 bg-slate-50 shadow-sm flex-shrink-0 bg-slate-100 flex items-center justify-center">
                                <?php try { ?>
                                    <img src="{{ $imageFile->temporaryUrl() }}" class="h-full w-full object-cover">
                                <?php } catch (\Throwable $e) { ?>
                                    <span class="text-[9px] font-bold text-slate-400 text-center leading-tight px-1">Uploaded</span>
                                <?php } ?>
                            </div>
                        @elseif($image)
                            <div class="relative h-16 w-16 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm flex-shrink-0">
                                <img src="{{ $image }}" class="h-full w-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1531297484001-80022131f5a1?q=80&w=200&auto=format&fit=crop'">
                            </div>
                        @endif
                        <div class="flex-1">
                            <label class="flex flex-col items-center justify-center w-full h-16 border-2 border-slate-200 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100/50 transition">
                                <div class="flex flex-col items-center justify-center pt-1.5 pb-1.5">
                                    <svg class="w-5 h-5 text-slate-450 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-[9px] text-slate-500 font-bold">Select image file</p>
                                </div>
                                <input type="file" wire:model="imageFile" accept="image/*" class="hidden" />
                            </label>
                        </div>
                    </div>
                    @error('imageFile') <span class="text-[10px] text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                    @error('image') <span class="text-[10px] text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Description (Optional)</label>
                    <textarea 
                        wire:model="description" 
                        rows="3" 
                        placeholder="Brief overview of the category..." 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    ></textarea>
                    @error('description') <span class="text-[10px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Status Toggle -->
                <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                    <span class="text-xs font-semibold text-slate-700">Active Status</span>
                    <button 
                        type="button"
                        wire:click="$toggle('is_active')"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-indigo-600' : 'bg-slate-200' }}"
                    >
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <!-- Footer buttons -->
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4 mt-6">
                    <button 
                        type="button" 
                        @click="show = false" 
                        class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-600 hover:to-purple-700 transition"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
