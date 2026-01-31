(function(){
  document.addEventListener('DOMContentLoaded', function(){
    function getCookie(name){
      var v = document.cookie.match('(^|;)\\s*'+name+'\\s*=\\s*([^;]+)');
      return v ? v.pop() : '';
    }
    var csrftoken = getCookie('csrftoken');

    // create flyout container if missing
    var existing = document.getElementById('mini-cart-root');
    if(!existing){
      var root = document.createElement('div');
      root.id = 'mini-cart-root';
      document.body.appendChild(root);
    } else {
      var root = existing;
    }

    var autoCloseTimer = null;
    function startAutoClose(delay){
      clearTimeout(autoCloseTimer);
      autoCloseTimer = setTimeout(function(){ hideMiniCart(); }, delay);
    }

    function showMiniCart(){
      root.classList.add('open');
      root.innerHTML = '<div class="mini-cart-overlay" id="mini-cart-overlay"></div><aside class="mini-cart-panel" id="mini-cart-panel">Loading…</aside>';
      document.body.classList.add('mini-cart-active');
      // attach overlay close
      var overlay = document.getElementById('mini-cart-overlay');
      overlay.addEventListener('click', hideMiniCart);
      // Esc to close
      document.addEventListener('keydown', onEsc);
      startAutoClose(4200);
      fetchAndRender();
    }

    function hideMiniCart(){
      root.classList.remove('open');
      document.body.classList.remove('mini-cart-active');
      document.removeEventListener('keydown', onEsc);
      clearTimeout(autoCloseTimer);
    }

    function onEsc(e){ if(e.key === 'Escape') hideMiniCart(); }

    function formatMoney(v){ return v; }

    function fetchAndRender(){
      fetch('/cart/ajax/summary/', {headers: {'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){ return r.json(); })
      .then(function(data){
        var panel = document.getElementById('mini-cart-panel');
        if(!panel) return;
        var html = ['<div class="mini-cart-inner">', '<h4>Đã thêm vào giỏ</h4>', '<ul class="mini-items">'];
        if(data.items && data.items.length){
          data.items.slice(0,6).forEach(function(it){
            // include qty controls and a machine-readable price for client-side updates
            html.push('<li class="mini-item" data-pk="'+it.pk+'" data-price="'+(it.price||0)+'">');
            if(it.image_url){ html.push('<img class="mini-thumb" src="'+it.image_url+'">'); }
            html.push('<div class="mini-meta"><div class="mini-name">'+it.name+'</div>');
            html.push('<div class="mini-qty-controls">');
            html.push('<button class="btn btn-sm qty-decrement" aria-label="Giảm số lượng">−</button>');
            html.push('<input class="mini-qty-input" name="quantity" type="number" min="0" value="'+it.qty+'" aria-label="Số lượng">');
            html.push('<button class="btn btn-sm qty-increment" aria-label="Tăng số lượng">+</button>');
            html.push('</div></div>');
            html.push('<div class="item-subtotal"><span class="muted">'+it.subtotal+'</span></div>');
            html.push('<div class="mini-item-actions"><button class="btn btn-sm btn-outline-danger mini-remove" data-pk="'+it.pk+'">Xóa</button></div>');
            html.push('</li>');
          });
          html.push('</ul>');
          html.push('<div class="mini-total">Tổng: <strong>'+data.total+'</strong></div>');
          html.push('<div class="mini-actions"><a class="btn btn-sm btn-outline-primary" href="/cart/">Xem giỏ</a> <a class="btn btn-sm" href="/checkout/">Thanh toán</a></div>');
        } else {
          html.push('<li class="mini-item">Không có sản phẩm.</li>');
          html.push('</ul>');
        }
        html.push('</div>');
        panel.innerHTML = html.join('');
        // notify other modules that mini-cart content is rendered
        try{ document.dispatchEvent(new CustomEvent('mini-cart:rendered', {detail: data})); }catch(e){}
        // attach remove handlers with collapse animation when possible
        panel.querySelectorAll('.mini-remove').forEach(function(b){
          b.addEventListener('click', function(e){
            var pk = b.getAttribute('data-pk');
            if(!pk) return;
            var li = b.closest('.mini-item');
            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            function doRemoveFetch(){
              fetch('/cart/ajax/remove/'+pk+'/', {method:'POST', headers: {'X-CSRFToken': csrftoken, 'X-Requested-With':'XMLHttpRequest'}})
              .then(function(r){ if(!r.ok) throw r; return r.json(); })
              .then(function(d){ fetchAndRender(); })
              .catch(function(){ fetchAndRender(); });
            }
            if(li && !prefersReduced){
              // measure and animate height collapse
              var startH = li.getBoundingClientRect().height;
              li.style.height = startH + 'px';
              // force repaint
              void li.offsetHeight;
              li.classList.add('removing');
              // animate to zero
              li.style.height = '0px';
              li.style.opacity = '0';
              li.addEventListener('transitionend', function onEnd(ev){
                if(ev.propertyName === 'height'){ li.removeEventListener('transitionend', onEnd); doRemoveFetch(); }
              });
            } else {
              doRemoveFetch();
            }
          });
        });
      }).catch(function(){
        var panel = document.getElementById('mini-cart-panel'); if(panel) panel.innerHTML = 'Lỗi tải giỏ hàng';
      });
    }

    // intercept add-to-cart forms/buttons
    document.querySelectorAll('form[action^="/cart/add/"] button[type="submit"]').forEach(function(btn){
      var form = btn.closest('form');
      if(!form) return;
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var fd = new FormData(form);
        fetch(form.action, {method: 'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest','X-CSRFToken': csrftoken}})
        .then(function(resp){
          // show mini cart regardless of response (server redirect will be followed only on non-AJAX)
          showMiniCart();
          // micro-pulse cart toggle for feedback
          var cartToggle = document.getElementById('mini-cart-toggle');
          if(cartToggle){
            cartToggle.classList.add('mc-pulse');
            setTimeout(function(){ cartToggle.classList.remove('mc-pulse'); }, 360);
          }
        }).catch(function(){ showMiniCart(); });
      });
    });

    // also attach a global custom event so other code can trigger the mini-cart
    document.addEventListener('mini-cart:show', function(){ showMiniCart(); });
  });
})();
