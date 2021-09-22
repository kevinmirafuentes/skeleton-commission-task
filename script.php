<?php 
require 'vendor/autoload.php';

use App\CommissionTask\Service\CommissionService;
use App\CommissionTask\Repositories\TransactionsRepository;

$inputFile = $argv[1];

if (!file_exists($inputFile)) {
    echo "Input file does not exists.\n";
}

echo "Importing file...\n";
$respository = TransactionsRepository::getInstance();
$respository->loadCsv($inputFile);

echo "Calculating...\n";
$commissionService = new CommissionService($respository);
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