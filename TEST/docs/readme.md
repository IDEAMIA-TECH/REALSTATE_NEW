# Property Valuation Calculations Documentation

## Overview
This document explains the calculations used for property valuations in the system. The calculations are based on the CSUSHPINSA (Case-Shiller U.S. National Home Price Index) and include several key metrics for property valuation.

## Calculation Formulas

### 1. Current Value (Valor Actual)
**Formula:**
```
Current Value = Initial Valuation + Appreciation
```

**Components:**
- `Initial Valuation`: Initial property value
- `Appreciation`: Accumulated appreciation since effective date

### 2. Appreciation (Apreciación)
**Formula:**
```
Appreciation = Initial Valuation * Appreciation Rate
```

**Appreciation Rate Calculation:**
```
Appreciation Rate = (Current Index Value - Initial Index Value) / Initial Index Value
```

**Components:**
- `Current Index Value`: Current home price index value
- `Initial Index Value`: Index value at effective date
- `Initial Valuation`: Initial property value

### 3. Share Appreciation (Apreciación Compartida)
**Formula:**
```
Share Appreciation = Appreciation * (Agreed Percentage / 100)
```

**Components:**
- `Appreciation`: Total calculated appreciation
- `Agreed Percentage`: Agreed share percentage

### 4. Terminal Value (Valor Terminal)
**Formula:**
```
Terminal Value = Current Value * (1 + (Appreciation Rate / 100)) ^ Years Remaining
```

**Components:**
- `Current Value`: Current property value
- `Appreciation Rate`: Current appreciation rate
- `Years Remaining`: Remaining contract years

### 5. Projected Payoff (Pago Proyectado)
**Formula:**
```
Projected Payoff = Terminal Value * (Agreed Percentage / 100)
```

**Components:**
- `Terminal Value`: Calculated terminal value
- `Agreed Percentage`: Agreed share percentage

### 6. Option Value (Valor de la Opción)
**Formula:**
```
Option Value = Projected Payoff - Option Price
```

**Components:**
- `Projected Payoff`: Calculated projected payoff
- `Option Price`: Agreed option price

## Example Calculation

Consider a property with:
- Initial Valuation: $500,000
- Agreed Percentage: 20%
- Initial Index Value: 100
- Current Index Value: 110
- Years Remaining: 5
- Option Price: $50,000

1. **Appreciation Rate:**
   ```
   (110 - 100) / 100 = 0.10 (10%)
   ```

2. **Appreciation:**
   ```
   $500,000 * 0.10 = $50,000
   ```

3. **Current Value:**
   ```
   $500,000 + $50,000 = $550,000
   ```

4. **Share Appreciation:**
   ```
   $50,000 * 0.20 = $10,000
   ```

5. **Terminal Value:**
   ```
   $550,000 * (1 + 0.10)^5 = $885,780.50
   ```

6. **Projected Payoff:**
   ```
   $885,780.50 * 0.20 = $177,156.10
   ```

7. **Option Value:**
   ```
   $177,156.10 - $50,000 = $127,156.10
   ```

## Important Notes

1. Calculations assume the current appreciation rate will remain constant for the remaining contract term.
2. Terminal value is a projection and may vary significantly from actual outcomes.
3. Option value represents the potential net benefit to the investor.
4. All monetary values are rounded to 2 decimal places for display.
5. Percentages are displayed with 2 decimal places.

## Technical Implementation

The calculations are implemented in the following files:
- `TEST/modules/csushpinsa/CSUSHPINSA.php`: Core calculation logic
- `TEST/modules/admin/properties.php`: Display and user interface
- `TEST/modules/admin/get_valuation_history.php`: Data retrieval and formatting

## Database Schema

The following tables are involved in the calculations:
- `properties`: Stores initial property data
- `property_valuations`: Stores calculated valuations
- `home_price_index`: Stores index values for calculations
