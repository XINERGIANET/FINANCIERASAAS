<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Advisor;

class AdvisorController extends Controller
{
    public function index(Request $request)
    {
        $advisors = Advisor::active()->when($request->search, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })->orderBy('name', 'asc')->paginate(20);

        return view('advisors.index', compact('advisors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'document' => 'nullable|digits:8',
            'phone'    => 'nullable|digits:9',
            'email'    => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error'  => $validator->errors()->first(),
            ]);
        }

        Advisor::create([
            'name'     => $request->name,
            'document' => $request->document,
            'phone'    => $request->phone,
            'email'    => $request->email,
        ]);

        return response()->json(['status' => true]);
    }

    public function edit(Request $request, Advisor $advisor)
    {
        return response()->json($advisor);
    }

    public function update(Request $request, Advisor $advisor)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'document' => 'nullable|digits:8',
            'phone'    => 'nullable|digits:9',
            'email'    => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error'  => $validator->errors()->first(),
            ]);
        }

        $advisor->update($request->only(['name', 'document', 'phone', 'email']));

        return response()->json(['status' => true]);
    }

    public function drop(string $id)
    {
        $advisor = Advisor::findOrFail($id);
        $advisor->update(['state' => 1]);

        return response()->json(['status' => true]);
    }

    public function up(string $id)
    {
        $advisor = Advisor::findOrFail($id);
        $advisor->update(['state' => 0]);

        return response()->json(['status' => true]);
    }

    public function destroy(Advisor $advisor)
    {
        $advisor->update(['deleted' => 1]);

        return response()->json(['status' => true]);
    }

    public function api(Request $request)
    {
        $advisors = Advisor::active()
            ->where('state', 0)
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'document']);

        return response()->json(['status' => true, 'advisors' => $advisors]);
    }
}
