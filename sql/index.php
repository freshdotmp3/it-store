<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['clear_all'])) {
        mysqli_query($conn, "TRUNCATE TABLE transactions");
        header("Location: index.php");
        exit();
    }

    $buyer_name = $_POST['buyer_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $payment_method = $_POST['payment_method'];

    if ($payment_method == 'เงินสด') {
        $paid_amount   = $_POST['paid_amount'];
        $change_amount = $_POST['change_amount'];
    } else {
        $paid_amount   = $price;
        $change_amount = 0;
    }

    $sql = "INSERT INTO transactions (buyer_name, category, quantity, price, paid_amount, change_amount, payment_method) 
            VALUES ('$buyer_name', '$category', '$quantity', '$price', '$paid_amount', '$change_amount', '$payment_method')";
    
    mysqli_query($conn, $sql);

    header("Location: index.php");
    exit();
}

$query_cash_in  = mysqli_query($conn, "SELECT SUM(paid_amount) AS total FROM transactions WHERE payment_method = 'เงินสด'");
$row_cash_in    = mysqli_fetch_assoc($query_cash_in);
$statCashIn     = $row_cash_in['total'] ?? 0;

$query_cash_out = mysqli_query($conn, "SELECT SUM(change_amount) AS total FROM transactions WHERE payment_method = 'เงินสด'");
$row_cash_out   = mysqli_fetch_assoc($query_cash_out);
$statCashOut    = $row_cash_out['total'] ?? 0;

$query_qr       = mysqli_query($conn, "SELECT SUM(price) AS total FROM transactions WHERE payment_method = 'โอนจ่าย'");
$row_qr         = mysqli_fetch_assoc($query_qr);
$statQrTotal    = $row_qr['total'] ?? 0;

$query_grand    = mysqli_query($conn, "SELECT SUM(price) AS total FROM transactions");
$row_grand      = mysqli_fetch_assoc($query_grand);
$statGrandTotal = $row_grand['total'] ?? 0;

