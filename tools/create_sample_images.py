import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


BASE = Path(__file__).resolve().parents[1]
STATIC_IMG = BASE / 'static' / 'images'
MEDIA_PRODUCTS = BASE / 'media' / 'products'


def ensure(path: Path):
    path.parent.mkdir(parents=True, exist_ok=True)


def create_logo(path: Path):
    ensure(path)
    w, h = 200, 60
    img = Image.new('RGBA', (w, h), (13, 110, 253, 255))
    draw = ImageDraw.Draw(img)
    try:
        font = ImageFont.truetype('arial.ttf', 28)
    except Exception:
        font = ImageFont.load_default()
    draw.text((16, 16), 'Paint Store', fill=(255, 255, 255, 255), font=font)
    img.save(path)
    print('Created logo:', path)


def create_product_image(path: Path):
    ensure(path)
    w, h = 800, 800
    img = Image.new('RGB', (w, h), (240, 240, 240))
    draw = ImageDraw.Draw(img)
    try:
        font = ImageFont.truetype('arial.ttf', 48)
    except Exception:
        font = ImageFont.load_default()
    text = 'Sample Product'
    # Compute text size in a way compatible with multiple Pillow versions
    try:
        bbox = draw.textbbox((0, 0), text, font=font)
        tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    except Exception:
        try:
            bbox = font.getbbox(text)
            tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
        except Exception:
            mask = font.getmask(text)
            tw, th = mask.size
    draw.rectangle(((40, 40), (w - 40, h - 40)), outline=(200, 200, 200), width=6)
    draw.text(((w - tw) // 2, (h - th) // 2), text, fill=(80, 80, 80), font=font)
    img.save(path, quality=90)
    print('Created product image:', path)


def main():
    STATIC_IMG.mkdir(parents=True, exist_ok=True)
    MEDIA_PRODUCTS.mkdir(parents=True, exist_ok=True)

    logo_path = STATIC_IMG / 'logo.png'
    product_path = MEDIA_PRODUCTS / 'sample_product.jpg'

    create_logo(logo_path)
    create_product_image(product_path)


if __name__ == '__main__':
    main()
