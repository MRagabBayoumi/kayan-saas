"""
A4 and A5 Document Print Templates for Sales and Purchase Invoices.
Renders high-resolution, enterprise dispatch and receiving documents suitable for laser/inkjet office printers
(e.g., TOSHIBA Generic Printer XL, Office Network Printer).
Supports pagination, Arabic text shaping, 8-column solid grid tables, stamp frames, and financial totals.
Matches the exact enterprise layout format (Original badge, 3-column header, stamp box, 8 columns, bottom bar).
"""

from datetime import datetime
from typing import Any, Dict, List, Optional, Tuple
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


def draw_dashed_rectangle(
    draw: ImageDraw.ImageDraw,
    bbox: Tuple[int, int, int, int],
    dash_len: int = 6,
    space_len: int = 4,
    outline: str = "#000000",
    width: int = 1,
) -> None:
    """Draw a crisp rectangle with dashed borders using PIL ImageDraw."""
    x0, y0, x1, y1 = bbox
    # Top edge
    x = x0
    while x < x1:
        draw.line([(x, y0), (min(x + dash_len, x1), y0)], fill=outline, width=width)
        x += dash_len + space_len
    # Bottom edge
    x = x0
    while x < x1:
        draw.line([(x, y1), (min(x + dash_len, x1), y1)], fill=outline, width=width)
        x += dash_len + space_len
    # Left edge
    y = y0
    while y < y1:
        draw.line([(x0, y), (x0, min(y + dash_len, y1))], fill=outline, width=width)
        y += dash_len + space_len
    # Right edge
    y = y0
    while y < y1:
        draw.line([(x1, y), (x1, min(y + dash_len, y1))], fill=outline, width=width)
        y += dash_len + space_len


def draw_text_in_rect(
    draw: ImageDraw.ImageDraw,
    bbox: Tuple[int, int, int, int],
    text: str,
    font: Any,
    fill: str = "#000000",
    align: str = "center",
    pad: int = 4,
) -> None:
    """Helper to draw text inside a bounding box with proper alignment and baseline normalization."""
    if not text:
        return
    s_text = shape_arabic(str(text))
    box = draw.textbbox((0, 0), s_text, font=font)
    tw = box[2] - box[0]
    th = box[3] - box[1]

    bx0, by0, bx1, by1 = bbox
    rect_w = bx1 - bx0
    rect_h = by1 - by0

    if align == "center":
        tx = bx0 + (rect_w - tw) // 2 - box[0]
    elif align == "right":
        tx = bx1 - pad - tw - box[0]
    else:  # left
        tx = bx0 + pad - box[0]

    ty = by0 + (rect_h - th) // 2 - box[1]
    draw.text((tx, ty), s_text, fill=fill, font=font)


