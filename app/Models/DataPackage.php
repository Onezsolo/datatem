<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPackage extends Model
{
    /** Status values that mean the package cannot be sold. */
    public const OUT_OF_STOCK_STATUSES = ['out of stock', 'sold out', 'unavailable'];

    /**
     * Scope: packages that are available for purchase (not explicitly out of stock).
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhereRaw('LOWER(TRIM(status)) NOT IN (?, ?, ?)', self::OUT_OF_STOCK_STATUSES);
        });
    }

    /**
     * Scope: exclude Express(MTN) and similar Express(*) networks so only main MTN (and other networks) are shown.
     */
    public function scopeExcludeExpressNetworks($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw("LOWER(TRIM(COALESCE(network, ''))) NOT LIKE 'express(%'");
        });
    }

    /**
     * For display/tabs: show Express(MTN) packages under "MTN" so the actual 1GB (e.g. bundle 217) appears under MTN.
     */
    public static function displayNetwork(?string $network): string
    {
        if ($network === null || $network === '') {
            return '';
        }
        $t = trim($network);
        if (strcasecmp($t, 'Express(MTN)') === 0) {
            return 'MTN';
        }
        return $t;
    }

    /** LegacyData bundle_id for the good MTN 1 GB at 4.50 – never exclude this from display. */
    public const MTN_1GB_450_BUNDLE_ID = '162';

    /**
     * Scope: exclude packages whose provider_bundle_id is in the excluded list.
     * Never excludes bundle_id 162 (MTN 1 GB 4.50) so the good 1 GB always shows.
     */
    public function scopeExcludeProviderBundleIds($query)
    {
        $ids = config('services.legacy_data.excluded_bundle_ids', []);
        if ($ids === [] || $ids === null) {
            return $query;
        }
        $strIds = array_map('strval', $ids);
        $strIds = array_values(array_filter($strIds, fn ($id) => trim($id) !== self::MTN_1GB_450_BUNDLE_ID));
        if ($strIds === []) {
            return $query;
        }
        return $query->whereNotIn('provider_bundle_id', $strIds);
    }

    /**
     * Scope: exclude only the wrong MTN 1 GB at 54.50 row (by external_id). Keeps MTN 1 GB at 4.50/5.60.
     */
    public function scopeExcludeMtn1Gb5450($query)
    {
        $badExternalId = abs(crc32('MTN|1 GB|54.5'));
        return $query->where('external_id', '!=', $badExternalId);
    }

    /** external_id of the wrong MTN 1 GB at 54.50 (used when substituting correct bundle for Resend to API). */
    public static function badMtn1Gb5450ExternalId(): int
    {
        return abs(crc32('MTN|1 GB|54.5'));
    }

    /**
     * Whether this package should be replaced with the correct MTN 1 GB when sending to API
     * (wrong MTN 54.50 row, or Express(MTN) 1 GB 64.50 so we send MTN + correct bundle instead).
     */
    public static function shouldUseCorrectMtn1GbForApi(self $package): bool
    {
        if (! $package) {
            return false;
        }
        $extIdStr = (string) $package->external_id;
        if ($extIdStr === (string) self::badMtn1Gb5450ExternalId()) {
            return true;
        }
        $net = strtolower(trim((string) ($package->network ?? '')));
        $vol = trim((string) ($package->volume_gb ?? ''));
        if ($net !== '' && str_starts_with($net, 'express(') && stripos($vol, '1') !== false && stripos($vol, 'gb') !== false) {
            return true;
        }
        return false;
    }

    /**
     * The correct MTN 1 GB package (not the 54.50 one, not Express(MTN)) for use when resending/fulfilling.
     */
    public static function getCorrectMtn1GbPackage(): ?self
    {
        return static::available()
            ->excludeMtn1Gb5450()
            ->where('network', 'MTN')
            ->where(function ($q) {
                $q->where('volume_gb', '1 GB')->orWhere('volume_gb', '1GB');
            })
            ->first();
    }

    protected $fillable = [
        'external_id',
        'network_id',
        'network',
        'volume',
        'volume_gb',
        'provider_bundle_id',
        'console_price',
        'selling_price',
        'walking_customer_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'string',
            'volume' => 'float',
            'console_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'walking_customer_price' => 'decimal:2',
        ];
    }

    /** Price shown to customer (selling_price if set, else console_price). */
    public function getDisplayPriceAttribute(): string
    {
        $p = $this->selling_price !== null ? (float) $this->selling_price : (float) $this->console_price;
        return number_format($p, 2);
    }

    /** Price for walking/guest customers (walking_customer_price if set, else member price + 5%). */
    public function getWalkingPriceAttribute(): float
    {
        $member = $this->selling_price !== null ? (float) $this->selling_price : (float) $this->console_price;
        if ($this->walking_customer_price !== null && (float) $this->walking_customer_price > 0) {
            return round((float) $this->walking_customer_price, 2);
        }
        return round($member * 1.05, 2);
    }

    /** Plan label for dropdown (e.g. "1GB – ₵4.50"). */
    public function getPlanLabelAttribute(): string
    {
        $vol = $this->volume_gb ?: $this->volume . 'GB';
        return $vol . ' – ₵' . $this->display_price;
    }
}
