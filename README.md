![Blinkt!](blinkt-logo.png)

https://shop.pimoroni.com/products/blinkt

Eight super-bright RGB LED indicators, ideal for adding visual notifications to your Raspberry Pi on their own or on a pHAT stacking header.

PHP port of https://github.com/pimoroni/blinkt

## Installing
```bash
composer require zghosts/blinkt
```

## Usage
```php
<?php

require_once 'vendor/autoload.php';

$gpio   = new \PiPHP\GPIO\GPIO();
$blinkt = new \Zghosts\Blinkt\Blinkt($gpio);

$blinkt->setup();
$blinkt->setPixels(255, 0, 0, 0.2);
$blinkt->show();

```

## Methods
### setup()
Connects to the GPIO and sets the GPIO pin modes. Must be called before any other commands.
Is automatically called by show if not initialized prior

### setup(int $dat = 23, int $clk=24)
Connects to an alternative set of GPIO pins and sets their modes. Can be called instead of the setup() function if you have wired blinkt up to alternative raspberry pi pins. The default values are 23 and 24 respectively.

### clear()
Resets the pixels in the buffer to black  

### setPixel($pixel, $red, $green, $blue, $brightness)
Sets the specififed pixel to the passed rgb and brightness level. The pixelNum is an integer between 0 and 7 to indicate the pixel to change.

### setBrightness($brightness)
Sets the brightness level between 0.0 (off) and 1.0 (full brightness) for all pixels.

### setAllPixels($red, $green, $blue, $brightness)
Sets all pixels to the passed rgb and brightness level.

### getPixels()
Returns the pixelbuffer as an array of Pixel[], allowing you to modify each pixel individually

### show()
This method is the most important. You can set pixels colours as much as you want but they will not update until you call this method.

### setClearOnExit
Clear the display on destruct

## Documentation & Support

* Guides and tutorials - https://learn.pimoroni.com/blinkt
* Function reference - http://docs.pimoroni.com/blinkt/
* GPIO Pinout - https://pinout.xyz/pinout/blinkt
* Get help - http://forums.pimoroni.com/c/support
