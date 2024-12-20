jQuery(function ($) {
  function getWindowScrollBarWidth() {
    if ($(document).height() == $(window).height()) {
      return 0;
    }

    let $outer = $("<div>")
      .css({ visibility: "hidden", width: 100, overflow: "scroll" })
      .appendTo("body");
    let widthWithScroll = $("<div>")
      .css({ width: "100%" })
      .appendTo($outer)
      .outerWidth();
    $outer.remove();
    return 100 - widthWithScroll;
  }

  function isotopeClick(e = false) {
    let currentFilter = "";
    let $el = "";
    let $instance = "";
    let instanceNum = "";
    if (e === false && window.location.hash) {
      currentFilter = window.location.hash.replace(/^#/, "");
    } else if (
      e !== false &&
      (e.type.startsWith("pointer") || e.type === "click")
    ) {
      $el = $(e.target);
      currentFilter = $el.attr("data-ocdfp-filter");

      $instance = $el.closest(".ocdfp-wrapper");
      if ($instance.length) {
        instanceNum = $instance.attr("id").slice(-1);
      } else {
        instanceNum = $el.closest(".ocdfp-modal").attr("id").slice(-1);
        $instance = $("div#ocd-filter-portfolio-" + instanceNum);
      }
    } else {
      return false;
    }

    if (
      e !== false &&
      $instance.length &&
      $instance.hasClass("link-internal")
    ) {
      e.preventDefault();
      if ("*" === currentFilter) {
        history.replaceState(null, null, window.location.pathname);
      } else {
        history.replaceState(null, null, "#" + currentFilter);
      }

      $(".ocdfp-modal").each(function () {
        MicroModal.close($(this).attr("id"));
      });
    } else if ($el.length && $el.is("a")) {
      return;
    }

    if (!$instance.length) {
      $instance = $("body");
    }

    let $items = $instance.find(".ocdfp-items");
    $items.isotope({
      filter: function () {
        if ("*" === currentFilter) {
          return true; // Show all items
        }

        let categories = $(this).attr("data-categories");
        return categories && categories.split(" ").includes(currentFilter);
      },
    });

    $instance
      .find(".ocdfp-filters button, .ocdfp-categories a")
      .removeClass("is-checked");
    $instance
      .find('[data-ocdfp-filter="' + currentFilter + '"]')
      .addClass("is-checked");

    if (instanceNum !== "") {
      $items.off("arrangeComplete").on("arrangeComplete", function () {
        let scrollTop = $(window).scrollTop();
        let windowHeight = $(window).height();
        if (
          $instance.offset().top + $instance.outerHeight() <
            scrollTop + windowHeight / 3 ||
          scrollTop + windowHeight >= $(document).height()
        ) {
          $("html, body").animate(
            { scrollTop: $instance.offset().top - 50 },
            500
          );
        }
      });

      $modals = $('.ocdfp-modal[id$="' + instanceNum + '"]');
      $modals.find(".ocdfp-categories a").removeClass("is-checked");
      $modals
        .find('[data-ocdfp-filter="' + currentFilter + '"]')
        .addClass("is-checked");
    }
  }

  $(document).ready(function () {
    // randomize display order of various badges
    $(".ocdfp-categories, .ocdfp-tags").each(function () {
      let $ul = $(this);
      $ul
        .children("li")
        .sort(function () {
          return Math.round(Math.random()) - 0.5;
        })
        .appendTo($ul);
    });

    /***************************** ISOTOPE *******************************/
    $(".ocdfp-wrapper").each(function () {
      let $instance = $(this);
      let $items = $instance.find(".ocdfp-items");

      $items.isotope({
        itemSelector: ".ocdfp-item",
        percentPosition: true,
        masonry: {
          columnWidth: ".ocdfp-item-sizer",
          gutter: 15,
        },
      });

      $items.imagesLoaded().progress(function () {
        $items.isotope("layout");
      });

      $instance.find(".ocdfp-spinner").hide();
      $instance
        .find(".ocdfp-filters, .ocdfp-items")
        .css({ "max-height": "none", opacity: "1", overflow: "visible" });
      $instance
        .find('.ocdfp-filters [data-ocdfp-filter="*"]')
        .addClass("is-checked");
      isotopeClick();

      // Intersection Observer to trigger Isotope layout on view
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              $items.isotope("layout");
            }
          });
        },
        { threshold: 0.1 }
      );

      observer.observe($items[0]); // Observe the items container
    });

    $("body").on(
      "click",
      ".ocdfp-wrapper .ocdfp-filters button, .ocdfp-wrapper .ocdfp-categories a, .ocdfp-modal .ocdfp-categories a",
      function (e) {
        let $target = $(e.target);

        // if we clicked on a child of the element that has the data attribute
        if (!$target.is("[data-ocdfp-filter]")) {
          let $closest = $target
            .parentsUntil(".ocdfp-wrapper")
            .filter("[data-ocdfp-filter]")
            .first();

          if ($closest.length) {
            e = $.extend({}, e, { target: $closest[0] });
          }
        }

        isotopeClick(e);
      }
    );
    /***************************** END ISOTOPE *******************************/

    /***************************** MODALS *******************************/
    $(".ocdfp-wrapper .ocdfp-modal").appendTo("body");

    window.ocdHtmlDocStyleAttrStr = "";

    MicroModal.init({
      disableScroll: true,
      //disableFocus: true,
      onShow: function (modal) {
        window.ocdHtmlDocStyleAttrStr = $("html").attr("style") || "";

        $("html").css({
          "margin-right": getWindowScrollBarWidth() + "px",
          overflow: "hidden",
        });

        let $modalEl = $(modal);

        let $detailInner = $modalEl.find(".ocdfp-detail");

        if (
          $modalEl.find(".ocdfp-detail-wrapper").outerHeight(true) >
          $detailInner.outerHeight(true)
        ) {
          let detailTopPosition =
            15 +
            $detailInner.position().top +
            $modalEl.find(".modal-header").outerHeight(true);

          $detailInner.css({
            position: "sticky",
            top: detailTopPosition + "px",
          });
        }

        let $imgInner = $modalEl.find(".ocdfp-image");

        if (
          $modalEl.find(".ocdfp-image-wrapper").outerHeight(true) >
          $imgInner.outerHeight(true)
        ) {
          let imgTopPosition =
            15 +
            $imgInner.position().top +
            $modalEl.find(".modal-header").outerHeight(true);

          $imgInner.css({
            position: "sticky",
            top: imgTopPosition + "px",
          });
        }

        $modalEl.on("click", ".ocdfp-image img", function () {
          if (window.matchMedia("(min-width: 768px)").matches) {
            $modalEl.toggleClass("img-expanded");
          }
        });

        if ("" === $.trim($detailInner.html())) {
          $imgInner.parent().addClass("no-description");
          $imgInner.children("img").first().click();
        }
      },
      onClose: function (modal) {
        $("html").attr("style", window.ocdHtmlDocStyleAttrStr);

        let $modalEl = $(modal);
        $modalEl.removeClass("img-expanded");
        $modalEl.off("click", ".ocdfp-image img");

        $modalEl.find(".ocdfp-detail").css({
          position: "relative",
          top: 0,
        });

        $modalEl.find(".ocdfp-image").css({
          position: "relative",
          top: 0,
        });
      },
    });
    /***************************** END MODALS *******************************/
  });
});
