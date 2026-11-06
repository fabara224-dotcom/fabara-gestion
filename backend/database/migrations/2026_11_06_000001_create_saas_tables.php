<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schéma multi-entreprises de Fabara Gestion.
 *
 * - Chaque ligne métier porte tenant_id (isolation des données).
 * - Les données créées sur mobile hors ligne utilisent des UUID (pas de conflit d'identifiants).
 * - Index (tenant_id, updated_at) sur les tables synchronisées : la synchro reste rapide
 *   même avec des milliers d'entreprises.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();          // starter, pro, business
            $t->string('name');
            $t->unsignedInteger('price_gnf');      // prix mensuel
            $t->unsignedInteger('max_users');
            $t->unsignedInteger('max_branches');
            $t->unsignedInteger('max_products');
            $t->json('features')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();            // code entreprise utilisé à la connexion
            $t->string('business_type')->default('boutique'); // boutique, quincaillerie, pharmacie, restaurant…
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('city')->default('Conakry');
            $t->string('country', 2)->default('GN');
            $t->string('currency', 8)->default('GNF');
            $t->string('logo_path')->nullable();
            $t->string('brand_color', 9)->default('#D7263D');
            $t->string('receipt_header')->nullable();
            $t->string('receipt_footer')->nullable()->default('Merci pour votre achat !');
            $t->string('tax_id')->nullable();         // NIF / RCCM
            $t->foreignId('plan_id')->nullable()->constrained('plans');
            $t->string('status')->default('trial');   // trial, active, past_due, suspended
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('subscription_ends_at')->nullable();
            $t->unsignedBigInteger('sale_counter')->default(0);
            $t->timestamps();
            $t->index('status');
        });

        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('address')->nullable();
            $t->string('phone')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $t->string('role')->default('cashier')->after('password'); // super_admin, owner, manager, cashier
            $t->string('phone')->nullable()->after('email');
            $t->boolean('is_active')->default(true)->after('role');
            $t->timestamp('last_seen_at')->nullable();
            $t->index(['tenant_id', 'role']);
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('color', 9)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['tenant_id', 'updated_at']);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->uuid('category_id')->nullable();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->string('barcode')->nullable();
            $t->text('description')->nullable();
            $t->unsignedBigInteger('price');            // prix de vente (GNF, entier)
            $t->unsignedBigInteger('cost')->default(0); // prix d'achat (pour le bénéfice)
            $t->string('unit')->default('pièce');
            $t->decimal('stock', 14, 3)->default(0);    // stock total, recalculé depuis les mouvements
            $t->decimal('alert_threshold', 14, 3)->default(3);
            $t->string('image_path')->nullable();
            $t->json('gallery')->nullable();
            $t->boolean('track_stock')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['tenant_id', 'updated_at']);
            $t->index(['tenant_id', 'name']);
            $t->index(['tenant_id', 'barcode']);
        });

        Schema::create('customers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->bigInteger('balance')->default(0); // crédit client (dette)
            $t->timestamps();
            $t->softDeletes();
            $t->index(['tenant_id', 'updated_at']);
            $t->index(['tenant_id', 'phone']);
        });

        Schema::create('sales', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->uuid('customer_id')->nullable();
            $t->string('number');                    // REC-000123 (unique par entreprise)
            $t->string('customer_name')->nullable();
            $t->string('customer_phone')->nullable();
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('paid')->default(0);
            $t->string('payment_method')->default('cash'); // cash, orange_money, mtn_momo, card, credit
            $t->string('status')->default('completed');   // completed, cancelled
            $t->string('receipt_token', 40)->unique();     // lien public du reçu
            $t->string('device_id')->nullable();
            $t->timestamp('sold_at');
            $t->timestamps();
            $t->unique(['tenant_id', 'number']);
            $t->index(['tenant_id', 'sold_at']);
            $t->index(['tenant_id', 'updated_at']);
        });

        Schema::create('sale_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('sale_id');
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->uuid('product_id')->nullable();
            $t->string('name');
            $t->decimal('quantity', 14, 3);
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('unit_cost')->default(0);
            $t->unsignedBigInteger('total');
            $t->timestamps();
            $t->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $t->index(['tenant_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->uuid('product_id');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');                     // in (entrée), out (sortie), sale, adjust, return
            $t->decimal('quantity', 14, 3);         // signé : +5 / -2
            $t->string('reason')->nullable();
            $t->uuid('sale_id')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'product_id']);
            $t->index(['tenant_id', 'updated_at']);
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('label');
            $t->string('category')->default('divers'); // loyer, transport, salaire, électricité…
            $t->unsignedBigInteger('amount');
            $t->date('spent_on');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['tenant_id', 'spent_on']);
            $t->index(['tenant_id', 'updated_at']);
        });

        Schema::create('subscription_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained('plans');
            $t->unsignedBigInteger('amount');
            $t->string('method')->default('orange_money');
            $t->string('reference')->nullable();
            $t->unsignedTinyInteger('months')->default(1);
            $t->timestamp('paid_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['subscription_payments', 'expenses', 'stock_movements', 'sale_items', 'sales',
                  'customers', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropConstrainedForeignId('tenant_id');
            $t->dropColumn(['role', 'phone', 'is_active', 'last_seen_at']);
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plans');
    }
};
