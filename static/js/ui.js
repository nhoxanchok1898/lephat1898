(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var navToggle = document.getElementById('nav-toggle');
    var mobileNav = document.getElementById('mobile-nav');
    if(navToggle && mobileNav){
      navToggle.addEventListener('click', function(){
        var open = mobileNav.hasAttribute('hidden') ? false : true;
        if(open){
          mobileNav.setAttribute('hidden','');
        } else {
          mobileNav.removeAttribute('hidden');
        }
      });
    }

    // mini cart toggle (if you later implement a flyout)
    var miniCartToggle = document.getElementById('mini-cart-toggle');
    if(miniCartToggle){
      miniCartToggle.addEventListener('click', function(e){
        // allow normal navigation but could be hijacked to show a modal
      });
    }

    // Note: cart AJAX quantity update logic moved to `cart-qty.js` for optimistic UI and animations.
  });
})();
