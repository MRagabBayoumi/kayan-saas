"""
Purchase Invoice Thermal Receipt Template (80mm POS).
Renders structured supplier receipt with item lines, tax, discounts, and totals.
Optimized for 576-dot native thermal print heads (XP-80C / Xprinter 80mm).
"""

from typing import Any, Dict, List
from PIL import Image, ImageDraw

try:
    from ..common.units import mm_to_px
    from ..common.arabic_helper import shape_arabic
    from ..common.font_helper import get_font
    from .base_template import BasePrintTemplate
except (ImportError, ValueError):
    from common.units import mm_to_px
    from common.arabic_helper import shape_arabic
    from common.font_helper import get_font
    from templates.base_template import BasePrintTemplate


class PurchaseInvoiceTemplate(BasePrintTemplate):
    """
    Thermal 80mm receipt template for Purchase Invoices.
    """

    @property
    def template_id(self) -> str:
        return "purchase_invoice_receipt"

    @property
    def document_type(self) -> str:
        return "purchase_invoice"

    @property
    def description(self) -> str:
        return "80mm Thermal Receipt for Purchase Invoices."

    @property
    def required_fields(self) -> List[str]:
        return ["invoice_number", "supplier_name", "items", "total_amount"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        # 576 dots native width for 80mm roll
        width_px = 576
        margin_x = 16
        content_right = width_px - margin_x
        content_left = margin_x

        company_name = str(data.get("company_name") or "شركة كيان للتجارة والتوزيع")
        invoice_number = str(data.get("invoice_number"))
        supplier_name = str(data.get("supplier_name") or "مورد عام")
        invoice_date = str(data.get("date") or data.get("invoice_date") or "")
        warehouse_name = str(data.get("warehouse_name") or "المستودع الرئيسي")
        items = data.get("items") or []
        subtotal = float(data.get("subtotal") or data.get("total_amount") or 0.0)
        discount_amount = float(data.get("discount_amount") or 0.0)
        total_amount = float(data.get("total_amount") or 0.0)
        paid_amount = float(data.get("paid_amount") or 0.0)
        remaining_amount = float(data.get("remaining_amount") or 0.0)

        font_header = get_font('arabic_bold', 20)
        font_sub = get_font('arabic_bold', 14)
        font_bold = get_font('arabic_bold', 12)
        font_body = get_font('arabic_regular', 12)
        font_small = get_font('arabic_regular', 10)

        def draw_rtl(draw_obj, text, x_right, y_pos, font, fill="black"):
            shaped = shape_arabic(str(text))
            bbox = draw_obj.textbbox((0, 0), shaped, font=font)
            tw = bbox[2] - bbox[0]
            draw_obj.text((x_right - tw, y_pos), shaped, fill=fill, font=font)
            return tw

        def draw_center(draw_obj, text, y_pos, font, fill="black"):
            shaped = shape_arabic(str(text))
            bbox = draw_obj.textbbox((0, 0), shaped, font=font)
            tw = bbox[2] - bbox[0]
            draw_obj.text(((width_px - tw) // 2, y_pos), shaped, fill=fill, font=font)
            return tw

        # Estimate receipt height based on lines
        line_count = len(items)
        estimated_height = 300 + (line_count * 45) + 250

        image = Image.new("RGB", (width_px, estimated_height), "white")
        draw = ImageDraw.Draw(image)

        y = 15

        # 1. Header
        draw_center(draw, company_name, y, font_header)
        y += 28

        # 2. Document Title
        draw_center(draw, "سند استلام بضاعة / فاتورة مشتريات", y, font_sub)
        y += 24

        draw.line([(content_left, y), (content_right, y)], fill="black", width=2)
        y += 10

        # 3. Metadata
        draw_rtl(draw, f"رقم الفاتورة: {invoice_number}", content_right, y, font_bold)
        if invoice_date:
            draw.text((content_left, y), invoice_date, fill="black", font=font_small)
        y += 22

        draw_rtl(draw, f"المورد: {supplier_name}", content_right, y, font_body)
        y += 20

        draw_rtl(draw, f"المستودع المستلم: {warehouse_name}", content_right, y, font_small)
        y += 18

        draw.line([(content_left, y), (content_right, y)], fill="black", width=1)
        y += 8

        # 4. Items Table Header
        draw_rtl(draw, "الصنف / البيان", content_right, y, font_bold)
        draw_center_at = lambda t, cx, py, f: draw.text((cx - (draw.textbbox((0, 0), shape_arabic(t), font=f)[2] - draw.textbbox((0, 0), shape_arabic(t), font=f)[0]) // 2, py), shape_arabic(t), fill="black", font=f)
        
        draw_center_at("الكمية", 185, y, font_bold)
        draw_center_at("التكلفة", 115, y, font_bold)
        draw_rtl(draw, "الإجمالي", 60, y, font_bold)
        y += 20

        draw.line([(content_left, y), (content_right, y)], fill="black", width=1)
        y += 6

        # 5. Items
        for it in items:
            raw_name = str(it.get("name") or it.get("product_name") or "صنف")
            unit_name = str(it.get("unit") or "")
            qty = it.get("quantity") or 1
            price = float(it.get("unit_price") or it.get("cost_price") or 0.0)
            line_total = float(it.get("total") or it.get("subtotal") or (float(qty) * price))

            name_display = raw_name if len(raw_name) <= 26 else raw_name[:24] + ".."
            draw_rtl(draw, name_display, content_right, y, font_body)

            qty_str = f"{float(qty):g}"
            draw_center_at(qty_str, 185, y, font_body)
            draw_center_at(f"{price:,.2f}", 115, y, font_small)
            draw_rtl(draw, f"{line_total:,.2f}", 60, y, font_bold)
            y += 20

            if unit_name and unit_name not in ("قطعة", "قطعه"):
                draw_rtl(draw, f"الوحدة: {unit_name}", content_right - 10, y, font_small, fill="#555555")
                y += 16

        draw.line([(content_left, y), (content_right, y)], fill="black", width=2)
        y += 10

        # 6. Totals Box
        total_box_top = y
        total_box_height = 36
        draw.rectangle([(content_left, total_box_top), (content_right, total_box_top + total_box_height)], fill="#f0f0f0", outline="black", width=1)
        draw_rtl(draw, "إجمالي الفاتورة:", content_right - 10, total_box_top + 7, font_header)
        draw_rtl(draw, f"{total_amount:,.2f} ج.م", 160, total_box_top + 7, font_header)
        y += total_box_height + 12

        if paid_amount > 0:
            draw_rtl(draw, "المدفوع للمورد:", content_right, y, font_bold)
            draw_rtl(draw, f"{paid_amount:,.2f} ج.م", 120, y, font_bold)
            y += 20

        if remaining_amount > 0:
            draw_rtl(draw, "المتبقي للمورد (آجل):", content_right, y, font_bold)
            draw_rtl(draw, f"{remaining_amount:,.2f} ج.م", 120, y, font_bold)
            y += 20

        y += 10
        draw.line([(content_left, y), (content_right, y)], fill="black", width=1)
        y += 10
        draw_center(draw, f"*** {invoice_number} ***", y, font_small)
        y += 35

        final_height = max(100, y)
        final_image = image.crop((0, 0, width_px, final_height))
        return [final_image]