class DocumentInvoiceBase(BasePrintTemplate):
    """Base class for multi-format (A4/A5) office invoice and dispatch documents."""

    def _resolve_canvas(self, data: Dict[str, Any], default_format: str = "a4") -> Tuple[int, int, int]:
        """Resolve pixel width, height, and DPI based on paper format (A4 vs A5)."""
        fmt = str(data.get("format") or default_format).lower().strip()
        dpi = int(data.get("dpi") or 203)

        if fmt == "a5":
            # A5: 148 x 210 mm
            width_mm = 148.0
            height_mm = 210.0
        else:
            # A4: 210 x 297 mm
            width_mm = 210.0
            height_mm = 297.0

        width_px = mm_to_px(width_mm, dpi)
        height_px = mm_to_px(height_mm, dpi)
        return width_px, height_px, dpi

    def _render_dispatch_layout(
        self,
        data: Dict[str, Any],
        doc_type_title: str,
        stamp_title: str,
        partner_code_label: str,
        partner_name_label: str,
        partner_code_val: str,
        partner_name_val: str,
        partner_phone_val: str,
        address_val: str,
        shipping_val: str,
        items: List[Dict[str, Any]],
        summary_rows: List[Tuple[str, str, bool]],
        notes: str,
        user_name: str,
    ) -> List[Image.Image]:
        """
        Renders the authoritative dispatch document layout matching the user's reference photo.
        Includes:
          - 3-column top header with 'Original' badge, stamp frame with dashed border and stars,
            and document metadata.
          - Exact 8-column solid grid table (م, كود الصنف, اسم الصنف, الكمية, وحده القياس, المخزن, السعر, الاجمالي).
          - Bottom section with notes and financial totals.
          - Bottom boxed footer bar (اسم المستخدم, الصفحات من X إلي Y, تاريخ ووقت الطباعة).
        """
        width_px, height_px, dpi = self._resolve_canvas(data, "a4")
        is_a5 = str(data.get("format", "")).lower() == "a5"

        scale = 0.72 if is_a5 else 1.0
        font_h1 = get_font('arabic_bold', int(22 * scale))
        font_h2 = get_font('arabic_bold', int(15 * scale))
        font_stamp = get_font('arabic_bold', int(18 * scale))
        font_bold = get_font('arabic_bold', int(11 * scale))
        font_regular = get_font('arabic_regular', int(10 * scale))
        font_small = get_font('arabic_regular', int(9 * scale))

        margin = int(35 * scale)
        usable_w = width_px - (margin * 2)

        # Pagination calculation
        header_h = int(120 * scale)
        footer_bar_h = int(24 * scale)
        totals_h = int(110 * scale)
        th_h = int(26 * scale)
        row_h = int(24 * scale)

        # Calculate items per page
        usable_table_h = height_px - (margin * 2) - header_h - totals_h - footer_bar_h - th_h
        max_rows_per_page = max(5, int(usable_table_h // row_h))

        pages_items: List[List[Dict[str, Any]]] = []
        if not items:
            pages_items = [[]]
        else:
            for i in range(0, len(items), max_rows_per_page):
                pages_items.append(items[i : i + max_rows_per_page])

        total_pages = len(pages_items)
        rendered_pages: List[Image.Image] = []
        print_datetime = datetime.now().strftime("%Y-%m-%d %H:%M")

        invoice_number = str(data.get("invoice_number") or "")
        invoice_date = str(data.get("date") or data.get("invoice_date") or datetime.now().strftime("%Y-%m-%d"))

        for page_idx, page_rows in enumerate(pages_items, start=1):
            image = Image.new("RGB", (width_px, height_px), "white")
            draw = ImageDraw.Draw(image)

            # 1. Outer Border
            draw.rectangle([margin, margin, width_px - margin, height_px - margin], outline="#000000", width=2)

            # 2. Top Header (3 Columns)
            y = margin + int(8 * scale)
            col_w = usable_w // 3

            # --- Right Column (Original badge + doc metadata) ---
            rx_start = width_px - margin - col_w
            badge_w = int(75 * scale)
            badge_h = int(20 * scale)
            badge_x = width_px - margin - badge_w - int(10 * scale)
            badge_y = y

            # Badge "Original"
            draw.rectangle([badge_x, badge_y, badge_x + badge_w, badge_y + badge_h], outline="#000000", width=1, fill="#f8fafc")
            draw_text_in_rect(draw, (badge_x, badge_y, badge_x + badge_w, badge_y + badge_h), "Original", font_bold, fill="#000000", align="center")

            # Right meta lines
            line_y = badge_y + badge_h + int(8 * scale)
            line_spacing = int(18 * scale)

            right_fields = [
                ("رقم المستند :", invoice_number),
                (f"{partner_code_label} :", partner_code_val or "—"),
                (f"{partner_name_label} :", partner_name_val or "عميل نقدي"),
                ("رقم التليفون :", partner_phone_val or "—"),
            ]

            for lbl, val in right_fields:
                s_lbl = shape_arabic(lbl)
                s_val = shape_arabic(str(val))
                lbl_box = draw.textbbox((0, 0), s_lbl, font=font_bold)
                val_box = draw.textbbox((0, 0), s_val, font=font_regular)

                # Right align label
                lbl_x = width_px - margin - int(10 * scale) - (lbl_box[2] - lbl_box[0]) - lbl_box[0]
                draw.text((lbl_x, line_y - lbl_box[1]), s_lbl, fill="#000000", font=font_bold)

                # Value to the left of label
                val_x = lbl_x - int(6 * scale) - (val_box[2] - val_box[0]) - val_box[0]
                draw.text((val_x, line_y - val_box[1]), s_val, fill="#000000", font=font_regular)

                line_y += line_spacing

            # --- Center Column (Stamp frame with dashed border & stars) ---
            cx_center = margin + (usable_w // 2)
            stamp_w = int(170 * scale)
            stamp_h = int(82 * scale)
            stamp_x0 = cx_center - (stamp_w // 2)
            stamp_y0 = y + int(4 * scale)
            stamp_x1 = stamp_x0 + stamp_w
            stamp_y1 = stamp_y0 + stamp_h

            # Dashed rectangle border
            draw_dashed_rectangle(draw, (stamp_x0, stamp_y0, stamp_x1, stamp_y1), dash_len=int(6 * scale), space_len=int(4 * scale), outline="#000000", width=1)

            # Top stars
            draw_text_in_rect(draw, (stamp_x0, stamp_y0 + 2, stamp_x1, stamp_y0 + int(22 * scale)), "★ ★ ★", font_small, fill="#000000", align="center")

            # Center Title (e.g. إذن صرف / إذن استلام)
            draw_text_in_rect(draw, (stamp_x0, stamp_y0 + int(20 * scale), stamp_x1, stamp_y0 + int(50 * scale)), stamp_title, font_stamp, fill="#000000", align="center")

            # Bottom stars
            draw_text_in_rect(draw, (stamp_x0, stamp_y0 + int(46 * scale), stamp_x1, stamp_y0 + int(64 * scale)), "★ ★ ★", font_small, fill="#000000", align="center")

            # Date inside stamp
            draw_text_in_rect(draw, (stamp_x0, stamp_y0 + int(62 * scale), stamp_x1, stamp_y1 - 2), invoice_date, font_small, fill="#000000", align="center")

            # --- Left Column (Date, Address, Shipping) ---
            lx_start = margin + int(10 * scale)
            left_y = y + int(20 * scale)

            left_fields = [
                ("التاريخ :", invoice_date),
                ("العنوان :", address_val or "—"),
                ("شركة الشحن :", shipping_val or "تسليم مباشر"),
            ]

            for lbl, val in left_fields:
                s_lbl = shape_arabic(lbl)
                s_val = shape_arabic(str(val))
                lbl_box = draw.textbbox((0, 0), s_lbl, font=font_bold)
                val_box = draw.textbbox((0, 0), s_val, font=font_regular)

                # Right align within left column
                ref_right = lx_start + col_w - int(10 * scale)
                lbl_x = ref_right - (lbl_box[2] - lbl_box[0]) - lbl_box[0]
                draw.text((lbl_x, left_y - lbl_box[1]), s_lbl, fill="#000000", font=font_bold)

                val_x = lbl_x - int(6 * scale) - (val_box[2] - val_box[0]) - val_box[0]
                draw.text((val_x, left_y - val_box[1]), s_val, fill="#000000", font=font_regular)

                left_y += line_spacing

            # 3. Horizontal Header Divider Line
            table_start_y = margin + header_h
            draw.line([(margin, table_start_y), (width_px - margin, table_start_y)], fill="#000000", width=2)

            # 4. Table Grid (8 Columns with Solid 1px Black Borders)
            # Width breakdown: 4% | 13% | 35% | 8% | 10% | 8% | 11% | 11% = 100%
            col_defs = [
                ("no", int(usable_w * 0.04), "م", "center"),
                ("sku", int(usable_w * 0.13), "كود الصنف", "center"),
                ("name", int(usable_w * 0.35), "اسم الصنف", "right"),
                ("qty", int(usable_w * 0.08), "الكمية", "center"),
                ("unit", int(usable_w * 0.10), "وحده القياس", "center"),
                ("wh", int(usable_w * 0.08), "المخزن", "center"),
                ("price", int(usable_w * 0.11), "السعر", "center"),
                ("total", int(usable_w * 0.11), "الاجمالي", "center"),
            ]

            # Adjust last column width for exact pixel match
            computed_sum = sum(c[1] for c in col_defs)
            diff = usable_w - computed_sum
            col_defs[-1] = (col_defs[-1][0], col_defs[-1][1] + diff, col_defs[-1][2], col_defs[-1][3])

            # Draw Table Header
            curr_x = margin
            for _, w, title, _ in col_defs:
                draw.rectangle([curr_x, table_start_y, curr_x + w, table_start_y + th_h], outline="#000000", width=1, fill="#f1f5f9")
                draw_text_in_rect(draw, (curr_x, table_start_y, curr_x + w, table_start_y + th_h), title, font_bold, fill="#000000", align="center")
                curr_x += w

            # Draw Table Rows
            curr_row_y = table_start_y + th_h
            start_idx = (page_idx - 1) * max_rows_per_page

            for row_offset, it in enumerate(page_rows):
                item_idx = start_idx + row_offset + 1
                curr_x = margin

                item_sku = str(it.get("sku") or it.get("product_sku") or "")
                item_name = str(it.get("name") or it.get("product_name") or "")
                item_qty = float(it.get("quantity") or 1)
                item_unit = str(it.get("unit") or it.get("unit_name") or "قطعة")
                item_wh = str(it.get("warehouse") or it.get("warehouse_name") or "الرئيسي")
                item_price = float(it.get("price") or it.get("unit_price") or it.get("cost_price") or 0.0)
                item_total = float(it.get("total") or (item_qty * item_price))

                row_values = [
                    (str(item_idx), "center"),
                    (item_sku, "center"),
                    (item_name, "right"),
                    (f"{item_qty:g}", "center"),
                    (item_unit, "center"),
                    (item_wh, "center"),
                    (f"{item_price:,.2f}", "center"),
                    (f"{item_total:,.2f}", "center"),
                ]

                for col_idx, (_, w, _, _) in enumerate(col_defs):
                    val, align = row_values[col_idx]
                    draw.rectangle([curr_x, curr_row_y, curr_x + w, curr_row_y + row_h], outline="#000000", width=1, fill="#ffffff")
                    draw_text_in_rect(draw, (curr_x, curr_row_y, curr_x + w, curr_row_y + row_h), val, font_regular, fill="#000000", align=align, pad=int(6 * scale))
                    curr_x += w

                curr_row_y += row_h

            # 5. Bottom Section: Notes (Right) & Financial Totals (Left)
            # Only render complete totals on the last page; on intermediate pages show continuation
            is_last_page = page_idx == total_pages
            footer_bar_y = height_px - margin - footer_bar_h
            bottom_sec_y = footer_bar_y - totals_h - int(8 * scale)

            if is_last_page:
                half_w = (usable_w - int(10 * scale)) // 2

                # --- Notes Box (Right) ---
                notes_x0 = margin + half_w + int(10 * scale)
                notes_x1 = width_px - margin
                notes_y0 = bottom_sec_y
                notes_y1 = footer_bar_y - int(8 * scale)

                draw.rectangle([notes_x0, notes_y0, notes_x1, notes_y1], outline="#000000", width=1, fill="#ffffff")
                # Notes title bar
                title_h = int(22 * scale)
                draw.rectangle([notes_x0, notes_y0, notes_x1, notes_y0 + title_h], outline="#000000", width=1, fill="#f8fafc")
                draw_text_in_rect(draw, (notes_x0, notes_y0, notes_x1, notes_y0 + title_h), "ملاحظات", font_bold, fill="#000000", align="right", pad=int(8 * scale))

                # Notes content
                clean_notes = notes or "البضاعة المباعة لا ترد ولا تستبدل إلا بموجب أصل الفاتورة وخلال 14 يوماً من تاريخ الشراء."
                draw_text_in_rect(draw, (notes_x0, notes_y0 + title_h, notes_x1, notes_y1), clean_notes, font_small, fill="#333333", align="right", pad=int(8 * scale))

                # --- Financial Totals Box (Left) ---
                totals_x0 = margin
                totals_x1 = margin + half_w
                totals_y0 = bottom_sec_y
                totals_y1 = footer_bar_y - int(8 * scale)

                draw.rectangle([totals_x0, totals_y0, totals_x1, totals_y1], outline="#000000", width=1, fill="#ffffff")

                num_rows = max(1, len(summary_rows))
                tot_row_h = (totals_y1 - totals_y0) // num_rows

                for s_idx, (s_lbl, s_val, is_prominent) in enumerate(summary_rows):
                    sy0 = totals_y0 + (s_idx * tot_row_h)
                    sy1 = sy0 + tot_row_h if s_idx < num_rows - 1 else totals_y1
                    bg_fill = "#f1f5f9" if is_prominent else "#ffffff"
                    f_to_use = font_bold if is_prominent else font_regular

                    draw.rectangle([totals_x0, sy0, totals_x1, sy1], outline="#000000", width=1, fill=bg_fill)

                    # Label on right side of totals box
                    draw_text_in_rect(draw, (totals_x0 + (half_w // 2), sy0, totals_x1, sy1), s_lbl, f_to_use, fill="#000000", align="right", pad=int(8 * scale))
                    # Value on left side of totals box
                    draw_text_in_rect(draw, (totals_x0, sy0, totals_x0 + (half_w // 2), sy1), s_val, f_to_use, fill="#000000", align="left", pad=int(8 * scale))

            # 6. Standard Bottom Bar (3 Side-by-Side Bordered Boxes)
            box_w = usable_w // 3
            b1_x0 = margin + (box_w * 2)
            b1_x1 = width_px - margin
            b2_x0 = margin + box_w
            b2_x1 = margin + (box_w * 2)
            b3_x0 = margin
            b3_x1 = margin + box_w

            # Box 1 (Right): اسم المستخدم
            draw.rectangle([b1_x0, footer_bar_y, b1_x1, footer_bar_y + footer_bar_h], outline="#000000", width=1, fill="#ffffff")
            draw_text_in_rect(draw, (b1_x0, footer_bar_y, b1_x1, footer_bar_y + footer_bar_h), f"اسم المستخدم : {user_name}", font_bold, fill="#000000", align="center")

            # Box 2 (Center): الصفحات من X إلي Y
            draw.rectangle([b2_x0, footer_bar_y, b2_x1, footer_bar_y + footer_bar_h], outline="#000000", width=1, fill="#ffffff")
            draw_text_in_rect(draw, (b2_x0, footer_bar_y, b2_x1, footer_bar_y + footer_bar_h), f"الصفحات من {page_idx} إلي {total_pages}", font_bold, fill="#000000", align="center")

            # Box 3 (Left): تاريخ ووقت الطباعة
            draw.rectangle([b3_x0, footer_bar_y, b3_x1, footer_bar_y + footer_bar_h], outline="#000000", width=1, fill="#ffffff")
            draw_text_in_rect(draw, (b3_x0, footer_bar_y, b3_x1, footer_bar_y + footer_bar_h), f"تاريخ ووقت الطباعة : {print_datetime}", font_bold, fill="#000000", align="center")

            rendered_pages.append(image)

        return rendered_pages


class SalesInvoiceDocumentTemplate(DocumentInvoiceBase):
    """Authoritative A4 / A5 office dispatch document template for Sales Invoices."""

    @property
    def template_id(self) -> str:
        return "sales_invoice_document"

    @property
    def document_type(self) -> str:
        return "sales_invoice"

    @property
    def description(self) -> str:
        return "A4 / A5 Enterprise Dispatch Document for Sales Invoices (Office Laser/Inkjet Printers)."

    @property
    def required_fields(self) -> List[str]:
        return ["invoice_number", "items", "total_amount"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        invoice_number = str(data.get("invoice_number"))
        customer_code = str(data.get("customer_code") or "")
        customer_name = str(data.get("customer_name") or "عميل نقدي")
        customer_phone = str(data.get("customer_phone") or "")
        customer_address = str(data.get("customer_address") or data.get("company_address") or "")
        shipping_company = str(data.get("shipping_company") or "")
        cashier_name = str(data.get("cashier_name") or data.get("creator_name") or "مسؤول المبيعات")
        notes = str(data.get("notes") or "")
        items = data.get("items") or []

        subtotal = float(data.get("subtotal") or data.get("total_amount") or 0.0)
        discount_amount = float(data.get("discount_amount") or 0.0)
        shipping_amount = float(data.get("shipping_amount") or 0.0)
        total_amount = float(data.get("total_amount") or 0.0)
        paid_amount = float(data.get("paid_amount") or total_amount)
        remaining_amount = float(data.get("remaining_amount") or max(0.0, total_amount - paid_amount))

        summary_rows = [
            ("المجموع :", f"{subtotal:,.2f} ج.م", False),
        ]
        if discount_amount > 0:
            summary_rows.append(("الخصم :", f"−{discount_amount:,.2f} ج.م", False))
        if shipping_amount > 0:
            summary_rows.append(("الشحن :", f"+{shipping_amount:,.2f} ج.م", False))
        summary_rows.append(("الصافي :", f"{total_amount:,.2f} ج.م", True))
        if paid_amount != total_amount:
            summary_rows.append(("المدفوع :", f"{paid_amount:,.2f} ج.م", False))
            summary_rows.append(("المتبقي :", f"{remaining_amount:,.2f} ج.م", False))

        return self._render_dispatch_layout(
            data=data,
            doc_type_title="إذن صرف",
            stamp_title="إذن صرف",
            partner_code_label="كود العميل",
            partner_name_label="اسم العميل",
            partner_code_val=customer_code,
            partner_name_val=customer_name,
            partner_phone_val=customer_phone,
            address_val=customer_address,
            shipping_val=shipping_company,
            items=items,
            summary_rows=summary_rows,
            notes=notes,
            user_name=cashier_name,
        )


class PurchaseInvoiceDocumentTemplate(DocumentInvoiceBase):
    """Authoritative A4 / A5 office receiving document template for Purchase Invoices."""

    @property
    def template_id(self) -> str:
        return "purchase_invoice_document"

    @property
    def document_type(self) -> str:
        return "purchase_invoice"

    @property
    def description(self) -> str:
        return "A4 / A5 Enterprise Receiving Document for Purchase Invoices (Office Laser/Inkjet Printers)."

    @property
    def required_fields(self) -> List[str]:
        return ["invoice_number", "supplier_name", "items", "total_amount"]

    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        self.validate(data)

        invoice_number = str(data.get("invoice_number"))
        supplier_code = str(data.get("supplier_code") or "")
        supplier_name = str(data.get("supplier_name") or "مورد عام")
        supplier_phone = str(data.get("supplier_phone") or "")
        supplier_address = str(data.get("supplier_address") or "")
        shipping_company = str(data.get("shipping_company") or "")
        user_name = str(data.get("creator_name") or data.get("cashier_name") or "أمين المستودع")
        notes = str(data.get("notes") or "")
        items = data.get("items") or []

        total_amount = float(data.get("total_amount") or 0.0)
        paid_amount = float(data.get("paid_amount") or 0.0)
        remaining_amount = float(data.get("remaining_amount") or max(0.0, total_amount - paid_amount))

        summary_rows = [
            ("إجمالي الفاتورة :", f"{total_amount:,.2f} ج.م", True),
            ("المسدد للمورد :", f"{paid_amount:,.2f} ج.م", False),
            ("المتبقي في الحساب :", f"{remaining_amount:,.2f} ج.م", False),
        ]

        return self._render_dispatch_layout(
            data=data,
            doc_type_title="إذن استلام",
            stamp_title="إذن استلام",
            partner_code_label="كود المورد",
            partner_name_label="اسم المورد",
            partner_code_val=supplier_code,
            partner_name_val=supplier_name,
            partner_phone_val=supplier_phone,
            address_val=supplier_address,
            shipping_val=shipping_company,
            items=items,
            summary_rows=summary_rows,
            notes=notes,
            user_name=user_name,
        )
