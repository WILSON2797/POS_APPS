<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class SalesReportExport
{
    protected $transactions;
    protected $brandName;
    protected $periode;

    public function __construct($transactions, $brandName, $periode)
    {
        $this->transactions = $transactions;
        $this->brandName = $brandName;
        $this->periode = $periode;
    }

    public function download()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Pendapatan');

        // Style header banner (Row 1-3)
        $sheet->setCellValue('A1', 'LAPORAN PENDAPATAN & PENJUALAN');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new Color('000000'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $this->brandName);
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->setColor(new Color('000000'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', $this->periode);
        $sheet->mergeCells('A3:K3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('000000'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table headers on Row 5
        $headers = [
            'No',
            'No. Invoice',
            'Tanggal & Waktu',
            'Kasir',
            'Customer',
            'Subtotal (Rp)',
            'Diskon (Rp)',
            'Pajak (Rp)',
            'Grand Total (Rp)',
            'Metode Pembayaran',
            'Estimasi Profit Bersih (Rp)'
        ];

        foreach ($headers as $colIndex => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '5', $headerText);
        }

        // Style table headers row (Dark Green for Revenue #059669)
        $headerRange = 'A5:K5';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF059669');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNum = 6;
        foreach ($this->transactions as $index => $tx) {
            $txCost = $tx->details->sum(fn($d) => $d->cost_price * $d->qty);
            $txProfit = ($tx->subtotal - $tx->discount) - $txCost;

            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $tx->invoice_no);
            $sheet->setCellValue('C' . $rowNum, $tx->created_at->format('d-m-Y H:i:s'));
            $sheet->setCellValue('D' . $rowNum, $tx->user->name);
            $sheet->setCellValue('E' . $rowNum, $tx->customer?->name ?? 'Umum');
            $sheet->setCellValue('F' . $rowNum, $tx->subtotal);
            $sheet->setCellValue('G' . $rowNum, $tx->discount);
            $sheet->setCellValue('H' . $rowNum, $tx->tax);
            $sheet->setCellValue('I' . $rowNum, $tx->grand_total);
            $sheet->setCellValue('J' . $rowNum, strtoupper($tx->payment_method));
            $sheet->setCellValue('K' . $rowNum, $txProfit);

            // Zebra striping
            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':K' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            // Alignments
            $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Currency formatting
            foreach (['F', 'G', 'H', 'I', 'K'] as $col) {
                $sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('"Rp "#,##0');
                $sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $rowNum++;
        }

        $endRow = $rowNum - 1;

        // Borders for data table
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
        $sheet->getStyle('A5:K' . $endRow)->applyFromArray($styleArray);

        // Autofit columns
        foreach (range(1, 11) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan-pendapatan-penjualan-' . date('Ymd-His') . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
