<?php
/** Template Name: Câu hỏi thường gặp */
get_header();
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Câu hỏi thường gặp</li>
      </ul>
      <h1 class="page-title">Câu hỏi thường gặp</h1>
      <p class="text-muted">Tổng hợp thắc mắc phổ biến khi chọn sơn và đặt hàng tại Đại lý Sơn Phát Tấn.</p>
      <div class="cta-compact">
        <div>
          <strong>Cần tư vấn gấp?</strong>
          <p class="text-muted">Gọi số tư vấn hoặc Zalo để được kỹ thuật hỗ trợ ngay.</p>
        </div>
        <div class="cta-compact__actions">
          <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi tư vấn</a>
          <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        </div>
      </div>

      <div class="page-section" style="margin-bottom:14px;">
        <h3>1. Tôi cần tư vấn màu và diện tích, liên hệ ai?</h3>
        <p>Gọi số tư vấn <a href="tel:0944857999">0944 857 999</a> hoặc nhắn Zalo <a href="https://zalo.me/0944857999" target="_blank" rel="noopener">0944 857 999</a>. Kỹ thuật sẽ hỗ trợ đo bóc khối lượng, đề xuất hệ sơn trong 15 phút giờ hành chính.</p>

        <h3>2. Bao lâu nhận được hàng?</h3>
        <p>Trong nội thành TP.HCM: 24 giờ. Các tỉnh lân cận: 24–48 giờ. Hàng pha màu gấp vui lòng báo trước để ưu tiên.</p>

        <div class="cta-inline" style="margin:14px 0;">
          <div class="cta-inline__content">
            <div>
              <h3>Nhận báo giá theo m²</h3>
              <p class="text-muted">Gửi diện tích và bề mặt, chúng tôi đề xuất định mức phù hợp.</p>
            </div>
            <div class="cta-inline__actions">
              <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu báo giá</a>
              <a class="btn btn-outline" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Xem bảng giá</a>
            </div>
          </div>
        </div>

        <h3>3. Có xuất hóa đơn VAT và chứng nhận CO/CQ?</h3>
        <p>Có. Vui lòng cung cấp thông tin công ty khi đặt hàng; chúng tôi giao kèm hóa đơn và chứng từ hãng.</p>

        <h3>4. Điều kiện đổi trả như thế nào?</h3>
        <p>Không đổi trả với sơn đã pha màu. Các trường hợp giao sai mã, lỗi bao bì hoặc lỗi kỹ thuật sẽ được đổi trong 48 giờ (xem chi tiết tại trang <a href="<?php echo esc_url(home_url('/chinh-sach-doi-tra')); ?>">Chính sách đổi trả</a>).</p>

        <h3>5. Tôi muốn lấy giá đại lý số lượng?</h3>
        <p>Liên hệ số tư vấn để nhận bảng chiết khấu theo thương hiệu và dung tích. Đơn số lượng sẽ được giao bằng xe tải/ cẩu nếu cần.</p>
      </div>

      <div class="cta">
        <div>
          <h3>Chưa thấy câu trả lời?</h3>
          <p>Gọi hoặc nhắn Zalo, chúng tôi phản hồi ngay trong giờ làm việc.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="tel:0944857999">Gọi hỗ trợ</a>
          <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo hỗ trợ</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi câu hỏi nhanh</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
