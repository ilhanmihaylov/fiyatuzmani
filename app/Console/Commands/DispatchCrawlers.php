<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CrawlHepsiburada;
use App\Jobs\CrawlTrendyol;
use Illuminate\Support\Facades\Log;

class DispatchCrawlers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dispatcher:crawlers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch Slaves';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $products = \App\Product::where('last_receive', "<", now()->subSeconds(43000))->where('source', '=', 'url')->where('provider', '=', 'trendyol')->orderBy('last_receive', 'asc')->limit(10)->get();
        $trendyol_dispatch_delay = 10;
        foreach ($products as $product) {
            $product->last_dispatch = now();
            $product->save();
            $trendyol_dispatch_delay += 3;
            CrawlTrendyol::dispatch($product)->delay(now()->addSeconds($trendyol_dispatch_delay));
            //Log::debug("TRENDYOL dispatching ".$product->id);
        }
        return;
        $products = \App\Product::where('last_receive', "<", now()->subSeconds(3612))->where('source', '=', 'url')->get();
        foreach ($products as $product) {
            $product->last_dispatch = now();
            $product->save();
            CrawlHepsiburada::dispatch($product->id)->delay(now()->addSeconds(3));
            //Log::debug("dispatching ".$product->id);
        }
        
        $rand_number = random_int(12, 20);
        $products = \App\Product::where('last_receive', "<", now()->subSeconds(43214))->where('source', '=', 'discovery')->orderBy('last_receive', 'asc')->limit($rand_number)->get();
        foreach ($products as $product) {
            $dispatch_delay = random_int(5, 40);
            $product->last_dispatch = now();
            $product->save();
            CrawlHepsiburada::dispatch($product->id)->delay(now()->addSeconds($dispatch_delay));
            //Log::debug("(DISCOVERY PRODUCT)dispatching ".$product->id);
        }
    }
}
