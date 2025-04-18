document.addEventListener("DOMContentLoaded", function () {
  // Tab switching logic
  const tabLinks = document.querySelectorAll(".tab-link");
  const tabPanes = document.querySelectorAll(".tab-pane");

  tabLinks.forEach((link) => {
    link.addEventListener("click", function () {
      // Remove active class from all links and panes
      tabLinks.forEach((l) => l.classList.remove("active"));
      tabPanes.forEach((p) => p.classList.remove("active"));

      // Add active class to clicked link and corresponding pane
      this.classList.add("active");
      const targetPane = document.querySelector(
        this.getAttribute("data-target")
      );
      targetPane.classList.add("active");
    });
  });
});
