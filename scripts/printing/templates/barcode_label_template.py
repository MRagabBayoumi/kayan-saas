"""
Thermal Sticker Barcode Label Template.
Renders high-precision bitmap labels for thermal printers (Xprinter XP-233B, etc.).
Faithfully adheres to BarcodeSetting dimensions, fonts, visibility flags, and layout.
"""

from typing import Any, Dict, List
from PIL import Image, ImageDraw

try:
    from ..common.units import mm_to_px
    from ..common.arabic_helper import shape_arabic
    from ..common.font_helper import get_font
    from ..common.barcode_helper import generate_barcode_image
    from .base_template import BasePrintTemplate
except (ImportError, ValueError):
    from common.units import mm_to_px
    from common.arabic_helper import shape_arabic
    from common.font_helper import get_font
    from common.barcode_helper import generate_barcode_image
    from templates.base_template import BasePrintTemplate


class BarcodeLabelTemplate(BasePrintTemplate):
    """
    Standard thermal sticker label template.
    Supports 38x15mm, 38x25mm, and any custom dimensions configured in BarcodeSetting.
    """

    @property
    def template_id(self) -> str:
        return "barcode_label"

    @property
    def document_type(self) -> str:
        return "barcode_label"

    @property
    def description(self) -> str:
        return "Continuous/die-cut thermal barcode sticker label (38x15mm, 38x25mm, etc.)."

    @property
    def required_fields(self) -> List[str]:
        return ["product_name", "barcode"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        # 1. Dimensions
        width_mm = float(data.get("width_mm") or 38)
        height_mm = float(data.get("height_mm") or 13)
        dpi = int(data.get("dpi") or 203)

        width_px = mm_to_px(width_mm, dpi)
        height_px = mm_to_px(height_mm, dpi)

        # 2. Extract Data Fields according to BarcodeSetting
        product_name = str(data.get("product_name", "")).strip()
        barcode_val = str(data.get("barcode", "")).strip()
        sku_val = str(data.get("sku") or data.get("item_no") or "").strip()
        company_name = str(data.get("company_name", "")).strip()

        show_company = bool(data.get("show_company_name", True)) and bool(company_name)
        show_product = bool(data.get("show_product_name", True)) and bool(product_name)
        show_bars = bool(data.get("show_barcode_bars", True)) and bool(barcode_val)
        show_sku = bool(data.get("show_item_sku", True)) and bool(sku_val)
        price_type = str(data.get("print_price_type") or "none").lower()

        # 3. Determine Price Display
        price_text = ""
        retail_p = data.get("retail_price") or data.get("price")
        wholesale_p = data.get("wholesale_price")

        if price_type == "retail" and retail_p is not None:
            try:
                price_text = f"السعر: {float(retail_p):.2f} ج.م"
            except (ValueError, TypeError):
                price_text = f"السعر: {retail_p}"
        elif price_type == "wholesale" and wholesale_p is not None:
            try:
                price_text = f"جملة: {float(wholesale_p):.2f} ج.م"
            except (ValueError, TypeError):
                price_text = f"جملة: {wholesale_p}"
        elif price_type == "both" and retail_p is not None and wholesale_p is not None:
            try:
                price_text = f"تجزئة: {float(retail_p):.0f} | جملة: {float(wholesale_p):.0f}"
            except (ValueError, TypeError):
                pass

        # 4. Font Sizes (strictly respect user settings from BarcodeSetting)
        raw_product_fs = int(data.get("product_font_size") or 14)
        raw_details_fs = int(data.get("details_font_size") or 9)

        # Basic sanity bounds to prevent negative or crashing font sizes
        product_font_size = max(6, min(36, raw_product_fs))
        details_font_size = max(6, min(28, raw_details_fs))
        company_font_size = max(6, min(24, int(data.get("company_font_size") or round(details_font_size * 1.05))))

        font_company = get_font('arabic_bold', company_font_size)
        font_product = get_font('arabic_bold', product_font_size)
        font_details = get_font('details_bold', details_font_size)

        # 5. Canvas Creation
        image = Image.new("RGB", (width_px, height_px), "white")
        draw = ImageDraw.Draw(image)

        # 6. Prepare Bottom Details (SKU & Price)
        bottom_parts = []
        if show_sku:
            bottom_parts.append(f"ITEM-NO: {sku_val}")
        if price_text:
            bottom_parts.append(shape_arabic(price_text))

        bottom_text = "   ".join(bottom_parts) if bottom_parts else ""

        # 7. Dynamic Measurement of Text Elements
        gap = 2 if height_mm <= 18 else 4

        comp_shaped = shape_arabic(company_name) if show_company else ""
        comp_w, comp_h = 0, 0
        if show_company and comp_shaped:
            bbox_c = draw.textbbox((0, 0), comp_shaped, font=font_company)
            comp_w = bbox_c[2] - bbox_c[0]
            comp_h = bbox_c[3] - bbox_c[1]

        prod_shaped = shape_arabic(product_name) if show_product else ""
        prod_w, prod_h = 0, 0
        if show_product and prod_shaped:
            bbox_p = draw.textbbox((0, 0), prod_shaped, font=font_product)
            prod_w = bbox_p[2] - bbox_p[0]
            prod_h = bbox_p[3] - bbox_p[1]

        bot_w, bot_h = 0, 0
        if bottom_text:
            bbox_b = draw.textbbox((0, 0), bottom_text, font=font_details)
            bot_w = bbox_b[2] - bbox_b[0]
            bot_h = bbox_b[3] - bbox_b[1]

        # 8. Dynamic Barcode Sizing (respect user's configured barcode_width_px and barcode_height_px)
        bar_h = 0
        bar_w = 0
        if show_bars:
            configured_bar_w = int(data.get("barcode_width_px") or int(width_px * 0.85))
            configured_bar_h = int(data.get("barcode_height_px") or (30 if height_mm <= 18 else 60))

            # Max allowed width within sticker borders
            bar_w = min(int(width_px * 0.96), max(50, configured_bar_w))

            # Dynamic vertical space budget:
            # We preserve text elements and calculate the maximum height available for the barcode
            text_height_sum = 0
            num_gaps = 0
            if show_company and comp_h > 0:
                text_height_sum += comp_h
                num_gaps += 1
            if show_product and prod_h > 0:
                text_height_sum += prod_h
                num_gaps += 1
            if bottom_text and bot_h > 0:
                text_height_sum += bot_h
                num_gaps += 1

            total_gaps_h = num_gaps * gap
            available_bar_h = max(15, height_px - text_height_sum - total_gaps_h - 4)

            # Use user's configured height if it fits; otherwise adapt to available space
            bar_h = max(15, min(configured_bar_h, available_bar_h))

        # 9. Dynamic Vertical Centering
        total_content_h = 0
        active_items = []
        if show_company and comp_h > 0:
            total_content_h += comp_h
            active_items.append(('company', comp_h))
        if show_product and prod_h > 0:
            total_content_h += prod_h
            active_items.append(('product', prod_h))
        if show_bars and bar_h > 0:
            total_content_h += bar_h
            active_items.append(('barcode', bar_h))
        if bottom_text and bot_h > 0:
            total_content_h += bot_h
            active_items.append(('bottom', bot_h))

        if len(active_items) > 1:
            total_content_h += (len(active_items) - 1) * gap

        start_y = max(1, (height_px - total_content_h) // 2) if total_content_h < height_px else 1
        current_y = start_y

        # 10. Sequential Drawing
        # Company Name
        if show_company and comp_shaped and comp_h > 0:
            comp_x = max(2, (width_px - comp_w) // 2)
            draw.text((comp_x, current_y), comp_shaped, fill="black", font=font_company)
            current_y += comp_h + gap

        # Product Name
        if show_product and prod_shaped and prod_h > 0:
            prod_x = max(2, (width_px - prod_w) // 2)
            draw.text((prod_x, current_y), prod_shaped, fill="black", font=font_product)
            current_y += prod_h + gap

        # Barcode
        if show_bars and bar_h > 0:
            barcode_img = generate_barcode_image(
                barcode_value=barcode_val,
                target_width_px=bar_w,
                target_height_px=bar_h,
                barcode_type="code128"
            )
            bar_x = max(0, (width_px - bar_w) // 2)
            image.paste(barcode_img, (bar_x, current_y))
            current_y += bar_h + gap

        # Bottom Details (SKU & Price)
        if bottom_text and bot_h > 0:
            bot_x = max(2, (width_px - bot_w) // 2)
            draw.text((bot_x, current_y), bottom_text, fill="black", font=font_details)

        return [image]
