import os
from io import BytesIO

from django.conf import settings
from django.core.files.base import ContentFile
from django.core.files.storage import default_storage


def _open_image(path):
    from PIL import Image

    return Image.open(path).convert("RGBA")


def _draw_text_watermark(base_img, text="Paint Store", opacity=160, margin=12):
    from PIL import ImageDraw, ImageFont

    img = base_img.copy()
    draw = ImageDraw.Draw(img)
    try:
        font = ImageFont.truetype("arial.ttf", max(14, img.width // 20))
    except Exception:
        font = ImageFont.load_default()

    # compute text size compatibly across Pillow versions
    try:
        bbox = draw.textbbox((0, 0), text, font=font)
        text_w, text_h = bbox[2] - bbox[0], bbox[3] - bbox[1]
    except Exception:
        try:
            bbox = font.getbbox(text)
            text_w, text_h = bbox[2] - bbox[0], bbox[3] - bbox[1]
        except Exception:
            mask = font.getmask(text)
            text_w, text_h = mask.size

    x = img.width - text_w - margin
    y = img.height - text_h - margin

    # draw semi-transparent rectangle behind text
    rect = (x - 8, y - 4, x + text_w + 8, y + text_h + 4)
    draw.rectangle(rect, fill=(0, 0, 0, int(opacity * 0.5)))
    draw.text((x, y), text, font=font, fill=(255, 255, 255, opacity))
    return img


def _apply_logo_overlay(base_img, logo_path, scale_ratio=0.18, margin=12):
    from PIL import Image

    logo = _open_image(logo_path)
    # resize logo to be a fraction of base image width
    max_w = int(base_img.width * scale_ratio)
    ratio = max_w / float(logo.width)
    new_size = (max_w, int(logo.height * ratio))
    try:
        resample = Image.Resampling.LANCZOS
    except Exception:
        try:
            resample = Image.LANCZOS
        except Exception:
            resample = Image.NEAREST
    logo = logo.resize(new_size, resample=resample)

    # position bottom-right
    x = base_img.width - logo.width - margin
    y = base_img.height - logo.height - margin

    base = base_img.copy()
    base.paste(logo, (x, y), logo)
    return base


def watermark_product_image(instance, field_name='image'):
    """Apply watermark to the image file attached to `instance.<field_name>`.

    Returns the new storage name if created, otherwise None.
    """
    field = getattr(instance, field_name, None)
    if not field:
        return None

    try:
        # use storage path if available
        path = field.path
    except Exception:
        # fallback: build path from MEDIA_ROOT
        name = getattr(field, 'name', None)
        if not name:
            return None
        path = os.path.join(settings.MEDIA_ROOT, name)

    # prefer checking the actual filesystem path (storage may not reflect immediate disk state)
    if not os.path.exists(path):
        return None

    # open base image for raster formats; SVGs handled below
    base = None
    try:
        base = _open_image(path)
    except Exception:
        # if original is SVG, create a raster placeholder and continue
        base_name, ext = os.path.splitext(field.name)
        if ext.lower() == '.svg':
            from PIL import Image, ImageDraw, ImageFont

            w, h = 800, 800
            base = Image.new('RGBA', (w, h), (255, 255, 255, 255))
            draw = ImageDraw.Draw(base)
            try:
                font = ImageFont.truetype('arial.ttf', 28)
            except Exception:
                font = ImageFont.load_default()
            title = os.path.splitext(os.path.basename(field.name))[0]
            text = title.replace('_', ' ').title()
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
            draw.text(((w - tw) // 2, (h - th) // 2), text, fill=(80, 80, 80), font=font)
        else:
            return None

    # try to find a logo PNG in static files
    static_logo = os.path.join(settings.BASE_DIR, 'static', 'images', 'logo.png')
    try:
        if os.path.exists(static_logo):
            result = _apply_logo_overlay(base, static_logo)
        else:
            # fallback draw text
            result = _draw_text_watermark(base, text="Paint Store")
    except Exception as exc:
        # surface the error to logs for debugging
        print('watermark: error applying overlay:', repr(exc))
        return None

    # save new file with _wm.png suffix (always PNG to preserve alpha/transparency)
    base_name, _ext = os.path.splitext(field.name)
    new_name = f"{base_name}_wm.png"

    # ensure result is RGBA so PNG can include transparency
    try:
        result = result.convert('RGBA')
    except Exception:
        pass

    buf = BytesIO()
    try:
        result.save(buf, format='PNG')
    except Exception as exc:
        print('watermark: png save failed, error:', repr(exc))
        return None

    buf.seek(0)
    content = ContentFile(buf.read())

    # store via default_storage to MEDIA_ROOT
    try:
        saved_name = default_storage.save(new_name, content)
        return saved_name
    except Exception as exc:
        print('watermark: storage save failed:', repr(exc))
        return None
