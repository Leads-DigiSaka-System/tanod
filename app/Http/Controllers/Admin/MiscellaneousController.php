<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TractorPart;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class MiscellaneousController extends Controller
{
    public function index(Request $request): Response
    {
        $parts = TractorPart::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Miscellaneous/Index', [
            'parts' => $parts,
            'filters' => $request->only(['search']),
        ]);
    }

    public function apiIndex(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => TractorPart::orderBy('name')->get(['id', 'name', 'amount']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0|max:99999999.99',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($request->input('amount') === '' || $request->input('amount') === null) {
            $data['amount'] = null;
        }

        $part = TractorPart::create($data);

        ActivityLogger::log('TractorPart', $part->id, 'created', [
            'name' => $part->name,
        ], $request->user());

        return Redirect::back()->with('success', 'Part added.');
    }

    public function update(Request $request, TractorPart $part): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0|max:99999999.99',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($request->input('amount') === '' || $request->input('amount') === null) {
            $data['amount'] = null;
        }

        $part->update($data);

        ActivityLogger::log('TractorPart', $part->id, 'updated', [
            'name' => $part->name,
        ], $request->user());

        return Redirect::back()->with('success', 'Part updated.');
    }

    public function destroy(TractorPart $part): RedirectResponse
    {
        $part->delete();

        ActivityLogger::log('TractorPart', $part->id, 'deleted', [
            'name' => $part->name,
        ], request()->user());

        return Redirect::back()->with('success', 'Part deleted.');
    }
}