$result = mysqli_query($conn, "SELECT * FROM transactions ORDER BY paid_at DESC, id DESC");
$totalCount = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบบันทึกรายรับ - แผนกเทคโนโลยีสารสนเทศ</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
      <span class="navbar-brand mb-0 h1">
        <i class="bi bi-shop me-2"></i>ร้านค้าแผนกเทคโนโลยีสารสนเทศ
      </span>
      <span class="text-white-50 small">จัดทำโดย ศรายุทธ โพธิ์ทรง ปี 2/1</span>
    </div>
  </nav>

  <div class="container mb-5">

    <div class="card shadow-sm mb-4">
      
      <div class="card-header bg-white pb-0">
        <ul class="nav nav-tabs card-header-tabs" id="paymentTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-success" id="cash-tab" data-bs-toggle="tab" data-bs-target="#cash-pane" type="button" role="tab">
              <i class="bi bi-cash-coin me-1"></i> จ่ายเงินสด
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-primary" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr-pane" type="button" role="tab">
              <i class="bi bi-qr-code-scan me-1"></i> โอนจ่าย / สแกนจ่าย
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body p-4">
        <div class="tab-content" id="paymentTabContent">
          
          <div class="tab-pane fade show active" id="cash-pane" role="tabpanel">
            <h5 class="text-success mb-3"><i class="bi bi-wallet2 me-2"></i>บันทึกรายการ: ชำระเงินสด</h5>
            <form action="index.php" method="POST">
              <input type="hidden" name="payment_method" value="เงินสด">
              
              <div class="row g-3">
                <div class="col-md-5">
                  <label class="form-label">ชื่อและชั้นปี</label>
                  <input type="text" name="buyer_name" class="form-control" placeholder="เช่น แกรม ปวช. 2/1" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">สินค้าที่ซื้อ</label>
                  <select name="category" class="form-select" required>
                    <option value="" selected disabled>-- เลือกสินค้า --</option>
                    <option value="อาหารว่าง">อาหารว่าง</option>
                    <option value="ขนม">ขนม</option>
                    <option value="เครื่องดื่ม">เครื่องดื่ม</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">จำนวน (ชิ้น)</label>
                  <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label">ราคารวมของสินค้า (บาท)</label>
                  <input type="number" id="cashPrice" name="price" class="form-control" min="1" step="0.5" placeholder="0" required oninput="kitmoneyChikChing()">
                </div>
                <div class="col-md-4">
                  <label class="form-label">ยอดจำนวนเงินที่จ่าย (บาท)</label>
                  <input type="number" id="cashPaid" name="paid_amount" class="form-control" min="1" step="0.5" placeholder="0" required oninput="kitmoneyChikChing()">
                </div>
                <div class="col-md-4">
                  <label class="form-label">เงินทอนที่หยิบออกจากกล่อง (บาท)</label>
                  <input type="number" id="cashChange" name="change_amount" class="form-control bg-warning-subtle text-danger border-warning fw-bold" value="0" readonly>
                </div>

                <div class="col-12 text-end mt-4">
                  <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-circle me-1"></i> บันทึกรายการเงินสด
                  </button>
                </div>
              </div>
            </form>
          </div>

          <div class="tab-pane fade" id="qr-pane" role="tabpanel">
            <h5 class="text-primary mb-3"><i class="bi bi-phone me-2"></i>บันทึกรายการ: โอนจ่าย / สแกน QR Code</h5>
            <form action="index.php" method="POST">
              <input type="hidden" name="payment_method" value="โอนจ่าย">
              
              <div class="row g-4 align-items-center">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">ชื่อและชั้นปี</label>
                    <input type="text" name="buyer_name" class="form-control" placeholder="เช่น แกรม ปวช. 2/1" required>
                  </div>
                  <div class="row g-2 mb-3">
                    <div class="col-md-8">
                      <label class="form-label">สินค้าที่ซื้อ</label>
                      <select name="category" class="form-select" required>
                        <option value="" selected disabled>-- เลือกสินค้า --</option>
                        <option value="อาหารว่าง">อาหารว่าง</option>
                        <option value="ขนม">ขนม</option>
                        <option value="เครื่องดื่ม">เครื่องดื่ม</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">จำนวน (ชิ้น)</label>
                      <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                  </div>
                  <div class="mb-4">
                    <label class="form-label">ยอดรวมสินค้า (บาท)</label>
                    <input type="number" name="price" class="form-control form-control-lg fw-bold text-primary" min="1" step="0.5" placeholder="0" required>
                  </div>
                  <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-check-circle me-1"></i> โอนแล้ว กดบันทึกรายการ
                  </button>
                </div>

                <div class="col-md-6 text-center">
                  <div class="p-3 bg-light rounded border">
                    <div class="fw-bold mb-2 text-dark">
                      <i class="bi bi-qr-code-scan me-1"></i> QR Code รับชำระเงิน
                    </div>
                    <img src="elements/images/qrcode/pay.png" 
                         alt="Thai QR Payment" 
                         class="img-fluid border shadow-sm bg-white rounded" 
                         style="max-width: 300px; width: 100%; height: auto;">
                    <div class="small text-muted mt-2">สแกนผ่านแอปธนาคารได้ทุกธนาคาร</div>
                  </div>
                </div>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0">
          <i class="bi bi-calculator me-2"></i>สรุปยอดเงินรวมของร้านค้า
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          
          <div class="col-md-3">
            <div class="card bg-light border-0 p-3 text-center h-100 justify-content-center">
              <div class="text-muted small mb-1">
                <i class="bi bi-box-arrow-in-down text-success me-1"></i> เงินสดที่ใส่เข้ากล่อง
              </div>
              <h4 class="fw-bold text-success mb-0"><?php echo number_format($statCashIn); ?> บาท</h4>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card bg-light border-0 p-3 text-center h-100 justify-content-center">
              <div class="text-muted small mb-1">
                <i class="bi bi-box-arrow-up text-danger me-1"></i> เงินทอนที่หยิบออกไป
              </div>
              <h4 class="fw-bold text-danger mb-0"><?php echo number_format($statCashOut); ?> บาท</h4>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card bg-light border-0 p-3 text-center h-100 justify-content-center">
              <div class="text-muted small mb-1">
                <i class="bi bi-phone text-primary me-1"></i> ยอดโอนเข้าบัญชี
              </div>
              <h4 class="fw-bold text-primary mb-0"><?php echo number_format($statQrTotal); ?> บาท</h4>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card bg-dark text-white p-3 text-center h-100 justify-content-center">
              <div class="text-white-50 small mb-1">
                <i class="bi bi-cash-stack text-warning me-1"></i> ยอดรวมรายรับทั้งหมด
              </div>
              <h4 class="fw-bold text-warning mb-0"><?php echo number_format($statGrandTotal); ?> บาท</h4>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="bi bi-table me-2"></i>ตารางบันทึกรายรับของร้านค้า
        </h5>
        
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-secondary fs-6"><?php echo $totalCount; ?> รายการ</span>
          
          <form action="index.php" method="POST" onsubmit="return confirm('คุณต้องการล้างข้อมูลทั้งหมดใช่หรือไม่?');" class="d-inline">
            <input type="hidden" name="clear_all" value="1">
            <button type="submit" class="btn btn-outline-danger btn-sm">
              <i class="bi bi-trash3 me-1"></i> ล้างข้อมูลทั้งหมด
            </button>
          </form>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-bordered table-hover mb-0 align-middle">
            <thead class="table-dark">
              <tr>
                <th style="width: 170px;">เวลาที่ซื้อ</th>
                <th>ชื่อ</th>
                <th>สินค้าที่ซื้อ</th>
                <th class="text-center" style="width: 90px;">จำนวน</th>
                <th class="text-end">ราคาสินค้า (บาท)</th>
                <th class="text-end">เงินที่จ่าย (บาท)</th>
                <th class="text-end">เงินทอน (บาท)</th>
                <th class="text-center">วิธีชำระ</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($totalCount > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><small class="text-muted"><?php echo date("d/m/Y H:i", strtotime($row['paid_at'])); ?></small></td>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category']); ?></span></td>
                    <td class="text-center fw-bold"><?php echo $row['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($row['price']); ?></td>
                    <td class="text-end"><?php echo number_format($row['paid_amount']); ?></td>
                    <td class="text-end <?php echo ($row['change_amount'] > 0) ? 'text-danger fw-bold' : ''; ?>">
                      <?php echo number_format($row['change_amount']); ?>
                    </td>
                    <td class="text-center">
                      <?php if ($row['payment_method'] == 'เงินสด'): ?>
                        <span class="badge bg-success"><i class="bi bi-cash me-1"></i>เงินสด</span>
                      <?php else: ?>
                        <span class="badge bg-primary"><i class="bi bi-qr-code me-1"></i>โอนจ่าย</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-4 d-block mb-1"></i> ไม่มีข้อมูลรายการซื้อขาย
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function kitmoneyChikChing() {
      let price = Number(document.getElementById("cashPrice").value);
      let paid = Number(document.getElementById("cashPaid").value);
      let change = paid - price;

      if (change > 0) {
        document.getElementById("cashChange").value = change;
      } else {
        document.getElementById("cashChange").value = 0;
      }
    }
  </script>

</body>
</html>