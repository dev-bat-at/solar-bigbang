INPUT:

- Bill (VNĐ/tháng)
- DayRatio (0.3 → 0.8)
- Type ("1P" hoặc "3P")
- K (0.8 → 1.2)
- MarketFactor (0.9 → 1.2)

CONST:

- ElecPrice = 2500
- Yield = 120

FUNCTION getPricePerKW(kW, Type):
IF Type == "1P":
IF kW < 10:
RETURN 7000000
ELSE:
RETURN 6800000

    ELSE IF Type == "3P":
        IF kW < 10:
            RETURN 6800000
        ELSE IF kW < 30:
            RETURN 6200000
        ELSE:
            RETURN 5700000

MAIN:
kW = Bill / ElecPrice / Yield

PricePerKW = getPricePerKW(kW, Type)

Price = kW
_ DayRatio
_ PricePerKW
_ (1 + (DayRatio - 0.5) _ K) \* MarketFactor

OUTPUT:

- Price (VNĐ)
