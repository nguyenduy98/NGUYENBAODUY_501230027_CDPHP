<?php 
require_once __DIR__ . '/../../../models/Product.php';
function format_money($amount) {
    $amount = preg_replace('/[^0-9]/', '', $amount);
    return number_format((int)$amount, 0, '', ',') . 'đ';
}
$ring = Product::getByType('ring', 20);
$daychuyen = Product::getByType('daychuyen', 20);
$vongtay = Product::getByType('vongtay', 20);
$bongtai = Product::getByType('bongtai', 20);
ob_start(); ?>
<div class="container mt-4" style="max-width:1400px; margin:0 auto;">
  <!-- Section Dây chuyền -->
  <h3 class="text-center mb-3" style="font-family:Montserrat,sans-serif;letter-spacing:1px;">DÂY CHUYỀN</h3>
  <div class="slider-wrap mb-5">
    <button class="slider-btn slider-btn-left" onclick="moveSlider('daychuyen',-1)"><i class='fa-solid fa-chevron-left'></i></button>
    <div class="slider-row" id="slider-daychuyen">
      <?php foreach ($daychuyen as $product): ?>
        <div class="slider-item">
          <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration:none; color:inherit;">
            <div class="card h-100">
              <div class="position-relative" style="padding-top: 100%; overflow: hidden;">
                <img src="<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top position-absolute top-0 start-0 w-100 h-100">
              </div>
              <div class="card-body text-center">
                <h5 class="card-title" style="min-height:40px; font-size:1.1rem;"><?= htmlspecialchars($product['name']) ?></h5>
                <p class="card-text text-danger fw-bold mb-2"><?= format_money($product['price']) ?></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="slider-btn slider-btn-right" onclick="moveSlider('daychuyen',1)"><i class='fa-solid fa-chevron-right'></i></button>
  </div>
  <!-- Section Nhẫn -->
  <h3 class="text-center mb-3" style="font-family:Montserrat,sans-serif;letter-spacing:1px;">NHẪN</h3>
  <div class="slider-wrap mb-5">
    <button class="slider-btn slider-btn-left" onclick="moveSlider('ring',-1)"><i class='fa-solid fa-chevron-left'></i></button>
    <div class="slider-row" id="slider-ring">
      <?php foreach ($ring as $product): ?>
        <div class="slider-item">
          <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration:none; color:inherit;">
            <div class="card h-100">
              <div class="position-relative" style="padding-top: 100%; overflow: hidden;">
                <img src="<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top position-absolute top-0 start-0 w-100 h-100">
              </div>
              <div class="card-body text-center">
                <h5 class="card-title" style="min-height:40px; font-size:1.1rem;"><?= htmlspecialchars($product['name']) ?></h5>
                <p class="card-text text-danger fw-bold mb-2"><?= format_money($product['price']) ?></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="slider-btn slider-btn-right" onclick="moveSlider('ring',1)"><i class='fa-solid fa-chevron-right'></i></button>
      </div>
  <!-- Section Vòng tay -->
  <h3 class="text-center mb-3" style="font-family:Montserrat,sans-serif;letter-spacing:1px;">VÒNG TAY</h3>
  <div class="slider-wrap mb-5">
    <button class="slider-btn slider-btn-left" onclick="moveSlider('vongtay',-1)"><i class='fa-solid fa-chevron-left'></i></button>
    <div class="slider-row" id="slider-vongtay">
      <?php foreach ($vongtay as $product): ?>
        <div class="slider-item">
          <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration:none; color:inherit;">
            <div class="card h-100">
              <div class="position-relative" style="padding-top: 100%; overflow: hidden;">
                <img src="<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top position-absolute top-0 start-0 w-100 h-100">
              </div>
              <div class="card-body text-center">
                <h5 class="card-title" style="min-height:40px; font-size:1.1rem;"><?= htmlspecialchars($product['name']) ?></h5>
                <p class="card-text text-danger fw-bold mb-2"><?= format_money($product['price']) ?></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="slider-btn slider-btn-right" onclick="moveSlider('vongtay',1)"><i class='fa-solid fa-chevron-right'></i></button>
  </div>
  <!-- Section Khuyên tai -->
  <h3 class="text-center mb-3" style="font-family:Montserrat,sans-serif;letter-spacing:1px;">KHUYÊN TAI</h3>
  <div class="slider-wrap mb-5">
    <button class="slider-btn slider-btn-left" onclick="moveSlider('bongtai',-1)"><i class='fa-solid fa-chevron-left'></i></button>
    <div class="slider-row" id="slider-bongtai">
      <?php foreach ($bongtai as $product): ?>
        <div class="slider-item">
          <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration:none; color:inherit;">
            <div class="card h-100">
              <div class="position-relative" style="padding-top: 100%; overflow: hidden;">
                <img src="<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top position-absolute top-0 start-0 w-100 h-100">
                </div>
              <div class="card-body text-center">
                <h5 class="card-title" style="min-height:40px; font-size:1.1rem;"><?= htmlspecialchars($product['name']) ?></h5>
                <p class="card-text text-danger fw-bold mb-2"><?= format_money($product['price']) ?></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="slider-btn slider-btn-right" onclick="moveSlider('bongtai',1)"><i class='fa-solid fa-chevron-right'></i></button>
  </div>
</div>
<style>
.slider-wrap { position:relative; display:flex; align-items:center; overflow:hidden; }
.slider-row { display:flex; gap:16px; min-width:0; flex-wrap:nowrap; transition: all 0.3s; }
.slider-item { min-width: 200px; max-width: 200px; flex:0 0 200px; }
.slider-btn { background:#fff; border:1px solid #ddd; border-radius:50%; width:36px; height:36px; font-size:1.5rem; font-weight:bold; color:#bfa14a; cursor:pointer; display:flex; align-items:center; justify-content:center; position:relative; z-index:2; transition:background 0.2s; }
.slider-btn:hover { background:#f7f3e8; }
@media (max-width:1200px){ .slider-item{min-width:180px;max-width:180px;} }
@media (max-width:900px){ .slider-item{min-width:160px;max-width:160px;} }
@media (max-width:600px){ .slider-item{min-width:140px;max-width:140px;} }
</style>
<script>
const sliderState = { ring: 0, daychuyen: 0, vongtay: 0, bongtai: 0 };
function moveSlider(type, dir) {
  const row = document.getElementById('slider-' + type);
  const total = row.children.length;
  if (total === 0) return;
  const itemWidth = row.children[0].offsetWidth + 16; // 16 là gap mới
  const wrap = row.parentElement;
  const visible = Math.max(1, Math.floor(wrap.offsetWidth / itemWidth));
  const maxSlide = Math.max(0, total - visible);
  sliderState[type] += dir;
  if (sliderState[type] < 0) sliderState[type] = 0;
  if (sliderState[type] > maxSlide) sliderState[type] = maxSlide;
  row.style.transform = `translateX(-${sliderState[type] * itemWidth}px)`;
}
window.addEventListener('resize',()=>{ moveSlider('ring',0); moveSlider('daychuyen',0); moveSlider('vongtay',0); moveSlider('bongtai',0); });
setTimeout(()=>{ moveSlider('ring',0); moveSlider('daychuyen',0); moveSlider('vongtay',0); moveSlider('bongtai',0); }, 200);
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?> 