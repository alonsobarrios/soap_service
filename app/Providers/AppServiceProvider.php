<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Response::macro('xml', function ($data, $status = 200, array $headers = [], $rootElement = 'response') {
            $xml = new \SimpleXMLElement("<{$rootElement}/>");
    
            $addData = function ($data, $xmlElement) use (&$addData) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $subNode = $xmlElement->addChild($key);
                        $addData($value, $subNode);
                    } else {
                        $xmlElement->addChild($key, htmlspecialchars($value));
                    }
                }
            };

            $addData($data, $xml);

            $headers['Content-Type'] = 'application/xml';

            return Response::make($xml->asXML(), $status, $headers);
        });
    }
}
