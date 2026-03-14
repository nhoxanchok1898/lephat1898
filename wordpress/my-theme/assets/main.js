(function () {
  function runSafe(stepName, handler) {
    if (typeof handler !== 'function') {
      return;
    }
    try {
      handler();
    } catch (error) {
      if (window.console && typeof window.console.error === 'function') {
        window.console.error('[my-theme][' + stepName + ']', error);
      }
    }
  }

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

  function renderAmountHtml(value, contactClassName) {
    var amount = Math.round(parseFloat(value) || 0);
    if (!amount) {
      return (
        '<span class="' +
        (contactClassName || 'product-price-contact-inline') +
        '">Liên hệ báo giá</span>'
      );
    }

    return (
      '<span class="woocommerce-Price-amount amount"><bdi>' +
      new Intl.NumberFormat('vi-VN').format(amount) +
      '&nbsp;<span class="woocommerce-Price-currencySymbol">\u20ab</span></bdi></span>'
    );
  }

  function renderSinglePriceHtml(priceValue, regularPriceValue) {
    var amount = Math.round(parseFloat(priceValue) || 0);
    var regularAmount = Math.round(parseFloat(regularPriceValue) || 0);

    if (!amount) {
      return '<span class="product-price-contact-inline">Liên hệ báo giá</span>';
    }

    if (regularAmount > amount) {
      return (
        '<del>' +
        renderAmountHtml(regularAmount, 'product-price-contact-inline') +
        '</del><ins>' +
        renderAmountHtml(amount, 'product-price-contact-inline') +
        '</ins>'
      );
    }

    return renderAmountHtml(amount, 'product-price-contact-inline');
  }

  function renderLoopPriceHtml(priceValue, regularPriceValue) {
    var amount = Math.round(parseFloat(priceValue) || 0);
    var regularAmount = Math.round(parseFloat(regularPriceValue) || 0);

    if (!amount) {
      return '<span class="product-card__price-contact">Liên hệ báo giá</span>';
    }

    if (regularAmount > amount) {
      return (
        '<del class="product-card__price-regular">' +
        renderAmountHtml(regularAmount, 'product-card__price-contact') +
        '</del><ins class="product-card__price-sale"><span class="product-card__price-value" data-price="' +
        String(amount) +
        '" data-regular-price="' +
        String(regularAmount) +
        '">' +
        renderAmountHtml(amount, 'product-card__price-contact') +
        '</span></ins>'
      );
    }

    return (
      '<span class="product-card__price-value" data-price="' +
      String(amount) +
      '">' +
      renderAmountHtml(amount, 'product-card__price-contact') +
      '</span>'
    );
  }

  function setPackItemState(item, isActive) {
    if (!item || !item.classList) {
      return;
    }
    var active = Boolean(isActive);
    item.classList.toggle('is-active', active);
    if (item.hasAttribute('aria-pressed')) {
      item.setAttribute('aria-pressed', active ? 'true' : 'false');
    }
  }

  function findInputByExactId(scope, selector, id) {
    if (!scope || !id) {
      return null;
    }
    var matched = null;
    Array.prototype.some.call(scope.querySelectorAll(selector), function (node) {
      if ((node.id || '') !== id) {
        return false;
      }
      matched = node;
      return true;
    });
    return matched;
  }

  function findInputByCapacity(scope, selector, capacity) {
    if (!scope) {
      return null;
    }
    var targetCapacity = String(capacity || '').trim();
    if (!targetCapacity) {
      return null;
    }
    var matched = null;
    Array.prototype.some.call(scope.querySelectorAll(selector), function (node) {
      var value = String(node.value || '').trim();
      var dataValue = String(node.getAttribute('data-capacity') || '').trim();
      if (value !== targetCapacity && dataValue !== targetCapacity) {
        return false;
      }
      matched = node;
      return true;
    });
    return matched;
  }

  function dispatchChange(input) {
    if (!input || typeof input.dispatchEvent !== 'function') {
      return;
    }
    try {
      input.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (error) {
      if (typeof document.createEvent === 'function') {
        var event = document.createEvent('Event');
        event.initEvent('change', true, true);
        input.dispatchEvent(event);
      }
    }
  }

  function syncSingleCapacityState(input) {
    if (!input || !input.classList || !input.classList.contains('capacity-option__input')) {
      return false;
    }

    var picker = input.closest('.capacity-picker');
    if (!picker) {
      return false;
    }

    var inputs = Array.prototype.slice.call(
      picker.querySelectorAll('.capacity-option__input')
    );
    var labels = Array.prototype.slice.call(
      picker.querySelectorAll('.capacity-option')
    );
    var priceInput = picker.querySelector('input[name="selected_capacity_price"]');
    var currentLabel = picker.querySelector('[data-capacity-current]');

    inputs.forEach(function (item) {
      item.checked = item === input;
    });

    var cap = input.value || input.getAttribute('data-capacity') || '';
    var priceRaw = input.getAttribute('data-price') || '';
    var priceValue = parseAmount(priceRaw);
    var regularPriceValue = parseAmount(input.getAttribute('data-regular-price') || '');

    if (currentLabel) {
      currentLabel.textContent = cap || '-';
    }
    if (priceInput) {
      priceInput.value = priceValue > 0 ? String(priceValue) : '';
    }

    labels.forEach(function (label) {
      var forId = label.getAttribute('for') || '';
      var isActive = forId === input.id;
      if (!isActive && forId === '' && label.getAttribute('data-capacity')) {
        isActive = label.getAttribute('data-capacity') === cap;
      }
      label.classList.toggle('is-active', isActive);
    });

    var summary = picker.closest('.summary');
    if (!summary) {
      return true;
    }

    var priceWrap = summary.querySelector('.price');
    if (priceWrap) {
      priceWrap.innerHTML = renderSinglePriceHtml(priceValue, regularPriceValue);
    }

    Array.prototype.forEach.call(
      summary.querySelectorAll('.product-pack-prices__item'),
      function (item) {
        var size = item.getAttribute('data-pack-size') || '';
        setPackItemState(item, size === cap);
      }
    );

    return true;
  }

  function syncLoopPackState(input) {
    if (!input || !input.classList || !input.classList.contains('loop-pack-option__input')) {
      return false;
    }

    var form = input.closest('.loop-pack-form');
    if (!form) {
      return false;
    }

    var inputs = Array.prototype.slice.call(
      form.querySelectorAll('.loop-pack-option__input')
    );
    var labels = Array.prototype.slice.call(
      form.querySelectorAll('.loop-pack-option')
    );
    var priceInput = form.querySelector('input[name="selected_capacity_price"]');
    var card = form.closest('.product-card');

    inputs.forEach(function (item) {
      item.checked = item === input;
    });

    var cap = input.value || input.getAttribute('data-capacity') || '';
    var priceRaw = input.getAttribute('data-price') || '';
    var priceValue = parseAmount(priceRaw);
    var regularPriceValue = parseAmount(input.getAttribute('data-regular-price') || '');

    if (priceInput) {
      priceInput.value = priceValue > 0 ? String(priceValue) : '';
    }

    labels.forEach(function (label) {
      var forId = label.getAttribute('for') || '';
      var isActive = forId === input.id;
      if (!isActive && forId === '' && label.getAttribute('data-capacity')) {
        isActive = label.getAttribute('data-capacity') === cap;
      }
      label.classList.toggle('is-active', isActive);
    });

    if (!card) {
      return true;
    }

    var priceWrap = card.querySelector('.product-card__price');
    if (priceWrap) {
      priceWrap.innerHTML = renderLoopPriceHtml(priceValue, regularPriceValue);
    }

    Array.prototype.forEach.call(
      card.querySelectorAll('.product-pack-prices__item'),
      function (item) {
        var size = item.getAttribute('data-pack-size') || '';
        setPackItemState(item, size === cap);
      }
    );

    return true;
  }

  function syncSingleCapacityFallback(option, picker) {
    if (!option || !picker) {
      return false;
    }

    var cap = String(option.getAttribute('data-capacity') || option.value || '').trim();
    var priceValue = parseAmount(option.getAttribute('data-price') || '');
    var regularPriceValue = parseAmount(option.getAttribute('data-regular-price') || '');
    var currentLabel = picker.querySelector('[data-capacity-current]');
    var priceInput = picker.querySelector('input[name="selected_capacity_price"]');

    if (currentLabel) {
      currentLabel.textContent = cap || '-';
    }
    if (priceInput) {
      priceInput.value = priceValue > 0 ? String(priceValue) : '';
    }

    Array.prototype.forEach.call(
      picker.querySelectorAll('.capacity-option'),
      function (node) {
        var nodeCap = String(node.getAttribute('data-capacity') || '').trim();
        var isActive = node === option;
        if (!isActive && nodeCap && cap) {
          isActive = nodeCap === cap;
        }
        node.classList.toggle('is-active', isActive);
      }
    );

    var summary = picker.closest('.summary');
    if (!summary) {
      return true;
    }

    var priceWrap = summary.querySelector('.price');
    if (priceWrap) {
      priceWrap.innerHTML = renderSinglePriceHtml(priceValue, regularPriceValue);
    }

    Array.prototype.forEach.call(
      summary.querySelectorAll('.product-pack-prices__item'),
      function (item) {
        var size = item.getAttribute('data-pack-size') || '';
        setPackItemState(item, size === cap);
      }
    );

    return true;
  }

  function syncLoopPackFallback(option, form) {
    if (!option || !form) {
      return false;
    }

    var cap = String(option.getAttribute('data-capacity') || option.value || '').trim();
    var priceValue = parseAmount(option.getAttribute('data-price') || '');
    var regularPriceValue = parseAmount(option.getAttribute('data-regular-price') || '');
    var priceInput = form.querySelector('input[name="selected_capacity_price"]');
    var card = form.closest('.product-card');

    if (priceInput) {
      priceInput.value = priceValue > 0 ? String(priceValue) : '';
    }

    Array.prototype.forEach.call(
      form.querySelectorAll('.loop-pack-option'),
      function (node) {
        var nodeCap = String(node.getAttribute('data-capacity') || '').trim();
        var isActive = node === option;
        if (!isActive && nodeCap && cap) {
          isActive = nodeCap === cap;
        }
        node.classList.toggle('is-active', isActive);
      }
    );

    if (!card) {
      return true;
    }

    var priceWrap = card.querySelector('.product-card__price');
    if (priceWrap) {
      priceWrap.innerHTML = renderLoopPriceHtml(priceValue, regularPriceValue);
    }

    Array.prototype.forEach.call(
      card.querySelectorAll('.product-pack-prices__item'),
      function (item) {
        var size = item.getAttribute('data-pack-size') || '';
        setPackItemState(item, size === cap);
      }
    );

    return true;
  }

  function runWhenIdle(callback, timeoutMs) {
    if (typeof callback !== 'function') {
      return;
    }
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(function () {
        callback();
      }, { timeout: timeoutMs || 1200 });
      return;
    }
    window.setTimeout(callback, 1);
  }

  function initHeaderOffsetVar() {
    var header = document.querySelector('.site-header');
    var root = document.documentElement;
    if (!header || !root) {
      return;
    }

    var frame = 0;

    function updateOffset() {
      frame = 0;
      var adminBar = document.getElementById('wpadminbar');
      var adminHeight = adminBar ? adminBar.offsetHeight || 0 : 0;
      var headerHeight = header.offsetHeight || 0;
      var extraGap = window.matchMedia('(max-width: 760px)').matches ? 12 : 18;
      var totalOffset = Math.max(96, headerHeight + adminHeight + extraGap);
      root.style.setProperty('--site-header-offset', String(totalOffset) + 'px');
    }

    function scheduleUpdate() {
      if (frame) {
        return;
      }
      frame = window.requestAnimationFrame(updateOffset);
    }

    scheduleUpdate();
    window.addEventListener('load', scheduleUpdate);
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('orientationchange', scheduleUpdate);

    if ('ResizeObserver' in window) {
      var observer = new ResizeObserver(function () {
        scheduleUpdate();
      });
      observer.observe(header);
    }
  }

  function initMainNav() {
    var nav = document.querySelector('.main-nav');
    var toggle = document.querySelector('.menu-toggle');
    if (!nav || !toggle) {
      return;
    }

    var mobileQuery = window.matchMedia('(max-width: 1180px)');
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

    function setBodyMenuState(isOpen) {
      if (!document.body) {
        return;
      }
      var mobile = mobileQuery.matches;
      document.body.classList.toggle('menu-open', Boolean(isOpen && mobile));
    }

    function closeMainNav() {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      closeSubMenus();
      setBodyMenuState(false);
    }

    function openDesktopSubMenu(parent, link) {
      if (mobileQuery.matches || !parent || !link) {
        return;
      }
      closeSubMenus();
      parent.classList.add('is-open');
      link.setAttribute('aria-expanded', 'true');
    }

    function closeDesktopSubMenus() {
      if (mobileQuery.matches) {
        return;
      }
      closeSubMenus();
    }

    toggle.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      if (!isOpen) {
        closeSubMenus();
      }
      setBodyMenuState(isOpen);
    });

    parentLinks.forEach(function (link) {
      link.setAttribute('aria-haspopup', 'true');
      link.setAttribute('aria-expanded', 'false');

      var parent = link.parentElement;

      if (parent) {
        parent.addEventListener('mouseenter', function () {
          openDesktopSubMenu(parent, link);
        });

        parent.addEventListener('mouseleave', function () {
          closeDesktopSubMenus();
        });

        parent.addEventListener('focusin', function () {
          openDesktopSubMenu(parent, link);
        });
      }

      link.addEventListener('click', function (event) {
        if (!mobileQuery.matches) {
          return;
        }

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
      if (mobileQuery.matches && !nav.classList.contains('is-open')) {
        return;
      }
      if (!mobileQuery.matches && !nav.querySelector('.menu-item-has-children.is-open')) {
        return;
      }
      if (nav.contains(event.target)) {
        return;
      }
      if (mobileQuery.matches) {
        closeMainNav();
        return;
      }
      closeDesktopSubMenus();
    });

    nav.addEventListener('click', function (event) {
      var target = event.target;
      if (!target || typeof target.closest !== 'function') {
        return;
      }
      var link = target.closest('a');
      if (!link) {
        return;
      }

      if (mobileQuery.matches) {
        if (!nav.classList.contains('is-open')) {
          return;
        }
        if (link.matches('.menu-item-has-children > a')) {
          return;
        }
        closeMainNav();
        return;
      }

      if (link.closest('.sub-menu')) {
        closeDesktopSubMenus();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }
      var hasDesktopOpenSubMenu = !mobileQuery.matches && nav.querySelector('.menu-item-has-children.is-open');
      if (!nav.classList.contains('is-open') && !hasDesktopOpenSubMenu) {
        return;
      }
      if (mobileQuery.matches) {
        closeMainNav();
      } else {
        closeDesktopSubMenus();
      }
      if (typeof toggle.focus === 'function') {
        toggle.focus();
      }
    });

    window.addEventListener('resize', function () {
      if (mobileQuery.matches) {
        setBodyMenuState(nav.classList.contains('is-open'));
        return;
      }
      closeMainNav();
    });

    window.addEventListener(
      'scroll',
      function () {
        if (mobileQuery.matches) {
          return;
        }
        closeDesktopSubMenus();
      },
      { passive: true }
    );
  }

  function initStickyHeaderState() {
    var header = document.querySelector('.site-header');
    if (!header || header.dataset.stickyReady === '1') {
      return;
    }

    // Disable scroll-shrink by default to avoid visual jitter while scrolling.
    // Set data-sticky-shrink="1" on .site-header only when this behavior is required.
    var shrinkEnabled = header.getAttribute('data-sticky-shrink') === '1';
    if (!shrinkEnabled) {
      header.classList.remove('is-scrolled');
      header.dataset.stickyReady = '1';
      return;
    }

    header.dataset.stickyReady = '1';

    var ticking = false;
    var thresholdEnter = 96;
    var thresholdExit = 56;
    var isScrolled = false;

    function updateState() {
      var y = window.scrollY || window.pageYOffset || 0;
      var nextScrolled = isScrolled;
      if (!isScrolled && y >= thresholdEnter) {
        nextScrolled = true;
      } else if (isScrolled && y <= thresholdExit) {
        nextScrolled = false;
      }

      if (nextScrolled !== isScrolled) {
        isScrolled = nextScrolled;
        header.classList.toggle('is-scrolled', isScrolled);
      }
      ticking = false;
    }

    function requestUpdate() {
      if (ticking) {
        return;
      }
      ticking = true;
      window.requestAnimationFrame(updateState);
    }

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    updateState();
  }

  function initCatalogDock() {
    var docks = Array.prototype.slice.call(
      document.querySelectorAll('[data-catalog-dock]')
    );
    if (!docks.length) {
      return;
    }

    docks.forEach(function (dock) {
      if (!dock || dock.dataset.catalogReady === '1') {
        return;
      }
      dock.dataset.catalogReady = '1';

      var triggers = Array.prototype.slice.call(
        dock.querySelectorAll('[data-catalog-target]')
      );
      var panels = Array.prototype.slice.call(
        dock.querySelectorAll('[data-catalog-panel]')
      );
      if (!triggers.length || !panels.length) {
        return;
      }

      function activate(target) {
        var normalizedTarget = String(target || '').trim();
        if (!normalizedTarget) {
          return;
        }

        triggers.forEach(function (trigger) {
          var triggerTarget = String(
            trigger.getAttribute('data-catalog-target') || ''
          ).trim();
          var isActive = triggerTarget === normalizedTarget;
          trigger.classList.toggle('is-active', isActive);
          trigger.setAttribute('aria-current', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
          var panelTarget = String(
            panel.getAttribute('data-catalog-panel') || ''
          ).trim();
          var isActive = panelTarget === normalizedTarget;
          panel.classList.toggle('is-active', isActive);
          if (isActive) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', 'hidden');
          }
        });
      }

      function activateByIndex(index, focusNext) {
        if (!triggers.length) {
          return;
        }
        var nextIndex = Number(index);
        if (!Number.isFinite(nextIndex)) {
          nextIndex = 0;
        }
        nextIndex = ((nextIndex % triggers.length) + triggers.length) % triggers.length;
        var nextTrigger = triggers[nextIndex];
        if (!nextTrigger) {
          return;
        }
        activate(nextTrigger.getAttribute('data-catalog-target'));
        if (focusNext && typeof nextTrigger.focus === 'function') {
          nextTrigger.focus();
        }
      }

      triggers.forEach(function (trigger, index) {
        var target = String(trigger.getAttribute('data-catalog-target') || '').trim();
        if (!target) {
          return;
        }

        trigger.addEventListener('mouseenter', function () {
          if (window.matchMedia('(max-width: 1180px)').matches) {
            return;
          }
          activate(target);
        });

        trigger.addEventListener('focus', function () {
          activate(target);
        });

        trigger.addEventListener('keydown', function (event) {
          var key = event.key;
          var nextIndex = null;
          if (key === 'ArrowDown') {
            nextIndex = index + 1;
          } else if (key === 'ArrowUp') {
            nextIndex = index - 1;
          } else if (key === 'Home') {
            nextIndex = 0;
          } else if (key === 'End') {
            nextIndex = triggers.length - 1;
          }
          if (nextIndex === null) {
            return;
          }
          event.preventDefault();
          activateByIndex(nextIndex, true);
        });
      });

      var initial =
        triggers.find(function (trigger) {
          return trigger.classList.contains('is-active');
        }) || triggers[0];
      activate(initial ? initial.getAttribute('data-catalog-target') : '');
    });
  }

  function initRevealMotion() {
    var body = document.body;
    var prefersReducedMotion = false;
    if (window.matchMedia) {
      prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    var isCommercePage = Boolean(
      body &&
      (
        body.classList.contains('woocommerce-shop') ||
        body.classList.contains('woocommerce-page') ||
        body.classList.contains('single-product')
      )
    );

    if (prefersReducedMotion || isCommercePage) {
      document.querySelectorAll('.reveal-block').forEach(function (node) {
        node.classList.remove('reveal-block');
        node.classList.add('is-inview');
      });
      return;
    }

    var selector = [
      '.hero--refined',
      '.brand-tile',
      '.home-hub__folder',
      '.brand-showcase',
      '.brand-showcase .product-card',
      '.category-card',
      '.insight-card'
    ].join(', ');
    var nodes = Array.prototype.slice.call(document.querySelectorAll(selector));
    if (!nodes.length) {
      return;
    }

    var maxAnimatedNodes = 56;
    var animatedNodes = nodes.slice(0, maxAnimatedNodes);
    var staticNodes = nodes.slice(maxAnimatedNodes);

    staticNodes.forEach(function (node) {
      if (!node) {
        return;
      }
      node.classList.add('is-inview');
      node.dataset.revealReady = '1';
    });

    animatedNodes.forEach(function (node, index) {
      if (!node || node.dataset.revealReady === '1') {
        return;
      }
      node.dataset.revealReady = '1';
      node.classList.add('reveal-block');
      node.style.setProperty('--reveal-delay', String((index % 8) * 42) + 'ms');
    });

    if (!('IntersectionObserver' in window)) {
      animatedNodes.forEach(function (node) {
        node.classList.add('is-inview');
      });
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          entry.target.classList.add('is-inview');
          observer.unobserve(entry.target);
        });
      },
      {
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.01
      }
    );

    animatedNodes.forEach(function (node) {
      if (node.classList.contains('is-inview')) {
        return;
      }
      observer.observe(node);
    });
  }

  function initHomeHubTabs() {
    var hubs = Array.prototype.slice.call(
      document.querySelectorAll('[data-home-hub]')
    );
    if (!hubs.length) {
      return;
    }

    hubs.forEach(function (hub) {
      if (!hub || hub.dataset.hubReady === '1') {
        return;
      }
      hub.dataset.hubReady = '1';

      var tabs = Array.prototype.slice.call(
        hub.querySelectorAll('[data-hub-target]')
      );
      var panels = Array.prototype.slice.call(
        hub.querySelectorAll('[data-hub-panel]')
      );
      var trigger = hub.querySelector('[data-hub-trigger]');
      var current = hub.querySelector('[data-hub-current]');
      var options = hub.querySelector('[data-hub-options]');
      if (!tabs.length || !panels.length) {
        return;
      }

      function setExpanded(isOpen) {
        if (!trigger || !options) {
          return;
        }
        hub.classList.toggle('is-open', Boolean(isOpen));
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) {
          options.removeAttribute('hidden');
        } else {
          options.setAttribute('hidden', 'hidden');
        }
      }

      function activate(target, options) {
        var normalizedTarget = String(target || '').trim();
        if (!normalizedTarget) {
          return;
        }
        var shouldScrollTab = Boolean(options && options.scrollTab);
        var activeTab = null;

        var activeLabel = '';
        tabs.forEach(function (tab) {
          var tabTarget = (tab.getAttribute('data-hub-target') || '').trim();
          var isActive = tabTarget === normalizedTarget;
          tab.classList.toggle('is-active', isActive);
          tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
          tab.setAttribute('tabindex', isActive ? '0' : '-1');
          if (isActive) {
            activeTab = tab;
            activeLabel = String(
              tab.getAttribute('data-hub-label') || tab.textContent || ''
            ).replace(/\s+/g, ' ').trim();
          }
        });

        panels.forEach(function (panel) {
          var panelTarget = (panel.getAttribute('data-hub-panel') || '').trim();
          var isActive = panelTarget === normalizedTarget;
          panel.classList.toggle('is-active', isActive);
          if (isActive) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', 'hidden');
          }
        });

        if (current && activeLabel) {
          current.textContent = activeLabel;
        }

        if (
          shouldScrollTab &&
          activeTab &&
          typeof activeTab.scrollIntoView === 'function'
        ) {
          activeTab.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
          });
        }

        setExpanded(false);
      }

      function focusAndActivateByIndex(index, focusTab) {
        if (!tabs.length) {
          return;
        }
        var nextIndex = Number(index);
        if (!Number.isFinite(nextIndex)) {
          nextIndex = 0;
        }
        nextIndex = ((nextIndex % tabs.length) + tabs.length) % tabs.length;
        var nextTab = tabs[nextIndex];
        if (!nextTab) {
          return;
        }
        activate(nextTab.getAttribute('data-hub-target'), {
          scrollTab: true
        });
        if (focusTab && typeof nextTab.focus === 'function') {
          nextTab.focus();
        }
      }

      if (trigger && options) {
        trigger.addEventListener('click', function () {
          var isOpen = trigger.getAttribute('aria-expanded') === 'true';
          setExpanded(!isOpen);
        });
      }

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
          if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
          }
          focusAndActivateByIndex(tabs.indexOf(tab), false);
        });

        tab.addEventListener('keydown', function (event) {
          var key = event.key;
          var index = tabs.indexOf(tab);
          var nextIndex = null;

          if (key === 'ArrowRight' || key === 'ArrowDown') {
            nextIndex = index + 1;
          } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
            nextIndex = index - 1;
          } else if (key === 'Home') {
            nextIndex = 0;
          } else if (key === 'End') {
            nextIndex = tabs.length - 1;
          }

          if (nextIndex === null) {
            return;
          }

          event.preventDefault();
          focusAndActivateByIndex(nextIndex, true);
        });
      });

      document.addEventListener('click', function (event) {
        if (!trigger || !options) {
          return;
        }
        if (hub.contains(event.target)) {
          return;
        }
        setExpanded(false);
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
          return;
        }
        if (!trigger || !options || trigger.getAttribute('aria-expanded') !== 'true') {
          return;
        }
        setExpanded(false);
        if (typeof trigger.focus === 'function') {
          trigger.focus();
        }
      });

      var initial =
        tabs.find(function (tab) {
          return tab.classList.contains('is-active');
        }) || tabs[0];
      activate(initial ? initial.getAttribute('data-hub-target') : '', {
        scrollTab: false
      });
    });
  }

  function initHeroRotators() {
    var rotators = Array.prototype.slice.call(
      document.querySelectorAll('[data-hero-rotator]')
    );
    if (!rotators.length) {
      return;
    }

    rotators.forEach(function (rotator) {
      if (!rotator || rotator.dataset.rotatorReady === '1') {
        return;
      }
      rotator.dataset.rotatorReady = '1';

      var slides = Array.prototype.slice.call(
        rotator.querySelectorAll('[data-hero-slide]')
      );
      var folders = Array.prototype.slice.call(
        rotator.querySelectorAll('[data-hero-folder]')
      );
      if (!slides.length) {
        return;
      }

      var activeIndex = slides.findIndex(function (slide) {
        return slide.classList.contains('is-active');
      });
      if (activeIndex < 0) {
        activeIndex = 0;
      }

      var intervalMs = parseInt(rotator.getAttribute('data-interval'), 10);
      if (!Number.isFinite(intervalMs) || intervalMs < 1500) {
        intervalMs = 5000;
      }

      var timer = null;

      function render(index) {
        if (!slides.length) {
          return;
        }
        var nextIndex = Number(index);
        if (!Number.isFinite(nextIndex)) {
          nextIndex = 0;
        }
        nextIndex = ((nextIndex % slides.length) + slides.length) % slides.length;
        activeIndex = nextIndex;

        slides.forEach(function (slide, i) {
          var isActive = i === activeIndex;
          slide.classList.toggle('is-active', isActive);
          slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        folders.forEach(function (folder, i) {
          var isActive = i === activeIndex;
          folder.classList.toggle('is-active', isActive);
          folder.setAttribute('aria-selected', isActive ? 'true' : 'false');
          folder.setAttribute('tabindex', isActive ? '0' : '-1');
        });
      }

      function stopAuto() {
        if (timer) {
          clearInterval(timer);
          timer = null;
        }
      }

      function startAuto() {
        stopAuto();
        if (slides.length <= 1) {
          return;
        }
        timer = window.setInterval(function () {
          render(activeIndex + 1);
        }, intervalMs);
      }

      folders.forEach(function (folder) {
        folder.addEventListener('click', function () {
          var index = parseInt(folder.getAttribute('data-hero-folder'), 10);
          if (!Number.isFinite(index)) {
            return;
          }
          render(index);
          startAuto();
        });

        folder.addEventListener('keydown', function (event) {
          if (!folders.length) {
            return;
          }

          var key = event.key;
          var current = parseInt(folder.getAttribute('data-hero-folder'), 10);
          if (!Number.isFinite(current)) {
            current = folders.indexOf(folder);
          }

          var next = null;
          if (key === 'ArrowRight' || key === 'ArrowDown') {
            next = current + 1;
          } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
            next = current - 1;
          } else if (key === 'Home') {
            next = 0;
          } else if (key === 'End') {
            next = folders.length - 1;
          }

          if (next === null) {
            return;
          }

          event.preventDefault();
          next = ((next % folders.length) + folders.length) % folders.length;
          render(next);
          startAuto();
          if (folders[next] && typeof folders[next].focus === 'function') {
            folders[next].focus();
          }
        });
      });

      rotator.addEventListener('mouseenter', stopAuto);
      rotator.addEventListener('mouseleave', startAuto);
      rotator.addEventListener('focusin', stopAuto);
      rotator.addEventListener('focusout', function () {
        window.setTimeout(function () {
          if (!rotator.contains(document.activeElement)) {
            startAuto();
          }
        }, 0);
      });

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
          stopAuto();
        } else {
          startAuto();
        }
      });

      render(activeIndex);
      startAuto();
    });
  }

  function initImageOnlyLinkLabels(rootNode) {
    var scope = rootNode && rootNode.querySelectorAll ? rootNode : document;
    var links = Array.prototype.slice.call(scope.querySelectorAll('a'));
    if (!links.length) {
      return;
    }

    links.forEach(function (link) {
      if (!link || link.dataset.a11yReady === '1') {
        return;
      }
      link.dataset.a11yReady = '1';

      var hasLabel =
        (link.getAttribute('aria-label') || '').trim() !== '' ||
        (link.getAttribute('title') || '').trim() !== '';
      if (hasLabel) {
        return;
      }

      var text = (link.textContent || '').replace(/\s+/g, ' ').trim();
      if (text !== '') {
        return;
      }

      var img = link.querySelector('img');
      if (img) {
        var imgAlt = (img.getAttribute('alt') || '').trim();
        if (imgAlt !== '') {
          link.setAttribute('aria-label', 'Xem sản phẩm ' + imgAlt);
          link.setAttribute('title', imgAlt);
          return;
        }
      }

      if (link.classList.contains('woocommerce-product-gallery__trigger')) {
        link.setAttribute('aria-label', 'Phóng to ảnh sản phẩm');
        link.setAttribute('title', 'Phóng to ảnh sản phẩm');
        return;
      }

      if (link.closest('.woocommerce-product-gallery') || link.closest('.woocommerce-product-gallery__image')) {
        link.setAttribute('aria-label', 'Xem ảnh sản phẩm');
        link.setAttribute('title', 'Xem ảnh sản phẩm');
      }
    });
  }

  function initSingleCapacityPicker(picker) {
    if (!picker || picker.dataset.ready === '1') {
      return;
    }
    picker.dataset.ready = '1';

    var inputs = Array.prototype.slice.call(
      picker.querySelectorAll('.capacity-option__input')
    );
    if (!inputs.length) {
      return;
    }

    var priceInput = picker.querySelector('input[name="selected_capacity_price"]');
    var currentLabel = picker.querySelector('[data-capacity-current]');
    var summary = picker.closest('.summary');
    var priceWrap = summary ? summary.querySelector('.price') : null;
    var priceItems = summary
      ? Array.prototype.slice.call(summary.querySelectorAll('.product-pack-prices__item'))
      : [];

    function getLabel(input) {
      if (!input) {
        return null;
      }

      var sibling = input.nextElementSibling;
      return sibling && sibling.classList.contains('capacity-option') ? sibling : null;
    }

    function activate(input) {
      if (!input) {
        return;
      }

      if (syncSingleCapacityState(input)) {
        return;
      }

      inputs.forEach(function (item) {
        item.checked = item === input;
        var label = getLabel(item);
        if (label) {
          label.classList.toggle('is-active', item === input);
        }
      });

      var cap = input.value || input.getAttribute('data-capacity') || '';
      var priceRaw = input.getAttribute('data-price') || '';
      var priceValue = parseAmount(priceRaw);
      var regularPriceValue = parseAmount(input.getAttribute('data-regular-price') || '');

      if (currentLabel) {
        currentLabel.textContent = cap || '-';
      }
      if (priceInput) {
        priceInput.value = priceValue > 0 ? String(priceValue) : '';
      }

      if (priceWrap) {
        priceWrap.innerHTML = renderSinglePriceHtml(priceValue, regularPriceValue);
      }

      if (priceItems.length) {
        priceItems.forEach(function (item) {
          var size = item.getAttribute('data-pack-size') || '';
          setPackItemState(item, size === cap);
        });
      }
    }

    var initial =
      inputs.find(function (item) {
        return item.checked;
      }) || inputs[0];
    activate(initial);

    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (input.checked) {
          activate(input);
        }
      });

      var label = getLabel(input);
      if (label) {
        label.addEventListener('click', function () {
          window.requestAnimationFrame(function () {
            activate(input);
          });
        });
      }
    });
  }

  function initLoopPackForm(form) {
    if (!form || form.dataset.loopReady === '1') {
      return;
    }
    form.dataset.loopReady = '1';

    var inputs = Array.prototype.slice.call(
      form.querySelectorAll('.loop-pack-option__input')
    );
    if (!inputs.length) {
      return;
    }

    var priceInput = form.querySelector('input[name="selected_capacity_price"]');
    var card = form.closest('.product-card');
    var priceWrap = card ? card.querySelector('.product-card__price') : null;
    var priceItems = card
      ? Array.prototype.slice.call(card.querySelectorAll('.product-pack-prices__item'))
      : [];

    function getLabel(input) {
      if (!input) {
        return null;
      }

      var sibling = input.nextElementSibling;
      return sibling && sibling.classList.contains('loop-pack-option') ? sibling : null;
    }

    function activate(input) {
      if (!input) {
        return;
      }

      if (syncLoopPackState(input)) {
        return;
      }

      inputs.forEach(function (item) {
        item.checked = item === input;
        var label = getLabel(item);
        if (label) {
          label.classList.toggle('is-active', item === input);
        }
      });

      var cap = input.value || input.getAttribute('data-capacity') || '';
      var priceRaw = input.getAttribute('data-price') || '';
      var priceValue = parseAmount(priceRaw);
      var regularPriceValue = parseAmount(input.getAttribute('data-regular-price') || '');

      if (priceInput) {
        priceInput.value = priceValue > 0 ? String(priceValue) : '';
      }

      if (priceWrap) {
        priceWrap.innerHTML = renderLoopPriceHtml(priceValue, regularPriceValue);
      }

      if (priceItems.length) {
        priceItems.forEach(function (item) {
          var size = item.getAttribute('data-pack-size') || '';
          setPackItemState(item, size === cap);
        });
      }
    }

    var initial =
      inputs.find(function (item) {
        return item.checked;
      }) || inputs[0];
    activate(initial);

    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (input.checked) {
          activate(input);
        }
      });

      var label = getLabel(input);
      if (label) {
        label.addEventListener('click', function () {
          window.requestAnimationFrame(function () {
            activate(input);
          });
        });
      }
    });

    form.addEventListener('submit', function () {
      var active =
        inputs.find(function (item) {
          return item.checked;
        }) || inputs[0];
      activate(active);
    });
  }

  function initCommerceDelegates() {
    var body = document.body;
    if (!body || body.dataset.commerceDelegatesReady === '1') {
      return;
    }
    body.dataset.commerceDelegatesReady = '1';

    function isElementNode(node) {
      return !!(node && node.nodeType === 1);
    }

    document.addEventListener('change', function (event) {
      var target = event.target;
      if (!isElementNode(target)) {
        return;
      }

      if (target.classList.contains('capacity-option__input')) {
        syncSingleCapacityState(target);
        return;
      }

      if (target.classList.contains('loop-pack-option__input')) {
        syncLoopPackState(target);
      }
    });

    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!isElementNode(target) || typeof target.closest !== 'function') {
        return;
      }

      var packItem = target.closest('.product-pack-prices__item[data-pack-size]');
      if (packItem) {
        var packSize = String(packItem.getAttribute('data-pack-size') || '').trim();
        if (!packSize) {
          return;
        }

        var singleSummary = packItem.closest('.summary');
        if (singleSummary) {
          var singlePicker = singleSummary.querySelector('.capacity-picker');
          if (singlePicker) {
            var singlePackInput = findInputByCapacity(
              singlePicker,
              '.capacity-option__input',
              packSize
            );
            if (singlePackInput) {
              singlePackInput.checked = true;
              dispatchChange(singlePackInput);
              window.requestAnimationFrame(function () {
                syncSingleCapacityState(singlePackInput);
              });
              return;
            }
          }
        }

        var productCard = packItem.closest('.product-card');
        if (productCard) {
          var loopPackForm = productCard.querySelector('.loop-pack-form');
          if (loopPackForm) {
            var loopPackInput = findInputByCapacity(
              loopPackForm,
              '.loop-pack-option__input',
              packSize
            );
            if (loopPackInput) {
              loopPackInput.checked = true;
              dispatchChange(loopPackInput);
              window.requestAnimationFrame(function () {
                syncLoopPackState(loopPackInput);
              });
              return;
            }
          }
        }

        var list = packItem.parentElement;
        if (list) {
          Array.prototype.forEach.call(
            list.querySelectorAll('.product-pack-prices__item[data-pack-size]'),
            function (item) {
              setPackItemState(item, item === packItem);
            }
          );
        }
        return;
      }

      var singleOption = target.closest('.capacity-option');
      if (singleOption) {
        var singleScope = singleOption.closest('.capacity-picker');
        if (!singleScope) {
          return;
        }

        var singleForId = String(singleOption.getAttribute('for') || '').trim();
        var singleInput = null;

        if (singleForId) {
          singleInput = findInputByExactId(
            singleScope,
            '.capacity-option__input',
            singleForId
          );
          if (!singleInput) {
            singleInput = document.getElementById(singleForId);
          }
        }
        if (!singleInput) {
          singleInput = findInputByCapacity(
            singleScope,
            '.capacity-option__input',
            singleOption.getAttribute('data-capacity') || singleOption.textContent
          );
        }

        if (
          singleInput &&
          singleInput.classList &&
          singleInput.classList.contains('capacity-option__input')
        ) {
          singleInput.checked = true;
          dispatchChange(singleInput);
          window.requestAnimationFrame(function () {
            syncSingleCapacityState(singleInput);
          });
          return;
        }

        window.requestAnimationFrame(function () {
          syncSingleCapacityFallback(singleOption, singleScope);
        });
        return;
      }

      var loopOption = target.closest('.loop-pack-option');
      if (!loopOption) {
        return;
      }

      var loopScope = loopOption.closest('.loop-pack-form');
      if (!loopScope) {
        return;
      }

      var loopForId = String(loopOption.getAttribute('for') || '').trim();
      var loopInput = null;

      if (loopForId) {
        loopInput = findInputByExactId(
          loopScope,
          '.loop-pack-option__input',
          loopForId
        );
      }
      if (!loopInput) {
        loopInput = findInputByCapacity(
          loopScope,
          '.loop-pack-option__input',
          loopOption.getAttribute('data-capacity') || loopOption.textContent
        );
      }
      if (!loopInput && loopForId) {
        loopInput = document.getElementById(loopForId);
      }

      if (
        loopInput &&
        loopInput.classList &&
        loopInput.classList.contains('loop-pack-option__input')
      ) {
        loopInput.checked = true;
        dispatchChange(loopInput);
        window.requestAnimationFrame(function () {
          syncLoopPackState(loopInput);
        });
        return;
      }

      window.requestAnimationFrame(function () {
        syncLoopPackFallback(loopOption, loopScope);
      });
    });

    document.addEventListener('keydown', function (event) {
      var target = event.target;
      if (!isElementNode(target) || typeof target.closest !== 'function') {
        return;
      }

      var item = target.closest('.product-pack-prices__item[data-pack-size]');
      if (!item) {
        return;
      }

      var key = event.key;
      if (key !== 'Enter' && key !== ' ' && key !== 'Spacebar') {
        return;
      }

      event.preventDefault();
      if (typeof item.click === 'function') {
        item.click();
      }
    });
  }

  function initAccountAuthForms() {
    var forms = Array.prototype.slice.call(
      document.querySelectorAll(
        '.account-auth form, .woocommerce-account .woocommerce-ResetPassword'
      )
    );
    if (!forms.length) {
      return;
    }

    forms.forEach(function (form) {
      if (!form || form.dataset.authReady === '1') {
        return;
      }
      form.dataset.authReady = '1';

      var submitButtons = Array.prototype.slice.call(
        form.querySelectorAll('button[type="submit"], input[type="submit"]')
      );
      if (!submitButtons.length) {
        return;
      }

      var isSubmitting = false;

      form.addEventListener('submit', function () {
        if (isSubmitting) {
          return;
        }
        isSubmitting = true;
        form.classList.add('is-submitting');
        submitButtons.forEach(function (button) {
          if (!button) {
            return;
          }
          button.disabled = true;
          button.setAttribute('aria-disabled', 'true');
        });
      });

      submitButtons.forEach(function (button) {
        if (!button) {
          return;
        }

        button.addEventListener('click', function (event) {
          if (isSubmitting) {
            event.preventDefault();
          }
        }
      });
    });
  }

  function initSearchAssist() {
    var payload = window.MyThemeSearchAssist;
    var roots = Array.prototype.slice.call(
      document.querySelectorAll('[data-search-assist-root]')
    );

    if (!payload || !roots.length) {
      return;
    }

    function normalizeSearchText(value) {
      if (!value) {
        return '';
      }

      var text = String(value).toLowerCase();
      if (typeof text.normalize === 'function') {
        text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      }

      return text
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9\s-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function buildItemMarkup(item) {
      var label = item && item.label ? item.label : '';
      var url = item && item.url ? item.url : '#';
      var type = item && item.type ? item.type : 'Gợi ý';
      var meta = item && item.meta ? item.meta : '';

      return (
        '<a class="search-assist__item" href="' + escapeHtml(url) + '">' +
          '<span class="search-assist__item-top"><span class="search-assist__badge">' + escapeHtml(type) + '</span></span>' +
          '<strong>' + escapeHtml(label) + '</strong>' +
          (meta ? '<span class="search-assist__meta">' + escapeHtml(meta) + '</span>' : '') +
        '</a>'
      );
    }

    function scoreItem(item, query) {
      var normalizedQuery = normalizeSearchText(query);
      if (!normalizedQuery) {
        return 0;
      }

      var label = normalizeSearchText(item && item.label ? item.label : '');
      var haystack = normalizeSearchText(item && item.search ? item.search : (label + ' ' + (item && item.meta ? item.meta : '')));

      if (!haystack) {
        return 0;
      }
      if (label === normalizedQuery) {
        return 140;
      }
      if (label.indexOf(normalizedQuery) === 0) {
        return 120;
      }
      if (haystack.indexOf(' ' + normalizedQuery) !== -1) {
        return 96;
      }
      if (label.indexOf(normalizedQuery) !== -1) {
        return 88;
      }
      if (haystack.indexOf(normalizedQuery) !== -1) {
        return 72;
      }
      return 0;
    }

    function getMatches(query, limit) {
      var groups = ['products', 'brands', 'lines'];
      var seen = {};
      var matches = [];
      var maxItems = Number(limit);
      if (!Number.isFinite(maxItems) || maxItems <= 0) {
        maxItems = 8;
      }

      groups.forEach(function (groupName) {
        var items = Array.isArray(payload[groupName]) ? payload[groupName] : [];
        items.forEach(function (item) {
          if (!item || !item.url) {
            return;
          }

          var score = scoreItem(item, query);
          if (score <= 0) {
            return;
          }

          var key = String(item.url);
          if (seen[key]) {
            return;
          }

          seen[key] = true;
          matches.push({
            score: score,
            item: item
          });
        });
      });

      matches.sort(function (a, b) {
        if (b.score !== a.score) {
          return b.score - a.score;
        }
        return String(a.item.label || '').localeCompare(String(b.item.label || ''), 'vi');
      });

      return matches.slice(0, maxItems).map(function (entry) {
        return entry.item;
      });
    }

    roots.forEach(function (root) {
      if (!root || root.dataset.searchAssistReady === '1') {
        return;
      }
      root.dataset.searchAssistReady = '1';

      var input = root.querySelector('input[type="search"]');
      var panel = root.querySelector('[data-search-assist-panel]');
      var results = root.querySelector('[data-search-assist-results]');
      var status = root.querySelector('[data-search-assist-status]');
      var context = String(root.getAttribute('data-search-assist-root') || 'header');
      var isHeaderContext = context === 'header';

      if (!input || !panel || !results) {
        return;
      }

      function openPanel() {
        root.classList.add('is-open');
        panel.hidden = false;
        if (isHeaderContext && document.body) {
          document.body.classList.add('header-search-open');
        }
      }

      function closePanel() {
        root.classList.remove('is-open');
        panel.hidden = true;
        if (isHeaderContext && document.body) {
          document.body.classList.remove('header-search-open');
        }
      }

      function render(items, query) {
        var normalizedQuery = normalizeSearchText(query);
        if (!items.length) {
          results.innerHTML = '<p class="search-assist__empty">Không thấy gợi ý phù hợp. Thử tên hãng, hạng mục hoặc mở giải pháp.</p>';
          if (status) {
            status.textContent = normalizedQuery
              ? 'Không thấy kết quả phù hợp trong kho hiện có.'
              : 'Gõ tên mã, hãng hoặc hạng mục để lọc nhanh hơn.';
          }
          return;
        }

        results.innerHTML = items.map(buildItemMarkup).join('');
        if (status) {
          status.textContent = normalizedQuery
            ? 'Hiển thị gợi ý gần nhất theo từ khóa bạn đang gõ.'
            : 'Một vài gợi ý nhanh từ sản phẩm, hãng và hạng mục hiện có.';
        }
      }

      function refresh() {
        var query = input.value || '';
        var normalizedQuery = normalizeSearchText(query);
        var defaultItems = Array.isArray(payload.defaults) ? payload.defaults : [];
        var visibleDefaults = isHeaderContext ? defaultItems.slice(0, 3) : defaultItems;

        if (!normalizedQuery) {
          render(visibleDefaults, '');
          return;
        }

        render(getMatches(normalizedQuery, isHeaderContext ? 4 : 8), normalizedQuery);
      }

      closePanel();

      input.addEventListener('click', function () {
        openPanel();
        refresh();
      });

      input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          closePanel();
          return;
        }

        if (event.ctrlKey || event.metaKey || event.altKey) {
          return;
        }

        if (event.key && event.key.length === 1) {
          openPanel();
        }
      });

      input.addEventListener('input', function () {
        if (!root.classList.contains('is-open')) {
          return;
        }
        refresh();
      });

      document.addEventListener('click', function (event) {
        if (root.contains(event.target)) {
          return;
        }
        closePanel();
      });

      root.addEventListener('focusout', function () {
        window.setTimeout(function () {
          if (!root.contains(document.activeElement)) {
            closePanel();
          }
        }, 0);
      });
    });
  }

  function initShopFilterDrawer() {
    if (!document.body || !document.body.classList.contains('woocommerce-shop')) {
      return;
    }

    var toggle = document.querySelector('[data-shop-filter-toggle]');
    var panel = document.querySelector('[data-shop-filter-panel]');
    var backdrop = document.querySelector('[data-shop-filter-backdrop]');

    if (!toggle || !panel || !backdrop) {
      return;
    }
    if (panel.dataset.shopFilterReady === '1') {
      return;
    }
    panel.dataset.shopFilterReady = '1';

    var mobileQuery = window.matchMedia('(max-width: 980px)');
    document.body.classList.add('shop-filter-ready');

    function setExpanded(isOpen) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function openDrawer() {
      if (!mobileQuery.matches) {
        return;
      }
      panel.classList.add('is-open');
      backdrop.hidden = false;
      backdrop.classList.add('is-open');
      document.body.classList.add('shop-filter-open');
      setExpanded(true);
    }

    function closeDrawer() {
      panel.classList.remove('is-open');
      backdrop.classList.remove('is-open');
      document.body.classList.remove('shop-filter-open');
      setExpanded(false);
      window.setTimeout(function () {
        if (!panel.classList.contains('is-open')) {
          backdrop.hidden = true;
        }
      }, 180);
    }

    toggle.addEventListener('click', function () {
      if (panel.classList.contains('is-open')) {
        closeDrawer();
        return;
      }
      openDrawer();
    });

    Array.prototype.forEach.call(
      panel.querySelectorAll('[data-shop-filter-close]'),
      function (button) {
        button.addEventListener('click', function () {
          closeDrawer();
        });
      }
    );

    backdrop.addEventListener('click', function () {
      closeDrawer();
    });

    panel.addEventListener('click', function (event) {
      if (!mobileQuery.matches) {
        return;
      }
      var target = event.target;
      if (!target || typeof target.closest !== 'function') {
        return;
      }
      var link = target.closest('a');
      if (link) {
        closeDrawer();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }
      if (!panel.classList.contains('is-open')) {
        return;
      }
      closeDrawer();
    });

    window.addEventListener('resize', function () {
      if (mobileQuery.matches) {
        return;
      }
      closeDrawer();
      backdrop.hidden = true;
    });
  }

  function boot() {
    runSafe('initHeaderOffsetVar', initHeaderOffsetVar);
    runSafe('initStickyHeaderState', initStickyHeaderState);
    runSafe('initMainNav', initMainNav);
    runSafe('initSearchAssist', initSearchAssist);
    runSafe('initShopFilterDrawer', initShopFilterDrawer);
    runSafe('initCatalogDock', initCatalogDock);
    runSafe('initHomeHubTabs', initHomeHubTabs);
    runSafe('initHeroRotators', initHeroRotators);
    runSafe('initImageOnlyLinkLabels', function () {
      initImageOnlyLinkLabels(document);
    });
    runSafe('initAccountAuthForms', initAccountAuthForms);
    runWhenIdle(function () {
      runSafe('initRevealMotion', initRevealMotion);
    }, 1400);

    var dynamicCommerceSelector = '.capacity-picker, .loop-pack-form';
    runSafe('initCommerceDelegates', initCommerceDelegates);

    function initCommerceBlocks() {
      runSafe('initSingleCapacityPicker', function () {
        document.querySelectorAll('.capacity-picker').forEach(initSingleCapacityPicker);
      });
      runSafe('initLoopPackForm', function () {
        document.querySelectorAll('.loop-pack-form').forEach(initLoopPackForm);
      });
    }

    runSafe('initCommerceBlocks', initCommerceBlocks);

    if (window.jQuery && window.jQuery.fn && window.jQuery(document.body).on) {
      window.jQuery(document.body).on(
        'updated_wc_div wc_fragments_loaded updated_cart_totals',
        function () {
          runSafe('initCommerceBlocks:jquery', initCommerceBlocks);
        }
      );
    }

    var shouldObserveCommerce = Boolean(
      document.querySelector(dynamicCommerceSelector)
    );
    if (!shouldObserveCommerce || !window.MutationObserver || !document.body) {
      return;
    }

    var observerRoot =
      document.querySelector('.shop-results') ||
      document.querySelector('.products') ||
      document.querySelector('main') ||
      document.body;

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
            node.matches(dynamicCommerceSelector) ||
            node.querySelector(dynamicCommerceSelector)
          );
        });
      });

      if (!shouldReinit) {
        return;
      }

      pending = true;
      window.requestAnimationFrame(function () {
        runSafe('initCommerceBlocks:observer', initCommerceBlocks);
        runSafe('initImageOnlyLinkLabels:observer', function () {
          initImageOnlyLinkLabels(document);
        });
        pending = false;
      });
    });

    observer.observe(observerRoot, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
