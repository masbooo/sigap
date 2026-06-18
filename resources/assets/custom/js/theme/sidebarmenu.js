(function () {
  var layout = document.documentElement.getAttribute("data-layout");

  function findMatchingElement(selector) {
    var currentUrl = window.location.href;
    var anchors = document.querySelectorAll(selector);

    for (var i = 0; i < anchors.length; i++) {
      if (anchors[i].href === currentUrl) {
        return anchors[i];
      }
    }

    return null;
  }

  function syncTemplateLink() {
    var currentLink = document.getElementById("get-url");
    if (!currentLink) {
      return;
    }

    var currentNewURL =
      window.location !== window.parent.location
        ? document.referrer
        : document.location.href;

    if (currentNewURL.includes("/main/index.html")) {
      currentLink.setAttribute("href", "../main/index.html");
    } else if (currentNewURL.includes("/index.html")) {
      currentLink.setAttribute("href", "./index.html");
    } else {
      currentLink.setAttribute("href", "./");
    }
  }

  function isBootstrapManagedSidebar(sidebarElement) {
    return (
      sidebarElement &&
      sidebarElement.getAttribute("data-sigap-sidebar-mode") === "bootstrap-collapse"
    );
  }

  if (layout === "vertical") {
    var verticalSidebar = document.getElementById("sidebarnav");

    if (isBootstrapManagedSidebar(verticalSidebar)) {
      syncTemplateLink();
      return;
    }

    var matchedVerticalLink = findMatchingElement("#sidebarnav a");
    if (matchedVerticalLink) {
      matchedVerticalLink.classList.add("active");
    }

    syncTemplateLink();

    document
      .querySelectorAll("ul#sidebarnav ul li a.active")
      .forEach(function (link) {
        var parentList = link.closest("ul");
        if (parentList) {
          parentList.classList.add("in");
        }

        if (parentList && parentList.parentElement) {
          parentList.parentElement.classList.add("selected");
        }
      });

    document.querySelectorAll("#sidebarnav li").forEach(function (li) {
      var isActive = li.classList.contains("selected");
      if (!isActive) {
        return;
      }

      var anchor = li.querySelector("a");
      if (anchor) {
        anchor.classList.add("active");
      }
    });

    document.querySelectorAll("#sidebarnav a").forEach(function (link) {
      link.addEventListener("click", function () {
        var isActive = this.classList.contains("active");
        var parentUl = this.closest("ul");

        if (!parentUl) {
          return;
        }

        if (!isActive) {
          parentUl.querySelectorAll("ul").forEach(function (submenu) {
            submenu.classList.remove("in");
          });

          parentUl.querySelectorAll("a").forEach(function (navLink) {
            navLink.classList.remove("active");
          });

          var submenu = this.nextElementSibling;
          if (submenu) {
            submenu.classList.add("in");
          }

          this.classList.add("active");
          return;
        }

        this.classList.remove("active");
        parentUl.classList.remove("active");

        var openedSubmenu = this.nextElementSibling;
        if (openedSubmenu) {
          openedSubmenu.classList.remove("in");
        }
      });
    });
  }

  if (layout === "horizontal") {
    var matchedHorizontalLink = findMatchingElement("#sidebarnavh ul#sidebarnav a");

    if (matchedHorizontalLink) {
      matchedHorizontalLink.classList.add("active");
    }

    document
      .querySelectorAll("#sidebarnavh ul#sidebarnav a.active")
      .forEach(function (link) {
        var parentAnchor = link.closest("a");
        if (parentAnchor && parentAnchor.parentElement) {
          parentAnchor.parentElement.classList.add("selected");
        }

        var parentList = link.closest("ul");
        if (parentList && parentList.parentElement) {
          parentList.parentElement.classList.add("selected");
        }
      });
  }
})();
