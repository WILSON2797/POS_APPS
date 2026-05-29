<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class StockMovementsExport
{
    protected $movements;
    protected $brandName;
    protected $periode;

    public function __construct($movements, $brandName, $periode)
    {
        $this->movements = $movements;
        $this->brandName = $brandName;
        $this->periode = $periode;
    }

    public function download()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mutasi Stok');

        // Style header banner (Row 1-3)
        $sheet->setCellValue('A1', 'LAPORAN MUTASI STOK');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new Color('000000'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $this->brandName);
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->setColor(new Color('000000'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', $this->periode);
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('000000'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table headers on Row 5
        $headers = [
            'No',
            'Tanggal & Waktu',
            'Nama Produk',
            'SKU/Barcode',
            'Operator (PIC)',
            'Tipe Pergerakan',
            'Qty',
            'Stok Awal',
            'Stok Akhir',
            'Keterangan'
        ];

        foreach ($headers as $colIndex => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '5', $headerText);
        }

        // Style table headers row (Theme Primary Blue)
        $headerRange = 'A5:J5';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3B82F6');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNum = 6;
        foreach ($this->movements as $index => $m) {
            $typeText = $m->type === 'in' ? 'Masuk (+)' : ($m->type === 'out' ? 'Keluar (-)' : 'Koreksi Stok');
            
            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $m->created_at->format('d-m-Y H:i:s'));
            $sheet->setCellValue('C' . $rowNum, $m->product->name ?? 'Produk Dihapus');
            $sheet->setCellValue('D' . $rowNum, $m->product->barcode ?? '-');
            $sheet->setCellValue('E' . $rowNum, $m->user->name ?? 'System');
            $sheet->setCellValue('F' . $rowNum, $typeText);
            $sheet->setCellValue('G' . $rowNum, $m->quantity);
            $sheet->setCellValue('H' . $rowNum, $m->old_stock);
            $sheet->setCellValue('I' . $rowNum, $m->new_stock);
            $sheet->setCellValue('J' . $rowNum, $m->note ?? '-');

            // Zebra striping
            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            // Alignments
            $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Numbers
            $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $rowNum++;
        }

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
        $sheet->getStyle('A5:J' . ($rowNum - 1))->applyFromArray($styleArray);

        // Autofit columns
        foreach (range(1, 10) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan-mutasi-stok-' . date('Ymd-His') . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
