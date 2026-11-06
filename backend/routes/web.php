<?php

use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// ---------- Site vitrine (public) ----------
Route::get('/', [LandingController::class, 'index'])->name('home');

// Reçu public : lien envoyé à l'acheteur par WhatsApp / SMS (aucune connexion requise)
Route::get('/r/{token}', [PublicReceiptController::class, 'show'])->name('receipt.public');
Route::get('/r/{token}/pdf', [PublicReceiptController::class, 'pdf'])->name('receipt.public.pdf');

// ---------- Authentification ----------
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'login'])->middleware('throttle:login');
    Route::get('/inscription', [RegisterController::class, 'show'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:login');
});
Route::post('/deconnexion', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Espace entreprise ----------
Route::middleware(['auth', 'tenant'])->group(function () {

    Route::get('/abonnement', [BillingController::class, 'show'])->name('billing');

    Route::middleware('subscribed')->group(function () {
        Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

        // Caisse (tous les rôles de l'entreprise)
        Route::get('/caisse', [PosController::class, 'index'])->name('pos');
        Route::post('/caisse', [PosController::class, 'store'])->name('pos.store');

        // Ventes & reçus
        Route::get('/ventes', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/ventes/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/ventes/{sale}/recu', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::post('/ventes/{sale}/annuler', [SaleController::class, 'cancel'])->middleware('role:owner,manager')->name('sales.cancel');

        // Produits & stock (consultation pour tous, modification gérant/propriétaire)
        Route::get('/produits', [ProductController::class, 'index'])->name('products.index');
        Route::middleware('role:owner,manager')->group(function () {
            Route::get('/produits/nouveau', [ProductController::class, 'create'])->name('products.create');
            Route::post('/produits', [ProductController::class, 'store'])->name('products.store');
            Route::get('/produits/{product}/modifier', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('/produits/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/produits/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
            Route::post('/stock', [StockController::class, 'store'])->name('stock.store');

            Route::get('/depenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::post('/depenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::delete('/depenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

            Route::get('/clients', [CustomerController::class, 'index'])->name('customers.index');
            Route::post('/clients/{customer}/paiement', [CustomerController::class, 'pay'])->name('customers.pay');

            Route::get('/rapports', [ReportController::class, 'index'])->name('reports.index');
        });

        // Réservé au propriétaire de l'entreprise
        Route::middleware('role:owner')->group(function () {
            Route::get('/equipe', [TeamController::class, 'index'])->name('team.index');
            Route::post('/equipe', [TeamController::class, 'store'])->name('team.store');
            Route::put('/equipe/{user}', [TeamController::class, 'update'])->name('team.update');
            Route::delete('/equipe/{user}', [TeamController::class, 'destroy'])->name('team.destroy');

            Route::get('/parametres', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/parametres', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/boutiques', [BranchController::class, 'store'])->name('branches.store');
            Route::put('/boutiques/{branch}', [BranchController::class, 'update'])->name('branches.update');
        });
    });

    // ---------- Super-admin (toi) ----------
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminTenantController::class, 'index'])->name('tenants.index');
        Route::get('/entreprises/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
        Route::put('/entreprises/{tenant}', [AdminTenantController::class, 'update'])->name('tenants.update');
        Route::post('/entreprises/{tenant}/paiement', [AdminTenantController::class, 'payment'])->name('tenants.payment');
        Route::post('/entreprises/{tenant}/entrer', [AdminTenantController::class, 'impersonate'])->name('tenants.impersonate');
    });
    Route::post('/admin/quitter', [AdminTenantController::class, 'leave'])->name('admin.leave');
});
