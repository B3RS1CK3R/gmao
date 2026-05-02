<?php
// vendor/SimpleXLSXGen.php - Génération de fichiers Excel simples
class SimpleXLSXGen {
    private $rows = [];
    private $filename = 'export.xlsx';
    
    public function __construct($rows = []) {
        $this->rows = $rows;
    }
    
    public static function fromArray($rows) {
        return new self($rows);
    }
    
    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }
    
    public function download() {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $this->filename . '"');
        header('Cache-Control: max-age=0');
        
        echo $this->generateExcel();
        exit;
    }
    
    private function generateExcel() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
            <sheetData>';
        
        foreach($this->rows as $rowIdx => $row) {
            $xml .= '<row r="' . ($rowIdx + 1) . '">';
            foreach($row as $colIdx => $cell) {
                $colLetter = $this->getColumnLetter($colIdx);
                $xml .= '<c r="' . $colLetter . ($rowIdx + 1) . '" t="inlineStr">
                            <is><t>' . htmlspecialchars($cell) . '</t></is>
                         </c>';
            }
            $xml .= '</row>';
        }
        
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }
    
    private function getColumnLetter($index) {
        $letters = '';
        while ($index >= 0) {
            $letters = chr($index % 26 + 65) . $letters;
            $index = floor($index / 26) - 1;
        }
        return $letters;
    }
}
?>