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

  function isElementTopInViewport($el) {
    if (!$el.length) return false;

    const elTop = $el.offset().top;
    const scrollTop = $(window).scrollTop();
    const windowBottom = scrollTop + $(window).height();

    return elTop >= scrollTop && elTop <= windowBottom;
  }

  function isotopeClick(e = false) {
    let willScrollIntoView = false;
    let currentFilter = "";
    let $el = "";
    let $instance = "";
    let instanceNum = "";
    if (e === false && window.location.hash) {
      currentFilter = window.location.hash.replace(/^#/, "");
      willScrollIntoView = true;
    } else if (
      e !== false &&
      (e.type.startsWith("pointer") || e.type === "click")
    ) {
      $el = $(e.target);

      if (
        !$el.closest(".ocdfp-wrapper").length &&
        !$el.closest(".ocdfp-modal").length
      ) {
        const targetHash = e.target.hash ? e.target.hash.replace(/^#/, "") : "";
        $(".ocdfp-wrapper").each(function () {
          const $match = $(this)
            .find(`[data-ocdfp-filter="${targetHash}"]`)
            .first();
          if ($match.length) {
            $el = $match;
            willScrollIntoView = true;
            return false;
          }
        });
      }

      $instance = $el.closest(".ocdfp-wrapper");
      if ($instance.length) {
        instanceNum = $instance.attr("id").slice(-1);
      } else {
        instanceNum = $el.closest(".ocdfp-modal").attr("id").slice(-1);
        $instance = $("div#ocd-filter-portfolio-" + instanceNum);
      }

      currentFilter = $el.attr("data-ocdfp-filter");
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
          !isElementTopInViewport($instance) &&
          ($instance.offset().top + $instance.outerHeight() <
            scrollTop + windowHeight / 3 ||
            scrollTop + windowHeight >= $(document).height())
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

    if (
      willScrollIntoView &&
      $(".ocdfp-items").find(
        '.ocdfp-categories a[data-ocdfp-filter="' + currentFilter + '"]'
      ).length
    ) {
      const $wrapperEl = $(".ocdfp-wrapper").first();
      if ($wrapperEl.length) {
        $("html, body").animate(
          { scrollTop: $wrapperEl.offset().top - 50 },
          500
        );
      }
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

    $("body").on(
      "click",
      "a[href*='#']:not([href='#']):not(.ocdfp-wrapper a)",
      function (e) {
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

        let $modalContainer = $modalEl.find(".modal-container");

        $modalEl.on("click", ".ocdfp-image img", function () {
          if (window.matchMedia("(min-width: 768px)").matches) {
            $modalEl.toggleClass("img-expanded");
          }

          setTimeout(() => {
            if (
              $modalContainer[0].scrollHeight > $modalContainer[0].clientHeight
            ) {
              $modalContainer.on("wheel", function (e) {
                const delta = e.originalEvent.deltaY;
                const el = this;

                const atTop = el.scrollTop === 0;
                const atBottom =
                  el.scrollHeight - el.scrollTop === el.clientHeight;

                if ((delta < 0 && atTop) || (delta > 0 && atBottom)) {
                  return;
                }

                e.stopPropagation();
              });
            }
          }, 0);

          const img = this;
          setTimeout(() => {
            img.scrollIntoView({
              behavior: "smooth",
              block: "start",
              inline: "nearest",
            });
          }, 0);
        });

        if ("" === $.trim($detailInner.html())) {
          $imgInner.parent().addClass("no-description");
          $imgInner.children("img").first().click();
        }
      },
      onClose: function (modal) {
        $("html").attr("style", window.ocdHtmlDocStyleAttrStr);

        document.activeElement.blur();

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
