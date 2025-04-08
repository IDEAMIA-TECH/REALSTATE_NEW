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
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        switch ($this->type) {
            case 'property_valuation':
                // Set headers
                $headers = [
                    'Property',
                    'Initial Value',
                    'Initial Index',
                    'Current Index',
                    'Difference',
                    'Appreciation',
                    'Share Appreciation',
                    'Option Price',
                    'Total Fees',
                    'Calculation'
                ];
                $sheet->fromArray($headers, NULL, 'A3');
                
                // Style headers
                $sheet->getStyle('A3:J3')->getFont()->setBold(true);
                $sheet->getStyle('A3:J3')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4CAF50');
                $sheet->getStyle('A3:J3')->getFont()->getColor()->setRGB('FFFFFF');
                
                // Add data
                $row = 4;
                foreach ($this->data as $data) {
                    $initialValue = floatval($data['initial_valuation']);
                    $initialIndex = floatval($data['initial_index']);
                    $currentIndex = floatval($data['index_value']);
                    $difference = $initialIndex > 0 ? (($currentIndex - $initialIndex) / $initialIndex) * 100 : 0;
                    $appreciation = floatval($data['appreciation']);
                    $currentValue = $initialValue + $appreciation;
                    $shareAppreciation = $appreciation * ($data['agreed_pct'] / 100);
                    $calculation = $data['option_price'] + $shareAppreciation + $data['total_fees'];
                    
                    $sheet->setCellValue('A'.$row, $data['address']);
                    $sheet->setCellValue('B'.$row, $initialValue);
                    $sheet->setCellValue('C'.$row, $initialIndex);
                    $sheet->setCellValue('D'.$row, $currentIndex);
                    $sheet->setCellValue('E'.$row, $difference);
                    $sheet->setCellValue('F'.$row, $appreciation);
                    $sheet->setCellValue('G'.$row, $shareAppreciation);
                    $sheet->setCellValue('H'.$row, $data['option_price']);
                    $sheet->setCellValue('I'.$row, $data['total_fees']);
                    $sheet->setCellValue('J'.$row, $calculation);
                    
                    // Format numbers
                    $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                    $sheet->getStyle('C'.$row.':D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00"%"');
                    $sheet->getStyle('F'.$row.':J'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                    
                    $row++;
                }
                
                // Auto-size columns
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                break;
                
            case 'client_activity':
                echo '<tr>
                    <th>Client ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Properties Count</th>
                    <th>Total Valuation</th>
                    <th>Last Activity</th>
                    <th>Action</th>
                </tr>';
                
                foreach ($this->data as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['property_count']) . '</td>';
                    echo '<td>' . htmlspecialchars('$' . number_format($row['total_valuation'], 2)) . '</td>';
                    echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['action']) . '</td>';
                    echo '</tr>';
                }
                break;
                
            case 'csushpinsa':
                echo '<tr>
                    <th>Date</th>
                    <th>Index Value</th>
                </tr>';
                
                foreach ($this->data as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['date']) . '</td>';
                    echo '<td>' . htmlspecialchars(number_format($row['value'], 2)) . '</td>';
                    echo '</tr>';
                }
                break;
                
            case 'user_activity':
                echo '<tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Entity Type</th>
                    <th>Action Count</th>
                    <th>Last Activity</th>
                </tr>';
                
                foreach ($this->data as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['role']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['action']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['entity_type']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['action_count']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                    echo '</tr>';
                }
                break;
        }
        
        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $this->getFilename('xlsx') . '"');
        header('Cache-Control: max-age=0');
        
        // Clear any previous output and buffers
        if (ob_get_length()) ob_end_clean();
        
        // Output file
        $writer->save('php://output');
        exit();
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