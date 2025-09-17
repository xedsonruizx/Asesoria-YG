<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Service;
use App\Models\Payment;
use App\Services\TransbankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    private TransbankService $transbankService;

    public function __construct(TransbankService $transbankService)
    {
        $this->transbankService = $transbankService;
    }

    /**
     * Mostrar historial de compras del usuario
     */
    public function index()
    {
        $purchases = Auth::user()->purchases()
            ->with(['service', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('ClientMenu/Purchases/Index', [
            'purchases' => $purchases
        ]);
    }

    /**
     * Crear una nueva compra
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'integer|min:1|max:10',
            'payment_method' => 'required|in:transbank,transfer,cash'
        ]);

        $service = Service::findOrFail($request->service_id);
        $quantity = $request->quantity ?? 1;
        $totalAmount = $service->price * $quantity;

        try {
            DB::beginTransaction();

            // Crear la compra
            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'service_id' => $service->id,
                'quantity' => $quantity,
                'unit_price' => $service->price,
                'total_amount' => $totalAmount,
                'currency' => $service->currency,
                'status' => Purchase::STATUS_PENDING,
            ]);

            // Si es pago con Transbank, crear la transacción
            if ($request->payment_method === 'transbank') {
                $result = $this->transbankService->createTransactionForPurchase($purchase);
                
                if ($result['success']) {
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $result['url'] . '?token_ws=' . $result['token'],
                        'purchase_id' => $purchase->id
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $result['error']
                    ], 500);
                }
            } else {
                // Para otros métodos de pago, crear el registro de pago pendiente
                Payment::create([
                    'user_id' => Auth::id(),
                    'purchase_id' => $purchase->id,
                    'amount' => $totalAmount,
                    'currency' => $service->currency,
                    'payment_method' => $request->payment_method,
                    'status' => Payment::STATUS_PENDING,
                ]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Compra creada exitosamente. Procede con el pago.',
                    'purchase_id' => $purchase->id
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una compra
     */
    public function show(Purchase $purchase)
    {
        // Verificar que la compra pertenece al usuario autenticado
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        $purchase->load(['service', 'payment']);

        return Inertia::render('ClientMenu/Purchases/Show', [
            'purchase' => $purchase
        ]);
    }
}