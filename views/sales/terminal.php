<?php require_once('../../includes/auth.php'); ?>
<link rel="stylesheet" href="../../assets/css/terminal.css">

<!-- Terminal starts here -->
<div class="sales-terminal" data-year="<?php echo date('Y'); ?>" style="margin-top: 60px;">


<!-- Top Search Bar -->
<div class="topbar">
  <input
    type="text"
    id="productSearch"
    placeholder="Search products by name, code or barcode..."
    autofocus
  >
  <button onclick="triggerSearch()">
    <i data-lucide="search"></i>
    F3 Search
  </button>
</div>



  <!-- Main Layout Container (NOW FLEXBOX) -->
  <div class="main-layout" style="display: flex; gap: 20px; height: calc(100vh - 140px); padding: 10px;">

    <!-- LEFT: Product Table + Summary -->
    <div class="left-panel" style="flex: 1; display: flex; flex-direction: column;">
      <!-- Scrollable Product Table -->
      <div class="product-area" style="flex-grow: 1; overflow-y: auto;">
        <table class="sales-table">
          <thead>
            <tr>
              <th>Product name</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="receiptItems">
            <tr class="no-items">
              <td colspan="5" class="center-text">No items<br><small>Add products using barcode, code or search (F3)</small></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Summary at Bottom -->
      <div class="summary-box" style="padding: 15px; background: #222; color: white;">
        <div style="margin-bottom: 8px; display: flex; justify-content: space-between;">
          <span>Subtotal:</span>
          <span id="subtotal">0.00</span>
        </div>

        <div style="margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
          <span>Tax (%):</span>
        <input 
  id="taxRate"
  type="number"
  value="18"
  readonly
  style="width: 70px; text-align: right; margin-left: 8px; background:#333; color:#fff;"
/>

        </div>

        <div style="margin-bottom: 8px; display: flex; justify-content: space-between;">
          <span>Tax Amount:</span>
          <span id="taxAmount">0.00</span>
        </div>

        <div style="font-weight: bold; color: lime; font-size: 18px; display: flex; justify-content: space-between;">
          <span>Total:</span>
          <span id="total">0.00</span>
        </div>
      </div>
    </div>

<!-- RIGHT: Action Buttons -->
<div class="right-panel" style="width: 330px; display: flex; flex-direction: column; justify-content: space-between;">

  <div class="side-panel" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
    <button class="wide-btn" id="btnSearch">F3 <i data-lucide="search"></i> Search</button>
    <button class="wide-btn" id="btnQuantity">F4 <i data-lucide="plus"></i> Quantity</button>
    <button class="wide-btn" id="btnNewSale">F8 <i data-lucide="file-plus"></i> New Sale</button>
    <button class="wide-btn" id="btncash">F12 <i data-lucide="banknote"></i> Cash</button>
    <button class="wide-btn" id="btncredit"><i data-lucide="credit-card"></i> Credit Card</button>
    <button class="wide-btn" id="btndebitcard"><i data-lucide="landmark"></i> Debit Card</button>
    <button class="wide-btn" id="btncheck"><i data-lucide="receipt"></i> Check</button>
    <button class="wide-btn" id="btnvoucher"><i data-lucide="file-text"></i> Voucher</button>
    <button class="wide-btn" id="giftcard"><i data-lucide="ticket"></i> Gift Card</button>
  </div>

  <div class="footer-buttons" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 20px;">
    <button class="wide-btn" id="btncashdrawer"><i data-lucide="archive"></i> Cash Drawer</button>
    <button class="wide-btn" id="btndiscount">F2 <i data-lucide="tag"></i> Discount</button>
    <button class="wide-btn" id="btncomment"><i data-lucide="message-square"></i> Comment</button>
    <button class="wide-btn" id="btncustomer"><i data-lucide="user"></i> Customer</button>
    <button class="wide-btn" id="btntransfer"><i data-lucide="repeat"></i> Transfer</button>
    <button class="wide-btn" id="btnrefund"><i data-lucide="rotate-ccw"></i> Refund</button>
    <button class="wide-btn" id="btnlock"><i data-lucide="lock"></i> Lock</button>
    <button class="wide-btn" id="btnsave">F9 <i data-lucide="save"></i> Save</button>
    <button class="wide-btn btn-pay" id="btnPayment">F10 <i data-lucide="banknote"></i> Payment</button>
    <button class="wide-btn btn-void" id="btnVoid"><i data-lucide="trash-2"></i> Void Order</button>
  </div>

</div>


  <!-- Admin Controls -->
  <button class="toggle-admin">☰</button>
  <div class="admin-date"><?php echo date('d/m/Y'); ?></div>

  <div id="adminPanel" class="admin-panel">
    <div class="admin-panel-header">
      <h3>POS - Admin</h3>
      <button onclick="toggleAdminPanel()" class="close-admin">
  <i data-lucide="x"></i>
