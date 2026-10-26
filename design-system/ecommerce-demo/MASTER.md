# Design System: Ecommerce Demo

Nguồn sự thật cho giao diện frontend ([frontend/src/styles.css](../../frontend/src/styles.css)). Khi chỉnh giao diện, đọc file này trước.

- **Phong cách:** sàn thương mại điện tử (cam đỏ, nền xám nhạt, thẻ trắng, mật độ thông tin cao), lấy cảm hứng từ các sàn phổ biến tại Việt Nam. Không dùng logo, tên hay hình ảnh của bất kỳ thương hiệu nào.
- **Công cụ:** skill [UI/UX Pro Max](../../.claude/skills/ui-ux-pro-max/SOURCE.txt) v2.15.0 (cài vào `.claude/skills/`), stack `react`.
- **Nguyên tắc:** mobile-first, tương phản chữ ≥ 4.5:1, vùng chạm ≥ 36-44px, không nhảy bố cục, tôn trọng `prefers-reduced-motion`.

## Quy trình đã chạy với skill

```text
python .claude/skills/ui-ux-pro-max/scripts/search.py \
  "ecommerce marketplace shopping mobile-first vibrant product grid" \
  --design-system -p "Ecommerce Demo" -f markdown --density 6
```

(Máy chưa cài Python nên chạy trong container `python:3.12-slim`, mount thư mục skill ở chế độ chỉ đọc.)
Sau đó tra thêm theo từng vấn đề: `--domain ux` (thẻ sản phẩm, skeleton/layout shift, thanh tìm kiếm và điều hướng dưới, stepper và form) và `--stack react` (key, lazy, memo).

## Đã áp dụng và khác biệt so với đề xuất của skill

| Hạng mục | Skill đề xuất | Dự án dùng | Lý do |
| --- | --- | --- | --- |
| Màu chủ đạo | Xanh lá `#059669`, nhấn cam `#EA580C` | **Cam đỏ** `#ee4d2d` (và bản đậm) | Yêu cầu giao diện kiểu sàn thương mại điện tử, ưu tiên hơn đề xuất chung của công cụ |
| Chữ trên nền cam | Chữ **đen** trên `#EA580C` | Chữ trắng trên cam **đậm** `#d93a1c` | Skill đúng khi cảnh báo chữ trắng trên cam sáng chỉ ~3.6:1. Dùng bản đậm để chữ trắng đạt 4.59:1 |
| Font | Rubik + Nunito Sans (Google Fonts) | Font hệ thống (Roboto, Segoe UI, system-ui) | Không gọi máy chủ ngoài (nhanh hơn, không lộ IP người dùng, CI không phụ thuộc mạng), tiếng Việt có dấu hiển thị tốt |
| Mẫu trang | "Feature-Rich Showcase" (landing page) | Bố cục sàn: banner, danh mục, thanh sắp xếp, lưới sản phẩm | Đó là mẫu cho trang giới thiệu, không phải trang bán hàng |
| Chế độ tối | "Supported" | Chưa làm | Ngoài phạm vi, ghi ở mục "Còn thiếu" |

Các quy tắc của skill được áp dụng nguyên: icon SVG (không emoji), `aspect-ratio` cho ảnh, skeleton giữ chỗ, vòng focus rõ, `prefers-reduced-motion`, trạng thái "không có kết quả" có gợi ý, thông báo số lượng giỏ hàng bằng `role="status"`, nhãn `nowrap`, `loading="lazy"` cho ảnh, `key` ổn định trong danh sách.

## Màu (biến CSS trong `:root`)

