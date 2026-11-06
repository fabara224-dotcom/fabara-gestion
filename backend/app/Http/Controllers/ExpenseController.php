<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->user()->scopedBranchId();
        $month = $request->input('mois', now()->format('Y-m'));
        [$y, $m] = explode('-', $month) + [now()->year, now()->month];

        $query = Expense::with('user')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereYear('spent_on', $y)->whereMonth('spent_on', $m);

        return view('expenses.index', [
            'expenses' => (clone $query)->latest('spent_on')->paginate(30)->withQueryString(),
            'byCategory' => (clone $query)->selectRaw('category, SUM(amount) t')->groupBy('category')->pluck('t', 'category'),
            'total' => (int) (clone $query)->sum('amount'),
            'month' => sprintf('%04d-%02d', $y, $m),
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:1'],
            'spent_on' => ['required', 'date'],
        ]);
        Expense::create([...$data, 'branch_id' => $request->user()->branch_id, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Dépense enregistrée.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Dépense supprimée.');
    }
}
