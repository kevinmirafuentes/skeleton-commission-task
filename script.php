<?php 
require 'vendor/autoload.php';

use App\CommissionTask\Service\CommissionService;
use App\CommissionTask\Repositories\TransactionsRepository;
use App\CommissionTask\Repositories\ExchangeRatesRepository;

$inputFile = $argv[1];

if (!file_exists($inputFile)) {
    echo "Input file does not exists.\n";
}

echo "Importing file...\n";
$transactionsRespository = TransactionsRepository::getInstance();
$transactionsRespository->loadCsv($inputFile);

$exchangeRatesRepository = ExchangeRatesRepository::getInstance();
$exchangeRatesRepository->importFromSource(config('exchange_rates.source_urls'));
// $exchangeRatesRepository->saveData('USD', 1.1497);
// $exchangeRatesRepository->saveData('JPY', 129.53);

echo "Calculating...\n";
$commissionService = new CommissionService($transactionsRespository, $exchangeRatesRepository);
$results = $commissionService->calculate();

// Uncomment to save data to repository
// foreach ($results as $index => $data) {
//     $respository->setData($index, $data);
// }

echo "------------------------------\n";
echo "OUTPUT: \n";
echo "------------------------------\n";
foreach ($results as $result) {
    echo $result['date'] . ',';
    echo $result['user'] . ',';
    echo $result['userType'] . ',';
    echo $result['operationType'] . ',';
    echo $result['amount'] . ',';
    echo $result['currency'];
    echo '... Commission = ' . $result['commission'] . "\n";
}