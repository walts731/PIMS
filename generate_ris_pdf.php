<?php
require_once '../connect.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ✅ Validate RIS ID
$ris_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ris_id <= 0) {
    die("Invalid RIS ID.");
}

// ✅ Fetch RIS form details
$sql = "SELECT f.id AS ris_id, f.header_image, f.division, f.responsibility_center,
               f.ris_no, f.date, f.sai_no, f.responsibility_code,
               f.reason_for_transfer,
               f.requested_by_name, f.requested_by_designation, f.requested_by_date,
               f.approved_by_name, f.approved_by_designation, f.approved_by_date,
               f.issued_by_name, f.issued_by_designation, f.issued_by_date,
               f.received_by_name, f.received_by_designation, f.received_by_date,
               o.office_name
        FROM ris_form f
        LEFT JOIN offices o ON f.office_id = o.id
        WHERE f.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ris_id);
$stmt->execute();
$result = $stmt->get_result();
$ris = $result->fetch_assoc();
$stmt->close();

if (!$ris) {
    die("RIS record not found.");
}

// ✅ Fetch RIS items
$sql_items = "SELECT stock_no, unit, description, quantity, price, total
              FROM ris_items
              WHERE ris_form_id = ?
              ORDER BY stock_no ASC";
$stmt = $conn->prepare($sql_items);
$stmt->bind_param("i", $ris_id);
$stmt->execute();
$result_items = $stmt->get_result();
$ris['items'] = $result_items->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Compute grand total
$grandTotal = 0;
foreach ($ris['items'] as $item) {
    $grandTotal += $item['total'];
}

