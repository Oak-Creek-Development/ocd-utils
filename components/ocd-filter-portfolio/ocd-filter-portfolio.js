jQuery(function ($) {
  function lmgpgdGetWindowScrollBarWidth() {
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

  $(document).ready(function () {
    // randomize display order of various badges
    $(
      ".lmg-project-item-categories, .lmg-project-modal-type ul, .lmg-project-modal-features ul"
    ).each(function () {
      let $ul = $(this);
      $ul
        .children("li")
        .sort(function () {
          return Math.round(Math.random()) - 0.5;
        })
        .appendTo($ul);
    });

    const $lmgProjectsIsotope = $(".lmgpgd-container").isotope({
      itemSelector: ".lmg-project-item",
      percentPosition: true,
      masonry: {
        columnWidth: ".lmgpgdis",
        gutter: 15,
      },
    });

    $lmgProjectsIsotope.imagesLoaded().progress(function () {
      $lmgProjectsIsotope.isotope("layout");
      $(".lmgpgd-spinner").hide();
      $(".lmgpgd-container, .lmgpgdf").css({ opacity: "1" });
    });

    $(".lmgpgd-wrapper").on("click", ".lmgpgdfs", function () {
      let currentFilter = $(this).attr("data-filter");

      $lmgProjectsIsotope.isotope({
        filter: currentFilter,
      });

      $(".lmgpgd-wrapper .lmgpgdfs").removeClass("is-checked");

      $('.lmgpgd-wrapper [data-filter="' + currentFilter + '"]').addClass(
        "is-checked"
      );
    });

    if (window.location.hash) {
      $(
        '.lmgpgdf > li > [data-filter=".' +
          window.location.hash.substring(1) +
          '"]'
      ).click();
    } else {
      $('.lmgpgdf > li > [data-filter="*"]').addClass("is-checked");
    }

    /**************************MicroModal********************************/
    $(".lmgpgd-wrapper").on(
      "click",
      ".lmgpgdf a.lmgpgdfs, .lmgpgd-container.filter a.lmgpgdfs, a.lmgpmt",
      function (e) {
        e.preventDefault();
      }
    );

    $(".lmgpgd-wrapper .lmgpgd-micromodal").appendTo("body");

    window.lmgHtmlDocStyleAttrStr = "";

    MicroModal.init({
      disableScroll: true,
      //disableFocus: true,
      onShow: function (modal) {
        window.lmgHtmlDocStyleAttrStr = $("html").attr("style") || "";
        $("html").css({
          "margin-right": lmgpgdGetWindowScrollBarWidth() + "px",
          overflow: "hidden",
        });

        let $modalEl = $(modal);

        //$modalEl.find(".modal__container").scrollTop(0);

        let $detailContainer = $modalEl.find(
          ".lmg-project-modal-detail-container"
        );

        if (
          $modalEl.find(".lmg-project-modal-detail").outerHeight(true) >
          $detailContainer.outerHeight(true)
        ) {
          let theTopPosition =
            15 +
            $detailContainer.position().top +
            $modalEl.find(".modal__header").outerHeight(true);

          $detailContainer.css({
            position: "sticky",
            top: theTopPosition + "px",
          });
        }

        $modalEl.on("click", ".lmg-project-modal-image > img", function () {
          $modalEl.toggleClass("img-expanded");
          //$modalEl.find(".lmg-project-modal-image").scrollTop(0);
        });
      },
      onClose: function (modal) {
        $("html").attr("style", window.lmgHtmlDocStyleAttrStr);

        let $modalEl = $(modal);
        $modalEl.removeClass("img-expanded");
        $modalEl.off("click", ".lmg-project-modal-image > img");

        $modalEl.find(".lmg-project-modal-detail-container").css({
          position: "relative",
          top: 0,
        });
      },
    });
    /**************************MicroModal********************************/
  });
});
