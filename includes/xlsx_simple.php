<?php
/**
 * Exportador XLSX mínimo (sem dependências externas) via ZipArchive
 */

function xlsx_col_letter($index)
{
    $index = (int) $index + 1;
    $letter = '';
    while ($index > 0) {
        $index--;
        $letter = chr(65 + ($index % 26)) . $letter;
        $index = (int) ($index / 26);
    }
    return $letter;
}

function xlsx_escape($value)
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsx_cell_xml($row, $col, $value)
{
    $ref = xlsx_col_letter($col) . $row;
    if (is_array($value) || is_object($value)) {
        $str = json_encode($value, JSON_UNESCAPED_UNICODE);
    } else {
        $str = (string) $value;
    }
    if ($str !== '' && is_numeric($str) && strlen($str) < 15 && strpos($str, '.') === false) {
        return '<c r="' . $ref . '"><v>' . $str . '</v></c>';
    }
    return '<c r="' . $ref . '" t="inlineStr"><is><t>' . xlsx_escape($str) . '</t></is></c>';
}

/**
 * Gera arquivo XLSX e envia para download.
 */
function xlsx_download(array $cabecalho, array $linhas, $filename)
{
    if (!class_exists('ZipArchive')) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Extensão ZipArchive não disponível no servidor.';
        exit;
    }

    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', (string) $filename);
    if (stripos($filename, '.xlsx') === false) {
        $filename .= '.xlsx';
    }

    $sheet_rows = '';
    $row_num = 1;
    $sheet_rows .= '<row r="' . $row_num . '">';
    foreach ($cabecalho as $ci => $col) {
        $sheet_rows .= xlsx_cell_xml($row_num, $ci, $col);
    }
    $sheet_rows .= '</row>';

    foreach ($linhas as $linha) {
        $row_num++;
        $sheet_rows .= '<row r="' . $row_num . '">';
        foreach ($linha as $ci => $cel) {
            $sheet_rows .= xlsx_cell_xml($row_num, $ci, $cel);
        }
        $sheet_rows .= '</row>';
    }

    $sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>' . $sheet_rows . '</sheetData></worksheet>';

    $workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Extrato" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '</Relationships>';

    $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '</Types>';

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        unlink($tmp);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Erro ao criar arquivo XLSX.';
        exit;
    }

    $zip->addFromString('[Content_Types].xml', $content_types);
    $zip->addFromString('_rels/.rels', $rels_xml);
    $zip->addFromString('xl/workbook.xml', $workbook_xml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet_xml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: max-age=0');
    readfile($tmp);
    unlink($tmp);
    exit;
}
