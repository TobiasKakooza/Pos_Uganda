<?php require_once('../../includes/auth.php'); ?>
<link rel="stylesheet" href="../../assets/css/terminal.css">

<!-- Terminal starts here -->
<div class="sales-terminal" style="margin-top: 60px;">

  <!-- Top Search Bar -->
  <div class="topbar">
    <input type="text" id="productSearch" placeholder="🔍 Search products by name, code or barcode..." autofocus>
    <button onclick="triggerSearch()">F3 Search</button>
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
            min="0"
            max="100"
            value="0"
            step="0.01"
            oninput="updateTotals()"
            style="width: 70px; text-align: right; margin-left: 8px;"
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
        <button class="wide-btn" id="btnSearch">F3 🔍 Search</button>
        <button class="wide-btn" id="btnQuantity">F4 ➕ Quantity</button>
        <button class="wide-btn" id="btnNewSale">F8 ➕ New Sale</button>
        <button class="wide-btn" id="btncash">F12 💵 Cash</button>
        <button class="wide-btn" id="btncredit">💳 Credit Card</button>
        <button class="wide-btn" id="btndebitcard">🏦 Debit Card</button>
        <button class="wide-btn" id="btncheck">💸 Check</button>
        <button class="wide-btn" id="btnvoucher">📄 Voucher</button>
        <button class="wide-btn" id="giftcard">🎫 Gift Card</button>
      </div>

      <div class="footer-buttons" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 20px;">
        <button class="wide-btn" id="btncashdrawer">🧾 Cash Drawer</button>
        <button class="wide-btn" id="btndiscount">F2 🏷️ Discount</button>
        <button class="wide-btn" id="btncomment">💬 Comment</button>
        <button class="wide-btn" id="btncustomer">👤 Customer</button>
        <button class="wide-btn" id="btntransfer">🔁 Transfer</button>
        <button class="wide-btn" id="btnrefund">🔄 Refund</button>
        <button class="wide-btn" id="btnlock">🔒 Lock</button>
        <button class="wide-btn" id="btnsave">F9 💾 Save</button>
        <button class="wide-btn btn-pay" id="btnPayment">F10 💵 Payment</button>
        <button class="wide-btn btn-void" id="btnVoid">🗑️ Void Order</button>
      </div>
    </div>
  </div>

  <!-- Admin Controls -->
  <button class="toggle-admin">☰</button>
  <div class="admin-date"><?php echo date('d/m/Y'); ?></div>

  <div id="adminPanel" class="admin-panel">
    <div class="admin-panel-header">
      <h3>POS - Admin</h3>
      <button onclick="toggleAdminPanel()" class="close-admin">✖</button>
    </div>
    <ul>
      <li onclick="navigateTo('management')">🛠️ Management</li>
      <li onclick="navigateTo('sales-history')">📊 View sales history</li>
      <li onclick="navigateTo('open-sales')">📂 View open sales</li>
      <li onclick="navigateTo('cash-in-out')">💰 Cash In / Out</li>
      <li onclick="navigateTo('credit-payments')">🏦 Credit payments</li>
      <li onclick="navigateTo('end-of-day')">📅 End of day</li>
      <hr>
      <li onclick="navigateTo('user-info')">👤 User info</li>
      <li onclick="navigateTo('sign-out')">🚪 Sign out</li>
      <li onclick="navigateTo('feedback')">📢 Feedback</li>
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
        <button onclick="choosePayment('Cash')">💵 Cash</button>
        <button onclick="choosePayment('Credit Card')">💳 Credit Card</button>
        <button onclick="choosePayment('Debit Card')">🏧 Debit Card</button>
        <button onclick="choosePayment('Voucher')">📄 Voucher</button>
        <button onclick="choosePayment('Gift Card')">🎁 Gift Card</button>
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
        <button class="btn-cancel" onclick="backToMethods()">← Back</button>
        <button class="btn-confirm" onclick="completePayment()">✅ Complete</button>
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
        <button onclick="document.getElementById('receiptFrame').contentWindow.print()" class="wide-btn">🖨️ Print</button>
        <button onclick="closeReceipt()" class="wide-btn btn-void">Close ✖</button>
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





<?php include_once __DIR__ . '/../../includes/footer.php'; ?>


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
      window.location.href = '/POS_UG/logout.php';
      break;

    case 'feedback':
      openFeedbackModal();
      break;

    default:
      alert('Unknown action: ' + module);
  }
}

</script> 
