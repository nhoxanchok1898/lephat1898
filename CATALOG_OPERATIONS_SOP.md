# Catalog Operations SOP

## Mục tiêu

Tài liệu này quy định cách thêm, rà và hoàn thiện sản phẩm trên website Sơn Phát Tấn để:

1. Không publish sản phẩm thiếu dữ liệu quan trọng.
2. Đội quản lý xử lý nhanh các nhóm `thiếu giá`, `ảnh nhỏ`, `thiếu nguồn`.
3. Dữ liệu catalog nhất quán giữa frontend và admin.

## Công cụ đang có

### WooCommerce > Catalog QA
Chức năng hiện có:
- Lọc theo lỗi:
  - Thiếu giá
  - Ảnh nhỏ
  - Thiếu quy cách
  - Thiếu nguồn
  - Có ảnh local tốt hơn
- Xuất CSV theo bộ lọc
- Xuất mẫu CSV nhập giá
- Xuất CSV ảnh nhỏ
- Nhập CSV giá
- Nhập CSV ảnh

### File xuất sẵn
- `exports/catalog-missing-prices-priority.csv`
- `exports/catalog-low-res-images-template.csv`

## Quy trình thêm sản phẩm mới

### Bước 1. Tạo sản phẩm
Bắt buộc nhập:
- Tên sản phẩm
- Slug sạch
- Hãng
- Danh mục
- Mô tả ngắn
- Mô tả chi tiết
- Quy cách
- Ảnh đại diện
- Nguồn hãng

### Bước 2. Chuẩn hóa quy cách
Chỉ dùng 1 trong 3 nhóm:
- Dung tích: `1L | 5L | 18L`
- Khối lượng: `1kg | 20kg | 40kg`
- Quy cách đặc biệt: `300ml/chai`, `600ml/sausage`, `Cuộn 1m x 20m`

Không nhập lẫn lộn nếu không cần.

### Bước 3. Giá
Chia làm 2 trường hợp:

1. Có giá rõ từ nguồn official:
   - nhập `Price` hoặc `PriceMap`
2. Không có giá rõ:
   - để `Liên hệ báo giá`

Không được để giá `0` giả.

### Bước 4. Kiểm tra frontend
Trước khi publish, phải xem lại:
- card ở shop
- trang sản phẩm
- mobile

## Tiêu chuẩn publish

Một sản phẩm chỉ nên coi là `đã hoàn thiện` khi đủ:

- Ảnh chấp nhận được
- Tên chuẩn
- Đúng hãng và danh mục
- Có mô tả ngắn
- Có mô tả chi tiết
- Có quy cách
- Có giá thật hoặc trạng thái báo giá hợp lệ
- Có nguồn hãng
- Có CTA đúng

## Quy trình xử lý thiếu giá

### Mục tiêu
Đưa các mã `Liên hệ báo giá` về đúng giá thật khi có nguồn đủ rõ.

### Cách làm

1. Vào `WooCommerce > Catalog QA`
2. Lọc `Thiếu giá`
3. Bấm `Xuất mẫu CSV nhập giá`
4. Điền một trong hai cột:
   - `Price`
   - `PriceMap`
5. Upload lại bằng `Nhập CSV giá`

### Format chuẩn

#### Giá đơn
`Price = 442500`

#### Giá theo quy cách
`PriceMap = 5L:442500 | 18L:1501500`

### Quy tắc
- Nếu sản phẩm có nhiều quy cách, ưu tiên `PriceMap`
- Nếu nguồn chưa rõ, không tự đoán
- Nếu giá theo công trình, tiếp tục để `Liên hệ báo giá`

## Quy trình xử lý ảnh nhỏ

### Mục tiêu
Loại dần ảnh packshot quá nhỏ khỏi các sản phẩm bán chính.

### Cách làm

1. Vào `WooCommerce > Catalog QA`
2. Lọc `Ảnh nhỏ`
3. Bấm `Xuất CSV ảnh nhỏ`
4. Điền cột `Replacement Image URL`
5. Upload lại bằng `Nhập CSV ảnh`

### Nguồn ảnh chấp nhận
- Trang hãng chính thức
- CDN chính thức của hãng
- Thư viện nội bộ đã xác minh đúng mã

### Không dùng
- Ảnh random từ Google
- Ảnh mờ / crop sai sản phẩm
- Ảnh không khớp mã hoặc dung tích

## Quy tắc đặt ưu tiên

### Ưu tiên cao
- Có nguồn hãng rõ
- Chỉ có 1 lỗi
- Chỉ có 1 quy cách
- Sản phẩm thuộc nhóm bán chính

### Ưu tiên trung bình
- Có nguồn nhưng nhiều quy cách
- Ảnh chấp nhận được nhưng chưa đẹp

### Ưu tiên thấp
- Thiếu nguồn
- Không có ảnh lớn hơn
- Giá không public từ hãng

## Checklist rà hàng tuần

### Mỗi tuần
- Rà `Thiếu giá`
- Rà `Ảnh nhỏ`
- Rà `Thiếu nguồn`
- Hoàn thiện tối thiểu 10 sản phẩm
- Viết tối thiểu 2 bài blog kéo về sản phẩm

### Mỗi tháng
- Rà top sản phẩm view cao nhưng chưa có giá
- Rà top sản phẩm view cao nhưng ảnh xấu
- Rà sản phẩm có tài liệu kỹ thuật nhưng chưa gắn đầy đủ

## Checklist khi sửa sản phẩm cũ

- Không xóa quy cách đúng đang live
- Không ghi đè ảnh tốt bằng ảnh nhỏ hơn
- Không đổi giá nếu chưa có nguồn
- Không đổi slug nếu không bắt buộc
- Sau khi sửa phải xem lại:
  - shop card
  - single product
  - mobile

## Phân vai nên dùng

### Người nhập dữ liệu
- Thêm mô tả
- Thêm quy cách
- Điền CSV giá
- Điền CSV ảnh

### Người kiểm duyệt
- Check nguồn
- Check ảnh đúng mã
- Check sản phẩm trên frontend

### Người quản lý
- Chốt nhóm ưu tiên tuần
- Theo dõi số mã thiếu giá / ảnh nhỏ còn lại

## KPI vận hành catalog

- Số mã `Thiếu giá` còn lại
- Số mã `Ảnh nhỏ` còn lại
- Số mã `Thiếu nguồn` còn lại
- Số sản phẩm hoàn thiện mỗi tuần
- Tỉ lệ sản phẩm có tài liệu kỹ thuật
