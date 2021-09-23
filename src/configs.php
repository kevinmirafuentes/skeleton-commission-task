<?php 

return [
    'exchange_rates' => [
        'base' => 'EUR',
        'source_url' => 'http://api.exchangeratesapi.io/v1/latest?access_key=70e141fb2263729d85163a81daffcdcd',
    ],
    'commissions' => [
        'private' => [
            'deposit' => 0.0003, // 0.03%
            'withdraw' => 0.003, // 0.3%
        ],
        'business' => [
            'deposit' => 0.0003, // 0.03%
            'withdraw' => 0.005, // 0.5%
        ]
    ]
];