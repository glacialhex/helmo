<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin']);
$pdo = DB::conn();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT r.*, s.first_name, s.last_name FROM receipts r JOIN students s ON s.id = r.student_id WHERE r.id=?');
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { http_response_code(404); echo 'Not found'; exit; }

// Simple QR placeholder data (for demo). For production, integrate a QR library/API.
$qrText = 'RECEIPT#'.$r['id'].'|'.number_format((float)$r['amount'],2).'|'.$r['currency'];
// A minimalistic inline SVG QR-like pattern placeholder
$qrSvg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="120" height="120" fill="#fff"/><rect x="10" y="10" width="20" height="20" fill="#000"/><rect x="90" y="10" width="20" height="20" fill="#000"/><rect x="10" y="90" width="20" height="20" fill="#000"/><text x="60" y="110" font-size="8" text-anchor="middle">QR</text></svg>');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <title>إيصال / Receipt #<?= (int)$r['id'] ?></title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .receipt { border:1px solid #ccc; padding:16px; border-radius:8px; max-width:700px; margin:auto; }
    .row { display:flex; justify-content:space-between; }
    .muted { color:#6b7280; }
    .qr { width:120px; height:120px; }
    .actions { text-align:center; margin-top:12px; }
    hr { margin:12px 0; }
  </style>
  <script>function doPrint(){ window.print(); }</script>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  </head>
<body>
  <div class="receipt">
    <div class="row">
      <div>
        <h2>إيصال دفع</h2>
        <div class="muted">Arabic + English</div>
      </div>
      <img class="qr" src="data:image/svg+xml;base64,<?= $qrSvg ?>" alt="QR" />
    </div>
    <hr />
    <p>
      <strong>رقم الإيصال / Receipt #:</strong> <?= (int)$r['id'] ?><br/>
      <strong>الطالب / Student:</strong> <?= e($r['first_name'].' '.$r['last_name']) ?><br/>
      <strong>القيمة / Amount:</strong> <?= number_format((float)$r['amount'],2) ?> <?= e($r['currency']) ?><br/>
      <strong>التاريخ / Date:</strong> <?= e($r['issued_at']) ?><br/>
      <strong>تفاصيل / Details:</strong> <?= e($r['details']) ?>
    </p>
    <div class="actions">
      <button onclick="doPrint()">Print / طباعة</button>
    </div>
  </div>
</body>
</html>
