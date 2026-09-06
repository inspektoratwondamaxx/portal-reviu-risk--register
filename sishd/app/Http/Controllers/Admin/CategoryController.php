<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.referensi.kategori', [
            'items' => Category::with('parent')->orderBy('nama')->paginate(20),
            'parents' => Category::whereNull('parent_id')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'kode' => ['nullable', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['nullable', 'in:material,upah,peralatan,jasa,lainnya'],
        ]);

        Category::create($validated + ['is_active' => true]);

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'kode' => ['nullable', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['nullable', 'in:material,upah,peralatan,jasa,lainnya'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->sshItems()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Kategori masih dipakai data lain, tidak bisa dihapus.']);
        }

        $category->delete();

        return back()->with('status', 'Kategori dihapus.');
    }
}
