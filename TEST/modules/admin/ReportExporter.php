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
        // Set headers for Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $this->title . '.xls"');
        header('Cache-Control: max-age=0');

        // Start output buffering
        ob_start();

        echo '<table border="1">';
        echo '<tr><th colspan="10" style="background-color: #4CAF50; color: white;">' . $this->title . '</th></tr>';
        
        switch ($this->type) {
            case 'property_valuation':
                echo '<tr>
                    <th>Property</th>
                    <th>Initial Value</th>
                    <th>Initial Index</th>
                    <th>Current Index</th>
                    <th>Difference</th>
                    <th>Appreciation</th>
                    <th>Share Appreciation</th>
                    <th>Option Price</th>
                    <th>Total Fees</th>
                    <th>Calculation</th>
                </tr>';
                
                foreach ($this->data as $row) {
                    $initialValue = floatval($row['initial_valuation']);
                    $initialIndex = floatval($row['initial_index']);
                    $currentIndex = floatval($row['index_value']);
                    $difference = $initialIndex > 0 ? (($currentIndex - $initialIndex) / $initialIndex) * 100 : 0;
                    $appreciation = $initialValue * ($difference / 100);
                    $shareAppreciation = $appreciation * ($row['agreed_pct'] / 100);
                    $calculation = $row['option_price'] + $shareAppreciation + $row['total_fees'];
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['address']) . '</td>';
                    echo '<td>$' . number_format($initialValue, 2) . '</td>';
                    echo '<td>' . number_format($initialIndex, 2) . '</td>';
                    echo '<td>' . number_format($currentIndex, 2) . '</td>';
                    echo '<td>' . number_format($difference, 2) . '%</td>';
                    echo '<td>$' . number_format($appreciation, 2) . '</td>';
                    echo '<td>$' . number_format($shareAppreciation, 2) . '</td>';
                    echo '<td>$' . number_format($row['option_price'], 2) . '</td>';
                    echo '<td>$' . number_format($row['total_fees'], 2) . '</td>';
                    echo '<td>$' . number_format($calculation, 2) . '</td>';
                    echo '</tr>';
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
        
        echo '</table>';
        
        // Get the contents of the output buffer
        $output = ob_get_clean();
        
        // Clear any previous output
        ob_clean();
        
        // Output the Excel file
        echo $output;
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