<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Exports\ChargesExport;
use App\Exports\DuesExport;
use App\Exports\PaymentsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentTransactionDetail;
use App\Models\User;
use App\Services\MultiPaymentService;
use RuntimeException;

class PaymentController extends Controller
{
    public function multiple(Request $request)
    {
        $user = auth()->user();
        $sellers = User::seller()->active()->get();

        $quotas = Quota::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($query) use ($user) {
                    return $query->where('seller_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->whereHas('contract', function ($query) use ($name) {
                    return $query->where(function ($query) use ($name) {
                        return $query->where('name', 'like', '%' . $name . '%')
                            ->orWhere('group_name', 'like', '%' . $name . '%');
                    });
                });
            })
            ->when($request->seller_id, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($query) use ($seller_id) {
                    return $query->where('seller_id', $seller_id);
                });
            })
            ->when($request->from_days, function ($query, $from_days) {
                return $query->whereRaw('DATEDIFF(?, date) >= ?', [now()->format('Y-m-d'), $from_days]);
            })
            ->when($request->to_days, function ($query, $to_days) {
                return $query->whereRaw('DATEDIFF(?, date) <= ?', [now()->format('Y-m-d'), $to_days]);
            })
            ->where('debt', '>', 0)
            ->with('contract.seller')
            ->orderBy('date')
            ->orderBy('number')
            ->paginate(20);

        $payment_methods = PaymentMethod::active()->get();

        return view('payments.multiple', compact('quotas', 'sellers', 'payment_methods'));
    }

    public function index(Request $request){
        $user = auth()->user();
        $payments = Payment::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('quota.contract', function($query) use($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('quota.contract', function($query) use($name){
                return $query->where(function($query) use ($name){
                    return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
                });
            });
        })->when($request->payment_method_id, function($query, $payment_method_id){
            return $query->where('payment_method_id', $payment_method_id);
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('quota.contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->with(['quota.contract.seller', 'payment_method'])->latest('date')->latest('id');

        $total = $payments->sum('amount');

        $payments = $payments->paginate(20);

        $payment_methods = PaymentMethod::active()->get();
        $sellers = User::seller()->where('state', 0)->active()->get();

        return view('payments.index', compact('payments', 'payment_methods', 'sellers', 'total'));
    }

    public function charges(Request $request){
        $user = auth()->user();
        $sellers = User::seller()->active()->get();
        $quotas = Quota::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('contract', function($query) use($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('contract', function($query) use($name){
                return $query->where(function($query) use ($name){
                    return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
                });
            });
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->where('debt', '>', 0)->orderBy('date')->paginate(20);

        $payment_methods = PaymentMethod::active()->get();

        $nextQuotas = Quota::whereIn('contract_id', $quotas->pluck('contract_id'))
            ->where('debt', '>', 0)
            ->groupBy('contract_id')
            ->select('contract_id', DB::raw('MIN(number) as next_number'))
            ->get()
            ->pluck('next_number', 'contract_id');

        return view('payments.charges', compact('quotas', 'sellers', 'payment_methods', 'nextQuotas'));
    }

    public function dues(Request $request){
        $user = auth()->user();
        $sellers = User::seller()->active()->get();
        $date = $request->date ? $request->date : now();
        $quotas = Quota::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('contract', function($query) use ($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('contract', function($query) use($name){
                return $query->where('name', 'like', '%'.$name.'%');
            });
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->from_days, function($query, $from_days){
            return $query->whereRaw('DATEDIFF(?, date) >= ?', [now()->format('Y-m-d'), $from_days]);
        })->when($request->to_days, function($query, $to_days){
            return $query->whereRaw('DATEDIFF(?, date) <= ?', [now()->format('Y-m-d'), $to_days]);
        })->whereDate('date', '<', $date)->where('debt', '>', 0)->with('contract.seller')->paginate(20);

        $payment_methods = PaymentMethod::active()->get();

        $nextQuotas = Quota::whereIn('contract_id', $quotas->pluck('contract_id'))
            ->where('debt', '>', 0)
            ->groupBy('contract_id')
            ->select('contract_id', DB::raw('MIN(number) as next_number'))
            ->get()
            ->pluck('next_number', 'contract_id');

        return view('payments.dues', compact('quotas', 'sellers', 'payment_methods', 'nextQuotas'));
    }

    public function store(Request $request){
        $scheduledIds = collect($request->input('scheduled_quota_ids', []))->filter()->values();
        if ($scheduledIds->isNotEmpty()) {
            return $this->storeScheduledPassive($request);
        }

        $multipleIds = collect($request->input('cuotas_seleccionadas_ids', []))->filter()->values();
        if ($multipleIds->isNotEmpty()) {
            return $this->storeMultiple($request);
        }


        $validator = Validator::make($request->all(), [
            'quota_id' => 'required',
            'amount' => 'required|numeric|min:0.1',
            'payment_method_id' => 'required',
            'date' => 'required|date'
        ]);

        $quota = Quota::find($request->quota_id);

        if($quota){
            $contract= $quota->contract;
        }

        $validator->after(function($validator) use ($request, $quota){

            if($quota){
                if($request->amount > $quota->debt){
                    $validator->errors()->add('cart', 'El pago debe ser menor o igual al saldo pendiente');
                }

                $previousQuota = Quota::where('contract_id', $quota->contract_id)
                    ->where('debt', '>', 0)
                    ->where('number', '<', $quota->number)
                    ->exists();

                if($previousQuota){
                    $validator->errors()->add('cart', 'Debe cobrar la cuota anterior antes de proceder con esta');
                }
            }else{
                $validator->errors()->add('cart', 'La cuota no se encuentra');
            }

        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {

            $payment_date = Carbon::parse($request->date);

            $diff = $payment_date->diffInDays($quota->date, false);

            $due_days = $diff < 0 ? abs($diff) : 0;

            $people = null;

            if($contract->client_type == 'Grupo'){
                $payment_people = $request->people ? $request->people : [];

                $people = [];

                foreach($payment_people as $document){

                    foreach(json_decode($contract->people) as $client){
                        if($client->document == $document){
                            $people[] = $client;
                        }
                    }

                }

                $people = json_encode($people);
            }

            $image = null;

            if($request->hasFile('image')){
                $image = $request->image->store('payments', 'public');
            }
            
            Payment::create([
                'quota_id' => $request->quota_id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'date' => $request->date,
                'due_days' => $due_days,
                'image' => $image,
                'people' => $people
            ]);

            $remainingDebt = max(round((float) $quota->debt - (float) $request->amount, 2), 0);

            $quota->update([
                'debt' => $remainingDebt,
                'paid' => $remainingDebt <= 0 ? 1 : 0
            ]);

            $quotas = Quota::where('contract_id', $quota->contract_id)->where('debt', '>', 0);

            if($quotas->count() == 0){
                $quota->contract()->update([
                  'paid' => 1
                ]);
            }

            DB::commit();

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'status' => false
            ]);
        }

        return response()->json([
            'status' => true
        ]);
        
    }

    private function storeScheduledPassive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_quota_ids' => 'required|array|min:1',
            'scheduled_quota_ids.*' => 'integer|distinct',
        ]);

        $validator->after(function ($validator) use ($request) {
            $quotaIds = collect($request->input('scheduled_quota_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($quotaIds->isEmpty()) {
                $validator->errors()->add('cart', 'Debe seleccionar al menos una cuota.');
                return;
            }

            $quotas = Quota::active()
                ->with('contract')
                ->whereIn('id', $quotaIds)
                ->orderBy('number')
                ->get()
                ->values();

            if ($quotas->count() !== $quotaIds->count()) {
                $validator->errors()->add('cart', 'Una o más cuotas seleccionadas no existen o ya no están disponibles.');
                return;
            }

            $contractIds = $quotas->pluck('contract_id')->unique()->values();
            if ($contractIds->count() !== 1) {
                $validator->errors()->add('cart', 'Solo puede pagar cuotas de un mismo contrato a la vez.');
                return;
            }

            $contractId = $contractIds->first();
            $pendingQuotas = Quota::active()
                ->where('contract_id', $contractId)
                ->where('debt', '>', 0)
                ->orderBy('number')
                ->get()
                ->values();

            $expectedIds = $pendingQuotas->take($quotaIds->count())->pluck('id')->map(fn ($id) => (int) $id)->values();
            $selectedIds = $quotas->pluck('id')->map(fn ($id) => (int) $id)->values();

            if ($expectedIds->count() !== $selectedIds->count() || $expectedIds->all() !== $selectedIds->all()) {
                $validator->errors()->add('cart', 'Solo puede seleccionar cuotas consecutivas desde la primera pendiente.');
                return;
            }

            if ($quotas->contains(fn ($quota) => round((float) $quota->debt, 2) <= 0)) {
                $validator->errors()->add('cart', 'Todas las cuotas seleccionadas deben tener saldo pendiente.');
                return;
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        try {
            DB::transaction(function () use ($request) {
                $quotaIds = collect($request->input('scheduled_quota_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                $quotas = Quota::active()
                    ->with('contract')
                    ->whereIn('id', $quotaIds)
                    ->lockForUpdate()
                    ->orderBy('number')
                    ->get()
                    ->values();

                $contract = optional($quotas->first())->contract;
                if (!$contract) {
                    throw new RuntimeException('No se pudo resolver el contrato de las cuotas seleccionadas.');
                }

                $cashMethod = PaymentMethod::active()
                    ->whereRaw('LOWER(name) = ?', ['efectivo'])
                    ->first();

                if (!$cashMethod) {
                    throw new RuntimeException('No existe el método de pago Efectivo activo.');
                }

                foreach ($quotas as $quota) {
                    $amount = round((float) $quota->debt, 2);
                    if ($amount <= 0) {
                        continue;
                    }

                    Payment::create([
                        'quota_id' => $quota->id,
                        'amount' => $amount,
                        'payment_method_id' => $cashMethod->id,
                        'date' => $quota->date->format('Y-m-d'),
                        'due_days' => 0,
                        'image' => null,
                        'people' => $contract->client_type === 'Grupo' ? $contract->people : null,
                    ]);

                    $quota->update([
                        'debt' => 0,
                        'paid' => 1,
                    ]);
                }

                $contract->update([
                    'paid' => $contract->quotas()->where('debt', '>', 0)->count() === 0 ? 1 : 0,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => true,
        ]);
    }

    public function storeMultiple(Request $request)
    {
        $multiPaymentService = app(MultiPaymentService::class);

        $validator = Validator::make($request->all(), [
            'cuotas_seleccionadas_ids' => 'required|array|min:1',
            'cuotas_seleccionadas_ids.*' => 'integer|distinct',
            'amount' => 'required|numeric|min:0.1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'date' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $validator->after(function ($validator) use ($request) {
            $quotaIds = collect($request->input('cuotas_seleccionadas_ids', []))->filter()->unique()->values();

            if ($quotaIds->isEmpty()) {
                $validator->errors()->add('cart', 'Debe seleccionar al menos una cuota.');
                return;
            }

            $quotas = Quota::active()
                ->with('contract')
                ->whereIn('id', $quotaIds)
                ->get();

            if ($quotas->count() !== $quotaIds->count()) {
                $validator->errors()->add('cart', 'Una o más cuotas seleccionadas no existen o ya no están disponibles.');
                return;
            }

            $contractIds = $quotas->pluck('contract_id')->unique();
            if ($contractIds->count() !== 1) {
                $validator->errors()->add('cart', 'Solo puede pagar cuotas de un mismo cliente/contrato por transacción.');
                return;
            }

            $selectedDebt = round((float) $quotas->sum('debt'), 2);
            $amount = round((float) $request->amount, 2);

            if ($amount > $selectedDebt) {
                $validator->errors()->add('cart', 'El monto total recibido no puede ser mayor que la suma de los saldos seleccionados.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $voucherPath = null;

        try {
            if ($request->hasFile('image')) {
                $voucherPath = $request->file('image')->store('payments', 'public');
            }

            $multiPaymentService->register([
                'quota_ids' => $request->input('cuotas_seleccionadas_ids', []),
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'date' => $request->date,
                'voucher_path' => $voucherPath,
            ]);
        } catch (\Throwable $e) {
            if (!empty($voucherPath) && Storage::disk('public')->exists($voucherPath)) {
                Storage::disk('public')->delete($voucherPath);
            }

            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Payment $payment){
        return response()->json([
            'id' => $payment->id,
            'client' => optional(optional($payment->quota)->contract)->client(),
            'quota' => $payment->quota,
            'amount' => $payment->amount,
            'payment_method_id' => $payment->payment_method_id,
            'date' => $payment->date->format('d/m/Y')
        ]);
    }

    public function update(Request $request, Payment $payment){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $quota = $payment->quota;

        $validator->after(function ($validator) use ($request, $payment, $quota) {
            if (!$quota) {
                $validator->errors()->add('quota', 'La cuota no se encuentra.');
                return;
            }

            $availableAmount = round((float) $quota->debt + (float) $payment->amount, 2);
            if (round((float) $request->amount, 2) > $availableAmount) {
                $validator->errors()->add('amount', 'El monto pagado no puede superar el saldo disponible de la cuota.');
            }
        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            $oldAmount = round((float) $payment->amount, 2);
            $newAmount = round((float) $request->amount, 2);
            $newDebt = max(round((float) $quota->debt + $oldAmount - $newAmount, 2), 0);
            $oldImage = $payment->image;
            $image = $oldImage;

            if ($request->hasFile('image')) {
                $image = $request->file('image')->store('payments', 'public');
            }

            $payment->update([
                'amount' => $newAmount,
                'payment_method_id' => $request->payment_method_id,
                'image' => $image,
            ]);

            $quota->update([
                'debt' => $newDebt,
                'paid' => $newDebt <= 0 ? 1 : 0,
            ]);

            $contract = $quota->contract;
            if ($contract) {
                $contract->update([
                    'paid' => $contract->quotas()->where('debt', '>', 0)->count() === 0 ? 1 : 0,
                ]);
            }

            $detail = PaymentTransactionDetail::where('payment_id', $payment->id)->first();
            if ($detail) {
                $balanceAfter = max(round((float) $detail->quota_balance_before - $newAmount, 2), 0);
                $detail->update([
                    'amount_applied' => $newAmount,
                    'quota_balance_after' => $balanceAfter,
                ]);
            }

            if ($payment->transaction) {
                $payment->transaction->update([
                    'payment_method_id' => $request->payment_method_id,
                    'total_amount' => $payment->transaction->payments()->active()->sum('amount'),
                ]);
            }

            if ($request->hasFile('image') && $oldImage && $oldImage !== $image) {
                $isImageStillUsed = Payment::active()
                    ->where('id', '!=', $payment->id)
                    ->where('image', $oldImage)
                    ->exists();

                if (!$isImageStillUsed && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if (isset($image) && $request->hasFile('image') && Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }

            return response()->json([
                'status' => false,
                'error' => 'No se pudo actualizar el pago.'
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Payment $payment){

        DB::beginTransaction();

        try {

            $payment->update(['deleted' => 1]);

            $quota = $payment->quota;

            $quota->update([
                'debt' => $quota->debt + $payment->amount,
                'paid' => 0
            ]);

            $quota->contract()->update([
                'paid' => 0
            ]);

            DB::commit();

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'status' => false
            ]);
        }

        

        return response()->json([
            'status' => true
        ]);
    }

    public function chargesExcel(Request $request){
        $name = "GestionDeCobranza_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new ChargesExport, $name);
    }

    public function duesExcel(Request $request){
        $name = "GestionDeMora_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new DuesExport, $name);
    }

    public function excel(Request $request)
    {
        $name = "Pagos_" . now()->format('d_m_Y') . ".xlsx";
        return Excel::download(new PaymentsExport, $name);
    }

    public function image(Payment $payment){
        if(!$payment->image || !Storage::disk('public')->exists($payment->image)){
            abort(404);
        }

        $path = Storage::disk('public')->path($payment->image);
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
        ]);
    }
}
