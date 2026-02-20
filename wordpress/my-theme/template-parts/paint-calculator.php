<?php
/**
 * Paint area & quantity calculator (UI only).
 */
?>
<section class="page-section paint-calculator" id="tinh-son">
  <div class="section-heading">
    <div>
      <h2 class="section-title">Tính diện tích & lượng sơn</h2>
      <p class="section-sub">Nhập kích thước hoặc diện tích để ước tính số lít và xô sơn.</p>
    </div>
  </div>

  <div class="calc-grid">
    <div class="calc-card">
      <div class="calc-field">
        <label for="calc-length">Chiều dài (m)</label>
        <input id="calc-length" type="number" min="0" step="0.1" placeholder="Ví dụ: 5.2">
      </div>
      <div class="calc-field">
        <label for="calc-width">Chiều rộng (m)</label>
        <input id="calc-width" type="number" min="0" step="0.1" placeholder="Ví dụ: 3.8">
      </div>
      <div class="calc-field">
        <label for="calc-area">Hoặc nhập diện tích (m²)</label>
        <input id="calc-area" type="number" min="0" step="0.1" placeholder="Ví dụ: 20">
        <small>Ưu tiên diện tích nếu bạn nhập trực tiếp.</small>
      </div>
      <div class="calc-field">
        <label for="calc-coats">Số lớp sơn</label>
        <input id="calc-coats" type="number" min="1" step="1" value="2">
      </div>
      <div class="calc-field">
        <label for="calc-surface">Loại bề mặt</label>
        <select id="calc-surface">
          <option value="10">Tường mới (10 m² / lít)</option>
          <option value="8">Tường cũ (8 m² / lít)</option>
        </select>
      </div>
    </div>

    <div class="calc-card calc-result">
      <div class="calc-result__item">
        <span>Diện tích tính toán</span>
        <strong data-out="area">0 m²</strong>
      </div>
      <div class="calc-result__item">
        <span>Tổng lượng sơn cần</span>
        <strong data-out="liters">0 lít</strong>
      </div>
      <div class="calc-result__item">
        <span>Gợi ý xô sơn</span>
        <strong data-out="buckets">—</strong>
      </div>
      <div class="calc-note">Khuyến nghị chỉ mang tính tham khảo.</div>
      <div class="calc-actions">
        <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi tư vấn</a>
        <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        <a class="btn btn-accent btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
      </div>
    </div>
  </div>
</section>
