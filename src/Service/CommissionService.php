<?php

declare(strict_types=1);

namespace App\CommissionTask\Service;

use App\CommissionTask\Repositories\TransactionsRepository;

class CommissionService
{
    const COMMISSION_RATES = [
        'deposit.private' => 0.0003, // 0.03%
        'deposit.business' => 0.0003, // 0.03%
        'withdraw.private' => 0.003, // 0.3%
        'withdraw.business' => 0.005, // 0.5%
    ];
    
    private $weeklyWithdrawalsEur = [];

    private $baseCurrency = 'EUR';

    public $exchangeRates = [];

    public $repository;

    public function __construct(TransactionsRepository $repository)
    {   
        // for default test same as sample provided
        $this->exchangeRates['USD'] = 1.1497;
        $this->exchangeRates['JPY'] = 129.53;

        $this->repository = $repository;

        // uncomment below to use latest rates
        // $this->downloadExchangeRates();
    }

    public function downloadExchangeRates()
    {
        $exchangeRates = json_decode(file_get_contents('http://api.exchangeratesapi.io/v1/latest?access_key=70e141fb2263729d85163a81daffcdcd'), true);
        if (!$exchangeRates) {
            throw new \Exception('Failed to get exchange rates');
        }
        $this->exchangeRates = $exchangeRates['rates'];
    }

    public function calculate()
    {
        $results = [];

        $this->weeklyWithdrawalsEur = [];
        foreach ($this->repository->get() as $key => $data) {
            $commission = 0;
            
            if ($data['operationType'] == 'withdraw') {
                $commission = $this->withdrawCommission($data);
            }
            if ($data['operationType'] == 'deposit') {
                $commission = $this->depositCommision($data);
            }

            $data['commission'] = $data['currency'] == 'JPY' ? 
                ceil($commission) : number_format(ceil($commission * 100) / 100, 2); 

            $results[] = $data;
        }

        return $results;
    }

    public function withdrawCommission($data)
    {
        if (!array_key_exists('withdraw.'.$data['userType'], self::COMMISSION_RATES)) {
            return 'Invalid user type ' . $data['userType'];
            // throw new \Exception('Invalid user type ' . $data['userType']);    
        }

        if ($data['userType'] == 'business') {
            return $data['amount'] * self::COMMISSION_RATES['withdraw.' . $data['userType']];
        }
        
        if ($data['userType'] == 'private') {
            // todo convert amount to EUR
            $amount = $data['amount'];

            if ($data['currency'] != $this->baseCurrency) {
                $amount = $this->convertToBaseCurrency($amount, $data['currency']);
            }

            $totalWeekWithdrawals = 0;

            if (isset($this->weeklyWithdrawalsEur[$data['weekId']])) {
                foreach ($this->weeklyWithdrawalsEur[$data['weekId']] as $value) {
                    $totalWeekWithdrawals += $value;    
                }
            }

            // this creates a list of withdrawals for the week
            $this->weeklyWithdrawalsEur[$data['weekId']][] = $amount;
            
            // if there less 3 withdrawals for the week then we proceed with further computation
            if (count($this->weeklyWithdrawalsEur[$data['weekId']]) < 3) {
                if ($totalWeekWithdrawals + $amount <= 1000) {
                    return 0;
                } 
                // If total free of charge amount is exceeded then commission is calculated only for 
                // the exceeded amount (i.e. up to 1000.00 EUR no commission fee is applied).
                else {
                    // get the exceeded amount
                    if ($totalWeekWithdrawals < 1000) {
                        $commissionable = abs(1000 - $totalWeekWithdrawals - $amount);
                    } else {
                        $commissionable = $amount;
                    }
                    
                    $commission = $commissionable * self::COMMISSION_RATES['withdraw.' . $data['userType']];

                    // conditions above calculates in base (EUR) currency
                    // so we convert it back to original currency
                    if ($data['currency'] != $this->baseCurrency) {
                        return $commission * $this->exchangeRates[$data['currency']];
                    }

                    return $commission;
                }
            } 
        }

        // this applies for business and private withdrawals not qualified for free of charge
        return $data['amount'] * self::COMMISSION_RATES['withdraw.' . $data['userType']];
    }

    public function depositCommision($data)
    {
        if (!array_key_exists('deposit.'.$data['userType'], self::COMMISSION_RATES)) {
            return 'Invalid user type ' . $data['userType'];
            // throw new \Exception('Invalid user type ' . $data['userType']);    
        }
        return $data['amount'] * self::COMMISSION_RATES['deposit.' . $data['userType']];
    }

    public function convertToBaseCurrency($amount, $currency)
    {
        if (!isset($this->exchangeRates[$currency])) {
            return 1;
        }
        $rate = $this->exchangeRates[$currency];
        return round($amount / $rate, 2);
    }
}