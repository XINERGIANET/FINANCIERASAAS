<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ClientsExport;
use App\Models\ClientImage;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InactiveClientsExport;

class ClientController extends Controller
{
    public function index(Request $request){
        $user = auth()->user();
        $sellers = User::seller()->where('state', 0)->active()->get();
        $clients = Contract::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->where('seller_id', $user->id);
        })->when($request->name, function($query, $name){
            return $query->where('name', 'like', '%'.$name.'%');
        })->when($request->seller_id, function($query, $seller_id){
            return $query->where('seller_id', $seller_id);
        })->latest('date')->latest('id')->groupBy('document')->groupBy('group_name')
        ->when($request->recurrence === 'nuevo', function($query){
            return $query->havingRaw('COUNT(*) = 1');
        })->when($request->recurrence === 'recurrente', function($query){
            return $query->havingRaw('COUNT(*) > 1');
        })->paginate(20);
        
        return view('clients.index', compact('clients', 'sellers'));
    }

    public function excel(Request $request)
    {
        $name = 'Clientes_' . now()->format('d_m_Y') . '.xlsx';

        return Excel::download(new ClientsExport, $name);
    }

    public function inactive(Request $request)
    {
        $sellers = User::seller()->where('state', 0)->active()->get();
        $inactive_clients = $this->inactiveClientsQuery($request)
            ->paginate(20);

        $total_clients = $inactive_clients->total();

        return view('clients.inactive', compact('inactive_clients', 'sellers', 'total_clients'));
    }

    public function inactiveExcel(Request $request)
    {
        $name = 'Clientes_Inactivos_' . now()->format('d_m_Y') . '.xlsx';

        return Excel::download(new InactiveClientsExport, $name);
    }

    public function check(Request $request){
        $quotas = Quota::whereHas('contract', function($query) use ($request){
            return $query->active()->where('document', $request->document);
        })->where('paid', 0)->orderBy('contract_id', 'asc')->orderBy('date', 'asc')->get();

        $status = true;

        if($quotas->count() > 0){
            $status = false;
        }

        $quotas = $quotas->map(function($quota){
            return [
                'id' => $quota->id,
                'contract_id' => $quota->contract_id,
                'number' => $quota->number,
                'amount' => $quota->amount,
                'debt' => $quota->debt,
                'date' => $quota->date->format('d/m/Y'),
                'paid' => $quota->paid
            ];
        });

        return response()->json([
            'status' => $status,
            'quotas' => $quotas
        ]);
    }

    public function details(Request $request){
        
        $client = Contract::active()->where('document', $request->document)->latest('date')->first();

        return response()->json([
            'id' => $client->id,
            'document' => $client->document,
            'name' => $client->name,
            'phone' => $client->phone,
            'address' => $client->address,
            'civil_status' => $client->civil_status
        ]);
    }

    public function update(Request $request)
    {
        $client_type = $request->client_type;

        if ($client_type == 'Personal') {
            $validator = Validator::make($request->all(), [
                'old_document' => 'required',
                'document' => 'required|size:8',
                'name' => 'required',
                'phone' => 'nullable',
                'address' => 'nullable',
                'civil_status' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
            }

            Contract::active()->where('document', $request->old_document)->update([
                'document' => $request->document,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'civil_status' => $request->civil_status,
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'old_group_name' => 'required',
                'group_name' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
            }

            Contract::active()->where('group_name', $request->old_group_name)->update([
                'group_name' => $request->group_name,
            ]);
        }

        return response()->json(['status' => true]);
    }

    public function contracts(Request $request){
        
        $user = auth()->user();
        $contracts = Contract::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->where('seller_id', $user->id);
        })->when($request->client_type, function($query, $client_type) use($request){
            if($client_type == 'Personal'){
                return $query->where('document', $request->document);
            }elseif($client_type == 'Grupo'){
                return $query->where('group_name', $request->group_name);
            }
        })->with('quotas')->latest('date')->get();

        return response()->json($contracts->map(function($contract){
            return [
                'id' => $contract->id,
                'requested_amount' => $contract->requested_amount,
                'quotas_number' => $contract->quotas_number,
                'interest' => $contract->interest,
                'payable_amount' => $contract->payable_amount,
                'date' => $contract->date->format('d/m/Y'),
                'paid' => $contract->paid
            ];
        }));
    }

    public function quotas(Request $request){
        $contract_id = $request->contract_id;

        $quotas = Quota::where('contract_id', $contract_id)->get();

        return response()->json($quotas->map(function($quota){
            return [
                'id' => $quota->id,
                'number' => $quota->number,
                'amount' => $quota->amount,
                'debt' => $quota->debt,
                'date' => $quota->date->format('d/m/Y'),
                'paid' => $quota->paid
            ];
        }));
    }

    public function api(Request $request){
        $contracts = Contract::active()->with('district.province.department')->where(function($query) use($request){
            return $query->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('document', 'like', '%'.$request->q.'%');
        })->where('client_type', 'Personal')->latest('date')->latest('id')->get()->unique('document')->values();
        return response()->json(['items' => $contracts]);
    }

    public function images(Request $request)
    {
        $images = ClientImage::active()
            ->when($request->document, fn($q, $v) => $q->where('document', $v))
            ->when($request->group_name, fn($q, $v) => $q->where('group_name', $v))
            ->get()
            ->map(fn($img) => [
                'id'  => $img->id,
                'url' => Storage::url($img->path),
            ]);

        return response()->json($images);
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,svg,tiff,tif,heic,heif,avif|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        $path = $request->file('image')->store('client-images', 'public');

        ClientImage::create([
            'company_id' => auth()->user()->company_id,
            'document'   => $request->document ?: null,
            'group_name' => $request->group_name ?: null,
            'path'       => $path,
        ]);

        return response()->json(['status' => true]);
    }

    public function deleteImage($id)
    {
        $image = ClientImage::find($id);
        if (!$image) {
            return response()->json(['status' => false]);
        }
        Storage::disk('public')->delete($image->path);
        $image->update(['deleted' => 1]);
        return response()->json(['status' => true]);
    }

    public static function inactiveClientsQuery(Request $request)
    {
        $user = auth()->user();

        $latestInactiveContractIds = Contract::query()
            ->selectRaw('MAX(contracts.id)')
            ->where('contracts.deleted', 0)
            ->where('contracts.paid', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('contracts as current_contracts')
                    ->where('current_contracts.deleted', 0)
                    ->where('current_contracts.paid', 0)
                    ->whereRaw("
                        (
                            contracts.client_type = 'Personal'
                            and current_contracts.client_type = 'Personal'
                            and current_contracts.document = contracts.document
                        )
                        or
                        (
                            contracts.client_type = 'Grupo'
                            and current_contracts.client_type = 'Grupo'
                            and current_contracts.group_name = contracts.group_name
                        )
                    ");
            })
            ->groupBy(DB::raw("
                CASE
                    WHEN contracts.client_type = 'Personal' THEN CONCAT('P:', COALESCE(contracts.document, ''))
                    ELSE CONCAT('G:', COALESCE(contracts.group_name, ''))
                END
            "));

        return Contract::active()
            ->whereIn('contracts.id', $latestInactiveContractIds)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('contracts.seller_id', $user->id);
            })
            ->when($request->name, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    return $query->where('contracts.name', 'like', '%' . $name . '%')
                        ->orWhere('contracts.group_name', 'like', '%' . $name . '%')
                        ->orWhere('contracts.document', 'like', '%' . $name . '%');
                });
            })
            ->when($request->client_type, function ($query, $client_type) {
                return $query->where('contracts.client_type', $client_type);
            })
            ->when($request->seller_id, function ($query, $seller_id) {
                return $query->where('contracts.seller_id', $seller_id);
            })
            ->when($request->start_date, function ($query, $start_date) {
                return $query->whereDate('contracts.date', '>=', $start_date);
            })
            ->when($request->end_date, function ($query, $end_date) {
                return $query->whereDate('contracts.date', '<=', $end_date);
            })
            ->when($request->last_payment_start_date, function ($query, $start_date) {
                return $query->whereRaw("
                    (
                        select max(payments.date)
                        from payments
                        inner join quotas on quotas.id = payments.quota_id
                        where quotas.contract_id = contracts.id
                            and payments.deleted = 0
                    ) >= ?
                ", [$start_date]);
            })
            ->when($request->last_payment_end_date, function ($query, $end_date) {
                return $query->whereRaw("
                    (
                        select max(payments.date)
                        from payments
                        inner join quotas on quotas.id = payments.quota_id
                        where quotas.contract_id = contracts.id
                            and payments.deleted = 0
                    ) <= ?
                ", [$end_date]);
            })
            ->with('seller')
            ->addSelect([
                'last_payment_date_value' => Payment::query()
                    ->select('payments.date')
                    ->join('quotas', 'quotas.id', '=', 'payments.quota_id')
                    ->whereColumn('quotas.contract_id', 'contracts.id')
                    ->where('payments.deleted', 0)
                    ->latest('payments.date')
                    ->latest('payments.id')
                    ->limit(1),
                'last_payment_amount_value' => Payment::query()
                    ->select('payments.amount')
                    ->join('quotas', 'quotas.id', '=', 'payments.quota_id')
                    ->whereColumn('quotas.contract_id', 'contracts.id')
                    ->where('payments.deleted', 0)
                    ->latest('payments.date')
                    ->latest('payments.id')
                    ->limit(1),
                'total_paid_value' => Payment::query()
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)')
                    ->join('quotas', 'quotas.id', '=', 'payments.quota_id')
                    ->whereColumn('quotas.contract_id', 'contracts.id')
                    ->where('payments.deleted', 0),
            ])
            ->orderByRaw("
                (
                    select max(payments.date)
                    from payments
                    inner join quotas on quotas.id = payments.quota_id
                    where quotas.contract_id = contracts.id
                        and payments.deleted = 0
                ) desc
            ")
            ->latest('contracts.date')
            ->latest('contracts.id');
    }
}
