document.addEventListener("DOMContentLoaded", function () {
  const coversContainer = document.querySelector(".black-covers");
  coversContainer.innerHTML = ""; // Clear any existing covers

  for (let i = 0; i < 200; i++) {
    const cover = document.createElement("div");
    cover.classList.add("cover");
    coversContainer.appendChild(cover);
  }

  // IN Animation - Start fully black, then move up to reveal content
  gsap.set(".cover", { y: "0%" }); // Covers start fully visible
  gsap.to(".cover", {
    y: "-100%", // Move up to reveal the page
    duration: 1,
    stagger: 0.0004,
    ease: "power4.inOut",
  });

  // OUT Animation - Apply to all links dynamically
  document.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault(); // Stop default navigation
      const targetUrl = this.href; // Get the URL of the clicked link

      // Play GSAP animation
      gsap.to(".cover", {
        y: "0%", // Move covers back from top to center (fully black)
        duration: 1,
        stagger: 0.0005,
        ease: "power4.inOut",
        onComplete: () => {
          window.location.href = targetUrl; // Redirect after animation
        },
      });
    });
  });
});
