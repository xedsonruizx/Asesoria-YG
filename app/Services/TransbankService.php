<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class TransbankService
{
    private $webpayPlus;
    private $environment;

    public function __construct()
    {
        $this->environment = config('transbank.environment', 'development');
        
        if ($this->environment === 'production') {
            \Transbank\Webpay\WebpayPlus::configureForProduction(
                config('transbank.commerce_code'),
                config('transbank.api_key')
            );
        } else {
            \Transbank\Webpay\WebpayPlus::configureForTesting();
        }

        $this->webpayPlus = new \Transbank\Webpay\WebpayPlus\Transaction();
    }

    /**
     * Crear una transacción para una compra
     */
    public function createTransactionForPurchase(Purchase $purchase): array
    {
        try {
            $orderId = 'PURCHASE_' . $purchase->id . '_' . time();
            $sessionId = Str::uuid()->toString();
            
            // Crear el registro de pago
            $payment = Payment::create([
                'user_id' => $purchase->user_id,
                'purchase_id' => $purchase->id,
                'amount' => $purchase->total_amount,
                'currency' => $purchase->currency,
                'payment_method' => Payment::METHOD_TRANSBANK,
                'transbank_order_id' => $orderId,
                'transbank_session_id' => $sessionId,
                'status' => Payment::STATUS_PENDING,
            ]);

            // URLs de retorno
            $returnUrl = route('purchases.payment.return');
            
            // Crear la transacción en Transbank
            $response = $this->webpayPlus->create(
                $orderId,
                $sessionId,
                $purchase->total_amount,
                $returnUrl
            );

            // Actualizar el payment con el token de Transbank
            $payment->update([
                'transbank_token' => $response->getToken()
            ]);

            Log::info('Transacción de compra creada exitosamente', [
                'purchase_id' => $purchase->id,
                'order_id' => $orderId,
                'token' => $response->getToken(),
                'amount' => $purchase->total_amount
            ]);

            return [
                'success' => true,
                'token' => $response->getToken(),
                'url' => $response->getUrl(),
                'payment_id' => $payment->id,
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            Log::error('Error al crear transacción de compra', [
                'error' => $e->getMessage(),
                'purchase_id' => $purchase->id
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar el pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Confirmar una transacción y activar la compra
     */
    public function confirmTransaction(string $token): array
    {
        try {
            // Buscar el pago por token
            $payment = Payment::where('transbank_token', $token)->first();
            
            if (!$payment) {
                throw new Exception('Pago no encontrado');
            }

            // Confirmar la transacción con Transbank
            $response = $this->webpayPlus->commit($token);

            // Actualizar el estado del pago
            $status = $this->mapTransbankStatus($response->getResponseCode());
            
            $payment->update([
                'status' => $status,
                'authorization_code' => $response->getAuthorizationCode(),
                'response_code' => $response->getResponseCode(),
                'transbank_response' => [
                    'vci' => $response->getVci(),
                    'amount' => $response->getAmount(),
                    'status' => $response->getStatus(),
                    'buy_order' => $response->getBuyOrder(),
                    'session_id' => $response->getSessionId(),
                    'card_detail' => $response->getCardDetail(),
                    'accounting_date' => $response->getAccountingDate(),
                    'transaction_date' => $response->getTransactionDate(),
                ],
                'paid_at' => $status === Payment::STATUS_APPROVED ? now() : null
            ]);

            // Si el pago fue aprobado, activar la compra
            if ($status === Payment::STATUS_APPROVED) {
                $purchase = $payment->purchase;
                $purchase->update([
                    'status' => Purchase::STATUS_PAID
                ]);
                
                // Activar la compra si es necesario
                $purchase->activate();
            }

            Log::info('Transacción confirmada', [
                'token' => $token,
                'status' => $status,
                'response_code' => $response->getResponseCode(),
                'purchase_id' => $payment->purchase_id
            ]);

            return [
                'success' => true,
                'payment' => $payment->load('purchase'),
                'status' => $status,
                'response' => $response
            ];

        } catch (Exception $e) {
            Log::error('Error al confirmar transacción', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error al confirmar el pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mapear el código de respuesta de Transbank a nuestros estados
     */
    private function mapTransbankStatus(string $responseCode): string
    {
        return match ($responseCode) {
            '0' => Payment::STATUS_APPROVED,
            '-1', '-2', '-3', '-4', '-5', '-6', '-7', '-8' => Payment::STATUS_REJECTED,
            default => Payment::STATUS_FAILED
        };
    }
}