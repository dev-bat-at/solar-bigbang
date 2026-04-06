---
trigger: always_on
---

# Solar Big Bang - Phase 1 Admin Rules

## 1. Mục tiêu

File này định nghĩa bộ quy tắc vận hành, phân quyền, kiểm soát dữ liệu và tiêu chuẩn kỹ thuật cho hệ thống Admin Phase 1 của Solar Big Bang.

Công nghệ mục tiêu:
- Laravel 11
- Filament 3
- MySQL 8
- Database: `db_solar_bigbang`

---

## 2. Quy tắc nền tảng hệ thống

### 2.1 Kiến trúc
- Admin Phase 1 là trung tâm kiểm soát toàn hệ thống.
- Mọi dữ liệu nghiệp vụ phải đi qua lớp quản trị và được kiểm soát bởi role + permission.
- Các thao tác nhạy cảm phải có audit log.
- Các dữ liệu cần duyệt phải đi theo approval workflow, không cập nhật trực tiếp vào bản live.
- Hệ thống phải sẵn sàng mở rộng cho API/mobile phase sau.

### 2.2 Quy tắc code
- Không viết nghiệp vụ quan trọng trực tiếp trong Filament Resource.
- Logic nghiệp vụ phải đi qua Service/Action class.
- Trạng thái nên dùng Enum.
- Permission là lớp kiểm soát chính, role chỉ là nhóm permission.
- Các thao tác approve, assign, import, publish phải chạy trong DB transaction.

### 2.3 Quy tắc dữ liệu
- Không hard delete dữ liệu nghiệp vụ quan trọng.
- Dùng soft delete cho dữ liệu có vòng đời nghiệp vụ.
- Log, timeline, verification log là immutable, không cho chỉnh sửa/xóa qua UI.
- Dữ liệu master đang được tham chiếu thì không được xóa, chỉ được deactivate.

---

## 3. Quy tắc authentication admin

### 3.1 Đăng nhập admin
- Chỉ `admin_users` mới được phép đăng nhập admin panel.
- Admin phải có ít nhất 1 role active.
- Admin status phải là `active`.
- Admin bị `locked` hoặc `inactive` thì không được đăng nhập.

### 3.2 Bảo mật đăng nhập
- Rate limit đăng nhập thất bại.
- Sai mật khẩu quá ngưỡng cấu hình thì khóa tạm tài khoản.
- Log đầy đủ:
  - email đăng nhập
  - IP
  - user agent
  - thời gian
  - trạng thái thành công/thất bại
- Có thể bật force change password ở lần đăng nhập đầu tiên hoặc sau reset mật khẩu.

### 3.3 Branding login
- Login page phải hỗ trợ cấu hình động:
  - logo login
  - banner login
  - favicon
  - tên công ty/hệ thống
- Chỉ Super Admin được sửa branding.
- Khi thay đổi branding phải clear cache giao diện.

---

## 4. Quy tắc role và permission

### 4.1 Role chuẩn Phase 1
- Super Admin
- CSKH
- Sales Admin
- Content Admin
- QC/Approver

### 4.2 Permission model
Mỗi module nên có permission theo action:
- `view_any`
- `view`
- `create`
- `update`
- `delete`
- `restore`
- `force_delete`
- `approve`
- `reject`
- `assign`
- `import`
- `export`
- `publish`
- `config`
- `audit_view`

Ví dụ:
- `lead.view_any`
- `lead.assign`
- `dealer.approve`
- `news.publish`
- `setting.branding_update`

### 4.3 Rule áp dụng permission
- Hiển thị menu theo permission.
- Hiển thị nút thao tác theo permission.
- Không đủ quyền thì field nhạy cảm chỉ đọc hoặc ẩn.
- Bulk action chỉ hiện nếu user có permission tương ứng.
- Widget dashboard phải lọc theo permission.
- Super Admin có thể bypass toàn bộ permission nghiệp vụ.

### 4.4 Quy tắc gán role
- Không cho lưu admin user nếu chưa có role.
- Không cho admin tự nâng quyền cho chính mình nếu không phải Super Admin.
- Không cho xóa hoặc vô hiệu hóa Super Admin cuối cùng.
- Role chính và role phụ phải hiển thị rõ trong UI quản trị.

---

## 5. Quy tắc quản lý admin/nhân sự

- Chỉ Super Admin được tạo/sửa/xóa admin nội bộ.
- Reset mật khẩu phải được log.
- Gán role/permission phải được log.
- Có thể buộc đổi mật khẩu sau reset.
- Mọi thao tác của admin phải gắn `created_by`, `updated_by` khi phù hợp.

---

## 6. Quy tắc dashboard

- Dashboard phải hỗ trợ lọc theo thời gian:
  - hôm nay
  - 7 ngày
  - 30 ngày
  - tùy chọn
- KPI tối thiểu gồm:
  - tổng user
  - lead mới
  - lead đang xử lý
  - tỷ lệ chốt
  - số lượt báo giá
  - số lượt check serial
  - đại lý hoạt động
