<?php

declare(strict_types=1);

namespace App\CommissionTask\Service;

use App\CommissionTask\Repositories\TransactionsRepository;
use App\CommissionTask\Repositories\ExchangeRatesRepository;

class CommissionService
{
    private $weeklyWithdrawalsEur = [];

    private $baseCurrency = 'EUR';

    public $exchangeRates = [];

    public $transactionsRepository;

    public $exchangeRatesRepository;

    public function __construct(TransactionsRepository $transactionsRepository, ExchangeRatesRepository $exchangeRatesRepository)
    {   
        $this->transactionsRepository = $transactionsRepository;
        $this->exchangeRatesRepository = $exchangeRatesRepository;
    }

    public function calculate()
    {
        $results = [];

        $this->weeklyWithdrawalsEur = [];
        foreach ($this->transactionsRepository->get() as $key => $data) {
            $commission = 0;
            
            if ($data['operationType'] == 'withdraw') {
                $commission = $this->withdrawCommission($data);
            }
            if ($data['operationType'] == 'deposit') {
                $commission = $this->depositCommision($data);
            }

            if (is_numeric($commission)) {
                $commission = $data['currency'] == 'JPY' ? ceil($commission) : number_format(ceil($commission * 100) / 100, 2); 
            }

            $data['commission'] = $commission;

            $results[] = $data;
        }

        return $results;
    }

    public function withdrawCommission($data)
    {
        if (!in_array($data['userType'], ['private', 'business'])) {
            return 'Invalid user type ' . $data['userType'];
        }

        // Calculate for business user type
        if ($data['userType'] == 'business') {
            return $data['amount'] * config('commissions.business.withdraw');
        }
        
        // Calculate for private user type

        $amount = $data['amount'];
        if ($data['currency'] != $this->baseCurrency) {
            $amount = $this->exchangeRatesRepository->convert($amount, $data['currency']);
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
                
                $commission = $commissionable * config('commissions.'.$data['userType'].'.withdraw');

                // conditions above calculates in base (EUR) currency
                // so we convert it back to original currency
                if ($data['currency'] != $this->baseCurrency) {
                    return $commission * $this->exchangeRatesRepository->find($data['currency']);
                }

                return $commission;
            }
        } 

        // this applies for business and private withdrawals not qualified for free of charge
        return $data['amount'] * config('commissions.private.withdraw');
    }

    public function depositCommision($data)
    {
        if (!in_array($data['userType'], ['private', 'business'])) {
            return 'Invalid user type ' . $data['userType'];
        }
        return $data['amount'] * config("commissions.$data[userType].deposit");
    }
}