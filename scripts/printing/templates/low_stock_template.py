"""
Low Stock Shortages Report Print Template (A4).
Renders structured inventory shortage alerts, reorder limits, and deficit quantities.
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


class LowStockDocumentTemplate(BasePrintTemplate):
    """A4 Office document template for Low Stock Inventory Shortages Report."""

    @property
    def template_id(self) -> str:
        return "low_stock_document"

    @property
    def document_type(self) -> str:
        return "low_stock_report"

    @property
    def description(self) -> str:
        return "A4 Document for Inventory Low Stock Shortages & Reorder Report."

    @property
    def required_fields(self) -> List[str]:
        return ["products"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        dpi = int(data.get("dpi") or 203)
        width_px = mm_to_px(210.0, dpi)
        height_px = mm_to_px(297.0, dpi)

        company_name = str(data.get("company_name") or "شركة كيان للتجارة والتوزيع")
        report_date = str(data.get("date") or data.get("report_date") or "")
        category_name = str(data.get("category_name") or "جميع التصنيفات")
        products = data.get("products") or []

        font_h1 = get_font('arabic_bold', 24)
        font_h2 = get_font('arabic_bold', 16)
        font_bold = get_font('arabic_bold', 12)
        font_regular = get_font('arabic_regular', 11)
        font_small = get_font('arabic_regular', 10)

        margin = 45
        usable_w = width_px - (margin * 2)

        image = Image.new("RGB", (width_px, height_px), "white")
        draw = ImageDraw.Draw(image)

        # Border
        draw.rectangle([margin // 2, margin // 2, width_px - (margin // 2), height_px - (margin // 2)], outline="#333333", width=2)

        # Header
        y = margin
        title_str = shape_arabic("تقرير نواقص المخزون وتنبيهات إعادة الطلب")
        t_box = draw.textbbox((0, 0), title_str, font=font_h1)
        draw.text(((width_px - (t_box[2] - t_box[0])) // 2, y), title_str, fill="#991b1b", font=font_h1)
        y += (t_box[3] - t_box[1]) + 8

        comp_str = shape_arabic(company_name)
        c_box = draw.textbbox((0, 0), comp_str, font=font_h2)
        draw.text(((width_px - (c_box[2] - c_box[0])) // 2, y), comp_str, fill="#334155", font=font_h2)
        y += (c_box[3] - c_box[1]) + 12

        draw.line([(margin, y), (width_px - margin, y)], fill="#991b1b", width=2)
        y += 10

        # Meta
        meta_str = shape_arabic(f"تاريخ التقرير: {report_date}  |  التصنيف المستهدف: {category_name}  |  إجمالي الأصناف الناقصة: {len(products)}")
        m_box = draw.textbbox((0, 0), meta_str, font=font_bold)
        draw.text((width_px - margin - (m_box[2] - m_box[0]), y), meta_str, fill="#0f172a", font=font_bold)
        y += (m_box[3] - m_box[1]) + 12

        # Table Header
        col_w = {
            "no": int(usable_w * 0.07),
            "sku": int(usable_w * 0.16),
            "name": int(usable_w * 0.37),
            "min": int(usable_w * 0.13),
            "stock": int(usable_w * 0.13),
            "deficit": int(usable_w * 0.14),
        }
        x_no = margin
        x_sku = x_no + col_w["no"]
        x_name = x_sku + col_w["sku"]
        x_min = x_name + col_w["name"]
        x_stock = x_min + col_w["min"]
        x_def = x_stock + col_w["stock"]

        th_h = 28
        draw.rectangle([margin, y, width_px - margin, y + th_h], fill="#991b1b")

        th_labels = [
            ("م", x_no, col_w["no"]),
            ("كود الصنف", x_sku, col_w["sku"]),
            ("اسم الصنف المطلوب", x_name, col_w["name"]),
            ("حد الطلب", x_min, col_w["min"]),
            ("الرصيد الفعلي", x_stock, col_w["stock"]),
            ("العجز المطلوب", x_def, col_w["deficit"]),
        ]
        for lbl, start_x, w in th_labels:
            s_lbl = shape_arabic(lbl)
            box = draw.textbbox((0, 0), s_lbl, font=font_bold)
            cx = start_x + (w - (box[2] - box[0])) // 2
            cy = y + (th_h - (box[3] - box[1])) // 2
            draw.text((cx, cy), s_lbl, fill="white", font=font_bold)

        y += th_h

        # Rows
        row_h = 25
        for idx, p in enumerate(products, start=1):
            if y + row_h > height_px - 70:
                draw.text((margin + 20, y + 4), shape_arabic("... بقية النواقص مسجلة بالنظام ..."), fill="#64748b", font=font_small)
                break

            bg_color = "#fef2f2" if idx % 2 == 0 else "#ffffff"
            draw.rectangle([margin, y, width_px - margin, y + row_h], fill=bg_color, outline="#fecaca", width=1)

            sku = str(p.get("sku") or p.get("barcode") or "-")
            name = str(p.get("name") or p.get("product_name") or "")[:35]
            min_alert = float(p.get("min_stock_alert") or p.get("minimum_stock") or 0)
            cur_stock = float(p.get("current_stock") or p.get("stock") or 0)
            deficit = max(0.0, min_alert - cur_stock)

            # No
            s_no = str(idx)
            draw.text((x_no + (col_w["no"] - draw.textbbox((0, 0), s_no, font=font_regular)[2]) // 2, y + 5), s_no, fill="#0f172a", font=font_regular)

            # SKU
            draw.text((x_sku + (col_w["sku"] - draw.textbbox((0, 0), sku, font=font_regular)[2]) // 2, y + 5), sku, fill="#0f172a", font=font_regular)

            # Name
            s_name = shape_arabic(name)
            box = draw.textbbox((0, 0), s_name, font=font_regular)
            draw.text((x_name + col_w["name"] - 10 - (box[2] - box[0]), y + 5), s_name, fill="#0f172a", font=font_regular)

            # Min
            s_min = f"{min_alert:g}"
            draw.text((x_min + (col_w["min"] - draw.textbbox((0, 0), s_min, font=font_regular)[2]) // 2, y + 5), s_min, fill="#0f172a", font=font_regular)

            # Stock
            s_st = f"{cur_stock:g}"
            draw.text((x_stock + (col_w["stock"] - draw.textbbox((0, 0), s_st, font=font_bold)[2]) // 2, y + 5), s_st, fill="#dc2626" if cur_stock <= 0 else "#b45309", font=font_bold)

            # Deficit
            s_def = f"{deficit:g}"
            draw.text((x_def + (col_w["deficit"] - draw.textbbox((0, 0), s_def, font=font_bold)[2]) // 2, y + 5), s_def, fill="#991b1b", font=font_bold)

            y += row_h

        # Footer Bar (3 Side-by-Side Bordered Boxes)
        footer_bar_h = 24
        footer_bar_y = height_px - margin - footer_bar_h
        box_w = usable_w // 3
        b1_x0 = margin + (box_w * 2)
        b1_x1 = width_px - margin
        b2_x0 = margin + box_w
        b2_x1 = margin + (box_w * 2)
        b3_x0 = margin
        b3_x1 = margin + box_w

        user_name = str(data.get("user_name") or data.get("cashier_name") or "مسؤول المشتريات")
        print_datetime = str(data.get("print_time") or report_date or "")

        # Box 1 (Right): اسم المستخدم
        draw.rectangle([b1_x0, footer_bar_y, b1_x1, footer_bar_y + footer_bar_h], outline="#000000", width=1, fill="#ffffff")
        s_u = shape_arabic(f"اسم المستخدم : {user_name}")
        u_box = draw.textbbox((0, 0), s_u, font=font_bold)
        draw.text((b1_x0 + (box_w - (u_box[2] - u_box[0])) // 2 - u_box[0], footer_bar_y + (footer_bar_h - (u_box[3] - u_box[1])) // 2 - u_box[1]), s_u, fill="#000000", font=font_bold)

        # Box 2 (Center): الصفحات من 1 إلي 1
        draw.rectangle([b2_x0, footer_bar_y, b2_x1, footer_bar_y + footer_bar_h], outline="#000000", width=1, fill="#ffffff")
        s_p = shape_arabic("الصفحات من 1 إلي 1")
        p_box = draw.textbbox((0, 0), s_p, font=font_bold)
        draw.text((b2_x0 + (box_w - (p_box[2] - p_box[0])) // 2 - p_box[0], footer_bar_y + (footer_bar_h - (p_box[3] - p_box[1])) // 2 - p_box[1]), s_p, fill="#000000", font=font_bold)

        # Box 3 (Left): تاريخ ووقت الطباعة
        draw.rectangle([b3_x0, footer_bar_y, b3_x1, footer_bar_y + footer_bar_h], outline="#000000", width=1, fill="#ffffff")
        s_t = shape_arabic(f"تاريخ ووقت الطباعة : {print_datetime}")
        t_box = draw.textbbox((0, 0), s_t, font=font_bold)
        draw.text((b3_x0 + (box_w - (t_box[2] - t_box[0])) // 2 - t_box[0], footer_bar_y + (footer_bar_h - (t_box[3] - t_box[1])) // 2 - t_box[1]), s_t, fill="#000000", font=font_bold)

        return [image]
