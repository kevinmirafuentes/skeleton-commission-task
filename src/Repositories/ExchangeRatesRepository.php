<?php 
namespace App\CommissionTask\Repositories;

class ExchangeRatesRepository
{
    use SingletonRepository;

    public $data;

    public function __construct()
    {
        // $this->importFromSource(config('exchange_rates.source_urls'));
    }

    public function saveData($currency, $value)
    {
        $this->data[$currency] = $value;
    }

    public function importFromSource($source)
    {
        $exchangeRates = json_decode(file_get_contents('http://api.exchangeratesapi.io/v1/latest?access_key=70e141fb2263729d85163a81daffcdcd'), true);
        if (!$exchangeRates) {
            throw new \Exception('Failed to get exchange rates');
        }
        $this->data = $exchangeRates['rates'];
    }

    public function convert($amount, $currency, $base = null) 
    {
        if (!$base) {
            $base = config('exchange_rates.base');
        }

        // the exchange rate data is it set on a single currency rate
        // below lines allow the function to  convert using a different currency to be the base.
        $baseRateCurrency = $currency == config('exchange_rates.base') ? $base : $currency;
        if (!isset($this->data[$baseRateCurrency])) {
            return false;
        }
        $rate = $this->data[$baseRateCurrency];
        if (config('exchange_rates.base') == $currency) {
            return round($amount * $rate, 2);
        }
        return round($amount / $rate, 2);
    }

    public function find($currency) 
    {
        return isset($this->data[$currency]) ? $this->data[$currency] : false; 
    }
}