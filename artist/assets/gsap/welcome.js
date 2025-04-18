document.addEventListener("DOMContentLoaded", function () {
  // Wait for GSAP to be available
  if (typeof gsap === "undefined") {
    console.error("GSAP not loaded");
    return;
  }

  const coversContainer = document.querySelector(".black-covers");
  coversContainer.innerHTML = ""; // Clear any existing covers

  // Create black covers dynamically
  for (let i = 0; i < 200; i++) {
    const cover = document.createElement("div");
    cover.classList.add("cover");
    coversContainer.appendChild(cover);
  }

  // Ensure introText is hidden initially
  gsap.set("#introText h1:first-child", { y: "-100%", opacity: 0 });
  gsap.set("#introText h1:last-child", { y: "100%", opacity: 0 });

  // **INTRO TEXT Animation FIRST**
  var tl = gsap.timeline();

  tl.to("#introText h1:first-child", {
    y: "0%",
    opacity: 1,
    duration: 0.7,
    ease: "back.out(4)",
  })
    .to(
      "#introText h1:last-child",
      {
        y: "0%",
        opacity: 1,
        duration: 1.3,
        ease: "back.out(4)",
      },
      "-=0.5"
    )

    // Move introText to the left
    .to(
      "#introText",
      {
        left: "50%",
        x: "0%",
        duration: 1,
        ease: "back.inOut(4)",
        onComplete: function () {
          // Change z-index to 1 after animation completes
          gsap.set("#introText", { zIndex: 1 });
        },
      },
      "-=.7"
    );
});
