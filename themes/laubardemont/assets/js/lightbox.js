(function () {
  var lightbox = document.getElementById('section-lightbox');
  if (!lightbox) return;

  var lbImg = document.getElementById('slb-img');
  var lbCaption = document.getElementById('slb-caption');
  var items = Array.from(document.querySelectorAll('[data-gallery-item]'));
  var currentIndex = 0;

  function open(index) {
    currentIndex = index;
    var item = items[currentIndex];
    lbImg.src = item.href;
    lbImg.alt = item.dataset.alt || '';
    lbCaption.textContent = item.dataset.alt || '';
    lightbox.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    lightbox.classList.remove('is-open');
    lbImg.src = '';
    document.body.style.overflow = '';
  }

  items.forEach(function (item, i) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      open(i);
    });
  });

  document.getElementById('slb-close').addEventListener('click', close);
  document.getElementById('slb-prev').addEventListener('click', function () {
    open((currentIndex - 1 + items.length) % items.length);
  });
  document.getElementById('slb-next').addEventListener('click', function () {
    open((currentIndex + 1) % items.length);
  });

  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) close();
  });

  document.addEventListener('keydown', function (e) {
    if (!lightbox.classList.contains('is-open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') document.getElementById('slb-prev').click();
    if (e.key === 'ArrowRight') document.getElementById('slb-next').click();
  });
})();
