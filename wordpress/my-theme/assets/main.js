(function () {
  function parseAmount(value) {
    if (value === null || value === undefined) {
      return 0;
    }
    var normalized = String(value)
      .replace(/\./g, '')
      .replace(/,/g, '.')
      .replace(/[^0-9.-]/g, '');
    var parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function formatCurrency(value, emptyText) {
    var amount = Math.round(parseFloat(value) || 0);
    if (!amount) {
      return emptyText || '';
    }
    return new Intl.NumberFormat('vi-VN').format(amount) + ' \u20ab';
  }

  function initMainNav() {
    var nav = document.querySelector('.main-nav');
    var toggle = document.querySelector('.menu-toggle');
    if (!nav || !toggle) {
      return;
    }

    var parentLinks = Array.prototype.slice.call(
      nav.querySelectorAll('.menu-item-has-children > a')
    );

    function closeSubMenus() {
      parentLinks.forEach(function (link) {
        var parent = link.parentElement;
        if (parent) {
          parent.classList.remove('is-open');
        }
        link.setAttribute('aria-expanded', 'false');
      });
    }

    toggle.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      if (!isOpen) {
        closeSubMenus();
      }
    });

    parentLinks.forEach(function (link) {
      link.setAttribute('aria-haspopup', 'true');
      link.setAttribute('aria-expanded', 'false');

      link.addEventListener('click', function (event) {
        if (!window.matchMedia('(max-width: 1180px)').matches) {
          return;
        }

        var parent = link.parentElement;
        if (!parent) {
          return;
        }

        var willOpen = !parent.classList.contains('is-open');
        if (willOpen) {
          event.preventDefault();
        }

        closeSubMenus();
        if (willOpen) {
          parent.classList.add('is-open');
          link.setAttribute('aria-expanded', 'true');
        }
      });
    });

    document.addEventListener('click', function (event) {
      if (!nav.classList.contains('is-open')) {
        return;
      }
      if (nav.contains(event.target)) {
        return;
      }
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      closeSubMenus();
    });

    window.addEventListener('resize', function () {
      if (window.matchMedia('(max-width: 1180px)').matches) {
        return;
      }
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      closeSubMenus();
    });
  }

  function initSingleCapacityPicker(picker) {
    if (!picker || picker.dataset.ready === '1') {
      return;
    }
    picker.dataset.ready = '1';

    var options = Array.prototype.slice.call(
      picker.querySelectorAll('.capacity-option')
    );
    if (!options.length) {
      return;
    }

    var capInput = picker.querySelector('input[name="selected_capacity"]');
    var priceInput = picker.querySelector('input[name="selected_capacity_price"]');
    var currentLabel = picker.querySelector('[data-capacity-current]');
    var summary = picker.closest('.summary');
    var priceAmount = summary ? summary.querySelector('.price .amount') : null;
    var priceContact = summary
      ? summary.querySelector('.price .product-price-contact-inline')
      : null;
    var priceItems = summary
      ? Array.prototype.slice.call(summary.querySelectorAll('.product-pack-prices__item'))
      : [];

    function activate(option) {
      if (!option) {
        return;
      }

      options.forEach(function (item) {
        item.classList.remove('is-active');
      });
      option.classList.add('is-active');

      var cap = option.getAttribute('data-capacity') || '';
      var priceRaw = option.getAttribute('data-price') || '';
      var priceValue = parseAmount(priceRaw);

      if (capInput) {
        capInput.value = cap;
      }
      if (currentLabel) {
        currentLabel.textContent = cap || '-';
      }
      if (priceInput) {
        priceInput.value = priceValue > 0 ? String(priceValue) : '';
      }

      if (priceAmount) {
        priceAmount.textContent = formatCurrency(priceValue, 'Liên hệ báo giá');
      } else if (priceContact) {
        priceContact.textContent = formatCurrency(priceValue, 'Liên hệ báo giá');
      }

      if (priceItems.length) {
        priceItems.forEach(function (item) {
          var size = item.getAttribute('data-pack-size') || '';
          item.classList.toggle('is-active', size === cap);
        });
      }
    }

    var initial =
      options.find(function (item) {
        return item.classList.contains('is-active');
      }) || options[0];
    activate(initial);

    options.forEach(function (option) {
      option.addEventListener('click', function () {
        activate(option);
      });
    });
  }

  function initLoopPackForm(form) {
    if (!form || form.dataset.loopReady === '1') {
      return;
    }
    form.dataset.loopReady = '1';

    var options = Array.prototype.slice.call(
      form.querySelectorAll('.loop-pack-option')
    );
    if (!options.length) {
      return;
    }

    var capInput = form.querySelector('input[name="selected_capacity"]');
    var priceInput = form.querySelector('input[name="selected_capacity_price"]');
    var card = form.closest('.product-card');
    var priceWrap = card ? card.querySelector('.product-card__price') : null;
    var priceBox = priceWrap
      ? priceWrap.querySelector('.product-card__price-value, .product-card__price-contact')
      : null;
    if (!priceBox && priceWrap) {
      priceBox = document.createElement('span');
      priceBox.className = 'product-card__price-value';
      priceWrap.appendChild(priceBox);
    }
    var priceItems = card
      ? Array.prototype.slice.call(card.querySelectorAll('.product-pack-prices__item'))
      : [];

    function activate(option) {
      if (!option) {
        return;
      }

      options.forEach(function (item) {
        item.classList.remove('is-active');
      });
      option.classList.add('is-active');

      var cap = option.getAttribute('data-capacity') || '';
      var priceRaw = option.getAttribute('data-price') || '';
      var priceValue = parseAmount(priceRaw);

      if (capInput) {
        capInput.value = cap;
      }
      if (priceInput) {
        priceInput.value = priceValue > 0 ? String(priceValue) : '';
      }

      if (priceBox) {
        priceBox.textContent = formatCurrency(priceValue, 'Liên hệ báo giá');
        if (priceValue > 0) {
          priceBox.classList.add('product-card__price-value');
          priceBox.classList.remove('product-card__price-contact');
          priceBox.setAttribute('data-price', String(priceValue));
        } else {
          priceBox.classList.add('product-card__price-contact');
          priceBox.classList.remove('product-card__price-value');
          priceBox.removeAttribute('data-price');
        }
      }

      if (priceItems.length) {
        priceItems.forEach(function (item) {
          var size = item.getAttribute('data-pack-size') || '';
          item.classList.toggle('is-active', size === cap);
        });
      }
    }

    var initial =
      options.find(function (item) {
        return item.classList.contains('is-active');
      }) || options[0];
    activate(initial);

    options.forEach(function (option) {
      option.addEventListener('click', function () {
        activate(option);
      });
    });

    form.addEventListener('submit', function () {
      var active =
        options.find(function (item) {
          return item.classList.contains('is-active');
        }) || options[0];
      activate(active);
    });
  }

  function boot() {
    initMainNav();
    function initInteractiveBlocks() {
      document.querySelectorAll('.capacity-picker').forEach(initSingleCapacityPicker);
      document.querySelectorAll('.loop-pack-form').forEach(initLoopPackForm);
    }

    initInteractiveBlocks();

    if (window.jQuery && window.jQuery.fn && window.jQuery(document.body).on) {
      window.jQuery(document.body).on(
        'updated_wc_div wc_fragments_loaded updated_cart_totals',
        initInteractiveBlocks
      );
    }

    var pending = false;
    var observer = new MutationObserver(function (mutations) {
      if (pending) {
        return;
      }

      var shouldReinit = mutations.some(function (mutation) {
        if (!mutation.addedNodes || !mutation.addedNodes.length) {
          return false;
        }
        return Array.prototype.some.call(mutation.addedNodes, function (node) {
          if (!node || node.nodeType !== 1) {
            return false;
          }
          return (
            node.matches('.capacity-picker, .loop-pack-form') ||
            node.querySelector('.capacity-picker, .loop-pack-form')
          );
        });
      });

      if (!shouldReinit) {
        return;
      }

      pending = true;
      window.requestAnimationFrame(function () {
        initInteractiveBlocks();
        pending = false;
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
