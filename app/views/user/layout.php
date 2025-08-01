<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/models/Database.php';
$userInfo = null;
if (isset($_SESSION['user'])) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Tính số lượng item trong giỏ hàng
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
?>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>
<!-- Banner Slider Start -->
<div class="main-banner-slider" style="width:100vw; max-width:100vw; overflow:hidden; margin:0 auto;">
  <div class="banner-slider-track" style="display:flex; transition:transform 0.5s;">
    <div class="banner-slide" style="display:flex; width:100vw;">
      <img src="/test/public/uploads/banner1.jpg" style="width:33.333%; object-fit:cover; height:320px;" alt="Banner 1">
      <img src="/test/public/uploads/banner2.jpg" style="width:33.333%; object-fit:cover; height:320px;" alt="Banner 2">
      <img src="/test/public/uploads/banner3.jpg" style="width:33.333%; object-fit:cover; height:320px;" alt="Banner 3">
    </div>
    <!-- Nếu có nhiều slide, copy thêm div.banner-slide ở đây -->
  </div>
  <!-- Đã xóa banner-dots (nút chấm tròn) -->
</div>
<style>
.main-banner-slider { position:relative; background:#fff; }
.banner-slide img { border:0; }
.banner-dots { margin-top:8px; }
.banner-dot { display:inline-block; width:12px; height:12px; border-radius:50%; background:#ccc; margin:0 4px; cursor:pointer; transition:background 0.2s; }
.banner-dot.active { background:#111; }
.banner-slider-track { cursor:grab; }
</style>
<script>
// Slider logic (chỉ 1 slide demo, có thể mở rộng)
const sliderTrack = document.querySelector('.banner-slider-track');
const dots = document.querySelectorAll('.banner-dot');
let currentSlide = 0;
let isDragging = false, startX = 0, currentX = 0, moved = 0;
function goToSlide(idx) {
  currentSlide = idx;
  sliderTrack.style.transform = `translateX(-${idx*100}vw)`;
  dots.forEach((d,i)=>d.classList.toggle('active',i===idx));
}
dots.forEach((dot,i)=>dot.onclick=()=>goToSlide(i));
// Kéo ngang slider
sliderTrack.addEventListener('mousedown', e => { isDragging=true; startX=e.pageX; sliderTrack.style.transition='none'; });
document.addEventListener('mousemove', e => {
  if (!isDragging) return;
  moved = e.pageX - startX;
  sliderTrack.style.transform = `translateX(${-currentSlide*window.innerWidth + moved}px)`;
});
document.addEventListener('mouseup', e => {
  if (!isDragging) return;
  isDragging=false; sliderTrack.style.transition='transform 0.5s';
  if (Math.abs(moved) > 100) {
    if (moved < 0 && currentSlide < dots.length-1) goToSlide(currentSlide+1);
    else if (moved > 0 && currentSlide > 0) goToSlide(currentSlide-1);
    else goToSlide(currentSlide);
  } else goToSlide(currentSlide);
  moved=0;
});
// Mobile touch
sliderTrack.addEventListener('touchstart', e => { isDragging=true; startX=e.touches[0].pageX; sliderTrack.style.transition='none'; });
sliderTrack.addEventListener('touchmove', e => {
  if (!isDragging) return;
  moved = e.touches[0].pageX - startX;
  sliderTrack.style.transform = `translateX(${-currentSlide*window.innerWidth + moved}px)`;
});
sliderTrack.addEventListener('touchend', e => {
  isDragging=false; sliderTrack.style.transition='transform 0.5s';
  if (Math.abs(moved) > 100) {
    if (moved < 0 && currentSlide < dots.length-1) goToSlide(currentSlide+1);
    else if (moved > 0 && currentSlide > 0) goToSlide(currentSlide-1);
    else goToSlide(currentSlide);
  } else goToSlide(currentSlide);
  moved=0;
});
</script>

<!-- Collection Tabs -->
<div class="collection-tabs">
  <div class="container" style="max-width:1200px; margin:0 auto;">
    <div class="row g-4">
      <div class="col">
        <a href="collection.php?category=ring" class="collection-tab">
          <div class="collection-img">
            <img src="/test/public/uploads/collection-rings.png" alt="Chrome Hearts Rings" onerror="this.src='/test/public/uploads/img_687eb604eec54_daychuyen.png'">
            <div class="collection-title">CHROME HEARTS RINGS</div>
          </div>
        </a>
      </div>
      <div class="col">
        <a href="collection.php?category=bongtai" class="collection-tab">
          <div class="collection-img">
            <img src="/test/public/uploads/collection-earrings.png" alt="Chrome Hearts Earrings" onerror="this.src='/test/public/uploads/img_687eb6b983374_daychuyen2.png'">
            <div class="collection-title">CHROME HEARTS EARRINGS</div>
          </div>
        </a>
      </div>
      <div class="col">
        <a href="collection.php?category=daychuyen" class="collection-tab">
          <div class="collection-img">
            <img src="/test/public/uploads/collection-pendants.png" alt="Chrome Hearts Pendants" onerror="this.src='/test/public/uploads/img_687eb71e53b74_daychuyen4.png'">
            <div class="collection-title">CHROME HEARTS PENDANTS</div>
          </div>
        </a>
      </div>
      <div class="col">
        <a href="collection.php?category=vongtay" class="collection-tab">
          <div class="collection-img">
            <img src="/test/public/uploads/collection-bracelets.png" alt="Chrome Hearts Bracelets" onerror="this.src='/test/public/uploads/img_687eb740a46ae_daychuyen5.png'">
            <div class="collection-title">CHROME HEARTS BRACELETS</div>
          </div>
        </a>
      </div>
    </div>
  </div>
</div>

<style>
.collection-tabs {
  padding: 60px 0;
  background: #fff;
}
.collection-tab {
  display: block;
  text-decoration: none;
  color: inherit;
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  height: 100%;
  border: 1px solid #f0f0f0;
}
.collection-tab:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.15);
  text-decoration: none;
  color: inherit;
}
.collection-img {
  position: relative;
  width: 100%;
  height: 280px;
  overflow: hidden;
  background: #1a1a1a;
}
.collection-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
  filter: brightness(0.9) contrast(1.1);
}
.collection-tab:hover .collection-img img {
  transform: scale(1.08);
}
.collection-title {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20px 16px;
  text-align: center;
  font-weight: bold;
  font-size: 15px;
  color: #fff;
  background: linear-gradient(transparent, rgba(0,0,0,0.8));
  letter-spacing: 0.5px;
  line-height: 1.3;
}
@media (max-width: 768px) {
  .collection-tabs {
    padding: 40px 0;
  }
  .collection-tabs .row {
    margin: 0 -6px;
  }
  .collection-tabs .col {
    padding: 0 6px;
  }
  .collection-img {
    height: 220px;
  }
  .collection-title {
    font-size: 13px;
    padding: 16px 12px;
  }
}
@media (max-width: 576px) {
  .collection-tabs .row {
    margin: 0 -4px;
  }
  .collection-tabs .col {
    padding: 0 4px;
  }
  .collection-img {
    height: 180px;
  }
  .collection-title {
    font-size: 12px;
    padding: 14px 10px;
  }
}
</style>

    <main>
        <?php if (isset($content)) echo $content; ?>
    </main>
<?php include dirname(__DIR__) . '/user/layout_footer.php'; ?>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 