- Dữ liệu dashboard nên cache ngắn hạn từ 1 đến 5 phút.
- Chỉ hiển thị số liệu mà role được phép xem.

---

## 7. Quy tắc quản lý khách hàng

- Không xóa cứng khách hàng.
- Chỉ cho phép khóa/mở khóa tài khoản.
- Khi khóa tài khoản phải lưu lý do khóa.
- Lịch sử đăng ký và trạng thái tài khoản phải truy vết được.
- Dữ liệu nhạy cảm phải giới hạn theo permission xem chi tiết.

---

## 8. Quy tắc quản lý đại lý

### 8.1 Trạng thái đại lý
- `draft`
- `pending`
- `approved`
- `rejected`
- `inactive`
- `suspended`

### 8.2 Rule dữ liệu đại lý
- Đại lý chưa approved không được public.
- Đại lý inactive không được nhận lead.
- Đại lý chỉ được hiển thị ngoài app khi:
  - approved
  - active
  - có vùng phủ hợp lệ
  - có thông tin cơ bản bắt buộc
- Có thể gán nhiều tỉnh/thành cho một đại lý.
- Có `priority_order` để quyết định ưu tiên hiển thị/gán lead.

### 8.3 Rule chỉnh sửa hồ sơ đại lý
- Đại lý đã approved khi sửa hồ sơ không được cập nhật trực tiếp bản live.
- Phải tạo change request để duyệt.
- Phải lưu snapshot before/after.
- Reject bắt buộc có lý do.

---

## 9. Quy tắc lead

### 9.1 Trạng thái lead
- `new`
- `assigned`
- `contacting`
- `quoted`
- `negotiating`
- `won`
- `lost`
- `expired`
- `reopened`

### 9.2 Rule tạo lead
- Lead mới phải tạo timeline record đầu tiên.
- Lead phải có mã định danh duy nhất.
- Lead phải lưu nguồn lead, khu vực và trạng thái hiện tại.

### 9.3 Rule gán lead
Ưu tiên auto-assign theo:
1. đúng tỉnh/thành
2. dealer approved
3. dealer active
4. dealer đang nhận lead
5. `priority_order`
6. fallback cho admin nếu không có dealer phù hợp

### 9.4 Rule cập nhật lead
- Mỗi lần đổi trạng thái phải tạo timeline.
- Mỗi lần gán lại dealer phải có lý do.
- Timeline là immutable, chỉ append.
- Có thể lưu khả năng chốt đơn theo %.

### 9.5 Rule SLA lead
- Lead phải có `sla_due_at` nếu thuộc loại cần SLA.
- Lead quá SLA phải tạo cảnh báo.
- Dashboard phải có chỉ số lead quá SLA.

---

## 10. Quy tắc timeline và ghi chú lead

- Phân biệt `system event`, `admin action`, `dealer action`.
- Ghi chú nội bộ và ghi chú hiển thị ngoài phải tách riêng.
- File đính kèm phải lưu người upload và thời gian upload.
- Không cho sửa timeline lịch sử, chỉ được thêm mới.

---

## 11. Quy tắc support request

### 11.1 Trạng thái
- `new`
- `in_progress`
- `waiting_response`
- `escalated`
- `resolved`
- `closed`

### 11.2 Rule
- Mỗi yêu cầu hỗ trợ phải có SLA theo loại yêu cầu.
- Quá SLA phải tự động escalate hoặc cảnh báo.
- Không cho đóng request nếu chưa có kết luận cuối.
- Mọi lần chuyển người phụ trách phải được log.

---

## 12. Quy tắc sản phẩm và tài liệu

### 12.1 Sản phẩm
- Mỗi model phải có mã duy nhất.
- Trạng thái sản phẩm đề xuất:
  - `draft`
  - `published`
  - `hidden`
- Không xóa cứng sản phẩm đã tham gia báo giá hoặc serial.

### 12.2 Media/tài liệu
- Chỉ cho phép định dạng trong whitelist.
- Giới hạn dung lượng theo system settings.
- File đang được sử dụng không được xóa trực tiếp, chỉ archive.
- Upload bản mới thì tăng version.

---

## 13. Quy tắc báo giá và rule engine

### 13.1 Rule versioning
- Một rule set đang active không nên sửa trực tiếp nếu đã phát sinh dữ liệu liên quan.
- Khi thay đổi cần clone version mới.
- Mỗi thời điểm chỉ có 1 version active cho cùng một nhóm rule.

### 13.2 Rule áp dụng
Các rule tối thiểu:
- bảng giá
- ngưỡng tiền điện
- 1 pha / 3 pha
- tỷ lệ ngày / đêm
- loại hệ thống: hòa lưới / hybrid / solar pump

### 13.3 Rule audit
- Mọi thay đổi rule phải log before/after.
- Phải lưu người thay đổi, thời gian, version, lý do thay đổi.

---

## 14. Quy tắc tin tức và nội dung

