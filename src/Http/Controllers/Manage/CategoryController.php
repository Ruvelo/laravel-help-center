<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers\Manage;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Ruvelo\HelpCenter\Exceptions\CategoryAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryNotEmpty;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\Models\Category;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return response()->view('help::manage.categories', [
            'categories' => Category::query()->withCount('articles')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $category = (new Category)->commit($this->validated($request));
        } catch (InvalidTitle|CategoryAlreadyExists $e) {
            throw ValidationException::withMessages(['name' => $e instanceof CategoryAlreadyExists ? 'A category with this name already exists.' : $e->getMessage()]);
        }

        return redirect()->route('help.manage.categories.index')->with('help.status', "Created “{$category->name}”.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        try {
            $category->commit($this->validated($request));
        } catch (InvalidTitle $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }

        return redirect()->route('help.manage.categories.index')->with('help.status', "Saved “{$category->name}”.");
    }

    public function move(Request $request, Category $category): RedirectResponse
    {
        $category->move($request->input('direction') === 'up' ? -1 : 1);

        return redirect()->route('help.manage.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->remove();
        } catch (CategoryNotEmpty $e) {
            return redirect()->route('help.manage.categories.index')->with('help.status', $e->getMessage());
        }

        return redirect()->route('help.manage.categories.index')->with('help.status', "Deleted “{$category->name}”.");
    }

    /**
     * @return array{name: string, icon: string|null, description: string|null}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        return [
            'name' => (string) $data['name'],
            'icon' => isset($data['icon']) ? (string) $data['icon'] : null,
            'description' => isset($data['description']) ? (string) $data['description'] : null,
        ];
    }
}
