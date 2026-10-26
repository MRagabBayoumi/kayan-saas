"""
Product Movement Ledger Print Template (A4 / A5).
Renders structured stock movement history with running balance.
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


class ProductLedgerDocumentTemplate(BasePrintTemplate):
    """A4 / A5 office document template for Product Stock Movement Ledger."""

    @property
    def template_id(self) -> str:
        return "product_ledger_document"

    @property
    def document_type(self) -> str:
        return "product_ledger"

    @property
    def description(self) -> str:
        return "A4 / A5 Document for Product Stock Movement Ledger & Card."

    @property
    def required_fields(self) -> List[str]:
        return ["product_name", "movements"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        fmt = str(data.get("format") or "a4").lower().strip()
        dpi = int(data.get("dpi") or 203)
        is_a5 = fmt == "a5"

        width_mm = 148.0 if is_a5 else 210.0
        height_mm = 210.0 if is_a5 else 297.0
        width_px = mm_to_px(width_mm, dpi)
        height_px = mm_to_px(height_mm, dpi)

        company_name = str(data.get("company_name") or "شركة كيان للتجارة والتوزيع")
        product_name = str(data.get("product_name"))
        product_sku = str(data.get("sku") or "")
        product_barcode = str(data.get("barcode") or "")
        current_balance = float(data.get("current_balance") or 0.0)
        unit = str(data.get("unit") or "قطعة")
        movements = data.get("movements") or []

        scale = 0.8 if is_a5 else 1.0
        font_h1 = get_font('arabic_bold', int(24 * scale))
        font_h2 = get_font('arabic_bold', int(17 * scale))
        font_bold = get_font('arabic_bold', int(12 * scale))
        font_regular = get_font('arabic_regular', int(11 * scale))
        font_small = get_font('arabic_regular', int(10 * scale))

        margin = int(45 * scale)
        usable_w = width_px - (margin * 2)

        image = Image.new("RGB", (width_px, height_px), "white")
        draw = ImageDraw.Draw(image)

        # Border
        draw.rectangle([margin // 2, margin // 2, width_px - (margin // 2), height_px - (margin // 2)], outline="#333333", width=2)

        # Header
        y = margin
        title_str = shape_arabic("كشف حركة الصنف والرصيد الدفتري")
        t_box = draw.textbbox((0, 0), title_str, font=font_h1)
        draw.text(((width_px - (t_box[2] - t_box[0])) // 2, y), title_str, fill="#0f172a", font=font_h1)
        y += (t_box[3] - t_box[1]) + 8

        comp_str = shape_arabic(company_name)
        c_box = draw.textbbox((0, 0), comp_str, font=font_h2)
        draw.text(((width_px - (c_box[2] - c_box[0])) // 2, y), comp_str, fill="#334155", font=font_h2)
        y += (c_box[3] - c_box[1]) + 12

        draw.line([(margin, y), (width_px - margin, y)], fill="#0f172a", width=2)
        y += 10

        # Product Info Card
        card_h = int(60 * scale)
        draw.rectangle([margin, y, width_px - margin, y + card_h], outline="#cbd5e1", fill="#f8fafc", width=1)

        p_line1 = f"الصنف: {product_name}  |  الكود: {product_sku}"
        p_line2 = f"الباركود: {product_barcode}  |  الرصيد الفعلي الحالي: {current_balance:g} {unit}"
        s_p1 = shape_arabic(p_line1)
        s_p2 = shape_arabic(p_line2)

        b1 = draw.textbbox((0, 0), s_p1, font=font_bold)
        b2 = draw.textbbox((0, 0), s_p2, font=font_bold)
        draw.text((width_px - margin - 15 - (b1[2] - b1[0]), y + 10), s_p1, fill="#0f172a", font=font_bold)
        draw.text((width_px - margin - 15 - (b2[2] - b2[0]), y + 10 + int(22 * scale)), s_p2, fill="#0f172a", font=font_bold)
        y += card_h + 15

        # Movements Table
        col_w = {
            "date": int(usable_w * 0.18),
            "type": int(usable_w * 0.20),
            "ref": int(usable_w * 0.18),
            "in": int(usable_w * 0.14),
            "out": int(usable_w * 0.14),
            "bal": int(usable_w * 0.16),
        }
        x_date = margin
        x_type = x_date + col_w["date"]
        x_ref = x_type + col_w["type"]
        x_in = x_ref + col_w["ref"]
        x_out = x_in + col_w["in"]
        x_bal = x_out + col_w["out"]

        th_height = int(28 * scale)
        draw.rectangle([margin, y, width_px - margin, y + th_height], fill="#0f172a")

        th_labels = [
            ("التاريخ والوقت", x_date, col_w["date"]),
            ("نوع الحركة", x_type, col_w["type"]),
            ("رقم المرجع", x_ref, col_w["ref"]),
            ("وارد (+)", x_in, col_w["in"]),
            ("منصرف (−)", x_out, col_w["out"]),
            ("الرصيد", x_bal, col_w["bal"]),
        ]
        for lbl, start_x, w in th_labels:
            s_lbl = shape_arabic(lbl)
            box = draw.textbbox((0, 0), s_lbl, font=font_bold)
            cx = start_x + (w - (box[2] - box[0])) // 2
            cy = y + (th_height - (box[3] - box[1])) // 2
            draw.text((cx, cy), s_lbl, fill="white", font=font_bold)

        y += th_height

        row_h = int(25 * scale)
        for idx, m in enumerate(movements, start=1):
            if y + row_h > height_px - int(70 * scale):
                draw.text((margin + 20, y + 4), shape_arabic("... بقية الحركات مسجلة بالنظام ..."), fill="#64748b", font=font_small)
                break

            bg_color = "#f8fafc" if idx % 2 == 0 else "#ffffff"
            draw.rectangle([margin, y, width_px - margin, y + row_h], fill=bg_color, outline="#e2e8f0", width=1)

            m_date = str(m.get("date") or m.get("created_at") or "")[:16]
            m_type = str(m.get("type_name") or m.get("type") or "حركة مخزنية")
            m_ref = str(m.get("reference") or m.get("ref_number") or "-")
            qty_in = float(m.get("quantity_in") or (m.get("quantity") if float(m.get("quantity") or 0) > 0 else 0) or 0)
            qty_out = float(m.get("quantity_out") or (abs(float(m.get("quantity") or 0)) if float(m.get("quantity") or 0) < 0 else 0) or 0)
            bal = float(m.get("balance_after") or m.get("balance") or 0)

            # Date
            draw.text((x_date + 6, y + 5), m_date, fill="#0f172a", font=font_small)

            # Type
            s_type = shape_arabic(m_type)
            box = draw.textbbox((0, 0), s_type, font=font_regular)
            draw.text((x_type + (col_w["type"] - (box[2] - box[0])) // 2, y + 5), s_type, fill="#0f172a", font=font_regular)

            # Ref
            s_ref = shape_arabic(m_ref)
            draw.text((x_ref + (col_w["ref"] - draw.textbbox((0, 0), s_ref, font=font_regular)[2]) // 2, y + 5), s_ref, fill="#0f172a", font=font_regular)

            # In
            s_in = f"+{qty_in:g}" if qty_in > 0 else "-"
            draw.text((x_in + (col_w["in"] - draw.textbbox((0, 0), s_in, font=font_regular)[2]) // 2, y + 5), s_in, fill="#16a34a" if qty_in > 0 else "#64748b", font=font_bold if qty_in > 0 else font_regular)

            # Out
            s_out = f"−{qty_out:g}" if qty_out > 0 else "-"
            draw.text((x_out + (col_w["out"] - draw.textbbox((0, 0), s_out, font=font_regular)[2]) // 2, y + 5), s_out, fill="#dc2626" if qty_out > 0 else "#64748b", font=font_bold if qty_out > 0 else font_regular)

            # Balance
            s_bal = f"{bal:g}"
            draw.text((x_bal + (col_w["bal"] - draw.textbbox((0, 0), s_bal, font=font_bold)[2]) // 2, y + 5), s_bal, fill="#0f172a", font=font_bold)

            y += row_h

        # Footer Bar (3 Side-by-Side Bordered Boxes)
        footer_bar_h = int(24 * scale)
        footer_bar_y = height_px - margin - footer_bar_h
        box_w = usable_w // 3
        b1_x0 = margin + (box_w * 2)
        b1_x1 = width_px - margin
        b2_x0 = margin + box_w
        b2_x1 = margin + (box_w * 2)
        b3_x0 = margin
        b3_x1 = margin + box_w

        user_name = str(data.get("user_name") or data.get("cashier_name") or "المسؤول")
        print_datetime = str(data.get("print_time") or data.get("date") or "")

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
