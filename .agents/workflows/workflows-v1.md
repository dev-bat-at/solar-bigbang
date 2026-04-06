---
description: PHASE 1
---

# Solar Big Bang - Phase 1 Admin Workflows
## 1. Mục tiêu

File này mô tả các workflow vận hành cho hệ thống Admin Phase 1 của Solar Big Bang để đưa vào MCP hoặc dùng làm tài liệu chuẩn hóa nghiệp vụ.

Công nghệ mục tiêu:
- Laravel 
- Filament
- MySQL
- Database: `db_solar_bigbang`

---

## 2. Workflow đăng nhập Admin

### Mục đích
Cho phép admin nội bộ đăng nhập vào hệ thống quản trị và truy cập đúng quyền.

### Trigger
- Admin truy cập `/admin/login`

### Actors
- Admin user
- System auth service

### Input
- email
- password

### Steps
1. Hệ thống tải login page với logo, banner, favicon từ system settings.
2. Admin nhập email và password.
3. Hệ thống kiểm tra email tồn tại trong `admin_users`.
4. Kiểm tra trạng thái tài khoản: active / locked / inactive.
5. Kiểm tra password hash.
6. Kiểm tra admin có role hợp lệ.
7. Ghi log đăng nhập thành công hoặc thất bại.
8. Điều hướng vào dashboard theo panel admin.

### Output
- đăng nhập thành công và vào dashboard
- hoặc thông báo lỗi phù hợp

### Exceptions
- sai mật khẩu
- tài khoản bị khóa
- tài khoản inactive
- không có role

---

## 3. Workflow cấu hình branding hệ thống

### Mục đích
Cho phép Super Admin thay đổi logo login, banner login, favicon và thông tin thương hiệu.

### Actors
- Super Admin
- System settings module

### Steps
1. Super Admin mở màn hình `System Settings`.
2. Chọn nhóm `Branding`.
3. Upload logo login.
4. Upload banner login.
5. Upload favicon.
6. Cập nhật tên công ty / tiêu đề hệ thống nếu cần.
7. Lưu cấu hình.
8. Hệ thống validate file type, size.
9. Hệ thống lưu settings và ghi audit log.
10. Hệ thống clear cache giao diện.

### Output
- branding mới có hiệu lực ở login page và web admin

---

## 4. Workflow quản lý admin/nhân sự

### Mục đích
Tạo và quản lý tài khoản nội bộ để vận hành hệ thống.

### Actors
- Super Admin

### Steps tạo admin
1. Super Admin mở `Admin Users`.
2. Chọn tạo mới.
3. Nhập họ tên, email, số điện thoại.
4. Gán role chính hoặc nhiều role.
5. Thiết lập trạng thái `active`.
6. Sinh hoặc nhập mật khẩu ban đầu.
7. Bật `force_change_password` nếu cần.
8. Lưu dữ liệu.
9. Hệ thống ghi audit log.

### Steps reset mật khẩu
1. Super Admin mở hồ sơ admin.
2. Chọn `Reset Password`.
3. Hệ thống tạo mật khẩu mới hoặc cho phép nhập mật khẩu tạm.
4. Bật bắt buộc đổi mật khẩu.
5. Ghi audit log.

### Output
- admin mới được tạo
- hoặc mật khẩu được reset thành công

---

## 5. Workflow phân quyền theo role

### Mục đích
Gán vai trò và quyền đúng để đảm bảo an toàn vận hành.

### Actors
- Super Admin

### Steps
1. Super Admin mở `Roles`.
2. Tạo mới hoặc chỉnh sửa role.
3. Gán permission theo module/action.
4. Lưu role.
5. Mở `Admin Users`.
6. Gán role cho admin user.
7. Hệ thống tính effective permissions.
8. UI menu và action của admin được cập nhật theo permission.

### Output
- admin nhìn thấy đúng menu, đúng hành động được phép thực hiện

---

## 6. Workflow dashboard tổng quan

### Mục đích
Cho admin theo dõi KPI vận hành toàn hệ thống.

### Actors
- Super Admin
- CSKH
- Sales Admin
- Content Admin
- QC/Approver

