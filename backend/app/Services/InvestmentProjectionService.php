<?php

namespace App\Services;

use App\Models\Estimation;

class InvestmentProjectionService
{
    private const HORIZONS = [1, 10, 20];

    /**
     * @return array<string, mixed>|null
     */
    public function forEstimation(Estimation $estimation): ?array
    {
        $estimation = $this->resolveRoot($estimation);
        $snapshot = $this->latestSnapshot($estimation);

        $purchasePrice = (float) ($snapshot->purchase_price ?? 0);
        if ($purchasePrice <= 0) {
            return null;
        }

        $netAnnual = $this->resolveNetAnnualIncome($snapshot, $purchasePrice);
        if ($netAnnual === null) {
            return null;
        }

        $netYieldPct = round(($netAnnual / $purchasePrice) * 100, 2);
        $monthlyCashflow = round($netAnnual / 12, 2);

        $horizons = [];
        foreach (self::HORIZONS as $years) {
            $cumulativeIncome = round($netAnnual * $years, 2);
            $totalReturnPct = round(($cumulativeIncome / $purchasePrice) * 100, 2);

            $horizons[] = [
                'years' => $years,
                'cumulative_income' => $cumulativeIncome,
                'total_return_pct' => $totalReturnPct,
                'annual_net_yield_pct' => $netYieldPct,
            ];
        }

        return [
            'purchase_price' => round($purchasePrice, 2),
            'net_annual_income' => round($netAnnual, 2),
            'net_yield_pct' => $netYieldPct,
            'monthly_cashflow' => $monthlyCashflow,
            'horizons' => $horizons,
        ];
    }

    private function resolveNetAnnualIncome(Estimation $estimation, float $purchasePrice): ?float
    {
        $rentability = $estimation->rentability;
        if (is_array($rentability)) {
            if (isset($rentability['monthly_cashflow'])) {
                return (float) $rentability['monthly_cashflow'] * 12;
            }
            if (isset($rentability['net_yield'])) {
                return ((float) $rentability['net_yield'] / 100) * $purchasePrice;
            }
            if (isset($rentability['annual_rent'])) {
                $annualRent = (float) $rentability['annual_rent'];
                $monthlyCharges = (float) ($rentability['monthly_charges'] ?? 0);

                return $annualRent - ($monthlyCharges * 12) - ($annualRent * 0.30);
            }
        }

        if ($estimation->net_yield !== null) {
            return ((float) $estimation->net_yield / 100) * $purchasePrice;
        }

        $annualRent = (float) $estimation->predicted_rent * 12;
        if ($annualRent <= 0) {
            return null;
        }

        $charges = is_array($estimation->form_data)
            ? (float) ($estimation->form_data['charges'] ?? 0)
            : 0;

        return $annualRent - ($charges * 12) - ($annualRent * 0.30);
    }

    private function resolveRoot(Estimation $estimation): Estimation
    {
        if ($estimation->isRoot()) {
            return $estimation;
        }

        return $estimation->parent ?? $estimation;
    }

    private function latestSnapshot(Estimation $root): Estimation
    {
        $latestVariant = $root->variants()->latest()->first();

        return $latestVariant ?? $root;
    }
}
