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
      if (!mobileQuery.matches) {
        return;
      }
      if (!nav.classList.contains('is-open')) {
        return;
      }

      var target = event.target;
      if (!target || typeof target.closest !== 'function') {
        return;
      }
      var link = target.closest('a');
      if (!link) {
        return;
      }
      if (link.matches('.menu-item-has-children > a')) {
        return;
      }

      closeMainNav();
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
      if (!tabs.length || !panels.length) {
        return;
      }

      function activate(target) {
        var normalizedTarget = String(target || '').trim();
        if (!normalizedTarget) {
          return;
        }

        tabs.forEach(function (tab) {
          var tabTarget = (tab.getAttribute('data-hub-target') || '').trim();
          var isActive = tabTarget === normalizedTarget;
          tab.classList.toggle('is-active', isActive);
          tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
          tab.setAttribute('tabindex', isActive ? '0' : '-1');
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
        activate(nextTab.getAttribute('data-hub-target'));
        if (focusTab && typeof nextTab.focus === 'function') {
          nextTab.focus();
        }
      }

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
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

      var initial =
        tabs.find(function (tab) {
          return tab.classList.contains('is-active');
        }) || tabs[0];
      activate(initial ? initial.getAttribute('data-hub-target') : '');
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
      }

      function closePanel() {
        root.classList.remove('is-open');
        panel.hidden = true;
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

  function boot() {
    initStickyHeaderState();
    initMainNav();
    initSearchAssist();
    initCatalogDock();
    initHomeHubTabs();
    initHeroRotators();
    initImageOnlyLinkLabels(document);
    initAccountAuthForms();
    runWhenIdle(initRevealMotion, 1400);

    var dynamicCommerceSelector = '.capacity-picker, .loop-pack-form';

    function initCommerceBlocks() {
      document.querySelectorAll('.capacity-picker').forEach(initSingleCapacityPicker);
      document.querySelectorAll('.loop-pack-form').forEach(initLoopPackForm);
    }

    initCommerceBlocks();

    if (window.jQuery && window.jQuery.fn && window.jQuery(document.body).on) {
      window.jQuery(document.body).on(
        'updated_wc_div wc_fragments_loaded updated_cart_totals',
        initCommerceBlocks
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
        initCommerceBlocks();
        initImageOnlyLinkLabels(document);
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
