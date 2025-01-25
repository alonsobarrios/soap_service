<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

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
}
