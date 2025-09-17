<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Purchase;
use App\Services\TransbankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    private TransbankService $transbankService;

    public function __construct(TransbankService $transbankService)
    {
        $this->transbankService = $transbankService;
    }

    /**
     * Mostrar historial de pagos del usuario
     */
    public function index()
    {
        $payments = Auth::user()->payments()
            ->with(['purchase.service'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('ClientMenu/Payments/Index', [
            'payments' => $payments
        ]);
    }

    /**
     * Procesar retorno de Transbank
     */
    public function return(Request $request)
    {
        $token = $request->get('token_ws');
        
        if (!$token) {
            Log::warning('Retorno de Transbank sin token', [
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);
            
            return redirect()->route('purchases.index')
                ->with('error', 'Token de transacción no válido');
        }

        Log::info('Procesando retorno de Transbank', [
            'token' => $token,
            'user_id' => Auth::id()
        ]);

        // Confirmar la transacción
        $result = $this->transbankService->confirmTransaction($token);

        if ($result['success']) {
            $payment = $result['payment'];
            
            // Verificar que el pago pertenece al usuario autenticado
            if ($payment->user_id !== Auth::id()) {
                Log::error('Intento de acceso no autorizado a pago', [
                    'payment_id' => $payment->id,
                    'payment_user_id' => $payment->user_id,
                    'current_user_id' => Auth::id()
                ]);
                
                return redirect()->route('purchases.index')
                    ->with('error', 'Acceso no autorizado');
            }
            
            if ($payment->isApproved()) {
                Log::info('Pago aprobado exitosamente', [
                    'payment_id' => $payment->id,
                    'purchase_id' => $payment->purchase_id,
                    'amount' => $payment->amount
                ]);
                
                return redirect()->route('payments.success', $payment->id)
                    ->with('success', 'Pago procesado exitosamente');
            } else {
                Log::warning('Pago rechazado', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'response_code' => $payment->response_code
                ]);
                
                return redirect()->route('payments.failed', $payment->id)
                    ->with('error', 'El pago fue rechazado');
            }
        }

        Log::error('Error al procesar retorno de Transbank', [
            'token' => $token,
            'error' => $result['error'] ?? 'Error desconocido'
        ]);

        return redirect()->route('purchases.index')
            ->with('error', 'Error al procesar el pago: ' . ($result['error'] ?? 'Error desconocido'));
    }

    /**
     * Mostrar página de éxito
     */
    public function success(Payment $payment)
    {
        // Verificar que el pago pertenece al usuario autenticado
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver este pago');
        }

        // Verificar que el pago está aprobado
        if (!$payment->isApproved()) {
            return redirect()->route('payments.failed', $payment->id)
                ->with('error', 'Este pago no fue aprobado');
        }

        $payment->load(['purchase.service']);

        return Inertia::render('ClientMenu/Payments/Success', [
            'payment' => $payment,
            'purchase' => $payment->purchase,
            'service' => $payment->purchase->service
        ]);
    }

    /**
     * Mostrar página de fallo
     */
    public function failed(Payment $payment)
    {
        // Verificar que el pago pertenece al usuario autenticado
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver este pago');
        }

        $payment->load(['purchase.service']);

        return Inertia::render('ClientMenu/Payments/Failed', [
            'payment' => $payment,
            'purchase' => $payment->purchase,
            'service' => $payment->purchase->service,
            'error_message' => $this->getPaymentErrorMessage($payment)
        ]);
    }

    /**
     * Mostrar detalles de un pago
     */
    public function show(Payment $payment)
    {
        // Verificar que el pago pertenece al usuario autenticado
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver este pago');
        }

        $payment->load(['purchase.service']);

        return Inertia::render('ClientMenu/Payments/Show', [
            'payment' => $payment,
            'purchase' => $payment->purchase,
            'service' => $payment->purchase->service
        ]);
    }

    /**
     * Reintentar un pago fallido
     */
    public function retry(Payment $payment)
    {
        // Verificar que el pago pertenece al usuario autenticado
        if ($payment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para reintentar este pago'
            ], 403);
        }

        // Verificar que el pago puede ser reintentado
        if ($payment->isApproved() || $payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Este pago no puede ser reintentado'
            ], 400);
        }

        try {
            // Crear una nueva transacción para la misma compra
            $result = $this->transbankService->createTransactionForPurchase($payment->purchase);
            
            if ($result['success']) {
                // Marcar el pago anterior como cancelado
                $payment->update([
                    'status' => Payment::STATUS_CANCELLED,
                    'notes' => 'Pago cancelado por reintento'
                ]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $result['url'] . '?token_ws=' . $result['token'],
                    'message' => 'Redirigiendo a nueva transacción'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error al reintentar pago', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el reintento'
            ], 500);
        }
    }

    /**
     * Cancelar un pago pendiente
     */
    public function cancel(Payment $payment)
    {
        // Verificar que el pago pertenece al usuario autenticado
        if ($payment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para cancelar este pago'
            ], 403);
        }

        // Verificar que el pago puede ser cancelado
        if (!$payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Este pago no puede ser cancelado'
            ], 400);
        }

        try {
            $payment->update([
                'status' => Payment::STATUS_CANCELLED,
                'notes' => 'Pago cancelado por el usuario'
            ]);

            // También cancelar la compra si no tiene otros pagos aprobados
            $purchase = $payment->purchase;
            $hasApprovedPayments = $purchase->payments()
                ->where('status', Payment::STATUS_APPROVED)
                ->exists();

            if (!$hasApprovedPayments) {
                $purchase->update([
                    'status' => Purchase::STATUS_CANCELLED
                ]);
            }

            Log::info('Pago cancelado por usuario', [
                'payment_id' => $payment->id,
                'purchase_id' => $purchase->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago cancelado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cancelar pago', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar el pago'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pagos del usuario
     */
    public function stats()
    {
        $user = Auth::user();
        
        $stats = [
            'total_payments' => $user->payments()->count(),
            'approved_payments' => $user->payments()->approved()->count(),
            'pending_payments' => $user->payments()->pending()->count(),
            'total_amount_paid' => $user->payments()->approved()->sum('amount'),
            'last_payment' => $user->payments()
                ->with(['purchase.service'])
                ->latest()
                ->first(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Obtener mensaje de error personalizado según el estado del pago
     */
    private function getPaymentErrorMessage(Payment $payment): string
    {
        return match ($payment->status) {
            Payment::STATUS_REJECTED => 'El pago fue rechazado por el banco. Verifica los datos de tu tarjeta e intenta nuevamente.',
            Payment::STATUS_FAILED => 'Ocurrió un error técnico durante el procesamiento del pago. Intenta nuevamente.',
            Payment::STATUS_CANCELLED => 'El pago fue cancelado.',
            default => 'El pago no pudo ser procesado. Contacta con soporte si el problema persiste.'
        };
    }

    /**
     * Descargar comprobante de pago (si está implementado)
     */
    public function downloadReceipt(Payment $payment)
    {
        // Verificar que el pago pertenece al usuario autenticado
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para descargar este comprobante');
        }

        // Verificar que el pago está aprobado
        if (!$payment->isApproved()) {
            return redirect()->back()
                ->with('error', 'Solo se pueden descargar comprobantes de pagos aprobados');
        }

        // Aquí puedes implementar la generación del PDF del comprobante
        // Por ahora retornamos un mensaje
        return redirect()->back()
            ->with('info', 'Funcionalidad de descarga de comprobante en desarrollo');
    }
}