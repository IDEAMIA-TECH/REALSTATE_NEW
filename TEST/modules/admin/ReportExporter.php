<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportExporter {
    private $data;
    private $type;
    private $title;
    
    public function __construct($data, $type, $title) {
        $this->data = $data;
        $this->type = $type;
        $this->title = $title;
    }
    
    public function exportToExcel() {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title
        $sheet->setCellValue('A1', $this->title);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Set headers based on report type
        $headers = $this->getHeaders();
        $sheet->fromArray($headers, NULL, 'A3');
        
        // Set data
        $row = 4;
        foreach ($this->data as $item) {
            $values = $this->formatRowData($item);
            $sheet->fromArray($values, NULL, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = $this->getFilename('xlsx');
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    
    public function exportToPDF() {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->generatePDFHtml();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = $this->getFilename('pdf');
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo $dompdf->output();
        exit;
    }
    
    private function getHeaders() {
        switch ($this->type) {
            case 'property_valuation':
                return [
                    'Property ID',
                    'Address',
                    'Initial Value',
                    'Current Value',
                    'Appreciation',
                    'Share Appreciation',
                    'Terminal Value',
                    'Projected Payoff',
                    'Option Valuation',
                    'Valuation Date'
                ];
            case 'client_activity':
                return [
                    'Client ID',
                    'Name',
                    'Email',
                    'Properties Count',
                    'Total Valuation',
                    'Last Activity',
                    'Action'
                ];
            case 'csushpinsa':
                return [
                    'Date',
                    'Index Value'
                ];
            case 'user_activity':
                return [
                    'Username',
                    'Email',
                    'Role',
                    'Action',
                    'Entity Type',
                    'Action Count',
                    'Last Activity'
                ];
            default:
                return [];
        }
    }
    
    private function formatRowData($item) {
        switch ($this->type) {
            case 'property_valuation':
                return [
                    $item['id'],
                    $item['address'],
                    '$' . number_format($item['initial_valuation'], 2),
                    '$' . number_format($item['current_value'], 2),
                    '$' . number_format($item['appreciation'], 2),
                    '$' . number_format($item['share_appreciation'], 2),
                    '$' . number_format($item['terminal_value'], 2),
                    '$' . number_format($item['projected_payoff'], 2),
                    '$' . number_format($item['option_valuation'], 2),
                    $item['valuation_date']
                ];
            case 'client_activity':
                return [
                    $item['id'],
                    $item['name'],
                    $item['email'],
                    $item['property_count'],
                    '$' . number_format($item['total_valuation'], 2),
                    $item['created_at'],
                    $item['action']
                ];
            case 'csushpinsa':
                return [
                    $item['date'],
                    number_format($item['value'], 2)
                ];
            case 'user_activity':
                return [
                    $item['username'],
                    $item['email'],
                    $item['role'],
                    $item['action'],
                    $item['entity_type'],
                    $item['action_count'],
                    $item['created_at']
                ];
            default:
                return [];
        }
    }
    
    private function generatePDFHtml() {
        $headers = $this->getHeaders();
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            h1 { text-align: center; }
        </style></head><body>';
        
        $html .= '<h1>' . $this->title . '</h1>';
        $html .= '<table>';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr>';
        
        foreach ($this->data as $item) {
            $html .= '<tr>';
            $values = $this->formatRowData($item);
            foreach ($values as $value) {
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table></body></html>';
        return $html;
    }
    
    private function getFilename($extension) {
        $timestamp = date('Y-m-d_H-i-s');
        return $this->type . '_report_' . $timestamp . '.' . $extension;
    }
} 