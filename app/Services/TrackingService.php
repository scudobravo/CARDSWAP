<?php

namespace App\Services;

class TrackingService
{
    /**
     * Costruisce un URL di tracking a partire da carrier e numero
     */
    public function buildTrackingUrl(?string $carrierCode, ?string $trackingNumber): ?string
    {
        if (!$carrierCode || !$trackingNumber) {
            return null;
        }

        $code = strtolower(trim($carrierCode));
        return match ($code) {
            'dhl' => 'https://www.dhl.com/it-it/home/tracking.html?tracking-id=' . urlencode($trackingNumber),
            'ups' => 'https://www.ups.com/track?loc=it_IT&tracknum=' . urlencode($trackingNumber),
            'fedex' => 'https://www.fedex.com/fedextrack/?trknbr=' . urlencode($trackingNumber),
            'poste', 'poste-italiane' => 'https://www.poste.it/cerca/index.html#/risultati-spedizioni/' . urlencode($trackingNumber),
            'gls' => 'https://www.gls-italy.com/it/assistenzaclive/ricerca-spedizioni?match=' . urlencode($trackingNumber),
            'bartolini', 'btl', 'brt' => 'https://vas.brt.it/vas/sped_det_show.hml?data_sped=' . urlencode($trackingNumber),
            default => null,
        };
    }
}


