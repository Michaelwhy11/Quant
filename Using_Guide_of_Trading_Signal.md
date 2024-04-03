# Trading_Signal.php Using Guide

This is an introducing document for explaining how Trading_Signal.php works. 

## Trading signals 

* Simple Moving Average
* Exponential Moving Average
* MACD(with a period of 9)
* HH, HL/LL, LH
* Mean Reversion
* Money FLow Index
* Ease of Movement

## Signals
**Simple Moving Average**: Short-term SMA crosses above long-term SMA, it's a buying signal(Golden Cross); Short-term SMA crosses below long-term SMA, it's a selling signal(Dead Cross).  

**MACD**: DIFF crosses above DEA, the market is bullish and it's a buying signal; DIFF crosses below DEA, the market is bearish and it's a selling signal. DIFF is the difference between EMA12 and EMA 26, and DEA is the EMA9 of DIFF.  

**HH, HL/LL, LH**: When a higher high or a higher low occurs, the price is going to rise and it's a buying signal; When a lower low or a lower high occurs, the price is going to fall and it's a selling signal. To prevent getting a fake peak, or, nadir, we set the range at 11, which means that we look the front 5 and behind 5 to find the local max and min. It gives ranges of rising channels and falling channels.  

**Mean Reversion**: The price will continually approach the mean. We combine it with RSI to find the signal. If the price is below the lower bound and RSI is less than 0.3, it's a strong buying signal; If the price is above higher bound and RSI is higher than 0.7, it's a strong selling signal.  

**Money FLow Index**: It considers both the price and the volume. It is used as RSI. If MFI crosses above SMA30 of prices and MFI is less than 0.5, it's a strong buying signal; If MFI crosses below SMA30 of prices and MFI is higher than 0.5, it's a strong selling signal.  

**Ease of Movement**: It is invented by Richard Arms and measures how easy it is for the price goes up. If there is a golden cross and EOM is positive, it's a strong buying signal; If there is a dead cross and EOM is negative, it's a strong selling signal.

## When to buy
Each function will return an array with a key-array of date and a value-array of signal. If the value is 1, it means on this day investors should buy(long) this stock. If the value is -1, it means that on this day investors shouldsell(short) this stock. If the value is 0, it means that on this day investors shell not take any actions(If having positions then they should hold, if having no positions then they shold not enter the market).

## Backtesting
We calculate how much we could earn when backtesting. If the signal is 1, we buy(long) 1 share, if the signal is -1, we sell(short) 1 share, if the signal is 0, we take no actions. At the end of the period, we close our positions by taking the oppsite actions. We then calculate the total earnings throughout the whole period.

## Backtesting2
Instead of calculating how much money we could earn, in backtesting2 we take the return ratio into account. When buying(longing) the position, we use position * (current price / last price); When selling(shorting) position, we use position * (last price / current price). If the position is zero, we set current return is 1. Then we multiply all the returns to get the return in the whole period.

## Main Problem
The main problem of this code is the speed and memory limit. The total number of stocks is 120000+ and each one has a nearly 500 time series. Set the memory limit to -1 may solve the problem, but it could cause the data missing when processing the code. The current method is to calculate the short period of time to make time series as short as possible. It would not influence the effect shown in the screener since it only shows present signal each time.
