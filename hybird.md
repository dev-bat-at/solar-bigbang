INPUT:

- Bill (VNĐ/tháng)
- Type ("1P" hoặc "3P")
- MarketFactor (0.9 → 1.2)

CONST:

- ElecPrice = 2800
- Yield = 135

Battery

- BatteryPricePerKWh = 2500000
- BackupHours = 1

FUNCTION getPricePerKW(kW, Type):
IF Type == "1P":
IF kW < 10:
RETURN 10000000
ELSE:
RETURN 9000000

    ELSE IF Type == "3P":
        IF kW < 10:
            RETURN 11200000
        ELSE IF kW < 30:
            RETURN 8600000
        ELSE:
            RETURN 8500000

MAIN:

# 1. Công suất hệ

kW = Bill / ElecPrice / Yield

# 2. Mốc cố định 50%

DayRatio = 0.5

# 3. Giá điện mặt trời

PricePerKW = getPricePerKW(kW, Type)

SolarPrice = kW
_ DayRatio
_ PricePerKW \* MarketFactor

# 4. Điện tiêu thụ mỗi ngày

Daily_kWh = (Bill / ElecPrice) / 30

# 5. Dung lượng pin (theo giờ backup)

BatteryCapacity = Daily_kWh \* BackupHours

# 6. Giá pin

BatteryPrice = BatteryCapacity \* BatteryPricePerKWh

# 7. Tổng giá

TotalPrice = SolarPrice + BatteryPrice

OUTPUT:

- SolarPrice
- BatteryCapacity (kWh)
- BatteryPrice
- TotalPrice