### Steps
1. Admin vào dashboard.
2. Chọn khoảng thời gian.
3. Hệ thống tính toán KPI tương ứng.
4. Hệ thống hiển thị các widget phù hợp theo permission.
5. Admin click vào widget để drill-down sang module liên quan.

### KPI chính
- tổng user
- lead mới
- lead đang xử lý
- tỷ lệ chốt
- lượt báo giá
- lượt check serial
- đại lý hoạt động
- hỗ trợ tồn đọng

---

## 7. Workflow quản lý khách hàng

### Mục đích
Xem và kiểm soát danh sách khách hàng trong hệ thống.

### Actors
- CSKH
- Sales Admin
- Super Admin

### Steps
1. Mở `Customers`.
2. Lọc theo trạng thái, nguồn, ngày đăng ký, tỉnh/thành.
3. Xem danh sách khách hàng.
4. Mở chi tiết khách hàng để xem thông tin liên hệ và lịch sử.
5. Thực hiện khóa hoặc mở khóa tài khoản nếu cần.
6. Hệ thống lưu lý do khóa/mở khóa và ghi log.

### Output
- tài khoản khách hàng được cập nhật trạng thái
- lịch sử thao tác được lưu

---

## 8. Workflow quản lý tài khoản đại lý

### Mục đích
Quản trị vòng đời tài khoản đại lý.

### Actors
- Sales Admin
- Super Admin
- QC/Approver

### Steps tạo mới
1. Mở `Dealers`.
2. Tạo đại lý mới với thông tin công ty, hotline, khu vực, liên hệ.
3. Đặt trạng thái ban đầu `pending` hoặc `draft`.
4. Lưu dữ liệu.

### Steps duyệt hồ sơ
1. Approver mở danh sách dealer pending.
2. Xem đầy đủ hồ sơ.
3. Kiểm tra vùng phủ, hotline, trạng thái hoạt động.
4. Approve hoặc reject.
5. Nếu approve, gán `approved_by`, `approved_at`.
6. Nếu reject, nhập lý do reject.
7. Ghi log.

### Steps vận hành
1. Sales Admin gán khu vực phụ trách.
2. Cấu hình `priority_order`.
3. Bật/tắt `is_accepting_leads`.
4. Bật/tắt `is_active`.
5. Lưu và ghi log.

---

## 9. Workflow danh sách đại lý theo tỉnh/thành

### Mục đích
Thiết lập vùng phủ và thứ tự ưu tiên đại lý.

### Actors
- Sales Admin
- Super Admin

### Steps
1. Mở module `Dealer Coverage`.
2. Chọn tỉnh/thành.
3. Gán một hoặc nhiều đại lý vào khu vực.
4. Thiết lập thứ tự ưu tiên hiển thị/gán lead.
5. Cấu hình hotline, nút gọi/nhắn nếu cần.
6. Bật/tắt trạng thái nhận lead.
7. Lưu dữ liệu.

### Output
- cấu hình vùng phủ sẵn sàng cho app và auto-assignment

---

## 10. Workflow kiểm duyệt cập nhật hồ sơ đại lý

### Mục đích
Đảm bảo thay đổi hồ sơ đại lý được kiểm duyệt trước khi lên dữ liệu live.

### Actors
- Dealer submitter
- QC/Approver
- Super Admin

### Steps
1. Dealer gửi yêu cầu thay đổi hồ sơ.
2. Hệ thống tạo `dealer_change_request` với dữ liệu before/after.
3. Approver mở danh sách yêu cầu chờ duyệt.
4. Xem diff giữa dữ liệu cũ và mới.
5. Approve hoặc reject.
6. Nếu approve, hệ thống cập nhật dữ liệu live.
7. Nếu reject, lưu lý do reject.
8. Ghi audit log và gửi thông báo kết quả.

---

## 11. Workflow kiểm duyệt công trình đã thi công

### Mục đích
Kiểm soát nội dung công trình do đại lý tạo trước khi public.

### Actors
- Dealer
- QC/Approver
- Content Admin

