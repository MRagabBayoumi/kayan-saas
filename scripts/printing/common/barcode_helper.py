"""
Barcode generation utility using python-barcode and Pillow.
Generates Code 128 / EAN13 barcodes entirely in-memory.
"""

import io
from PIL import Image

try:
    import barcode
    from barcode.writer import ImageWriter
    HAS_BARCODE_SUPPORT = True
except ImportError:
    HAS_BARCODE_SUPPORT = False


def generate_barcode_image(
    barcode_value: str,
    target_width_px: int,
    target_height_px: int,
    barcode_type: str = "code128"
) -> Image.Image:
    """
    Generate a 1D barcode image resized to exact pixel width and height.
    Generated entirely in-memory using BytesIO.
    """
    code_str = str(barcode_value).strip() if barcode_value else "000000000000"

    if not HAS_BARCODE_SUPPORT:
        # Fallback dummy black rectangle if python-barcode is not installed
        img = Image.new("RGB", (target_width_px, target_height_px), "white")
        return img

    try:
        # If numeric 13 digits and ean13 requested
        if barcode_type.lower() == "ean13" and len(code_str) == 13 and code_str.isdigit():
            bc_class = barcode.get_barcode_class("ean13")
        else:
            bc_class = barcode.get_barcode_class("code128")

        writer = ImageWriter()
        bc_obj = bc_class(code_str, writer=writer)

        stream = io.BytesIO()
        bc_obj.write(
            stream,
            options={
                "write_text": False,
                "module_width": 0.25,
                "module_height": 15.0,
                "quiet_zone": 0.5,
            }
        )
        stream.seek(0)

        raw_img = Image.open(stream).convert("RGB")
        resized_img = raw_img.resize(
            (max(10, int(target_width_px)), max(10, int(target_height_px))),
            Image.Resampling.LANCZOS
        )
        return resized_img

    except Exception:
        # If barcode generation fails, fallback to blank white box
        return Image.new("RGB", (target_width_px, target_height_px), "white")
