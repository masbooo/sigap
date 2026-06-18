(function () {
  function equalizeReviewCardHeights($reviewCarousel) {
    if (!$reviewCarousel || !$reviewCarousel.length) {
      return;
    }

    var $allCards = $reviewCarousel.find(".owl-item .card");
    var $sourceCards = $reviewCarousel.find(".owl-item:not(.cloned) .card");
    var maxHeight = 0;

    $allCards.css("min-height", "");

    $sourceCards.each(function () {
      maxHeight = Math.max(maxHeight, window.jQuery(this).outerHeight());
    });

    if (!maxHeight) {
      return;
    }

    $allCards.css("min-height", maxHeight + "px");
  }

  function initReviewSlider() {
    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.owlCarousel !== "function") {
      return;
    }

    var $reviewCarousel = window.jQuery(".review-slider .owl-carousel");

    if (!$reviewCarousel.length || $reviewCarousel.hasClass("owl-loaded")) {
      return;
    }

    $reviewCarousel.on("initialized.owl.carousel refreshed.owl.carousel resized.owl.carousel", function () {
      equalizeReviewCardHeights($reviewCarousel);
    });

    $reviewCarousel.owlCarousel({
      loop: true,
      margin: 0,
      dots: true,
      autoplay: true,
      autoplayTimeout: 5000,
      autoplayHoverPause: true,
      responsive: {
        0: {
          items: 1
        },
        1024: {
          items: 2
        },
        1200: {
          items: 3
        }
      }
    });

    window.addEventListener("load", function () {
      equalizeReviewCardHeights($reviewCarousel);
    }, { once: true });

    window.addEventListener("resize", function () {
      window.clearTimeout(window.__reviewCardHeightTimer);
      window.__reviewCardHeightTimer = window.setTimeout(function () {
        equalizeReviewCardHeights($reviewCarousel);
      }, 120);
    });
  }

  function initAos() {
    if (!window.AOS || typeof window.AOS.init !== "function") {
      return;
    }

    window.AOS.init({
      once: true,
      duration: 800,
      easing: "ease-out",
      offset: 40
    });

    window.setTimeout(function () {
      if (typeof window.AOS.refreshHard === "function") {
        window.AOS.refreshHard();
      } else if (typeof window.AOS.refresh === "function") {
        window.AOS.refresh();
      }
    }, 150);
  }

  function initTooltips() {
    if (!window.bootstrap || typeof window.bootstrap.Tooltip !== "function") {
      return;
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
      if (!window.bootstrap.Tooltip.getInstance(element)) {
        new window.bootstrap.Tooltip(element);
      }
    });
  }

  function initLandingPage() {
    initAos();
    initReviewSlider();
    initTooltips();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLandingPage, { once: true });
  } else {
    initLandingPage();
  }
})();
