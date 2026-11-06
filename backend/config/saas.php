<?php

return [
    'name' => env('SAAS_NAME', 'Fabara Gestion'),
    'support_phone' => env('SAAS_SUPPORT_PHONE', '626 43 22 76'),
    'support_whatsapp' => env('SAAS_SUPPORT_WHATSAPP', '224626432276'),
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 14),

    // Formules de location (créées en base par le seeder ; modifiables ensuite)
    'plans' => [
        ['code' => 'starter', 'name' => 'Boutique', 'price_gnf' => 150000, 'max_users' => 2, 'max_branches' => 1, 'max_products' => 500,
         'features' => ['Caisse & reçus', 'Stock avec photos', 'Application mobile hors ligne', 'Rapports de base']],
        ['code' => 'pro', 'name' => 'Commerce', 'price_gnf' => 300000, 'max_users' => 6, 'max_branches' => 2, 'max_products' => 5000,
         'features' => ['Tout Boutique', 'Espace propriétaire', 'Dépenses & bénéfices', 'Clients à crédit']],
        ['code' => 'business', 'name' => 'Entreprise', 'price_gnf' => 600000, 'max_users' => 25, 'max_branches' => 10, 'max_products' => 100000,
         'features' => ['Tout Commerce', 'Plusieurs boutiques', 'Export Excel', 'Support prioritaire']],
    ],
];