### 14.1 Trạng thái bài viết
- `draft`
- `pending_review`
- `scheduled`
- `published`
- `archived`

### 14.2 Rule nội dung
- `slug` bài viết phải unique.
- Bài `scheduled` phải có `publish_at`.
- Chỉ người có quyền `publish` mới được xuất bản.
- Bài nổi bật phải có giới hạn số lượng cấu hình.

---

## 15. Quy tắc kiểm duyệt công trình đại lý

- Công trình dealer tạo mới mặc định ở trạng thái pending.
- Chỉ công trình approved mới được public.
- Ảnh phải đúng định dạng và dung lượng theo quy định.
- Approver có thể chỉnh sửa nội dung cơ bản trước khi duyệt.
- Reject bắt buộc có lý do.

---

## 16. Quy tắc serial

### 16.1 Rule kho serial
- `serial_number` phải unique.
- Serial phải gắn với product/model hợp lệ.
- Trạng thái serial đề xuất:
  - `unused`
  - `activated`
  - `flagged`
  - `invalid`

### 16.2 Rule import serial
- Import theo batch.
- Validate trùng trong file và trùng trong DB.
- Lưu kết quả import: tổng dòng, thành công, lỗi, lý do lỗi.

### 16.3 Rule check serial
- Mỗi lượt tra cứu phải ghi log.
- Nếu số lần check bất thường trong khoảng thời gian ngắn thì tạo alert.
- Kết quả real/fake phải được lưu để phục vụ phân tích.

---

## 17. Quy tắc notification

### 17.1 Sự kiện cần gửi thông báo
- lead mới
- lead gán lại
- lead quá SLA
- dealer pending approval
- dealer profile change pending
- project pending approval
- support request escalated
- serial abnormal alert
- import serial lỗi

### 17.2 Rule gửi
- Gửi hàng loạt phải qua queue.
- Mỗi notification phải có:
  - title
  - body
  - target_type
  - target_id
  - priority
  - action_url
  - read_at
- Tránh gửi trùng theo event bằng idempotent key nếu cần.

---

## 18. Quy tắc log và audit

### 18.1 Các hành động bắt buộc log
- login success/fail
- logout
- create/update/delete
- approve/reject
- assign lead/dealer
- reset password
- role assignment
- permission change
- settings update
- import/export
- lỗi hệ thống quan trọng

### 18.2 Nội dung log tối thiểu
- actor_id
- actor_type
- actor_role
- module
- action
- target_type
- target_id
- before_json
- after_json
- ip
- user_agent
- created_at

### 18.3 Rule log
- Log không cho sửa/xóa qua UI.
- Log phải filter được theo module, user, action, date, IP.

---

## 19. Quy tắc system settings

### 19.1 Nhóm settings tối thiểu
- branding
- company info
- security
- upload
- notification
- data limit

### 19.2 Rule cập nhật settings
- Chỉ Super Admin được sửa settings quan trọng.
- Settings quan trọng phải log before/after.
- Có snapshot backup hoặc version khi thay đổi settings nhạy cảm.
- Settings upload phải validate mime type, extension, size.

---

## 20. Quy tắc database

### 20.1 Database name
- Database hệ thống: `db_solar_bigbang`

### 20.2 Quy ước thiết kế bảng
- Dùng `id` bigint unsigned auto increment hoặc UUID theo chiến lược dự án.
- Có `created_at`, `updated_at` cho bảng nghiệp vụ.
- Có `softDeletes` cho bảng cần khôi phục.
- Tạo index cho các cột filter/search thường dùng.
- Tạo unique index cho:
  - email admin
  - mã dealer
  - mã product/model
  - slug bài viết
  - serial_number

---

## 21. Quy tắc hiển thị trong Filament

- Menu nhóm rõ theo module.
- Badge trạng thái phải nhất quán màu và tên.
- Role và quyền phải hiển thị rõ trong hồ sơ admin.
- Form phải ẩn/readonly field nhạy cảm nếu không đủ quyền.
- Dashboard widget phải điều kiện hóa theo permission.
- Nút approve/reject/publish/export chỉ hiện khi có quyền tương ứng.

---

## 22. Quy tắc triển khai Phase 1

Thứ tự ưu tiên triển khai:
1. Core auth + branding + role/permission
2. Admin user + audit log + settings
3. Provinces/master data
4. Dealer + dealer approval
5. Customer
6. Lead + timeline + SLA
7. Support request
8. Product + media
9. News/content
10. Quotation rules
11. Serial management
12. Notifications
13. Dashboard KPI

---

## 23. Acceptance rules Phase 1

Phase 1 được xem là đạt khi:
- Admin login hoạt động ổn định.
- Branding login/logo/favicon cấu hình được từ settings.
- Role và permission gán đúng, hiển thị rõ.
- Dealer approval workflow hoạt động.
- Lead assignment + timeline + SLA hoạt động.
- Có audit log đầy đủ.
- Có system settings, serial management, media, news, dashboard cơ bản.