| Biến | Giá trị | Dùng cho | Tương phản |
| --- | --- | --- | --- |
| `--primary` | `#ee4d2d` | Viền nhấn, cuối gradient banner (chỉ chữ lớn ≥ 24px) | trắng 3.66:1 |
| `--primary-strong` | `#d93a1c` | Nền nút chính, header, **giá**, chip đang chọn | trắng 4.59:1 |
| `--primary-deep` | `#c93418` | Link, nút viền, nhãn nhỏ, đầu gradient header | trắng 5.27:1 |
| `--primary-tint` | `#fff1ee` | Nền hover, icon danh mục | chữ `--primary-deep` 4.78:1 |
| `--bg` / `--surface` | `#f5f5f5` / `#fff` | Nền trang / thẻ | |
| `--text` / `--muted` | `#222` / `#6b6b6b` | Chữ chính / phụ | 14.6:1 / 4.89:1 (trên nền trang) |
| `--success` `--danger` `--warning` `--info` | `#1e7a3c` `#c62828` `#8a5a00` `#1b47ad` | Trạng thái | đều ≥ 4.5:1 |

Đã kiểm tra bằng cách tính trực tiếp 19 cặp màu từ file CSS. Quy tắc: **không đặt chữ trắng cỡ nhỏ lên `--primary`**.

## Chữ

Font hệ thống, nền 16px, `line-height` 1.5. Tiêu đề trang 20-22px; giá nổi bật 28px; mô tả nhỏ nhất 12px (chỉ cho nhãn và huy hiệu). Số tiền hiển thị `₫3.680.000` (ký hiệu nhỏ đứng trước), định dạng `vi-VN`.

## Bố cục

| Điểm ngắt | Thay đổi |
| --- | --- |
| mặc định (< 560px) | Lưới 2 cột; header một hàng (logo, tìm kiếm, giỏ); thanh điều hướng dưới cố định (≤ 5 mục) |
| ≥ 560px | Lưới 3 cột |
| ≥ 768px | Lưới 4 cột; hiện thanh tài khoản phía trên, ẩn thanh dưới; giỏ hàng dạng bảng |
| ≥ 1000px / ≥ 1100px | Lưới 5 / 6 cột |

Container tối đa 1200px, lề 16px. Header dính (`position: sticky`) nằm trong luồng nên không che nội dung.

## Thành phần

- **Thẻ sản phẩm:** ảnh vuông, tên tối đa 2 dòng, giá màu chủ đạo, "Đã bán" từ dữ liệu thật (`sold_count`, không tính đơn huỷ), nhãn "Chỉ còn N" khi tồn kho ≤ 5, phủ "Hết hàng" khi bằng 0. Hover nhấc nhẹ chỉ trên thiết bị có chuột.
- **Thanh sắp xếp:** Mới nhất, Bán chạy, Giá (chọn thấp-cao hoặc cao-thấp). Trạng thái nằm trên URL.
- **Bộ chọn số lượng (`QuantityStepper`):** nút 40px, giữ giá trị trong `[min, max]`.
- **Phân trang:** số trang có dấu `…`, `aria-current="page"`.
- **Skeleton:** cùng kích thước nội dung thật; tự dừng khi bật giảm chuyển động.
- **Giỏ hàng:** thanh thanh toán dính đáy; mobile hiển thị theo thẻ, desktop theo bảng.
- **Icon:** bộ SVG tự vẽ trong `components/icons.jsx`, luôn `aria-hidden`; nút chỉ có icon phải có `aria-label`.

## Còn thiếu / hướng cải thiện

- Ảnh sản phẩm mẫu lấy từ picsum.photos (ảnh phong cảnh ngẫu nhiên, không khớp sản phẩm). Thay bằng ảnh thật; nên phục vụ WebP/AVIF với `srcset`.
- Chưa có chế độ tối.
- Tìm kiếm chưa gợi ý khi gõ (autocomplete) như skill khuyến nghị.
- Chưa có đánh giá sao, giảm giá, flash sale vì chưa có dữ liệu thật tương ứng (không bịa số liệu).
- Bản đồ danh mục → icon nằm cố định cho 5 slug mẫu (`CategoryIcon.jsx`); danh mục mới dùng icon hộp mặc định.