</button>

    </div>
    <ul>
  <li onclick="navigateTo('management')">
    <i data-lucide="tool"></i> Management
  </li>

  <li onclick="navigateTo('sales-history')">
    <i data-lucide="bar-chart-3"></i> View sales history
  </li>

  <li onclick="navigateTo('open-sales')">
    <i data-lucide="folder-open"></i> View open sales
  </li>

  <li onclick="navigateTo('cash-in-out')">
    <i data-lucide="banknote"></i> Cash In / Out
  </li>

  <li onclick="navigateTo('credit-payments')">
    <i data-lucide="credit-card"></i> Credit payments
  </li>

  <li onclick="navigateTo('end-of-day')">
    <i data-lucide="calendar-check"></i> End of day
  </li>

  <hr>

  <li onclick="navigateTo('user-info')">
    <i data-lucide="user"></i> User info
  </li>

  <li onclick="navigateTo('sign-out')">
    <i data-lucide="log-out"></i> Sign out
  </li>

  <li onclick="navigateTo('feedback')">
    <i data-lucide="megaphone"></i> Feedback
  </li>
</ul>

    <div class="admin-date"><?php echo date('d/m/Y'); ?></div>
  </div>
</div> <!-- sales-terminal ends -->

<!-- Payment Modal -->
<div id="paymentModal" class="payment-modal hidden">
  <div class="payment-box">
    <!-- step 1: method selection -->
    <div id="step1" class="payment-step">
      <h3>Select Payment Method</h3>
<div class="method-buttons">
  <button onclick="choosePayment('Cash')">
    <i data-lucide="banknote"></i> Cash
  </button>

  <button onclick="choosePayment('Credit Card')">
    <i data-lucide="credit-card"></i> Credit Card
  </button>

  <button onclick="choosePayment('Debit Card')">
    <i data-lucide="landmark"></i> Debit Card
  </button>

  <button onclick="choosePayment('Voucher')">
    <i data-lucide="file-text"></i> Voucher
  </button>

  <button onclick="choosePayment('Gift Card')">
    <i data-lucide="gift"></i> Gift Card
  </button>
</div>

      <button class="btn-cancel" onclick="closePaymentModal()">Cancel</button>
    </div>

    <!-- step 2: enter amount -->
    <div id="step2" class="payment-step hidden">
      <h3 id="selectedMethodLabel">Cash Payment</h3>
      <div class="payment-inputs">
        <div>
          <span>Total:</span>
          <span id="paymentTotal">0.00</span>
        </div>
        <div>
          <label for="amountPaid">Paid:</label>
          <input type="number" id="amountPaid" oninput="updateBalance()" />
        </div>
        <div>
          <span>Balance:</span>
          <span id="balanceDue">0.00</span>
        </div>
      </div>
      <div class="payment-actions">
  <button class="btn-cancel" onclick="backToMethods()">
    <i data-lucide="arrow-left"></i> Back
  </button>

  <button class="btn-confirm" onclick="completePayment()">
    <i data-lucide="check-circle"></i> Complete
  </button>
</div>

    </div>
  </div>
</div>

<!-- 🔽 RECEIPT VIEWER MODAL (same tab) -->
<div id="receiptModal" class="hidden" style="
  position: fixed; inset: 0; background: rgba(0,0,0,.6);
  align-items: center; justify-content: center; z-index: 10000;">
  <div style="background:#1f1f1f; width: 90vw; height: 90vh; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.5); display:flex; flex-direction:column;">
    <div style="padding:10px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #333;">
      <strong>Receipt Preview</strong>
     <div>
  <button
    onclick="document.getElementById('receiptFrame').contentWindow.print()"
    class="wide-btn"
  >
    <i data-lucide="printer"></i> Print
  </button>

  <button
    onclick="closeReceipt()"
    class="wide-btn btn-void"
  >
    <i data-lucide="x-circle"></i> Close
  </button>
</div>

    </div>
    <iframe id="receiptFrame" style="flex:1; border:none; background:#fff;"></iframe>
  </div>
</div>

<script>
function openReceipt(url) {
  const modal = document.getElementById('receiptModal');
  const frame = document.getElementById('receiptFrame');
  frame.src = url;
  modal.style.display = 'flex';         // show
  modal.classList.remove('hidden');
}

function closeReceipt() {
  const modal = document.getElementById('receiptModal');
  const frame = document.getElementById('receiptFrame');
  frame.src = '';
  modal.style.display = 'none';         // hide
  modal.classList.add('hidden');
}
</script>


<!-- was ../assets/... -->
<script src="../../assets/js/terminal.js?v=5"></script>






 <script>
  function toggleAdminPanel() {
    const panel = document.getElementById('adminPanel');
    panel.classList.toggle('visible');
  }

function navigateTo(module) {
  switch (module) {
    case 'management':
      // just go back to the main app (your left sidebar)
      window.location.href = '/POS_UG/views/dashboard.php';
      break;

    case 'sales-history':
      window.location.href = '/POS_UG/views/sales/history.php';
      openSalesHistory();
      break;

    case 'open-sales':
      // open drafts/held sales (Step 3 wires this)
      openOpenSales();
      break;

    case 'cash-in-out':
      openCashModal();
      break;

    case 'credit-payments':
      openCreditModal();
      break;

    case 'end-of-day':
      // quick win: send them to a report page, you can build later
      openEndOfDayModal();
      break;

    case 'user-info':
      openUserInfo();
      break;

    case 'sign-out':
      // your logout path may differ
      window.location.href = '/POS_UG/views/dashboard.php';
      break;

    case 'feedback':
      openFeedbackModal();
      break;

    default:
      alert('Unknown action: ' + module);
  }
}

</script> 
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
</script>
