document.addEventListener('DOMContentLoaded', function () {
  var targets = ['Proceed to Checkout', 'Proceed to checkout'];
  var replacement = 'Thanh toán';

  function hasOnlyText(el) {
    for (var i = 0; i < el.childNodes.length; i++) {
      if (el.childNodes[i].nodeType === 1) {
        return false;
      }
    }
    return true;
  }

  function replaceTexts() {
    var nodes = document.querySelectorAll('a, button, span, div, p');
    nodes.forEach(function (el) {
      if (!el || !hasOnlyText(el)) {
        return;
      }
      var text = el.textContent.trim();
      if (targets.indexOf(text) !== -1) {
        el.textContent = replacement;
      }
    });
  }

  replaceTexts();

  // Theo dõi DOM để bắt nội dung do Woo Blocks render sau khi load.
  var observer = new MutationObserver(function () {
    replaceTexts();
  });
  observer.observe(document.body, { childList: true, subtree: true, characterData: true });
  setTimeout(function () {
    observer.disconnect();
  }, 5000);
});
