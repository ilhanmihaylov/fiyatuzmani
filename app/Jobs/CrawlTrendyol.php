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

class CrawlTrendyol implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!$this->product) return;
        /*$client = new \Goutte\Client();
        $client->setHeader('User-Agent', env('HB_USERAGENT', "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36"));*/
        $client = GetGoutteForCrawler();
        $crawler = $client->request('GET', $this->product->productURL);

        try {
            $this->product_price = $crawler->filter('meta[name="twitter:data1"]')->attr('content');
            
        } catch (\Exception $e) {
            Log::debug("Content could not fetched. Details:". $e->getMessage());
            $this->product_price = 0;
        }
        $price = new \App\Price;
        $price->price = doubleval($this->product_price);
        $price->productID = $this->product->id;
        $price->pricedate = now();
        $price->save();
        $this->product->last_receive = now();
        $this->product->save();
    }
}
