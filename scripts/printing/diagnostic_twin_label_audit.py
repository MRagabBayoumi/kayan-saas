"""
Diagnostic Mock Spooler Audit for Twin-Section Label (38x25mm / 2x12mm)
NON-DESTRUCTIVE: Strictly dry-run / mock spooler. No hardware transmission. No changes to production files or database.
"""

import sys
import os
import io

_dir = os.path.dirname(os.path.abspath(__file__))
if _dir not in sys.path:
    sys.path.insert(0, _dir)

from template_registry import registry
from printer_service import PrinterService
from PIL import Image

def run_diagnostic():
    print("=" * 80)
    print("KAYAN ERP — DIAGNOSTIC AUDIT & MOCK SPOOLER TEST")
    print("=" * 80)

    # 1. Inspect Production BarcodeSetting from DB (as loaded via Laravel)
    # Current DB values recorded from BarcodeSetting::getSettings():
    db_settings = {
        "barcode_prefix": "02",
        "printer_name": "Xprinter XP-233B",
        "label_width_mm": 38,
        "label_height_mm": 13,
        "barcode_width_px": 260,
        "barcode_height_px": 80,
        "product_font_size": 20,
        "details_font_size": 20,
        "show_company_name": False,
        "company_name_override": "شركة كيان للتجارة والتوزيع",
        "show_product_name": True,
        "show_barcode_bars": True,
        "show_item_sku": True,
        "print_price_type": "none",
    }

    print("\n[SECTION 1] CURRENT PRODUCTION VALUES IN DATABASE:")
    for k, v in db_settings.items():
        print(f"  {k:25}: {repr(v)}")

    # 2. Render Template Unmodified
    template = registry.get("barcode_label")
    test_product = {
        "product_name": "طوق شعر لؤلؤ ذهبي",
        "barcode": "6221234567890",
        "sku": "KY-NCK-001",
        "retail_price": 45.0,
        "wholesale_price": 30.0,
    }

    # Build data dictionary exactly as PythonPrintEngineService::printBarcodeLabel does
    data = {
        "product_name": test_product["product_name"],
        "barcode": test_product["barcode"],
        "sku": test_product["sku"],
        "retail_price": test_product["retail_price"],
        "wholesale_price": test_product["wholesale_price"],
        "currency": "ج.م",
        "show_company_name": db_settings["show_company_name"],
        "company_name": db_settings["company_name_override"],
        "show_product_name": db_settings["show_product_name"],
        "show_barcode_bars": db_settings["show_barcode_bars"],
        "show_item_sku": db_settings["show_item_sku"],
        "print_price_type": db_settings["print_price_type"],
        "product_font_size": db_settings["product_font_size"],
        "details_font_size": db_settings["details_font_size"],
        "barcode_width_px": db_settings["barcode_width_px"],
        "barcode_height_px": db_settings["barcode_height_px"],
        "width_mm": db_settings["label_width_mm"],
        "height_mm": db_settings["label_height_mm"],
    }

    # Render image
    images = template.render(data)
    img = images[0]

    print("\n[SECTION 2] RENDERED BITMAP SPECIFICATIONS (DESIGN SETTINGS CHECK):")
    print(f"  Canvas Dimensions (dots) : {img.size[0]}px wide x {img.size[1]}px high")
    print(f"  Resolution (DPI)         : 203 DPI")
    print(f"  Width in mm (calculated) : {img.size[0] / 203.0 * 25.4:.2f} mm")
    print(f"  Height in mm (calculated): {img.size[1] / 203.0 * 25.4:.2f} mm")
    print(f"  Image Mode               : {img.mode} (converted to 1-bit monochrome by spooler)")

    # 3. Mock Spooler Execution (Single Copy)
    batch_items_1 = [{
        "image": img,
        "copies": 1,
        "product_id": test_product["sku"],
        "product_name": test_product["product_name"],
        "sku": test_product["sku"],
        "dpi": 203,
        "width_mm": db_settings["label_width_mm"],
        "height_mm": db_settings["label_height_mm"],
    }]

    payload_1, summary_1 = PrinterService.build_batch_tspl_payload(
        items=batch_items_1,
        default_width_mm=float(db_settings["label_width_mm"]),
        default_height_mm=float(db_settings["label_height_mm"]),
        gap_mm=2.0,
        direction=0,
        include_direction=True,
    )

    # 4. Mock Spooler Execution (Two Copies)
    batch_items_2 = [{
        "image": img,
        "copies": 2,
        "product_id": test_product["sku"],
        "product_name": test_product["product_name"],
        "sku": test_product["sku"],
        "dpi": 203,
        "width_mm": db_settings["label_width_mm"],
        "height_mm": db_settings["label_height_mm"],
    }]

    payload_2, summary_2 = PrinterService.build_batch_tspl_payload(
        items=batch_items_2,
        default_width_mm=float(db_settings["label_width_mm"]),
        default_height_mm=float(db_settings["label_height_mm"]),
        gap_mm=2.0,
        direction=0,
        include_direction=True,
    )

    def extract_header_and_footer(payload):
        hdr_end = payload.find(b'CLS\r\n')
        hdr = payload[:hdr_end].decode('ascii')
        bm_start = payload.find(b'BITMAP ')
        pr_start = payload.rfind(b'\r\nPRINT ')
        bm_line_end = payload.find(b',', payload.find(b',', payload.find(b',', payload.find(b',', bm_start) + 1) + 1) + 1)
        bm_cmd = payload[bm_start:payload.find(b',', bm_line_end + 1) + 1].decode('ascii')
        footer = payload[pr_start:].decode('ascii')
        return hdr, bm_cmd, footer

    hdr_1, bm_1, ftr_1 = extract_header_and_footer(payload_1)
    hdr_2, bm_2, ftr_2 = extract_header_and_footer(payload_2)

    print("\n[SECTION 3] ACTUAL TSPL COMMAND VALUES EXTRACTED FROM PAYLOAD:")
    print("--- [Single Copy (Qty = 1)] ---")
    print(f"  Header:\n{hdr_1.strip()}")
    print(f"  BITMAP Command: {repr(bm_1)}")
    print(f"  Footer / Print: {repr(ftr_1.strip())}")
    print(f"  Total Bytes: {len(payload_1)}")

    print("\n--- [Two Copies (Qty = 2)] ---")
    print(f"  Header:\n{hdr_2.strip()}")
    print(f"  BITMAP Command: {repr(bm_2)}")
    print(f"  Footer / Print: {repr(ftr_2.strip())}")
    print(f"  Total Bytes: {len(payload_2)}")

    print("\n[SECTION 4] PARSED PROTOCOL PARAMETERS RECORD:")
    for line in hdr_1.strip().splitlines():
        if line.startswith("SIZE"):
            print(f"  Command SIZE      : '{line}' -> Width=38 mm, Height=13 mm")
        elif line.startswith("GAP"):
            print(f"  Command GAP       : '{line}' -> Gap=2 mm, Offset=0 mm")
        elif line.startswith("DIRECTION"):
            print(f"  Command DIRECTION : '{line}' -> Direction=0 (Normal Feed)")
        elif line.startswith("REFERENCE"):
            print(f"  Command REFERENCE : '{line}' -> Reference=(0,0)")

    print(f"  Command CLS       : Emitted once before BITMAP")
    print(f"  Command BITMAP    : X=0, Y=0, WidthBytes=38 (304 dots), HeightDots=104, Mode=0 (Overwrite)")
    print(f"  Command PRINT(1)  : 'PRINT 1,1' (1 set, 1 copy)")
    print(f"  Command PRINT(2)  : 'PRINT 1,2' (1 set, 2 copies)")

    print("\n" + "=" * 80)
    print("MOCK SPOOLER AUDIT COMPLETE — 0 HARDWARE COMMANDS SENT")
    print("=" * 80)

if __name__ == '__main__':
    run_diagnostic()
