const background = document.querySelector(".background");
const colors = [
  "rgba(255, 215, 0, 0.3)",
  "rgba(218, 165, 32, 0.3)",
  "rgba(184, 134, 11, 0.3)",
  "rgba(205, 133, 63, 0.3)",
];
for (let i = 0; i < 4; i++) {
  const circle = document.createElement("div");
  circle.className = "circle";
  circle.style.backgroundColor = colors[i];
  circle.style.width = Math.random() * 300 + 100 + "px";
  circle.style.height = circle.style.width;
  circle.style.left = Math.random() * 100 + "%";
  circle.style.top = Math.random() * 100 + "%";
  background.appendChild(circle);

  gsap.to(circle, {
    x: "random(-200, 200)",
    y: "random(-200, 200)",
    duration: "random(10, 20)",
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
  });
}

const floatingShapes = document.querySelector(".floating-shapes");
const goldColors = [
  "#FFD700",
  "#DAA520",
  "#B8860B",
  "#CD853F",
  "#D4AF37",
  "#CFB53B",
  "#FFDF00",
  "#FDB931",
  "#E6BE8A",
];

for (let i = 0; i < 15; i++) {
  const shape = document.createElement("div");
  shape.className = `shape${i}`;
  const size = 15 + i * 7;
  shape.style.width = `${size}px`;
  shape.style.height = `${size}px`;
  shape.style.backgroundColor =
    goldColors[Math.floor(Math.random() * goldColors.length)];
  shape.style.left = `${Math.random() * 100}%`;
  shape.style.top = `${Math.random() * 100}%`;
  floatingShapes.appendChild(shape);

  gsap.to(shape, {
    x: "random(-250, 250)",
    y: "random(-250, 250)",
    rotation: "random(-180, 180)",
    duration: "random(20, 35)",
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
    delay: i * 0.3,
  });
}
