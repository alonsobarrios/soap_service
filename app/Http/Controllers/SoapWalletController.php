<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentSession;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SoapWalletController extends Controller
{
    public function handle()
    {
        $server = new \SoapServer(public_path('service.wsdl'));
        
        $server->setClass(self::class);
        $server->handle();
    }

    public function registerCustomer($document, $name, $email, $phone)
    {
        try {
            DB::beginTransaction();
            $exists = Customer::where('document', $document)->orWhere('email', $email)->first();

            if ($exists) {
                return ['success' => 0, 'code' => 400, 'message' => 'Cliente ya existe!', 'data' => []];
            }
    
            $customer = Customer::create([
                'document' => $document,
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ]);
    
            Wallet::create(['customer_id' => $customer->id, 'balance' => 0]);
    
            DB::commit();
            return ['success' => 1, 'code' => 200, 'message' => 'Cliente registrado con éxito', 'data' => $customer->toArray()];
        } catch (\Exception $ex) {
            DB::rollBack();
            return ['success' => 0, 'code' => 500, 'message' => $ex->getMessage(), 'data' => []];
        }
    }

    public function rechargeWallet($document, $phone, $amount)
    {
        try {
            $amount = (float)$amount;

            $customer = Customer::where(compact('document', 'phone'))->first();
            if (!$customer) {
                return ['success' => 0, 'code' => 404, 'message' => 'Cliente no encontrado', 'data' => []];
            }

            if (!$amount || $amount <= 0) {
                return ['success' => 0, 'code' => 400, 'message' => 'El monto a recargar debe ser mayor a cero', 'data' => []];
            }

            $customer->wallet->increment('balance', $amount);
            return ['success' => 1, 'code' => 200, 'message' => 'Recarga exitosa', 'data' => []];
        } catch (\Exception $ex) {
            return ['success' => 0, 'code' => 500, 'message' => $ex->getMessage(), 'data' => []];
        }
    }

    public function makePayment($document, $phone, $amount)
    {
        try {
            DB::beginTransaction();
            $amount = (float)$amount;
            $customer = Customer::where('document', $document)->where('phone', $phone)->first();

            if (!$customer || $customer->wallet->balance < $amount) {
                return ['success' => 0, 'code' => 400, 'message' => 'Fondos insuficientes o cliente no encontrado', 'data' => []];
            }

            $token = rand(100000, 999999);
            $session = PaymentSession::create([
                'customer_id' => $customer->id,
                'token' => $token,
                'amount' => $amount
            ]);

            Mail::raw("Su token de pago es: $token, y el ID de sesión es: $session->id", function ($message) use ($customer) {
                $message->to($customer->email)->subject('Token de Confirmación');
            });

            DB::commit();
            return ['success' => 1, 'code' => 200, 'message' => 'Se ha enviado un correo con el TOKEN que debe ser usado en la confirmación de la compra.', 'data' => $session->toArray()];
        } catch (\Exception $ex) {
            DB::rollBack();
            return ['success' => 0, 'code' => 500, 'message' => $ex->getMessage(), 'data' => []];
        }
    }
}
