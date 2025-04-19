// Add CSS for hover effects and transitions
document.addEventListener("DOMContentLoaded", function () {
  // Helper function to safely initialize charts
  function initializeChart(elementId, config) {
    const element = document.getElementById(elementId);
    if (element) {
      return new Chart(element, config);
    }
    console.log(`Chart element with ID "${elementId}" not found.`);
    return null;
  }

  // Initialize mini charts with enhanced data
  const miniChart1Config = {
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
  };
  
  const miniChart2Config = {
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
  };
  
  const miniChart3Config = {
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
  };
  
  const miniChart4Config = {
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
  };

  // Safely initialize each chart
  const miniChart1 = initializeChart("miniChart1", miniChart1Config);
  const miniChart2 = initializeChart("miniChart2", miniChart2Config);
  const miniChart3 = initializeChart("miniChart3", miniChart3Config);
  const miniChart4 = initializeChart("miniChart4", miniChart4Config);

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

  // Add any other initialization code here
  
  try {
    // This is where the error was happening
    // If additional charts need to be initialized, use the safe initializeChart function
  } catch (error) {
    console.error("An error occurred while initializing charts:", error);
  }
});