### Steps
1. Dealer tạo công trình và upload ảnh.
2. Công trình ở trạng thái `pending`.
3. Approver mở danh sách công trình chờ duyệt.
4. Kiểm tra nội dung, hình ảnh, sản phẩm liên quan.
5. Chỉnh sửa nội dung cơ bản nếu được phép.
6. Approve hoặc reject.
7. Nếu approve, công trình chuyển sang trạng thái hiển thị.
8. Nếu reject, trả về lý do.

---

## 12. Workflow quản lý sản phẩm

### Mục đích
Quản trị danh mục sản phẩm, model, thông số và tài liệu.

### Actors
- Content Admin
- Super Admin

### Steps
1. Tạo nhóm sản phẩm.
2. Tạo sản phẩm/model.
3. Nhập thông số kỹ thuật.
4. Gán giá tham khảo.
5. Upload tài liệu kỹ thuật.
6. Chọn trạng thái hiển thị trên app.
7. Lưu và ghi log.

### Output
- dữ liệu sản phẩm sẵn sàng cho app, báo giá, serial mapping

---

## 13. Workflow quản lý media/tài liệu

### Mục đích
Lưu trữ và kiểm soát ảnh, PDF, tài liệu kỹ thuật.

### Actors
- Content Admin
- Super Admin

### Steps
1. Mở `Media Library`.
2. Upload file.
3. Chọn loại tài liệu và module sử dụng.
4. Hệ thống validate dung lượng và định dạng.
5. Lưu file và metadata.
6. Nếu là bản cập nhật, tạo version mới.
7. Ghi log upload/update/archive.

---

## 14. Workflow quản lý tham số báo giá

### Mục đích
Cấu hình bộ rule và bảng giá phục vụ engine báo giá.

### Actors
- Sales Admin
- Super Admin
- QC/Approver nếu cần duyệt

### Steps
1. Mở `Quotation Rules`.
2. Tạo rule set mới hoặc clone từ rule hiện tại.
3. Cấu hình:
   - bảng giá
   - ngưỡng tiền điện
   - rule 1/3 pha
   - tỷ lệ ngày/đêm
   - rule hòa lưới/hybrid/solar pump
4. Lưu dưới dạng draft.
5. Gửi duyệt nếu có approval flow.
6. Kích hoạt version mới.
7. Version cũ chuyển archive/inactive.
8. Ghi audit log.

---

## 15. Workflow lead tập trung

### Mục đích
Quản trị toàn bộ lead từ app và phân phối đúng đại lý/admin phụ trách.

### Actors
- System auto assignment
- CSKH
- Sales Admin
- Super Admin

### Steps nhận lead
1. Lead từ app được tạo vào hệ thống.
2. Hệ thống chuẩn hóa dữ liệu lead.
3. Tạo timeline đầu tiên.
4. Chạy auto-assign dealer theo tỉnh/thành, trạng thái nhận lead, ưu tiên.
5. Nếu không có dealer phù hợp, chuyển admin xử lý.
6. Gửi notification cho người nhận phụ trách.

### Steps vận hành lead
1. Admin mở danh sách lead.
2. Lọc theo nguồn, module, khu vực, dealer, trạng thái.
3. Mở chi tiết lead.
4. Cập nhật trạng thái, ghi chú, tệp đính kèm, khả năng chốt.
5. Nếu cần, gán lại dealer hoặc admin phụ trách.
6. Hệ thống tạo timeline cho mọi thay đổi quan trọng.
7. Theo dõi SLA và cảnh báo quá hạn.

---

## 16. Workflow chi tiết và timeline lead

### Mục đích
Theo dõi toàn bộ lịch sử xử lý lead.

### Actors
- CSKH
- Sales Admin
- Super Admin

### Steps
1. Mở lead detail.
2. Xem customer info, nguồn lead, khu vực, dealer được gán.
3. Xem trạng thái hiện tại và xác suất chốt.
4. Xem timeline lịch sử.
5. Thêm ghi chú nội bộ hoặc file đính kèm.
6. Cập nhật trạng thái nếu cần.
7. Hệ thống append timeline mới.

---

## 17. Workflow quản lý yêu cầu hỗ trợ

### Mục đích
Tiếp nhận và xử lý các yêu cầu hỗ trợ gửi đến đại lý hoặc admin.

### Actors
- CSKH
- Sales Admin
- Super Admin

