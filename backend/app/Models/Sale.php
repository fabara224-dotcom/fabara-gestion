<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToTenant, HasUuids;

    public const PAYMENT_METHODS = [
        'cash' => 'Espèces',
        'orange_money' => 'Orange Money',
        'mtn_momo' => 'MTN MoMo',
        'card' => 'Carte bancaire',
        'credit' => 'À crédit',
    ];

    protected $fillable = [
        'id', 'tenant_id', 'branch_id', 'user_id', 'customer_id', 'number', 'customer_name',
        'customer_phone', 'subtotal', 'discount', 'total', 'paid', 'payment_method', 'status',
        'receipt_token', 'device_id', 'sold_at',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'subtotal' => 'integer', 'discount' => 'integer', 'total' => 'integer', 'paid' => 'integer',
    ];

    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public function change(): int { return max(0, $this->paid - $this->total); }
    public function due(): int { return max(0, $this->total - $this->paid); }

    public function paymentLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function publicUrl(): string
    {
        return route('receipt.public', $this->receipt_token);
    }

    /** Message WhatsApp prêt à envoyer à l'acheteur avec le lien du reçu */
    public function whatsappUrl(): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $this->customer_phone);
        if ($phone === '') return null;
        if (strlen($phone) === 9) $phone = '224'.$phone; // numéro guinéen local
        $text = "Bonjour, voici votre reçu {$this->number} ({$this->tenant->name}) : ".$this->publicUrl();
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }
}
