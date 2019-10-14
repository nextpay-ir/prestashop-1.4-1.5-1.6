
<!-- Nextpay.ir Payment Module -->
<p class="payment_module">
  <a href="javascript:$('#nextpay').submit();" title="Online payment with Nextpay.ir">
    <img src="http://nextpay.ir/download/nextpay_60x60.png" alt="Online payment with Nextpay.ir" style="margin-left:20px;" />
    پرداخت از طریق درگاه پرداخت و کیف پول الکترونیک Nextpay.ir
  </a>
</p>
<form id="nextpay" action="modules/nextpay/process.php?do=payment" method="post" class="hidden">
  <input type="hidden" name="orderId" value="{$orderId}" />
</form>
<!-- End of Nextpay.ir Payment Module-->
