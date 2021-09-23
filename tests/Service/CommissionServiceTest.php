<?php

declare(strict_types=1);

namespace App\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\CommissionTask\Service\CommissionService;
use App\CommissionTask\Repositories\TransactionsRepository;
use App\CommissionTask\Repositories\ExchangeRatesRepository;

class CommissionServiceTest extends TestCase
{
    public function testCalculateCommission()
    {
        $input = __DIR__ . '/../../input.csv';

        $transactionsRespository = TransactionsRepository::getInstance();
        $transactionsRespository->loadCsv($input);

        $exchangeRatesRepository = ExchangeRatesRepository::getInstance();
        // $exchangeRatesRepository->importFromSource(config('exchange_rates.source_urls'));

        $exchangeRatesRepository->saveData('USD', 1.1497);
        $exchangeRatesRepository->saveData('JPY', 129.53);


        $commissionService = new CommissionService($transactionsRespository, $exchangeRatesRepository);
        $results = $commissionService->calculate();

        // saves data to repository
        foreach ($results as $index => $data) {
            $transactionsRespository->setData($index, $data);
        }
        $commissions = $transactionsRespository->getCommissions();
        
        $this->assertEquals($commissions[0], 0.60);
        $this->assertEquals($commissions[1], 3.00);
        $this->assertEquals($commissions[2], 0.00);
        $this->assertEquals($commissions[3], 0.06);
        $this->assertEquals($commissions[4], 1.50);
        $this->assertEquals($commissions[5], 0);
        $this->assertEquals($commissions[6], 0.70);
        $this->assertEquals($commissions[7], 0.30);
        $this->assertEquals($commissions[8], 0.30);
        $this->assertEquals($commissions[9], 3.00);
        $this->assertEquals($commissions[10], 0.00);
        $this->assertEquals($commissions[11], 0.00);
        $this->assertEquals($commissions[12], 8612);
    }
}