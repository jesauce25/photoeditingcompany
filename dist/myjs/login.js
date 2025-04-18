document.addEventListener("DOMContentLoaded", function () {
  gsap.from(".login-container", {
    duration: 1,
    y: 100,
    opacity: 0,
    ease: "power4.out",
  });

  gsap.from("h2", {
    duration: 1,
    y: -50,
    opacity: 0,
    delay: 0.5,
    ease: "back.out",
  });

  gsap.from(".input-group", {
    duration: 0.8,
    x: -100,
    opacity: 0,
    stagger: 0.2,
    delay: 0.8,
    ease: "power2.out",
  });

  gsap.fromTo(
    "button",
    { x: -1100, opacity: 0 },
    { x: 0, opacity: 1, duration: 0.8, delay: 0.5, ease: "back.(4)" }
  );

  gsap.from(".forgot-password-container", {
    duration: 1,
    y: -50,
    opacity: 0,
    delay: 2,
    ease: "back.out",
  });

  // Hover effects for all buttons
  document.querySelectorAll("button").forEach((button) => {
    button.addEventListener("mouseenter", () => {
      gsap.to(button, {
        duration: 0.3,
        scale: 1.05,
        ease: "power2.out",
      });
    });

    button.addEventListener("mouseleave", () => {
      gsap.to(button, {
        duration: 0.3,
        scale: 1,
        ease: "power2.out",
      });
    });

    // Target the letters inside all buttons
    const letters = button.querySelectorAll(".letter");

    if (letters.length > 0) {
      // Step 1: Start letters from the right (outside button)
      gsap.set(letters, {
        x: "100px", // Move completely out of view to the right
        rotation: 20, // Slight rotation for effect
      });

      // Step 2: Animate letters to normal position while stacked
      gsap.to(letters, {
        x: "0", // Move letters back into place
        rotation: 0,
        duration: 1.2,
        letterSpacing: "normal",
        ease: "back.inOut(4)",
        delay: 1.1,
        stagger: 0.05, // Apply staggered animation effect
      });
    }
  });
});