// ✅ Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// ✅ Start HTML
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
  h2 { text-align: center; margin: 0; font-size: 16px; }
  .meta { margin: 10px 0; font-size: 12px; }

  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 4px; text-align: center; font-size: 11px; }
  th { font-weight: bold; background: #f2f2f2; border: 1px solid #000; }

  /* ✅ Items table: only vertical (column) lines + outer border */
  .items-table { 
    border: 1px solid #000; 
    border-collapse: collapse;
  }
  .items-table th, 
  .items-table td {
    border-left: 1px solid #000;
    border-right: 1px solid #000;
  }
  .items-table th {
    border-top: 1px solid #000;
    border-bottom: 1px solid #000; /* Keep top row separation */
  }

  /* ✅ Header (meta info) table with full grid border */
  .meta table {
    border: 1px solid #000;
    border-collapse: collapse;
    width: 100%;
  }
  .meta td {
    border: 1px solid #000;
    padding: 4px;
    font-size: 11px;
    text-align: left;
  }

  /* ✅ Footer signatories table with full border */
  .footer-table {
    border: 1px solid #000;
    border-collapse: collapse;
    margin-top: 10px;
    width: 100%;
  }
  .footer-table th, 
  .footer-table td {
    border: 1px solid #000;
    padding: 4px;
    font-size: 11px;
    text-align: center;
  }

  .grand-total { font-weight: bold; color: red; border-top: 1px solid #000; }

  .designation { font-size: 10px; }
  .header-img { text-align: center; margin-bottom: 10px; }
  .header-img img { max-width: 100%; height: auto; }
</style>
</head>
<body>
';

// ✅ Show header image if available
if (!empty($ris['header_image'])) {
    $imagePath = realpath(__DIR__ . '/../img/' . $ris['header_image']);
    if ($imagePath && file_exists($imagePath)) {
        $imageData = base64_encode(file_get_contents($imagePath));
        $src = 'data:image/png;base64,' . $imageData;
        $html .= '<div class="header-img"><img src="' . $src . '" alt="Header"></div>';
    }
}

// ✅ Meta information
$html .= '
<div class="meta">
  <!-- SUBHEADER TABLE -->
  <table>
    <tr>
      <td><strong>DIVISION:</strong> '.htmlspecialchars($ris['division']).'</td>
      <td><strong>Responsibility Center:</strong> '.htmlspecialchars($ris['responsibility_center']).'</td>
      <td><strong>RIS NO:</strong> '.htmlspecialchars($ris['ris_no']).'</td>
      <td><strong>DATE:</strong> '.(!empty($ris['date']) ? date("F d, Y", strtotime($ris['date'])) : '').'</td>
    </tr>
    <tr>
      <td><strong>OFFICE:</strong> '.htmlspecialchars($ris['office_name']).'</td>
      <td><strong>Code:</strong> '.htmlspecialchars($ris['responsibility_code']).'</td>
      <td><strong>SAI NO:</strong> '.htmlspecialchars($ris['sai_no']).'</td>
      <td></td>
    </tr>
  </table>
</div>
';

// ✅ Items table + Purpose inside
$html .= '
<table class="items-table" style="width:100%; table-layout:fixed; height:500px;">
  <thead>
    <tr>
      <th colspan="4">REQUISITION</th>
      <th colspan="3">ISSUANCE</th>
    </tr>
    <tr>
      <th>Stock No</th>
      <th>Unit</th>
      <th>Description</th>
      <th>Quantity</th>
      <th>Signature</th>
      <th>Price</th>
      <th>Total Amount</th>
    </tr>
  </thead>
  <tbody>';

// ✅ Insert RIS items
if (!empty($ris['items'])) {
    // Cache for resolved unit IDs -> unit_name
    $unitCache = [];

    foreach ($ris['items'] as $item) {
        // Resolve unit: if numeric ID, fetch unit_name from unit table; else use as-is
        $unitDisplay = $item['unit'];
        if ($unitDisplay !== null && $unitDisplay !== '' && ctype_digit((string)$unitDisplay)) {
            $uid = (int)$unitDisplay;
            if (isset($unitCache[$uid])) {
                $unitDisplay = $unitCache[$uid];
            } else {
                $ustmt = $conn->prepare("SELECT unit_name FROM unit WHERE id = ? LIMIT 1");
                if ($ustmt) {
                    $ustmt->bind_param("i", $uid);
                    $ustmt->execute();
                    $ures = $ustmt->get_result();
                    if ($urow = $ures->fetch_assoc()) {
                        $unitDisplay = $urow['unit_name'];
                        $unitCache[$uid] = $unitDisplay;
                    }
                    $ustmt->close();
                }
            }
        }

        $html .= '
        <tr>
          <td>' . htmlspecialchars($item['stock_no']) . '</td>
          <td>' . htmlspecialchars($unitDisplay) . '</td>
          <td style="text-align:left;">' . htmlspecialchars($item['description']) . '</td>
          <td>' . htmlspecialchars($item['quantity']) . '</td>
          <td></td>
          <td>' . number_format($item['price'], 2) . '</td>
          <td>' . number_format($item['total'], 2) . '</td>
        </tr>';
    }
    
    // Add "nothing follows" row if there are items
    if (!empty($ris['items'])) {
        $html .= '
        <tr>
          <td colspan="7" style="text-align: center; font-style: italic; padding: 6px 0; border-top: 1px solid #000;">— NOTHING FOLLOWS —</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="7">No items found.</td></tr>';
}

// ✅ Fill blank rows
$minRows = 15;
$currentRows = count($ris['items']);
$emptyRows = max(0, $minRows - $currentRows);

for ($i = 0; $i < $emptyRows; $i++) {
    $html .= '
    <tr>
      <td>&nbsp;</td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
    </tr>';
}

// ✅ Grand total + Purpose row
$html .= '
  <tr>
    <td colspan="6" style="text-align:right; border-top:1px solid #000;"><strong>Grand Total:</strong></td>
    <td class="grand-total">' . number_format($grandTotal, 2) . '</td>
  </tr>
  <tr>
    <td colspan="7" style="text-align:left; border-top:1px solid #000;"><strong>Purpose:</strong> ' . htmlspecialchars($ris['reason_for_transfer']) . '</td>
  </tr>
</tbody>
</table>
';

// ✅ Footer signatories
$html .= '
<table class="footer-table">
  <thead>
    <tr>
      <th></th>
      <th>Requested By</th>
      <th>Approved By</th>
      <th>Issued By</th>
      <th>Received By</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Signature</td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td>Printed Name</td>
      <td>' . htmlspecialchars($ris['requested_by_name']) . '</td>
      <td>' . htmlspecialchars($ris['approved_by_name']) . '</td>
      <td>' . htmlspecialchars($ris['issued_by_name']) . '</td>
      <td>' . htmlspecialchars($ris['received_by_name']) . '</td>
    </tr>
    <tr>
      <td>Designation</td>
      <td>' . htmlspecialchars($ris['requested_by_designation']) . '</td>
      <td>' . htmlspecialchars($ris['approved_by_designation']) . '</td>
      <td>' . htmlspecialchars($ris['issued_by_designation']) . '</td>
      <td>' . htmlspecialchars($ris['received_by_designation']) . '</td>
    </tr>
    <tr>
  <td>Date</td>
  <td>' . (!empty($ris['requested_by_date']) && $ris['requested_by_date'] != '0000-00-00' ? htmlspecialchars($ris['requested_by_date']) : '') . '</td>
  <td>' . (!empty($ris['approved_by_date']) && $ris['approved_by_date'] != '0000-00-00' ? htmlspecialchars($ris['approved_by_date']) : '') . '</td>
  <td>' . (!empty($ris['issued_by_date']) && $ris['issued_by_date'] != '0000-00-00' ? htmlspecialchars($ris['issued_by_date']) : '') . '</td>
  <td>' . (!empty($ris['received_by_date']) && $ris['received_by_date'] != '0000-00-00' ? htmlspecialchars($ris['received_by_date']) : '') . '</td>
</tr>

  </tbody>
</table>
';

// ✅ Generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("RIS_" . $ris['ris_no'] . ".pdf", ["Attachment" => false]);
