<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Génère le reçu de vente à remettre à l'acheteur :
 *  - format "ticket" 80 mm (imprimante thermique de caisse)
 *  - format A4 (facture classique, à envoyer en PDF)
 */
class ReceiptService
{
    public function pdf(Sale $sale, string $format = 'ticket')
    {
        $sale->loadMissing(['items', 'user', 'branch', 'tenant']);
        $tenant = $sale->tenant;

        $logo = null;
        if ($tenant->logo_path && is_file($path = storage_path('app/public/'.$tenant->logo_path))) {
            $logo = 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
        }

        $pdf = Pdf::loadView('receipts.pdf', [
            'sale' => $sale, 'tenant' => $tenant, 'logo' => $logo, 'format' => $format,
            'qr' => $sale->publicUrl(),
        ]);

        if ($format === 'ticket') {
            // 80 mm de large ; hauteur selon le nombre de lignes
            $height = 400 + $sale->items->count() * 28 + ($logo ? 70 : 0);
            $pdf->setPaper([0, 0, 226.77, $height]);
        } else {
            $pdf->setPaper('a4');
        }

        return $pdf;
    }

    public function filename(Sale $sale): string
    {
        return 'recu-'.$sale->number.'.pdf';
    }
}
