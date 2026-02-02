// AJAX Mini Cart (Bootstrap 5 offcanvas + toasts)
(() => {
  const API = {
    add: (id) => `/cart/add/${id}/`,
    update: (id) => `/cart/ajax/update/${id}/`,
    remove: (id) => `/cart/ajax/remove/${id}/`,
    summary: () => `/cart/ajax/summary/`
  };

  const qs = (s, root = document) => root.querySelector(s);
  const qsa = (s, root = document) => Array.from(root.querySelectorAll(s));

  function getCookie(name) {
    const v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return v ? v.pop() : '';
  }

  function showToast(message, type = 'success') {
    const container = qs('#mini-cart-toasts') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
  }

  function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'mini-cart-toasts';
    container.style.position = 'fixed';
    container.style.top = '1rem';
    container.style.right = '1rem';
    container.style.zIndex = 1080;
    document.body.appendChild(container);
    return container;
  }

  function updateCartBadge(count) {
    qsa('.mini-cart-count').forEach(el => el.textContent = count);
  }

  async function fetchSummary() {
    try {
      const res = await fetch(API.summary(), { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Network error');
      return await res.json();
    } catch (e) {
      console.error('fetchSummary', e);
      return null;
    }
  }

  async function renderMiniCart() {
    const summary = await fetchSummary();
    if (!summary) return;
    const container = qs('#mini-cart-body');
    if (!container) return;
    container.innerHTML = '';
    if (!summary.items || summary.items.length === 0) {
      container.innerHTML = `<div class="p-4 text-center">Giỏ hàng trống</div>`;
      updateCartBadge(0);
      return;
    }
    // compute count if backend didn't provide it
    const count = summary.count !== undefined ? summary.count : summary.items.reduce((s,i)=>s+(i.qty||i.quantity||0),0);
    updateCartBadge(count || 0);
    summary.items.forEach(item => {
      const row = document.createElement('div');
      row.className = 'd-flex align-items-center mb-3';
      const img = item.image_url || item.image || '/static/images/icons/cart.svg';
      const qty = item.quantity !== undefined ? item.quantity : (item.qty !== undefined ? item.qty : 0);
      const price_display = item.price_display || (item.price !== undefined ? item.price : '');
      row.innerHTML = `
        <img src="${img}" alt="" style="width:64px;height:64px;object-fit:cover;margin-right:12px;">
        <div class="flex-grow-1">
          <div class="fw-bold">${item.name}</div>
          <div class="text-muted small">${price_display} x <input data-pk="${item.pk}" class="mini-qty form-control form-control-sm d-inline-block" style="width:70px;" value="${qty}"></div>
        </div>
        <button class="btn btn-sm btn-link text-danger mini-remove" data-pk="${item.pk}">✕</button>
      `;
      container.appendChild(row);
    });
  }

  function debounce(fn, wait) {
    let t;
    return function(...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), wait); };
  }

  async function postJSON(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRFToken': getCookie('csrftoken') },
      body: JSON.stringify(data)
    });
    if (!res.ok) throw new Error('Network error');
    return res.json();
  }

  function bindUI() {
    // Add-to-cart buttons (data attribute, redesign class, or inline onclick)
    qsa('[data-add-to-cart]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const pk = btn.getAttribute('data-add-to-cart');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
          await postJSON(API.add(pk), { quantity: 1 });
          showToast('Đã thêm vào giỏ hàng');
          await renderMiniCart();
        } catch (err) {
          console.error(err);
          showToast('Không thể thêm vào giỏ hàng', 'danger');
        } finally {
          btn.disabled = false;
          btn.innerHTML = original;
        }
      });
      });

      // redesign buttons using class or inline onclick
      qsa('.btn-add-to-cart-redesign').forEach(btn => {
        btn.addEventListener('click', async (e) => {
          // try to extract product id from onclick or surrounding data
          let pk = btn.getAttribute('data-pk');
          if (!pk) {
            const oc = btn.getAttribute('onclick') || '';
            const m = oc.match(/addToCart\((\d+)\)/);
            if (m) pk = m[1];
          }
          if (!pk) return;
          btn.disabled = true;
          const original = btn.innerHTML;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
          try {
            await postJSON(API.add(pk), { quantity: 1 });
            showToast('Đã thêm vào giỏ hàng');
            await renderMiniCart();
          } catch (err) {
            console.error(err);
            showToast('Không thể thêm vào giỏ hàng', 'danger');
          } finally {
            btn.disabled = false;
            btn.innerHTML = original;
          }
        });
      });

      // buttons with inline onclick addToCart(...) not using redesign class
      qsa('button[onclick]').forEach(btn => {
        const oc = btn.getAttribute('onclick') || '';
        if (!oc.includes('addToCart(')) return;
        btn.addEventListener('click', async (e) => {
          const m = oc.match(/addToCart\((\d+)\)/);
          if (!m) return;
          const pk = m[1];
          btn.disabled = true;
          const original = btn.innerHTML;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
          try {
            await postJSON(API.add(pk), { quantity: 1 });
            showToast('Đã thêm vào giỏ hàng');
            await renderMiniCart();
          } catch (err) {
            console.error(err);
            showToast('Không thể thêm vào giỏ hàng', 'danger');
          } finally {
            btn.disabled = false;
            btn.innerHTML = original;
          }
        });
      });

    // Open mini-cart toggle
    const offcanvasEl = qs('#miniCartOffcanvas');
    if (offcanvasEl) {
      offcanvasEl.addEventListener('show.bs.offcanvas', () => renderMiniCart());
    }

    // Quantity change (delegated)
    document.body.addEventListener('input', debounce(async (e) => {
      if (!e.target.classList.contains('mini-qty')) return;
      const pk = e.target.getAttribute('data-pk');
      const qty = parseInt(e.target.value) || 1;
      e.target.disabled = true;
      try {
        await postJSON(API.update(pk), { quantity: qty });
        await renderMiniCart();
      } catch (err) {
        console.error(err);
        showToast('Cập nhật thất bại', 'danger');
      } finally { e.target.disabled = false; }
    }, 500));

    // Remove item (delegated)
    document.body.addEventListener('click', async (e) => {
      if (!e.target.classList.contains('mini-remove')) return;
      const pk = e.target.getAttribute('data-pk');
      e.target.disabled = true;
      try {
        await postJSON(API.remove(pk), {});
        showToast('Đã xoá');
        await renderMiniCart();
      } catch (err) {
        console.error(err);
        showToast('Xoá thất bại', 'danger');
      } finally { e.target.disabled = false; }
    });
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    bindUI();
    // Initial render for badge
    renderMiniCart();
  });

})();
