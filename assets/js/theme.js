if (window.NodeList && !window.NodeList.prototype.forEach) {
  window.NodeList.prototype.forEach = Array.prototype.forEach;
}

document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.dv-header');
  var adminBar = document.getElementById('wpadminbar');
  var navigation = document.querySelector('.dv-main-menu');
  var blogCategorySelect = document.querySelector('[data-dv-blog-category]');
  var errorScene = document.querySelector('[data-dv-404-scene]');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var addMediaChangeListener = function (mediaQuery, callback) {
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', callback);
    } else if (mediaQuery.addListener) {
      mediaQuery.addListener(callback);
    }
  };

  document.querySelectorAll('.dv-managed-home-section--testimonials').forEach(function (section) {
    var carousel = section.querySelector('.dv-testimonials');
    var editorHint = section.querySelector('.dv-dynamic-hint');

    if (!carousel && !editorHint) {
      section.hidden = true;
      return;
    }

    if (!carousel) {
      section.classList.add('dv-managed-home-section--empty-state');
    }
  });

  if (blogCategorySelect) {
    blogCategorySelect.addEventListener('change', function () {
      if (blogCategorySelect.value) {
        window.location.assign(blogCategorySelect.value);
      }
    });
  }

  document.querySelectorAll('[data-dv-service-load-more]').forEach(function (button) {
    var grid = document.getElementById(button.getAttribute('aria-controls'));
    var status = button.parentNode.querySelector('[data-dv-service-load-status]');
    var batchSize = parseInt(button.getAttribute('data-batch-size'), 10) || 6;

    if (!grid) {
      return;
    }

    var serviceItems = Array.prototype.slice.call(grid.querySelectorAll('[data-dv-service-load-item]'));
    var updateServiceCount = function () {
      var visibleItems = serviceItems.filter(function (item) {
        return !item.hidden;
      });
      var isComplete = visibleItems.length >= serviceItems.length;

      button.hidden = isComplete;
      button.setAttribute('aria-expanded', isComplete ? 'true' : 'false');
    };

    updateServiceCount();
    button.addEventListener('click', function () {
      var nextItems = serviceItems.filter(function (item) {
        return item.hidden;
      }).slice(0, batchSize);

      nextItems.forEach(function (item, index) {
        item.hidden = false;
        if (!reducedMotion) {
          window.setTimeout(function () {
            item.classList.add('is-revealed');
          }, index * 55);
        }
      });

      updateServiceCount();
      if (status) {
        status.textContent = 'Mais soluções exibidas.';
      }
      if (nextItems.length) {
        nextItems[0].setAttribute('tabindex', '-1');
        nextItems[0].focus({ preventScroll: true });
        nextItems[0].removeAttribute('tabindex');
      }
    });
  });

  if (errorScene && !reducedMotion && window.matchMedia('(pointer: fine)').matches) {
    errorScene.addEventListener('pointermove', function (event) {
      var sceneBox = errorScene.getBoundingClientRect();
      var sceneX = ((event.clientX - sceneBox.left) / sceneBox.width - 0.5) * 2;
      var sceneY = ((event.clientY - sceneBox.top) / sceneBox.height - 0.5) * 2;

      errorScene.style.setProperty('--dv-404-rotate-x', (sceneY * -2.5).toFixed(3) + 'deg');
      errorScene.style.setProperty('--dv-404-rotate-y', (sceneX * 3).toFixed(3) + 'deg');
    });

    errorScene.addEventListener('pointerleave', function () {
      errorScene.style.setProperty('--dv-404-rotate-x', '0deg');
      errorScene.style.setProperty('--dv-404-rotate-y', '0deg');
    });
  }

  if (header) {
    var updateHeader = function () {
      var adminBarStyles = adminBar ? window.getComputedStyle(adminBar) : null;
      var adminBarBox = adminBar ? adminBar.getBoundingClientRect() : null;
      var adminBarRendered = Boolean(
        adminBar &&
        adminBarStyles &&
        adminBarStyles.display !== 'none' &&
        adminBarStyles.visibility !== 'hidden' &&
        adminBarBox &&
        adminBarBox.width > 0 &&
        adminBarBox.height > 0
      );
      var adminBarVisible = Boolean(
        adminBarRendered &&
        adminBarBox.bottom > 0 &&
        adminBarBox.top < window.innerHeight
      );
      var adminBarFixed = Boolean(adminBarRendered && adminBarStyles.position === 'fixed');
      var headerBox = header.getBoundingClientRect();
      var headerReachedAdminBar = Boolean(
        adminBarVisible &&
        adminBarFixed &&
        window.scrollY > 0 &&
        headerBox.top <= adminBarBox.bottom + 1
      );

      document.documentElement.classList.toggle(
        'dv-admin-bar-placeholder',
        document.body.classList.contains('admin-bar') && !adminBarRendered
      );
      document.body.classList.toggle('dv-admin-bar-visible', adminBarVisible);
      document.body.classList.toggle(
        'dv-admin-bar-sticky',
        headerReachedAdminBar
      );
      header.classList.toggle('dv-header-scrolled', window.scrollY > 18);
    };
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
    window.addEventListener('resize', updateHeader);
  }

  if (navigation && navigation.classList.contains('dv-wordpress-menu')) {
    var managedPanel = navigation.querySelector('.dv-menu-offcanvas');
    var managedOpenButton = navigation.querySelector('.dv-menu-toggle');
    var managedCloseButton = navigation.querySelector('.dv-menu-close');
    var managedMobileMenu = window.matchMedia('(max-width: 920px)');
    var managedInstance = managedPanel && window.bootstrap && window.bootstrap.Offcanvas
      ? window.bootstrap.Offcanvas.getOrCreateInstance(managedPanel, { backdrop: true, keyboard: true, scroll: false })
      : null;

    var setManagedMenuState = function (isOpen) {
      document.body.classList.toggle('dv-menu-open', isOpen);
      if (managedOpenButton) {
        managedOpenButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      }
    };

    var collapseManagedSubmenus = function () {
      navigation.querySelectorAll('.dv-submenu-toggle').forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
      });
      navigation.querySelectorAll('.dv-submenu-open').forEach(function (item) {
        item.classList.remove('dv-submenu-open');
      });
    };

    var showManagedMenu = function () {
      if (!managedPanel || !managedMobileMenu.matches) {
        return;
      }
      setManagedMenuState(true);
      if (managedInstance) {
        managedInstance.show();
      } else {
        managedPanel.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (managedCloseButton) {
          managedCloseButton.focus();
        }
      }
    };

    var hideManagedMenu = function () {
      if (!managedPanel) {
        return;
      }
      if (managedInstance) {
        managedInstance.hide();
      } else {
        managedPanel.classList.remove('show');
        setManagedMenuState(false);
        document.body.style.overflow = '';
        collapseManagedSubmenus();
      }
    };

    navigation.querySelectorAll('.menu-item-has-children').forEach(function (item, index) {
      var children = Array.prototype.slice.call(item.children);
      var submenu = null;
      var parentLink = null;
      var existingToggle = null;

      children.forEach(function (child) {
        if (!submenu && child.classList.contains('sub-menu')) {
          submenu = child;
        }
        if (!parentLink && child.tagName === 'A') {
          parentLink = child;
        }
        if (!existingToggle && child.classList.contains('dv-submenu-toggle')) {
          existingToggle = child;
        }
      });

      if (!submenu || existingToggle) {
        return;
      }

      var submenuId = 'dv-submenu-' + (index + 1);
      var submenuButton = document.createElement('button');
      submenu.id = submenuId;
      submenuButton.type = 'button';
      submenuButton.className = 'dv-submenu-toggle';
      submenuButton.setAttribute('aria-controls', submenuId);
      submenuButton.setAttribute('aria-expanded', 'false');
      submenuButton.setAttribute('aria-label', 'Abrir submenu de ' + (parentLink ? parentLink.textContent.trim() : 'navegação'));
      item.insertBefore(submenuButton, submenu);

      submenuButton.addEventListener('click', function () {
        var willOpen = !item.classList.contains('dv-submenu-open');
        item.classList.toggle('dv-submenu-open', willOpen);
        submenuButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        submenuButton.setAttribute('aria-label', (willOpen ? 'Fechar submenu de ' : 'Abrir submenu de ') + (parentLink ? parentLink.textContent.trim() : 'navegação'));
      });

      submenuButton.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && item.classList.contains('dv-submenu-open')) {
          item.classList.remove('dv-submenu-open');
          submenuButton.setAttribute('aria-expanded', 'false');
          submenuButton.focus();
        }
      });

      if (parentLink && parentLink.getAttribute('href') === '#') {
        parentLink.addEventListener('click', function (event) {
          if (managedMobileMenu.matches) {
            event.preventDefault();
            submenuButton.click();
          }
        });
      }
    });

    if (managedOpenButton) {
      managedOpenButton.addEventListener('click', showManagedMenu);
    }
    if (managedCloseButton) {
      managedCloseButton.addEventListener('click', hideManagedMenu);
    }

    if (managedPanel) {
      managedPanel.addEventListener('show.bs.offcanvas', function () {
        setManagedMenuState(true);
      });
      managedPanel.addEventListener('shown.bs.offcanvas', function () {
        if (managedCloseButton) {
          managedCloseButton.focus();
        }
      });
      managedPanel.addEventListener('hidden.bs.offcanvas', function () {
        setManagedMenuState(false);
        collapseManagedSubmenus();
      });
    }

    navigation.querySelectorAll('.dv-menu-list a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (managedMobileMenu.matches && link.getAttribute('href') !== '#') {
          hideManagedMenu();
        }
      });
    });

    var syncManagedMenu = function (event) {
      if (!event.matches) {
        hideManagedMenu();
        collapseManagedSubmenus();
        setManagedMenuState(false);
        if (managedPanel) {
          managedPanel.removeAttribute('style');
        }
      }
    };
    addMediaChangeListener(managedMobileMenu, syncManagedMenu);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && managedPanel && managedPanel.classList.contains('show') && !managedInstance) {
        hideManagedMenu();
        managedOpenButton.focus();
      }
    });
  } else if (navigation && window.bootstrap && window.bootstrap.Offcanvas) {
    var panel = navigation.querySelector('.wp-block-navigation__responsive-container');
    var openButton = navigation.querySelector('.wp-block-navigation__responsive-container-open');
    var closeButton = navigation.querySelector('.wp-block-navigation__responsive-container-close');
    var mobileMenu = window.matchMedia('(max-width: 920px)');
    var panelId = 'dv-menu-offcanvas';

    var closeOffcanvas = function () {
      var instance = window.bootstrap.Offcanvas.getInstance(panel);
      if (instance) {
        instance.hide();
      }
    };

    var clearWordPressMenuState = function () {
      panel.classList.remove('is-menu-open', 'has-modal-open');
      document.body.classList.remove('has-modal-open');
      document.documentElement.classList.remove('has-modal-open');
      openButton.setAttribute('aria-expanded', 'false');
    };

    var configureOffcanvas = function (event) {
      var isMobile = event.matches;

      if (isMobile) {
        panel.id = panelId;
        panel.classList.add('offcanvas', 'offcanvas-end', 'dv-offcanvas');
        panel.setAttribute('tabindex', '-1');
        panel.setAttribute('aria-label', 'Menu principal');
        openButton.setAttribute('data-bs-toggle', 'offcanvas');
        openButton.setAttribute('data-bs-target', '#' + panelId);
        openButton.setAttribute('aria-controls', panelId);
        openButton.setAttribute('aria-expanded', 'false');
        closeButton.setAttribute('data-bs-dismiss', 'offcanvas');
        closeButton.setAttribute('aria-label', 'Fechar menu');
        return;
      }

      closeOffcanvas();
      clearWordPressMenuState();
      panel.classList.remove('offcanvas', 'offcanvas-end', 'dv-offcanvas', 'show', 'showing', 'hiding');
      panel.removeAttribute('tabindex');
      panel.removeAttribute('aria-label');
      panel.removeAttribute('style');
      openButton.removeAttribute('data-bs-toggle');
      openButton.removeAttribute('data-bs-target');
      closeButton.removeAttribute('data-bs-dismiss');
      document.querySelectorAll('.offcanvas-backdrop').forEach(function (backdrop) {
        backdrop.remove();
      });
    };

    panel.addEventListener('show.bs.offcanvas', function () {
      document.body.classList.add('dv-menu-open');
      openButton.setAttribute('aria-expanded', 'true');
    });

    panel.addEventListener('hidden.bs.offcanvas', function () {
      document.body.classList.remove('dv-menu-open');
      clearWordPressMenuState();
    });

    navigation.querySelectorAll('.wp-block-navigation-item__content').forEach(function (link) {
      link.addEventListener('click', function () {
        var parentItem = link.parentElement;
        var opensSubmenu = parentItem && parentItem.classList.contains('wp-block-navigation-submenu');

        if (mobileMenu.matches && !opensSubmenu) {
          closeOffcanvas();
        }
      });
    });

    configureOffcanvas(mobileMenu);
    addMediaChangeListener(mobileMenu, configureOffcanvas);
  }

  document.querySelectorAll('.dv-hero-visual').forEach(function (heroVisual) {
    if (!reducedMotion && window.matchMedia('(pointer: fine)').matches) {
      heroVisual.addEventListener('pointermove', function (event) {
        var bounds = heroVisual.getBoundingClientRect();
        var rotateY = ((event.clientX - bounds.left) / bounds.width - 0.5) * 7;
        var rotateX = ((event.clientY - bounds.top) / bounds.height - 0.5) * -7;
        heroVisual.style.transform = 'rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
      });
      heroVisual.addEventListener('pointerleave', function () {
        heroVisual.style.transform = '';
      });
    }
  });

  if (window.Swiper) {
    var swiperA11y = {
      enabled: true,
      prevSlideMessage: 'Slide anterior',
      nextSlideMessage: 'Próximo slide',
      firstSlideMessage: 'Este é o primeiro slide',
      lastSlideMessage: 'Este é o último slide',
      paginationBulletMessage: 'Ir para o slide {{index}}'
    };

    var getSlideCount = function (element) {
      var wrapper = element.querySelector('.swiper-wrapper');
      return wrapper ? wrapper.children.length : 0;
    };

    var getLoopSlidesPerView = function (slideCount, desiredSlides) {
      if (slideCount < 2) {
        return 1;
      }
      return Math.min(desiredSlides, Math.max(1, Math.floor(slideCount / 2)));
    };

    var ensureLoopCapacity = function (element, minimumSlides) {
      var wrapper = element.querySelector('.swiper-wrapper');
      var originals = wrapper ? Array.prototype.slice.call(wrapper.children) : [];
      if (!wrapper || originals.length < 2 || originals.length >= minimumSlides) {
        return originals.length;
      }

      var copyIndex = 0;
      while (wrapper.children.length < minimumSlides) {
        var copy = originals[copyIndex % originals.length].cloneNode(true);
        copy.setAttribute('data-dv-loop-copy', 'true');
        copy.setAttribute('aria-hidden', 'true');
        copy.querySelectorAll('a, button, input, select, textarea').forEach(function (control) {
          control.setAttribute('tabindex', '-1');
        });
        wrapper.appendChild(copy);
        copyIndex += 1;
      }
      element.classList.add('dv-swiper-loop-copies');
      return originals.length;
    };

    var getAutoplay = function (element, fallbackDelay) {
      var enabled = element.getAttribute('data-dv-autoplay') !== 'false';
      var delay = parseInt(element.getAttribute('data-dv-delay'), 10) || fallbackDelay;
      if (reducedMotion || !enabled || getSlideCount(element) < 2) {
        return false;
      }
      return {
        delay: delay,
        disableOnInteraction: false,
        pauseOnMouseEnter: false
      };
    };

    document.querySelectorAll('.dv-hero-swiper.swiper').forEach(function (element) {
      var slideCount = getSlideCount(element);
      if (!slideCount) {
        return;
      }
      var autoplay = getAutoplay(element, 5000);
      var heroSwiper = new window.Swiper(element, {
        slidesPerView: 1,
        spaceBetween: slideCount > 1 ? 20 : 0,
        speed: reducedMotion ? 1 : 1000,
        loop: slideCount > 1,
        effect: 'slide',
        grabCursor: slideCount > 1,
        watchOverflow: true,
        observer: true,
        observeParents: true,
        keyboard: { enabled: true, onlyInViewport: true },
        a11y: swiperA11y,
        navigation: {
          prevEl: element.querySelector('.dv-swiper-prev'),
          nextEl: element.querySelector('.dv-swiper-next')
        },
        pagination: {
          el: element.querySelector('.dv-swiper-pagination'),
          clickable: true
        },
        autoplay: autoplay
      });
      if (slideCount < 2) {
        element.classList.add('dv-swiper-static');
      }
    });

    document.querySelectorAll('.dv-client-swiper.swiper').forEach(function (element) {
      var realSlideCount = getSlideCount(element);
      if (!realSlideCount) {
        return;
      }
      ensureLoopCapacity(element, Math.max(6, realSlideCount * 2));
      var slideCount = getSlideCount(element);
      var autoplay = getAutoplay(element, 2400);
      var clientSwiper = new window.Swiper(element, {
        slidesPerView: getLoopSlidesPerView(slideCount, 1.35),
        spaceBetween: 10,
        speed: reducedMotion ? 1 : 700,
        loop: slideCount > 1,
        grabCursor: slideCount > 1,
        watchOverflow: true,
        observer: true,
        observeParents: true,
        keyboard: { enabled: true, onlyInViewport: true },
        a11y: swiperA11y,
        navigation: {
          prevEl: element.querySelector('.dv-swiper-prev'),
          nextEl: element.querySelector('.dv-swiper-next')
        },
        pagination: {
          el: element.querySelector('.dv-swiper-pagination'),
          clickable: true
        },
        autoplay: autoplay,
        breakpoints: {
          360: { slidesPerView: getLoopSlidesPerView(slideCount, 1.8), spaceBetween: 10 },
          480: { slidesPerView: getLoopSlidesPerView(slideCount, 2.35), spaceBetween: 12 },
          640: { slidesPerView: getLoopSlidesPerView(slideCount, 3), spaceBetween: 12 },
          782: { slidesPerView: getLoopSlidesPerView(slideCount, 3.6), spaceBetween: 14 },
          1024: { slidesPerView: getLoopSlidesPerView(slideCount, 4.6), spaceBetween: 14 },
          1280: { slidesPerView: getLoopSlidesPerView(slideCount, 5.4), spaceBetween: 16 }
        }
      });
      if (realSlideCount < 2) {
        element.classList.add('dv-swiper-static');
      }
    });

    document.querySelectorAll('.dv-software-swiper.swiper').forEach(function (element) {
      var realSoftwareCount = getSlideCount(element);
      if (!realSoftwareCount) {
        return;
      }

      ensureLoopCapacity(element, 8);
      var softwareSlideCount = getSlideCount(element);
      var softwareAutoplay = getAutoplay(element, 3000);
      element.dvSoftwareSwiper = new window.Swiper(element, {
        slidesPerView: 'auto',
        spaceBetween: 14,
        speed: reducedMotion ? 1 : 650,
        loop: softwareSlideCount > 1,
        grabCursor: softwareSlideCount > 1,
        watchOverflow: true,
        observer: true,
        observeParents: true,
        keyboard: { enabled: true, onlyInViewport: true },
        a11y: swiperA11y,
        navigation: {
          prevEl: element.querySelector('.dv-swiper-prev'),
          nextEl: element.querySelector('.dv-swiper-next')
        },
        pagination: {
          el: element.querySelector('.dv-swiper-pagination'),
          clickable: true
        },
        autoplay: softwareAutoplay,
        breakpoints: {
          480: { spaceBetween: 16 },
          640: { spaceBetween: 18 },
          782: { spaceBetween: 20 },
          1024: { spaceBetween: 22 },
          1180: { spaceBetween: 24 }
        }
      });

      if (realSoftwareCount < 2) {
        element.classList.add('dv-swiper-static');
      }
    });

    /*
     * Portfolio Scroll container: one tall, freely scrollable Swiper slide.
     * The slide can contain responsive images and embedded PDF documents.
     */
    document.querySelectorAll('.dv-project-scroll-swiper.swiper').forEach(function (element) {
      var scrollSlide = element.querySelector('.dv-project-scroll__slide');
      var scrollbar = element.querySelector('.swiper-scrollbar');

      if (!scrollSlide || element.dvProjectScrollSwiper) {
        return;
      }

      element.dvProjectScrollSwiper = new window.Swiper(element, {
        direction: 'vertical',
        slidesPerView: 'auto',
        freeMode: {
          enabled: true,
          momentum: !reducedMotion,
          momentumBounce: false,
          sticky: false
        },
        mousewheel: {
          enabled: true,
          forceToAxis: false,
          releaseOnEdges: true,
          sensitivity: 0.8
        },
        speed: reducedMotion ? 1 : 500,
        grabCursor: true,
        nested: true,
        resistanceRatio: 0.65,
        touchReleaseOnEdges: true,
        observer: true,
        observeParents: true,
        resizeObserver: true,
        keyboard: { enabled: true, onlyInViewport: true },
        a11y: swiperA11y,
        scrollbar: {
          el: scrollbar,
          draggable: true,
          hide: false,
          snapOnRelease: false
        }
      });

      element.querySelectorAll('img, iframe').forEach(function (media) {
        media.addEventListener('load', function () {
          if (element.dvProjectScrollSwiper) {
            element.dvProjectScrollSwiper.update();
          }
        });
      });
    });

    document.querySelectorAll('.dv-testimonials.swiper').forEach(function (element) {
      var slideCount = getSlideCount(element);
      if (!slideCount) {
        return;
      }
      var autoplay = reducedMotion || slideCount < 2 ? false : {
        delay: 5500,
        disableOnInteraction: false,
        pauseOnMouseEnter: true
      };
      var testimonialSwiper = new window.Swiper(element, {
        slidesPerView: 1,
        spaceBetween: 18,
        speed: reducedMotion ? 1 : 650,
        loop: slideCount > 1,
        grabCursor: slideCount > 1,
        watchOverflow: true,
        observer: true,
        observeParents: true,
        keyboard: { enabled: true, onlyInViewport: true },
        a11y: swiperA11y,
        navigation: {
          prevEl: element.querySelector('.dv-swiper-prev'),
          nextEl: element.querySelector('.dv-swiper-next')
        },
        pagination: {
          el: element.querySelector('.dv-swiper-pagination'),
          clickable: true
        },
        autoplay: autoplay,
        breakpoints: { 782: { slidesPerView: getLoopSlidesPerView(slideCount, 1.25) } }
      });
      if (slideCount < 2) {
        element.classList.add('dv-swiper-static');
      }
    });

    var servicesMobile = window.matchMedia('(max-width: 920px)');
    var serviceSliders = document.querySelectorAll('.dv-services-swiper.swiper');

    var syncServiceSliders = function () {
      serviceSliders.forEach(function (element) {
        if (servicesMobile.matches && !element.dvServicesSwiper) {
          var serviceSlideCount = getSlideCount(element);
          var serviceAutoplay = getAutoplay(element, 4200);
          element.dvServicesSwiper = new window.Swiper(element, {
            slidesPerView: getLoopSlidesPerView(serviceSlideCount, 1.12),
            spaceBetween: 18,
            speed: reducedMotion ? 1 : 650,
            loop: serviceSlideCount > 1,
            grabCursor: serviceSlideCount > 1,
            watchOverflow: true,
            observer: true,
            observeParents: true,
            keyboard: { enabled: true, onlyInViewport: true },
            a11y: swiperA11y,
            navigation: {
              prevEl: element.querySelector('.dv-swiper-prev'),
              nextEl: element.querySelector('.dv-swiper-next')
            },
            pagination: {
              el: element.querySelector('.dv-swiper-pagination'),
              clickable: true
            },
            autoplay: serviceAutoplay,
            breakpoints: {
              480: { slidesPerView: Math.min(serviceSlideCount, 1.28), spaceBetween: 20 },
              640: { slidesPerView: Math.min(serviceSlideCount, 1.5), spaceBetween: 22 },
              700: { slidesPerView: Math.min(serviceSlideCount, 2), spaceBetween: 24 }
            }
          });
          if (serviceSlideCount < 2) {
            element.classList.add('dv-swiper-static');
          }
        } else if (!servicesMobile.matches && element.dvServicesSwiper) {
          element.dvServicesSwiper.destroy(true, true);
          element.dvServicesSwiper = null;
          element.classList.remove('dv-swiper-static');
        }
      });
    };

    syncServiceSliders();
    addMediaChangeListener(servicesMobile, syncServiceSliders);

    var portfolioMobile = window.matchMedia('(max-width: 920px)');
    var portfolioSliders = document.querySelectorAll('.dv-portfolio-home__slider.swiper');

    var syncPortfolioSliders = function () {
      portfolioSliders.forEach(function (element) {
        if (portfolioMobile.matches && !element.dvPortfolioSwiper) {
          var portfolioSlideCount = getSlideCount(element);
          if (!portfolioSlideCount) {
            return;
          }
          var portfolioAutoplay = reducedMotion || portfolioSlideCount < 2 ? false : {
            delay: 4200,
            disableOnInteraction: false,
            pauseOnMouseEnter: false
          };
          element.dvPortfolioSwiper = new window.Swiper(element, {
            slidesPerView: getLoopSlidesPerView(portfolioSlideCount, 1.08),
            spaceBetween: 14,
            speed: reducedMotion ? 1 : 650,
            loop: portfolioSlideCount > 1,
            grabCursor: portfolioSlideCount > 1,
            watchOverflow: true,
            observer: true,
            observeParents: true,
            keyboard: { enabled: true, onlyInViewport: true },
            a11y: swiperA11y,
            navigation: {
              prevEl: element.querySelector('.dv-swiper-prev'),
              nextEl: element.querySelector('.dv-swiper-next')
            },
            pagination: {
              el: element.querySelector('.dv-swiper-pagination'),
              clickable: true
            },
            autoplay: portfolioAutoplay,
            breakpoints: {
              480: { slidesPerView: getLoopSlidesPerView(portfolioSlideCount, 1.35), spaceBetween: 16 },
              640: { slidesPerView: getLoopSlidesPerView(portfolioSlideCount, 1.8), spaceBetween: 18 }
            }
          });
          if (portfolioSlideCount < 2) {
            element.classList.add('dv-swiper-static');
          }
        } else if (!portfolioMobile.matches && element.dvPortfolioSwiper) {
          element.dvPortfolioSwiper.destroy(true, true);
          element.dvPortfolioSwiper = null;
          element.classList.remove('dv-swiper-static');
        }
      });
    };

    syncPortfolioSliders();
    addMediaChangeListener(portfolioMobile, syncPortfolioSliders);
  }

  document.querySelectorAll('[data-dv-portfolio-load-more]').forEach(function (button) {
    var portfolio = button.closest('[data-dv-portfolio]');
    var wrapper = button.closest('.dv-portfolio-load-more');
    var grid = portfolio ? portfolio.querySelector('#dv-portfolio-results') : null;
    var status = wrapper ? wrapper.querySelector('[data-dv-load-more-status]') : null;
    var label = button.querySelector('[data-dv-load-more-label]');

    if (!window.fetch || !grid || !label) {
      return;
    }

    button.addEventListener('click', function (event) {
      if (button.getAttribute('data-ajax-failed') === 'true') {
        return;
      }

      event.preventDefault();
      if (button.getAttribute('aria-busy') === 'true') {
        return;
      }

      button.setAttribute('aria-busy', 'true');
      button.classList.add('is-loading');
      label.textContent = 'Carregando…';
      if (status) {
        status.textContent = 'Buscando mais projetos.';
      }

      var requestBody = [
        'action=diniz_studio_portfolio_load_more',
        'nonce=' + encodeURIComponent(button.getAttribute('data-nonce') || ''),
        'page=' + encodeURIComponent(button.getAttribute('data-page') || '2'),
        'sector=' + encodeURIComponent(button.getAttribute('data-sector') || '')
      ].join('&');

      window.fetch(button.getAttribute('data-ajax-url'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: requestBody
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Portfolio request failed');
          }
          return response.json();
        })
        .then(function (payload) {
          if (!payload.success || !payload.data) {
            throw new Error('Portfolio response failed');
          }

          if (payload.data.html) {
            grid.insertAdjacentHTML('beforeend', payload.data.html);
          }

          button.setAttribute('data-page', String(payload.data.next_page));
          button.href = button.href.replace(
            /([?&]portfolio_page=)\d+/,
            '$1' + payload.data.next_page
          );

          if (status) {
            status.textContent = payload.data.message || '';
          }

          if (!payload.data.has_more) {
            button.hidden = true;
            if (wrapper) {
              wrapper.classList.add('is-complete');
            }
          }
        })
        .catch(function () {
          button.setAttribute('data-ajax-failed', 'true');
          if (status) {
            status.textContent = 'Não foi possível carregar agora. Clique novamente para abrir a próxima página.';
          }
        })
        .then(function () {
          button.removeAttribute('aria-busy');
          button.classList.remove('is-loading');
          label.textContent = 'Ver mais projetos';
        });
    });
  });

  document.querySelectorAll('[data-dv-portfolio]').forEach(function (portfolio) {
    var filterButtons = portfolio.querySelectorAll('[data-dv-portfolio-filter]');
    var projectCards = portfolio.querySelectorAll('[data-dv-portfolio-card]');

    if (!filterButtons.length || !projectCards.length) {
      return;
    }

    var updatePortfolio = function (selectedFilter, activeButton) {
      projectCards.forEach(function (card) {
        var sectors = (card.getAttribute('data-dv-sectors') || '').split(/\s+/);
        var isVisible = selectedFilter === '*' || sectors.indexOf(selectedFilter) !== -1;
        card.hidden = !isVisible;
        card.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
      });

      filterButtons.forEach(function (button) {
        var isActive = button === activeButton;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    };

    filterButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        updatePortfolio(button.getAttribute('data-dv-portfolio-filter') || '*', button);
      });
    });
  });

  document.querySelectorAll('.dv-entry-content[data-dv-toc="true"]').forEach(function (content) {
    var layout = content.closest('.dv-cpt-single__layout');
    var toc = layout ? layout.querySelector('.dv-article-toc') : null;
    var list = toc ? toc.querySelector('ol') : null;
    var headings = content.querySelectorAll('h2, h3');
    var usedIds = {};

    if (!list || !headings.length) {
      if (toc) {
        toc.hidden = true;
      }
      return;
    }

    var tocCount = toc.querySelector('[data-dv-toc-count]');
    if (tocCount) {
      tocCount.textContent = '· ' + headings.length + (headings.length === 1 ? ' tópico' : ' tópicos');
    }

    headings.forEach(function (heading, index) {
      var baseId = heading.id || heading.textContent
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '') || 'secao-' + (index + 1);
      var headingId = baseId;
      var duplicate = 2;
      var existing = document.getElementById(headingId);

      while (usedIds[headingId] || (existing && existing !== heading)) {
        headingId = baseId + '-' + duplicate;
        duplicate += 1;
        existing = document.getElementById(headingId);
      }

      usedIds[headingId] = true;
      heading.id = headingId;

      var item = document.createElement('li');
      var link = document.createElement('a');
      link.href = '#' + headingId;
      link.textContent = heading.textContent;
      if (heading.tagName === 'H3') {
        item.classList.add('dv-article-toc__subitem');
      }
      item.appendChild(link);
      list.appendChild(item);
    });

    toc.hidden = false;

    var tocLinks = toc.querySelectorAll('a');
    var activateTocLink = function (headingId) {
      tocLinks.forEach(function (link) {
        var isActive = link.getAttribute('href') === '#' + headingId;
        link.classList.toggle('is-active', isActive);
        if (isActive) {
          link.setAttribute('aria-current', 'location');
        } else {
          link.removeAttribute('aria-current');
        }
      });
    };

    tocLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        var targetId = link.getAttribute('href').slice(1);
        var target = document.getElementById(targetId);

        if (!target) {
          return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
        activateTocLink(targetId);

        if (window.history && window.history.pushState) {
          window.history.pushState(null, '', '#' + targetId);
        }
      });
    });

    activateTocLink(headings[0].id);

    if ('IntersectionObserver' in window) {
      var tocObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            activateTocLink(entry.target.id);
          }
        });
      }, { rootMargin: '-18% 0px -68% 0px', threshold: 0 });

      headings.forEach(function (heading) {
        tocObserver.observe(heading);
      });
    }
  });

  document.querySelectorAll('[data-dv-copy-url]').forEach(function (button) {
    button.addEventListener('click', function () {
      var url = button.getAttribute('data-dv-copy-url') || window.location.href;
      var share = button.closest('.dv-blog-share');
      var status = share ? share.querySelector('[data-dv-copy-status]') : null;
      var success = button.getAttribute('data-success') || 'Link copiado';
      var copyPromise;

      if (navigator.clipboard && navigator.clipboard.writeText) {
        copyPromise = navigator.clipboard.writeText(url);
      } else {
        copyPromise = new Promise(function (resolve, reject) {
          var fallback = document.createElement('textarea');
          fallback.value = url;
          fallback.setAttribute('readonly', '');
          fallback.style.position = 'fixed';
          fallback.style.opacity = '0';
          document.body.appendChild(fallback);
          fallback.select();
          try {
            document.execCommand('copy');
            resolve();
          } catch (error) {
            reject(error);
          }
          fallback.remove();
        });
      }

      copyPromise.then(function () {
        button.classList.add('is-copied');
        if (status) {
          status.textContent = success;
        }
        window.setTimeout(function () {
          button.classList.remove('is-copied');
          if (status) {
            status.textContent = '';
          }
        }, 2200);
      }).catch(function () {
        if (status) {
          status.textContent = url;
        }
      });
    });
  });

  /* Portfolio gallery lightbox: keyboard, touch, counter and focus handling. */
  var lightboxLinks = Array.prototype.slice.call(document.querySelectorAll('[data-dv-lightbox]'));
  if (lightboxLinks.length) {
    var lightbox = document.createElement('div');
    lightbox.className = 'dv-lightbox';
    lightbox.hidden = true;
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Visualização ampliada da galeria');
    lightbox.innerHTML = [
      '<div class="dv-lightbox__panel">',
      '<button class="dv-lightbox__close" type="button" aria-label="Fechar galeria"><span aria-hidden="true"></span></button>',
      '<button class="dv-lightbox__nav dv-lightbox__nav--prev" type="button" aria-label="Imagem anterior"><span aria-hidden="true">←</span></button>',
      '<div class="dv-lightbox__stage">',
      '<img class="dv-lightbox__image" alt="">',
      '</div>',
      '<button class="dv-lightbox__nav dv-lightbox__nav--next" type="button" aria-label="Próxima imagem"><span aria-hidden="true">→</span></button>',
      '<div class="dv-lightbox__meta" aria-live="polite">',
      '<p class="dv-lightbox__caption"></p>',
      '<span class="dv-lightbox__counter"></span>',
      '</div>',
      '</div>'
    ].join('');
    document.body.appendChild(lightbox);

    var lightboxPanel = lightbox.querySelector('.dv-lightbox__panel');
    var lightboxStage = lightbox.querySelector('.dv-lightbox__stage');
    var lightboxImage = lightbox.querySelector('.dv-lightbox__image');
    var lightboxCaption = lightbox.querySelector('.dv-lightbox__caption');
    var lightboxCounter = lightbox.querySelector('.dv-lightbox__counter');
    var lightboxClose = lightbox.querySelector('.dv-lightbox__close');
    var lightboxPrevious = lightbox.querySelector('.dv-lightbox__nav--prev');
    var lightboxNext = lightbox.querySelector('.dv-lightbox__nav--next');
    var lightboxIndex = 0;
    var lightboxGroup = [];
    var lightboxReturnFocus = null;
    var lightboxTouchStartX = 0;

    var renderLightboxImage = function (index) {
      if (!lightboxGroup.length) {
        return;
      }

      lightboxIndex = (index + lightboxGroup.length) % lightboxGroup.length;
      var selectedLink = lightboxGroup[lightboxIndex];
      var caption = selectedLink.getAttribute('data-dv-lightbox-caption') || '';
      var thumbnail = selectedLink.querySelector('img');

      lightboxStage.classList.add('is-loading');
      lightboxImage.classList.remove('is-visible');
      lightboxImage.src = selectedLink.href;
      lightboxImage.alt = caption || (thumbnail ? thumbnail.alt : '') || 'Imagem ampliada do projeto';
      lightboxCaption.textContent = caption;
      lightboxCaption.hidden = !caption;
      lightboxCounter.textContent = (lightboxIndex + 1) + ' / ' + lightboxGroup.length;
      lightboxPrevious.hidden = lightboxGroup.length < 2;
      lightboxNext.hidden = lightboxGroup.length < 2;
    };

    var closeLightbox = function () {
      if (lightbox.hidden) {
        return;
      }

      lightbox.classList.remove('is-open');
      document.body.classList.remove('dv-lightbox-open');
      window.setTimeout(function () {
        lightbox.hidden = true;
        lightboxImage.removeAttribute('src');
      }, reducedMotion ? 0 : 220);

      if (lightboxReturnFocus) {
        lightboxReturnFocus.focus();
      }
    };

    var openLightbox = function (link) {
      var groupName = link.getAttribute('data-dv-lightbox-group');
      lightboxGroup = lightboxLinks.filter(function (item) {
        return item.getAttribute('data-dv-lightbox-group') === groupName;
      });
      lightboxIndex = lightboxGroup.indexOf(link);
      lightboxReturnFocus = link;
      lightbox.hidden = false;
      document.body.classList.add('dv-lightbox-open');
      renderLightboxImage(lightboxIndex);
      (window.requestAnimationFrame || window.setTimeout)(function () {
        lightbox.classList.add('is-open');
        lightboxClose.focus();
      });
    };

    lightboxLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        openLightbox(link);
      });
    });

    lightboxImage.addEventListener('load', function () {
      lightboxStage.classList.remove('is-loading');
      lightboxImage.classList.add('is-visible');
    });
    lightboxImage.addEventListener('error', function () {
      lightboxStage.classList.remove('is-loading');
    });
    lightboxClose.addEventListener('click', closeLightbox);
    lightboxPrevious.addEventListener('click', function () {
      renderLightboxImage(lightboxIndex - 1);
    });
    lightboxNext.addEventListener('click', function () {
      renderLightboxImage(lightboxIndex + 1);
    });
    lightbox.addEventListener('click', function (event) {
      if (event.target === lightbox || event.target === lightboxPanel) {
        closeLightbox();
      }
    });
    lightboxStage.addEventListener('touchstart', function (event) {
      lightboxTouchStartX = event.changedTouches[0].clientX;
    }, { passive: true });
    lightboxStage.addEventListener('touchend', function (event) {
      var lightboxTouchDistance = event.changedTouches[0].clientX - lightboxTouchStartX;
      if (Math.abs(lightboxTouchDistance) < 48 || lightboxGroup.length < 2) {
        return;
      }
      renderLightboxImage(lightboxTouchDistance > 0 ? lightboxIndex - 1 : lightboxIndex + 1);
    }, { passive: true });

    document.addEventListener('keydown', function (event) {
      if (lightbox.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        closeLightbox();
      } else if (event.key === 'ArrowLeft' && lightboxGroup.length > 1) {
        event.preventDefault();
        renderLightboxImage(lightboxIndex - 1);
      } else if (event.key === 'ArrowRight' && lightboxGroup.length > 1) {
        event.preventDefault();
        renderLightboxImage(lightboxIndex + 1);
      } else if (event.key === 'Tab') {
        var lightboxControls = Array.prototype.slice.call(lightbox.querySelectorAll('button:not([hidden])'));
        var firstControl = lightboxControls[0];
        var lastControl = lightboxControls[lightboxControls.length - 1];
        if (event.shiftKey && document.activeElement === firstControl) {
          event.preventDefault();
          lastControl.focus();
        } else if (!event.shiftKey && document.activeElement === lastControl) {
          event.preventDefault();
          firstControl.focus();
        }
      }
    });
  }

  if (!reducedMotion && 'IntersectionObserver' in window) {
    var targets = document.querySelectorAll('main > .wp-block-group, .dv-card, .dv-post-card, .dv-project-card, .dv-portfolio-card, .dv-service-list > a, .dv-software-card, .dv-cpt-card, .dv-cpt-featured, .dv-cpt-features__grid > article, .dv-case-story > article');
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('dv-is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    targets.forEach(function (target) {
      target.classList.add('dv-reveal');
      observer.observe(target);
    });
  }
});
