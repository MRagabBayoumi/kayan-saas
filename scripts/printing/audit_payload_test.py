import sys
import os

_dir = os.path.dirname(os.path.abspath(__file__))
if _dir not in sys.path:
    sys.path.insert(0, _dir)

from template_registry import registry
from printer_service import PrinterService

def run_tests():
    print("=" * 80)
    print("KAYAN ERP — EXACT BYTE-LEVEL TSPL PAYLOAD AUDIT")
    print("=" * 80)

    for h, w in [(25, 38), (15, 38), (13, 38)]:
        print(f"\n{'#'*70}")
        print(f">>> CONFIGURATION: {w}x{h}mm (Nominal) <<<")
        print(f"{'#'*70}")
        for qty in [1, 2, 3]:
            data = {
                'product_name': 'طوق شعر ذهبي',
                'barcode': '6221234567890',
                'sku': 'KY-01',
                'width_mm': w,
                'height_mm': h,
                'dpi': 203,
                'show_company_name': False,
                'show_product_name': True,
                'show_barcode_bars': True,
                'show_item_sku': True,
                'print_price_type': 'none',
            }
            tpl = registry.get('barcode_label')
            img = tpl.render(data)[0]
            items = [{
                'image': img,
                'copies': qty,
                'product_id': 'KY-01',
                'product_name': 'طوق شعر ذهبي',
                'sku': 'KY-01',
                'dpi': 203,
                'width_mm': w,
                'height_mm': h,
            }]

            payload, summary = PrinterService.build_batch_tspl_payload(
                items=items,
                default_width_mm=float(w),
                default_height_mm=float(h),
                gap_mm=2.0,
                direction=0,
                include_direction=True,
            )

            # 1. Header Analysis
            hdr_end = payload.find(b'CLS\r\n')
            hdr = payload[:hdr_end].decode('ascii')

            # 2. Bitmap Command
            bm_start = payload.find(b'BITMAP ')
            # Find the 5th comma from bm_start
            pos = bm_start
            for _ in range(5):
                pos = payload.find(b',', pos + 1)
            bm_cmd = payload[bm_start : pos + 1].decode('ascii')
            parts = bm_cmd.replace('BITMAP ', '').rstrip(',').split(',')
            x, y, wb, hd, mode = parts[:5]
            wb, hd = int(wb), int(hd)
            expected_raw_len = wb * hd

            # Raw bytes slice
            raw_start = pos + 1
            raw_end = raw_start + expected_raw_len
            raw_slice = payload[raw_start : raw_end]
            actual_raw_len = len(raw_slice)

            # Footer
            footer_bytes = payload[raw_end:]
            footer = footer_bytes.decode('ascii', errors='replace')

            # Form feed check
            has_0x0c = (b'\x0c' in payload)

            print(f"\n--- [Quantity = {qty}] Total Payload: {len(payload)} bytes ---")
            print(f"  Image Size (px) : {img.size[0]}w x {img.size[1]}h dots")
            print(f"  Header Bytes    :\n{hdr.strip()}")
            print(f"  BITMAP Command  : {repr(bm_cmd)}")
            print(f"  Bitmap Bytes    : {actual_raw_len} / Expected: {expected_raw_len} -> {'MATCH [OK]' if actual_raw_len == expected_raw_len else 'MISMATCH [FAIL]'}")
            print(f"  Footer Command  : {repr(footer)}")
            print(f"  Contains 0x0C   : {has_0x0c}")
            print(f"  Exact PRINT line: {repr(footer.strip())}")

if __name__ == '__main__':
    run_tests()
