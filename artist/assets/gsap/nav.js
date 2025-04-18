document.addEventListener("DOMContentLoaded", function () {
  const nav = document.querySelector(".nav-links");
  const snake = document.createElement("div");
  snake.classList.add("snake-hover");
  nav.appendChild(snake);

  document.querySelectorAll(".nav-links a").forEach((link) => {
    link.addEventListener("mouseenter", (e) => {
      const { left, width } = e.target.getBoundingClientRect();
      const navLeft = nav.getBoundingClientRect().left;

      // Move the snake effect under the hovered link
      snake.style.width = `${width}px`;
      snake.style.transform = `translateX(${left - navLeft}px)`;
    });

    link.addEventListener("mouseleave", () => {
      // Hide the snake effect when not hovering
      snake.style.width = "0";
    });
  });
});
