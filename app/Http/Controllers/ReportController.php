<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request): View
    {
        [$sales, $purchases, $labels, $amounts] = $this->reportData($request);

        $topProducts = SaleItem::query()
            ->selectRaw('product_id, product_name, sku, SUM(quantity) as units, SUM(line_total) as revenue')
            ->whereHas('sale', fn ($query) => $query->where('status', 'completed')->whereBetween('created_at', [$request->fromDate(), $request->toDate().' 23:59:59']))
            ->groupBy('product_id', 'product_name', 'sku')
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        return view('reports.index', [
            'from' => $request->fromDate(),
            'to' => $request->toDate(),
            'sales' => $sales,
            'purchases' => $purchases,
            'labels' => $labels,
            'amounts' => $amounts,
            'topProducts' => $topProducts,
        ]);
    }

    public function csv(ReportFilterRequest $request): StreamedResponse
    {
        $from = $request->fromDate();
        $to = $request->toDate();

        return response()->streamDownload(function () use ($from, $to): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('No se pudo crear el archivo CSV.');
            }
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Fecha', 'Comprobante', 'Cliente', 'Vendedor', 'Subtotal', 'Descuento', 'Impuesto', 'Total', 'Estado'], ';');

            Sale::with('customer', 'user')
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->orderBy('id')
                ->chunk(500, function ($sales) use ($stream): void {
                    foreach ($sales as $sale) {
                        fputcsv($stream, [
                            $sale->created_at->format('Y-m-d H:i:s'),
                            $this->safeCsvText($sale->reference),
                            $this->safeCsvText($sale->customer?->name ?? 'Venta general'),
                            $this->safeCsvText($sale->user?->name ?? ''),
                            $sale->subtotal,
                            $sale->discount_amount,
                            $sale->tax_amount,
                            $sale->total,
                            $this->safeCsvText($sale->status),
                        ], ';');
                    }
                });
            fclose($stream);
        }, "inventorypro-ventas-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(ReportFilterRequest $request)
    {
        [$sales, $purchases] = $this->totals($request->fromDate(), $request->toDate());
        $records = Sale::with('customer', 'user')
            ->whereBetween('created_at', [$request->fromDate(), $request->toDate().' 23:59:59'])
            ->orderByDesc('created_at')->get();

        return Pdf::loadView('reports.pdf', [
            'from' => $request->fromDate(),
            'to' => $request->toDate(),
            'salesTotal' => $sales['total'],
            'purchasesTotal' => $purchases['total'],
            'salesCount' => $sales['count'],
            'records' => $records,
            'currency' => config('inventory.currency_symbol'),
        ])->setPaper('a4', 'landscape')
            ->download("inventorypro-reporte-{$request->fromDate()}-{$request->toDate()}.pdf");
    }

    private function reportData(ReportFilterRequest $request): array
    {
        [$sales, $purchases] = $this->totals($request->fromDate(), $request->toDate());
        $daily = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$request->fromDate(), $request->toDate().' 23:59:59'])
            ->selectRaw('DATE(created_at) as day, SUM(total) as amount')
            ->groupBy('day')
            ->pluck('amount', 'day');

        $labels = collect();
        $amounts = collect();
        $day = Carbon::parse($request->fromDate())->startOfDay();
        $lastDay = Carbon::parse($request->toDate())->startOfDay();
        while ($day->lte($lastDay)) {
            $key = $day->toDateString();
            $labels->push($day->format('d/m'));
            $amounts->push((float) ($daily[$key] ?? 0));
            $day->addDay();
        }

        return [$sales, $purchases, $labels, $amounts];
    }

    private function totals(string $from, string $to): array
    {
        $range = [$from, $to.' 23:59:59'];

        $sales = Sale::query()->where('status', 'completed')->whereBetween('created_at', $range);
        $purchases = Purchase::query()->where('status', 'received')->whereBetween('received_at', $range);

        return [
            ['count' => (clone $sales)->count(), 'total' => (float) $sales->sum('total')],
            ['count' => (clone $purchases)->count(), 'total' => (float) $purchases->sum('total')],
        ];
    }

    private function safeCsvText(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
