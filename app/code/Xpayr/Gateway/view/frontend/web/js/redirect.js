(function () {
  'use strict';

  function runRedirect() {
    var node = document.getElementById('xpayr-redirect-data');
    if (!node) {
      return;
    }

    var paymentUrl = node.getAttribute('data-payment-url') || '';
    var fallbackUrl = node.getAttribute('data-fallback-url') || '/checkout/cart';

    if (paymentUrl) {
      window.location.replace(paymentUrl);
      return;
    }

    window.location.replace(fallbackUrl);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runRedirect);
  } else {
    runRedirect();
  }
})();
