(function(){
  document.addEventListener('DOMContentLoaded', function(){
    function getCookie(name){
      var v = document.cookie.match('(^|;)\\s*'+name+'\\s*=\\s*([^;]+)');
      return v ? v.pop() : '';
    }
    var csrftoken = getCookie('csrftoken');

    function debounce(fn, wait){
      var t=null;
      return function(){
        var ctx=this, args=arguments;
        clearTimeout(t);
        t=setTimeout(function(){ fn.apply(ctx,args); }, wait);
      };
    }

    function postUpdate(pk, qty, cb){
      var url = '/cart/ajax/update/' + pk + '/';
      var fd = new FormData(); fd.append('quantity', qty);
      fetch(url, {method:'POST', body:fd, headers: {'X-CSRFToken': csrftoken, 'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){ if(!r.ok) throw r; return r.json(); })
      .then(function(data){ cb && cb(null,data); })
      .catch(function(err){ cb && cb(err); });
    }

    function animateHighlight(el){
      if(!el) return;
      if(window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      el.classList.remove('subtotal-highlight');
      // force reflow
      void el.offsetWidth;
      el.classList.add('subtotal-highlight');
      setTimeout(function(){ el.classList.remove('subtotal-highlight'); }, 400);
    }

    function showToast(container, msg){
      var t = document.createElement('div');
      t.className = 'cart-toast'; t.textContent = msg;
      container.appendChild(t);
      setTimeout(function(){ t.classList.add('show'); }, 20);
      setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ t.remove(); },220); }, 3000);
    }

    // handle form submit optimistically
    document.querySelectorAll('.cart-update-form').forEach(function(form){
      var inFlight = false;
      var debounceUpdate = debounce(function(pk, qty, els){
        if(inFlight) return;
        inFlight = true;
        // optimistic state already applied by caller
        postUpdate(pk, qty, function(err, data){
          inFlight = false;
          var input = els.input, subtotalEl = els.subtotalEl, totalEl = els.totalEl, wrap = els.wrap;
          if(err){
            // revert
            input.value = els.oldQty;
            if(subtotalEl) subtotalEl.textContent = els.oldSubtotal;
            if(totalEl) totalEl.textContent = els.oldTotal;
            showToast(wrap, 'Không thể cập nhật. Thử lại.');
          } else {
            // commit server values
            input.value = data.quantity;
            if(subtotalEl) subtotalEl.textContent = data.subtotal;
            if(totalEl) totalEl.textContent = data.total;
            animateHighlight(subtotalEl);
          }
          // re-enable controls
          els.updateBtns && els.updateBtns.forEach(function(b){ b.disabled=false; });
        });
      }, 150);

      form.addEventListener('submit', function(e){
        e.preventDefault();
        var input = form.querySelector('input[name="quantity"]');
        var qty = parseInt(input.value||0,10)||0;
        var row = form.closest('.cart-row'); if(!row){ form.submit(); return; }
        var pk = row.getAttribute('data-pk'); if(!pk){ form.submit(); return; }
        var subtotalEl = row.querySelector('.item-subtotal .muted');
        var totalEl = document.getElementById('cart-total');
        var wrap = row;
        // save old state
        var oldQty = input.value;
        var oldSubtotal = subtotalEl ? subtotalEl.textContent : null;
        var oldTotal = totalEl ? totalEl.textContent : null;
        // optimistic update
        var price = parseFloat(row.getAttribute('data-price')||0);
        var newSubtotal = price * qty;
        if(subtotalEl) subtotalEl.textContent = newSubtotal;
        if(totalEl){
          // compute delta: best-effort by summing visible subtotals to avoid another fetch
          try{
            var tot=0; document.querySelectorAll('.mini-item, .cart-row').forEach(function(r){
              var s = r.querySelector('.item-subtotal .muted');
              if(s) tot += parseFloat(s.textContent)||0;
            });
            totalEl.textContent = tot;
          }catch(e){}
        }
        // disable controls while request pending
        var updateBtns = Array.from(form.querySelectorAll('button,input[type=submit]'));
        updateBtns.forEach(function(b){ b.disabled=true; });
        // perform debounced request
        debounceUpdate(pk, qty, {input:input, subtotalEl:subtotalEl, totalEl:totalEl, oldQty:oldQty, oldSubtotal:oldSubtotal, oldTotal:oldTotal, updateBtns:updateBtns, wrap:wrap});
      });

      // wire +/- buttons inside this form
      var dec = form.querySelector('.qty-decrement');
      var inc = form.querySelector('.qty-increment');
      var input = form.querySelector('input[name="quantity"]');
      [dec,inc].forEach(function(btn){ if(!btn) return; btn.addEventListener('click', function(e){
        var cur = parseInt(input.value||0,10)||0; var next = btn.classList.contains('qty-increment') ? cur+1 : cur-1; if(next<0) next=0; input.value = next; // optimistic
        // submit via debounced handler
        var evt = new Event('submit',{cancelable:true}); form.dispatchEvent(evt);
      }); });
    });

    // also apply to mini-cart items if they later include qty controls (hook: listen for custom event to attach)
    document.addEventListener('mini-cart:rendered', function(ev){
      // attach controls for mini-cart items (dynamically rendered)
      var panel = document.getElementById('mini-cart-panel');
      if(!panel) return;
      panel.querySelectorAll('.mini-item').forEach(function(item){
        if(item.__cartQtyBound) return; // avoid double-bind
        item.__cartQtyBound = true;
        var pk = item.getAttribute('data-pk');
        var price = parseFloat(item.getAttribute('data-price')||0);
        var input = item.querySelector('input[name="quantity"]');
        var dec = item.querySelector('.qty-decrement');
        var inc = item.querySelector('.qty-increment');
        var subtotalEl = item.querySelector('.item-subtotal .muted') || item.querySelector('.mini-sub');
        var totalEl = panel.querySelector('.mini-total strong');

        function currentTotal(){
          var t = 0;
          panel.querySelectorAll('.mini-item').forEach(function(it){
            var s = it.querySelector('.item-subtotal .muted') || it.querySelector('.mini-sub');
            if(s) t += parseFloat(s.textContent)||0;
          });
          return t;
        }

        function disableBtns(state){
          [dec,inc,input].forEach(function(el){ if(!el) return; el.disabled = !!state; });
        }

        function applyOptimistic(q){
          if(subtotalEl) subtotalEl.textContent = (price * q);
          if(totalEl) totalEl.textContent = currentTotal();
        }

        function revert(oldQty, oldSubtotal, oldTotal){
          if(input) input.value = oldQty;
          if(subtotalEl) subtotalEl.textContent = oldSubtotal;
          if(totalEl) totalEl.textContent = oldTotal;
        }

        function doUpdate(q){
          var oldQty = input ? input.value : null;
          var oldSubtotal = subtotalEl ? subtotalEl.textContent : null;
          var oldTotal = totalEl ? totalEl.textContent : null;
          // optimistic
          if(input) input.value = q;
          applyOptimistic(q);
          disableBtns(true);
          postUpdate(pk, q, function(err,data){
            disableBtns(false);
            if(err){ revert(oldQty, oldSubtotal, oldTotal); showToast(item, 'Không thể cập nhật. Thử lại.'); }
            else {
              if(input) input.value = data.quantity;
              if(subtotalEl) subtotalEl.textContent = data.subtotal;
              if(totalEl) totalEl.textContent = data.total;
              animateHighlight(subtotalEl);
              // update any visible cart badge/counts
              var badge = document.querySelector('[data-cart-count], .cart-badge, #cart-badge');
              if(badge){ try{ badge.textContent = data.total; }catch(e){} }
            }
          });
        }

        // wire clicks
        if(dec) dec.addEventListener('click', function(e){ e.preventDefault(); var cur = parseInt(input.value||0,10)||0; var next = Math.max(0, cur-1); doUpdate(next); });
        if(inc) inc.addEventListener('click', function(e){ e.preventDefault(); var cur = parseInt(input.value||0,10)||0; var next = cur+1; doUpdate(next); });
        if(input) input.addEventListener('change', function(e){ var q = parseInt(input.value||0,10)||0; doUpdate(q); });
      });
    });
  });
})();
