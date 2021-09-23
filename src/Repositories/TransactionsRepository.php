<?php 
namespace App\CommissionTask\Repositories;

class TransactionsRepository
{
    use SingletonRepository;

    private $data;

    public function loadCsv($csv) 
    {
        if (($handle = fopen($csv, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                call_user_func_array([$this, 'addData'], $data);
            }
            fclose($handle);
        }
    }

    public function addData($date, $user, $userType, $operationType, $amount, $currency)
    {
        $weekId = $user . ':' . date('oW', strtotime($date));
        $commission = null;
        $this->data[] = compact('date', 'user', 'userType', 'operationType', 'amount', 'currency', 'weekId', 'commission');
    }

    public function get()
    {
        return $this->data;
    }

    public function getCommissions()
    {
        return array_map(function($a) {
            return $a['commission'];
        }, $this->data);
    }

    public function setData($index, $data)
    {
        $this->data[$index] = $data;
    }
}