"""
Cashier Shift Closing Receipt Template (80mm POS).
Renders structured shift closure summary with financial metrics, discrepancy, and sold items.
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


class ShiftClosingTemplate(BasePrintTemplate):
    """
    Thermal 80mm receipt template for Cashier Shift Closing.
    """

    @property
    def template_id(self) -> str:
        return "shift_closing_receipt"

    @property
    def document_type(self) -> str:
        return "cashier_shift"

    @property
    def description(self) -> str:
        return "80mm Thermal Receipt for Cashier Shift Closing & Drawer Reconciliation."

    @property
    def required_fields(self) -> List[str]:
        return ["shift_number", "cashier_name", "expected_balance"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        width_mm = float(data.get("width_mm") or 80)
        dpi = int(data.get("dpi") or 203)
        width_px = mm_to_px(width_mm, dpi)

        company_name = str(data.get("company_name") or "شركة كيان التجارية")
        shift_number = str(data.get("shift_number"))
        cashier_name = str(data.get("cashier_name"))
        closed_by_name = str(data.get("closed_by") or "")
        warehouse_name = str(data.get("warehouse_name") or "")
        opened_at = str(data.get("opened_at") or "")
        closed_at = str(data.get("closed_at") or "")

        opening_balance = float(data.get("opening_balance") or 0.0)
        cash_sales = float(data.get("cash_sales") or 0.0)
        cash_additions = float(data.get("cash_additions") or 0.0)
        cash_expenses = float(data.get("cash_expenses") or 0.0)
        cash_returns = float(data.get("cash_returns") or 0.0)
        expected_balance = float(data.get("expected_balance") or 0.0)
        actual_balance = float(data.get("actual_balance") or 0.0)
        difference = float(data.get("difference") or (actual_balance - expected_balance))

        sold_items = data.get("sold_items") or []

        font_header = get_font('arabic_bold', 18)
        font_sub = get_font('arabic_bold', 13)
        font_body = get_font('arabic_regular', 12)
        font_bold = get_font('arabic_bold', 12)
        font_small = get_font('arabic_regular', 10)

        # Estimate receipt height
        estimated_height = 350 + (len(sold_items) * 24) + 150

        image = Image.new("RGB", (width_px, estimated_height), "white")
        draw = ImageDraw.Draw(image)

        y = 15

        # Header
        comp_text = shape_arabic(company_name)
        bbox = draw.textbbox((0, 0), comp_text, font=font_header)
        draw.text(((width_px - (bbox[2] - bbox[0])) // 2, y), comp_text, fill="black", font=font_header)
        y += (bbox[3] - bbox[1]) + 6

        sub_text = shape_arabic("تقرير إغلاق وجرد وردية كاشير")
        bbox = draw.textbbox((0, 0), sub_text, font=font_sub)
        draw.text(((width_px - (bbox[2] - bbox[0])) // 2, y), sub_text, fill="black", font=font_sub)
        y += (bbox[3] - bbox[1]) + 8

        shf_text = f"رقم الوردية: {shift_number}"
        draw.text((width_px - 20 - draw.textbbox((0, 0), shape_arabic(shf_text), font=font_bold)[2], y), shape_arabic(shf_text), fill="black", font=font_bold)
        y += 20

        # Separator Line
        draw.line([(15, y), (width_px - 15, y)], fill="black", width=2)
        y += 8

        # Shift Meta
        lines = [
            f"الكاشير: {cashier_name}",
            f"المستودع: {warehouse_name}" if warehouse_name else "",
            f"المستلم / معتمد الإغلاق: {closed_by_name}" if closed_by_name else "",
            f"وقت الافتتاح: {opened_at}" if opened_at else "",
            f"وقت الإغلاق: {closed_at}" if closed_at else "",
        ]
        for l in lines:
            if not l:
                continue
            draw.text((width_px - 20 - draw.textbbox((0, 0), shape_arabic(l), font=font_body)[2], y), shape_arabic(l), fill="black", font=font_body)
            y += 18

        draw.line([(15, y), (width_px - 15, y)], fill="black", width=1)
        y += 8

        # Financial Breakdown
        fin_rows = [
            (f"(+) رصيد بداية العهدة:", f"{opening_balance:,.2f} ج.م"),
            (f"(+) مبيعات نقدية بالوردية:", f"+{cash_sales:,.2f} ج.م"),
            (f"(+) إيداعات إضافية بالدرج:", f"+{cash_additions:,.2f} ج.م"),
            (f"(−) مسحوبات ومصروفات:", f"−{cash_expenses:,.2f} ج.م"),
            (f"(−) مرتجعات نقدية:", f"−{cash_returns:,.2f} ج.م"),
            (f"(=) الرصيد الدفتري المتوقع:", f"{expected_balance:,.2f} ج.م"),
            (f"الجرد الفعلي للنقدية بالدرج:", f"{actual_balance:,.2f} ج.م"),
        ]

        for label, val in fin_rows:
            is_bold = "(=)" in label or "الجرد" in label
            fn = font_bold if is_bold else font_body
            draw.text((width_px - 20 - draw.textbbox((0, 0), shape_arabic(label), font=fn)[2], y), shape_arabic(label), fill="black", font=fn)
            draw.text((20, y), val, fill="black", font=fn)
            y += 20

        # Discrepancy Badge Row
        diff_str = "مطابق تماماً (0)" if abs(difference) < 0.01 else (f"زيادة: +{difference:,.2f} ج.م" if difference > 0 else f"عجز: {difference:,.2f} ج.م")
        diff_label = f"فارق مطابقة الدرج: {diff_str}"
        draw.line([(15, y), (width_px - 15, y)], fill="black", width=2)
        y += 8
        draw.text((width_px - 20 - draw.textbbox((0, 0), shape_arabic(diff_label), font=font_header)[2], y), shape_arabic(diff_label), fill="black", font=font_header)
        y += 28

        # Sold Items Breakdown (if available)
        if sold_items:
            draw.line([(15, y), (width_px - 15, y)], fill="black", width=1)
            y += 6
            sec_title = shape_arabic("تقرير الأصناف المباعة:")
            draw.text((width_px - 20 - draw.textbbox((0, 0), sec_title, font=font_bold)[2], y), sec_title, fill="black", font=font_bold)
            y += 18

            for it in sold_items:
                name = str(it.get("product_name") or it.get("name") or "")[:20]
                qty = it.get("total_qty") or it.get("quantity") or 1
                tot = float(it.get("total_amount") or it.get("total") or 0.0)
                item_line = f"{name:<20} {qty:>4} {tot:>8.1f}"
                draw.text((20, y), shape_arabic(item_line), fill="black", font=font_small)
                y += 18

        # Signatures
        y += 10
        draw.line([(15, y), (width_px - 15, y)], fill="black", width=1)
        y += 8
        sig_cashier = shape_arabic("توقيع الكاشير: ...............")
        sig_supervisor = shape_arabic("توقيع المشرف: ...............")
        draw.text((width_px - 20 - draw.textbbox((0, 0), sig_cashier, font=font_small)[2], y), sig_cashier, fill="black", font=font_small)
        draw.text((20, y), sig_supervisor, fill="black", font=font_small)
        y += 25

        final_height = max(100, y + 20)
        final_image = image.crop((0, 0, width_px, final_height))
        return [final_image]
