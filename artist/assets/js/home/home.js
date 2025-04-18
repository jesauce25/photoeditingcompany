// Add CSS for hover effects and transitions
document.addEventListener("DOMContentLoaded", function () {
  // Initialize mini charts with enhanced data
  const miniChart1 = new Chart(document.getElementById("miniChart1"), {
    type: "line",
    data: {
      labels: ["", "", "", "", "", "", ""],
      datasets: [
        {
          data: [144, 148, 152, 154, 155, 155, 156],
          borderColor: "#4CAF50",
          backgroundColor: "rgba(75, 192, 192, 0.1)",
          borderWidth: 2,
          pointRadius: 0,
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          display: false,
        },
        y: {
          display: false,
          min: 140,
          max: 160,
        },
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });

  const miniChart2 = new Chart(document.getElementById("miniChart2"), {
    type: "line",
    data: {
      labels: ["", "", "", "", "", "", ""],
      datasets: [
        {
          data: [40, 41, 42, 43, 44, 44, 45],
          borderColor: "#ff4444",
          backgroundColor: "rgba(255, 215, 0, 0.1)",
          borderWidth: 2,
          pointRadius: 0,
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          display: false,
        },
        y: {
          display: false,
          min: 35,
          max: 50,
        },
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });

  const miniChart3 = new Chart(document.getElementById("miniChart3"), {
    type: "line",
    data: {
      labels: ["", "", "", "", "", "", ""],
      datasets: [
        {
          data: [5, 5, 6, 6, 6, 7, 7],
          borderColor: "#2196F3",
          backgroundColor: "rgba(255, 99, 132, 0.1)",
          borderWidth: 2,
          pointRadius: 0,
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          display: false,
        },
        y: {
          display: false,
          min: 0,
          max: 10,
        },
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });

  const miniChart4 = new Chart(document.getElementById("miniChart4"), {
    type: "line",
    data: {
      labels: ["", "", "", "", "", "", ""],
      datasets: [
        {
          data: [2.1, 2.2, 2.3, 2.3, 2.4, 2.4, 2.4],
          borderColor: "#4CAF50",
          backgroundColor: "rgba(153, 102, 255, 0.1)",
          borderWidth: 2,
          pointRadius: 0,
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          display: false,
        },
        y: {
          display: false,
          min: 1,
          max: 3,
        },
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });

  // Animate counters with enhanced values
  const counters = document.querySelectorAll(".counter");
  counters.forEach((counter) => {
    const value = parseFloat(counter.innerText);
    let startValue = 0;
    const duration = 1500;
    const startTime = performance.now();

    function updateCounter(timestamp) {
      const elapsed = timestamp - startTime;
      const progress = Math.min(elapsed / duration, 1);

      if (counter.innerText.includes(".")) {
        counter.innerText = (
          startValue +
          progress * (value - startValue)
        ).toFixed(1);
      } else if (counter.innerText.includes(",")) {
        counter.innerText = Math.floor(
          startValue + progress * (value - startValue)
        ).toLocaleString();
      } else {
        counter.innerText = Math.floor(
          startValue + progress * (value - startValue)
        );
      }

      if (progress < 1) {
        requestAnimationFrame(updateCounter);
      }
    }

    requestAnimationFrame(updateCounter);
  });

  // Add the original chart code here if needed
  // ... existing code ...
});
