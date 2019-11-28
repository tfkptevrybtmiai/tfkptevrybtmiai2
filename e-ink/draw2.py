#!/usr/bin/python
# -*- coding:utf-8 -*-

import epd7in5b
import time
from PIL import Image,ImageDraw,ImageFont
import traceback
import sys

try:
    epd = epd7in5b.EPD()
    epd.init()
    print("Clear...")
    epd.Clear(0xFF)
    image = Image.open(sys.argv[1])
    epd.display(epd.getbuffer(image, 0), epd.getbuffer(image, 1))
    epd.sleep()

except:
    print('traceback.format_exc():\n%s',traceback.format_exc())
    exit()
