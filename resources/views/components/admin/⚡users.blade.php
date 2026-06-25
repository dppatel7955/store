<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = [
        'search' => ['except' => '']
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('is_admin', 'desc')
            ->orderBy('id', 'asc')
            ->paginate(12);
    }

    public function toggleAdmin($id)
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'You cannot revoke your own administrator privileges.');
            return;
        }

        $user = User::findOrFail($id);
        $user->is_admin = !$user->is_admin;
        $user->save();

        session()->flash('success', "Updated privileges for '{$user->name}' successfully.");
    }

    public function deleteUser($id)
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user = User::findOrFail($id);
        $user->delete();
        session()->flash('success', 'User deleted successfully.');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Users</h1>
            <p class="text-xs text-slate-500 mt-1">Manage user privileges and administrator status.</p>
        </div>
    </div>

    <!-- Feedback messages -->
    @if (session()->has('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs font-semibold text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <!-- Toolbar -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex-1 max-w-xs relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search users..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
            />
            <span class="absolute left-3 top-2.5 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Joined Date</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Administrator</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($this->users as $user)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <!-- User info -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-650 font-bold border border-indigo-100">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800 leading-normal">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                                <span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full ml-1">You</span>
                                            @endif
                                        </span>
                                        <span class="text-[10px] text-slate-500">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <!-- Joined Date -->
                            <td class="p-4 text-xs text-slate-550">{{ $user->created_at->format('M d, Y') }}</td>
                            <!-- Admin Switch -->
                            <td class="p-4 text-center">
                                <button 
                                    wire:click="toggleAdmin({{ $user->id }})"
                                    @if($user->id === auth()->id()) disabled @endif
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $user->is_admin ? 'bg-indigo-600' : 'bg-slate-200' }} {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    title="{{ $user->id === auth()->id() ? 'You cannot revoke your own privileges' : 'Toggle Administrator Role' }}"
                                >
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $user->is_admin ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <!-- Actions -->
                            <td class="p-4 text-right">
                                <button 
                                    wire:confirm="Are you sure you want to delete this user?"
                                    wire:click="deleteUser({{ $user->id }})"
                                    @if($user->id === auth()->id()) disabled @endif
                                    class="text-rose-600 hover:text-rose-700 text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-xs text-slate-400 font-semibold">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->users->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/40">
                {{ $this->users->links() }}
            </div>
        @endif
    </div>
</div>
