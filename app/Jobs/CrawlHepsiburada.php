<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Product;
use \Goutte\Client;

class CrawlHepsiburada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug($this->id);
        $prd = \App\Product::find($this->id);
        if(!$prd) return;
        Log::debug($prd->id ." has arrived. Lets crawl!");
        $client = new \Goutte\Client();
        Log::debug($prd->productURL);
        $client->setHeader('User-Agent', env('HB_USERAGENT', "FUZM/v1.0r3 Discovery"));
        $crawler = $client->request('GET', $prd->productURL);

        try {
            Log::debug($prd->id ." getting price content.");
            $product_price = $crawler->filter('#offering-price')->attr('content');
            
        } catch (\Exception $e) {
            Log::debug("Content could not fetched. Details:". $e->getMessage());
            $product_price = 0;
        }

        try {
            if(true/*$prd->productid == "" || $prd->productid == null*/){
                $prd->productid = $crawler->filter('input[name=productId]')->attr('value');
            }
        } catch (\Exception $e) {
            //throw $th;
        }
        
        Log::debug("Price Found: ".$product_price);
        Log::debug($prd->id ." crawl complete. Creating database entry");

        $price = new \App\Price;
        $price->price = doubleval($product_price);
        $price->productID = $prd->id;
        $price->pricedate = now();
        Log::debug($prd->id ." saving to databse...");
        $price->save();
        $prd->last_receive = now();
        $prd->save();
        Log::debug($prd->id ." saved.");

        HepsiburadaDiscoveryService::dispatch($prd->productid);
        Log::debug("Dispatched discovery service for:". $prd->productid);


    }
}