### Steps
1. Hệ thống nhận support request từ app/dealer.
2. Tạo ticket hỗ trợ với trạng thái `new`.
3. Gán người phụ trách xử lý.
4. Theo dõi tiến độ xử lý.
5. Nếu quá SLA hoặc cần cấp cao hơn, chuyển `escalated`.
6. Khi xử lý xong, chuyển `resolved`.
7. Sau xác nhận cuối, chuyển `closed`.
8. Ghi timeline và log mọi thay đổi.

---

## 18. Workflow quản lý tin tức

### Mục đích
Quản trị bài viết, chuyên mục, tác giả và lịch đăng.

### Actors
- Content Admin
- QC/Approver
- Super Admin

### Steps
1. Tạo bài viết mới.
2. Nhập tiêu đề, summary, nội dung.
3. Chọn chuyên mục, tag, tác giả.
4. Upload ảnh cover.
5. Lưu `draft`.
6. Gửi duyệt hoặc publish trực tiếp nếu có quyền.
7. Có thể đặt lịch `publish_at`.
8. Đánh dấu bài nổi bật nếu cần.
9. Ghi log publish/update/archive.

---

## 19. Workflow quản lý check serial

### Mục đích
Kiểm soát kho serial và truy vết kết quả kiểm tra real/fake.

### Actors
- Admin vận hành
- System import
- End user/checker

### Steps import serial
1. Admin upload file import.
2. Hệ thống tạo import batch.
3. Parse file và validate dữ liệu.
4. Kiểm tra trùng trong file và DB.
5. Lưu serial hợp lệ.
6. Lưu report số dòng lỗi/thành công.
7. Ghi log import.

### Steps check serial
1. Người dùng hoặc hệ thống gửi yêu cầu tra serial.
2. Hệ thống tìm serial trong kho.
3. Trả kết quả real/fake/not found.
4. Lưu verification log.
5. Nếu phát hiện tần suất check bất thường thì tạo alert.

---

## 20. Workflow thông báo

### Mục đích
Soạn và gửi thông báo theo user segment hoặc theo sự kiện tự động.

### Actors
- Super Admin
- CSKH
- Sales Admin
- System event processor

### Steps gửi thủ công
1. Mở `Notifications`.
2. Soạn title và nội dung.
3. Chọn kênh gửi: in-app, email, push nếu có.
4. Chọn đối tượng nhận theo nhóm hoặc bộ lọc.
5. Gửi ngay hoặc hẹn lịch.
6. Hệ thống đẩy vào queue.
7. Theo dõi trạng thái gửi thành công/thất bại.

### Steps gửi tự động
1. Một event nghiệp vụ xảy ra.
2. Hệ thống kiểm tra rule notification.
3. Tạo notification phù hợp.
4. Đưa vào queue.
5. Cập nhật delivery log.

---

## 21. Workflow quản lý tỉnh/thành và danh mục dùng chung

### Mục đích
Quản lý dữ liệu master dùng chung cho toàn hệ thống.

### Actors
- Super Admin
- Admin có quyền cấu hình

### Steps
1. Mở module master data.
2. Chọn loại dữ liệu: tỉnh/thành, trạng thái lead, loại yêu cầu, loại hệ thống, tag tin tức.
3. Tạo mới hoặc chỉnh sửa bản ghi.
4. Thiết lập mã, tên, sort order, trạng thái active/inactive.
5. Lưu dữ liệu.
6. Ghi log thay đổi.

---

## 22. Workflow nhật ký hệ thống

### Mục đích
Giám sát đăng nhập, thay đổi dữ liệu và lỗi hệ thống.

### Actors
- Super Admin
- QC/Approver nếu có quyền xem log

### Steps
1. Mở `Audit Logs` hoặc `System Logs`.
2. Lọc theo user, module, action, date, IP.
3. Xem chi tiết log.
4. So sánh before/after nếu là update.
5. Dùng log để truy vết sự cố hoặc kiểm tra tuân thủ.

---

## 23. Workflow cấu hình hệ thống

### Mục đích
Quản lý các cấu hình toàn cục của hệ thống.

### Actors
- Super Admin

### Steps
1. Mở `System Settings`.
2. Chọn nh