<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleService;
use App\Support\CurrentTenant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('saas.plans') as $p) {
            Plan::updateOrCreate(['code' => $p['code']], $p);
        }

        // Ton compte de propriétaire de la plateforme (à changer après la première connexion !)
        User::updateOrCreate(['email' => env('SUPERADMIN_EMAIL', 'admin@fabara-gestion.com')], [
            'name' => 'Super Admin',
            'password' => env('SUPERADMIN_PASSWORD', 'ChangeMoi2026!'),
            'role' => 'super_admin',
        ]);

        if (app()->environment('production')) {
            return; // pas de données de démonstration en production
        }

        // ---- Entreprise de démonstration ----
        $tenant = Tenant::updateOrCreate(['slug' => 'demo'], [
            'name' => 'Boutique Démo', 'business_type' => 'boutique', 'phone' => '620 00 00 00',
            'address' => 'Dixinn, Conakry', 'plan_id' => Plan::where('code', 'pro')->value('id'),
            'status' => 'trial', 'trial_ends_at' => now()->addDays(30),
        ]);
        app(CurrentTenant::class)->set($tenant);

        $branch = Branch::firstOrCreate(['tenant_id' => $tenant->id, 'name' => 'Boutique principale']);

        User::updateOrCreate(['email' => 'proprio@demo.gn'], [
            'tenant_id' => $tenant->id, 'name' => 'Mamadou Bah', 'password' => 'demo1234', 'role' => 'owner',
        ]);
        $seller = User::updateOrCreate(['email' => 'vendeuse@demo.gn'], [
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'name' => 'Awa Camara',
            'password' => 'demo1234', 'role' => 'manager',
        ]);

        if (Product::count() > 0) return;

        $cats = collect(['Vêtements femme' => '#E84A8A', 'Vêtements homme' => '#1B998B', 'Accessoires' => '#F4B400', 'Cosmétiques' => '#D7263D'])
            ->map(fn ($color, $name) => Category::create(['name' => $name, 'color' => $color]));

        $items = [
            ['Robe wax', 'Vêtements femme', 250000, 150000, 24], ['Tunique brodée', 'Vêtements femme', 350000, 220000, 11],
            ['Boubou homme', 'Vêtements homme', 400000, 260000, 8], ['Chemise lin', 'Vêtements homme', 180000, 100000, 15],
            ['Sac à main', 'Accessoires', 180000, 110000, 9], ['Montre homme', 'Accessoires', 450000, 300000, 4],
            ['Crème éclaircissante', 'Cosmétiques', 85000, 50000, 30], ['Parfum 100 ml', 'Cosmétiques', 220000, 140000, 2],
        ];
        $products = [];
        foreach ($items as [$name, $cat, $price, $cost, $qty]) {
            $p = Product::create(['name' => $name, 'category_id' => $cats[$cat]->id, 'price' => $price, 'cost' => $cost, 'alert_threshold' => 3]);
            StockMovement::create(['product_id' => $p->id, 'branch_id' => $branch->id, 'type' => 'in', 'quantity' => $qty, 'reason' => 'Stock initial']);
            $p->recalculateStock();
            $products[] = $p;
        }

        // Quelques ventes sur les 7 derniers jours pour remplir les rapports
        $service = app(SaleService::class);
        for ($d = 6; $d >= 0; $d--) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                $p = $products[array_rand($products)];
                $service->create($seller, [
                    'items' => [['product_id' => $p->id, 'quantity' => 1]],
                    'payment_method' => ['cash', 'orange_money', 'mtn_momo'][rand(0, 2)],
                    'sold_at' => now()->subDays($d)->setTime(rand(9, 19), rand(0, 59)),
                ]);
            }
        }
    }
}
