(function() {
  "use strict";

  var container = document.querySelector(".hero-slider");
  if (!container) return;

  var slides = container.querySelectorAll(".slide");
  var dots = container.querySelectorAll(".slider-dot");
  var prevBtn = container.querySelector(".slider-nav--prev");
  var nextBtn = container.querySelector(".slider-nav--next");
  var total = slides.length;
  var current = 0;
  var animating = false;

  function goToSlide(index) {
    if (animating || index === current) return;
    animating = true;

    var next = ((index % total) + total) % total;
    var direction = next > current ? 1 : -1;

    if (current === 0 && next === total - 1) direction = -1;
    if (current === total - 1 && next === 0) direction = 1;

    var currentSlide = slides[current];
    var nextSlide = slides[next];

    nextSlide.style.transition = "none";
    nextSlide.style.transform = "translateX(" + (direction * 100) + "%)";
    nextSlide.style.opacity = "1";
    nextSlide.classList.add("active");

    void nextSlide.offsetWidth;

    nextSlide.style.transition = "";
    currentSlide.style.transition = "";

    currentSlide.style.transform = "translateX(" + (-direction * 100) + "%)";
    currentSlide.style.opacity = "0";
    nextSlide.style.transform = "translateX(0)";

    setTimeout(function() {
      currentSlide.classList.remove("active");
      currentSlide.style.transform = "";
      currentSlide.style.opacity = "";
      currentSlide.style.transition = "";
      nextSlide.style.transition = "";

      currentSlide.setAttribute("aria-hidden", "true");
      nextSlide.setAttribute("aria-hidden", "false");

      var oldCta = currentSlide.querySelector(".slide-cta");
      var newCta = nextSlide.querySelector(".slide-cta");
      if (oldCta) oldCta.setAttribute("tabindex", "-1");
      if (newCta) newCta.setAttribute("tabindex", "0");

      dots[current].classList.remove("active");
      dots[current].setAttribute("aria-selected", "false");
      dots[next].classList.add("active");
      dots[next].setAttribute("aria-selected", "true");

      current = next;
      animating = false;
    }, 600);
  }

  prevBtn.addEventListener("click", function() {
    goToSlide(current - 1);
  });

  nextBtn.addEventListener("click", function() {
    goToSlide(current + 1);
  });

  dots.forEach(function(dot) {
    dot.addEventListener("click", function() {
      var index = parseInt(this.getAttribute("data-slide"), 10);
      goToSlide(index);
    });
  });

  document.addEventListener("keydown", function(e) {
    if (!container.contains(document.activeElement) &&
        document.activeElement !== document.body) return;
    if (e.key === "ArrowLeft") { e.preventDefault(); goToSlide(current - 1); }
    if (e.key === "ArrowRight") { e.preventDefault(); goToSlide(current + 1); }
  });
})();
